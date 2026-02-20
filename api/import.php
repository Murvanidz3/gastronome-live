<?php
/**
 * API: CSV Import Handler
 * POST /api/import.php — accepts a CSV file upload
 * Returns JSON with insert/update/error counts.
 *
 * Handles UTF-8, UTF-8 BOM, UTF-16 LE/BE encodings.
 * Uses str_getcsv instead of fgetcsv for proper multi-byte (Georgian) support.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['csv_file'];

// Validate extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['error' => 'Only .csv files are accepted.']);
    exit;
}

// ─── Read & Convert Encoding ───
$rawContent = file_get_contents($file['tmp_name']);
if ($rawContent === false || strlen($rawContent) === 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read uploaded file.']);
    exit;
}

// Detect encoding from BOM markers or auto-detect
$first2 = substr($rawContent, 0, 2);
$first3 = substr($rawContent, 0, 3);

if ($first2 === "\xFF\xFE") {
    // UTF-16 Little Endian (Excel export)
    $rawContent = mb_convert_encoding(substr($rawContent, 2), 'UTF-8', 'UTF-16LE');
} elseif ($first2 === "\xFE\xFF") {
    // UTF-16 Big Endian
    $rawContent = mb_convert_encoding(substr($rawContent, 2), 'UTF-8', 'UTF-16BE');
} elseif ($first3 === "\xEF\xBB\xBF") {
    // UTF-8 BOM — strip it
    $rawContent = substr($rawContent, 3);
} else {
    // No BOM — check if valid UTF-8
    if (!mb_check_encoding($rawContent, 'UTF-8')) {
        $encoding = mb_detect_encoding($rawContent, ['UTF-16LE', 'UTF-16BE', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding) {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
        }
    }
}

// Normalize line endings
$rawContent = str_replace(["\r\n", "\r"], "\n", $rawContent);
$rawContent = trim($rawContent);

// ─── Split into lines and detect delimiter ───
$lines = explode("\n", $rawContent);
if (empty($lines)) {
    http_response_code(400);
    echo json_encode(['error' => 'CSV file is empty.']);
    exit;
}

// Detect delimiter: tab or comma
$firstLine = $lines[0];
$delimiter = (substr_count($firstLine, "\t") > substr_count($firstLine, ",")) ? "\t" : ",";

// ─── Parse header using str_getcsv (safe with multi-byte) ───
$headerRow = str_getcsv(array_shift($lines), $delimiter);
if (!$headerRow || count($headerRow) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'CSV header is invalid.']);
    exit;
}

// Normalize headers (lowercase, trim, remove leftover BOM chars)
$headers = array_map(function ($h) {
    $h = str_replace(["\xEF\xBB\xBF", "\xFE\xFF", "\xFF\xFE"], '', $h);
    return strtolower(trim($h));
}, $headerRow);

$requiredCols = ['barcode', 'name'];
foreach ($requiredCols as $col) {
    if (!in_array($col, $headers)) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required column: $col. Found: " . implode(', ', $headers)]);
        exit;
    }
}

// Map column indices
$colMap = array_flip($headers);

$db = getDB();

$inserted = 0;
$updated = 0;
$errors = 0;
$rowNum = 1;
$errorMessages = [];

$sql = "INSERT INTO products (image_url, barcode, name, quantity, price, currency, comment)
        VALUES (:image_url, :barcode, :name, :quantity, :price, :currency, :comment)
        ON DUPLICATE KEY UPDATE
            image_url = VALUES(image_url),
            name      = VALUES(name),
            quantity  = VALUES(quantity),
            price     = VALUES(price),
            currency  = VALUES(currency),
            comment   = VALUES(comment)";

$stmt = $db->prepare($sql);

foreach ($lines as $line) {
    $rowNum++;

    // Skip empty lines
    $line = trim($line);
    if ($line === '')
        continue;

    // Parse each line with str_getcsv (multi-byte safe)
    $row = str_getcsv($line, $delimiter);

    if (count($row) < 2)
        continue;

    try {
        $barcode = trim($row[$colMap['barcode']] ?? '');
        $name = trim($row[$colMap['name']] ?? '');

        if ($barcode === '' || $name === '') {
            $errors++;
            $errorMessages[] = "Row $rowNum: barcode and name are required.";
            continue;
        }

        $image_url = isset($colMap['image_url']) ? trim($row[$colMap['image_url']] ?? '') : '';
        $quantity = isset($colMap['quantity']) ? (int) ($row[$colMap['quantity']] ?? 0) : 0;
        $price = isset($colMap['price']) ? (float) ($row[$colMap['price']] ?? 0) : 0;
        $currency = isset($colMap['currency']) ? strtoupper(trim($row[$colMap['currency']] ?? 'GEL')) : 'GEL';
        if (!in_array($currency, ['GEL', 'EUR', 'USD']))
            $currency = 'GEL';
        $comment = isset($colMap['comment']) ? trim($row[$colMap['comment']] ?? '') : '';

        // Check if product exists (for counting inserts vs updates)
        $check = $db->prepare('SELECT id FROM products WHERE barcode = :barcode');
        $check->execute([':barcode' => $barcode]);
        $exists = $check->fetch();

        $stmt->execute([
            ':image_url' => $image_url,
            ':barcode' => $barcode,
            ':name' => $name,
            ':quantity' => $quantity,
            ':price' => $price,
            ':currency' => $currency,
            ':comment' => $comment,
        ]);

        if ($exists) {
            $updated++;
        } else {
            $inserted++;
        }
    } catch (Exception $e) {
        $errors++;
        $errorMessages[] = "Row $rowNum: " . $e->getMessage();
    }
}

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'updated' => $updated,
    'errors' => $errors,
    'messages' => array_slice($errorMessages, 0, 10),
]);

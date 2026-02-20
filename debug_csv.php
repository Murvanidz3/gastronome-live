<?php
/**
 * Diagnostic: Check CSV encoding
 * Visit: http://localhost/debug_csv.php
 * Upload the same CSV to see what encoding it is.
 */
session_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>CSV Debug</title>
</head>

<body style="background:#1a1a2e;color:#fff;font-family:monospace;padding:20px;">
    <h2>CSV Encoding Debugger</h2>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv">
            <button type="submit" style="padding:8px 16px;">Analyze</button>
        </form>
    <?php else:
        $raw = file_get_contents($_FILES['csv_file']['tmp_name']);

        echo "<h3>File Info</h3>";
        echo "<p>File: " . htmlspecialchars($_FILES['csv_file']['name']) . "</p>";
        echo "<p>Size: " . strlen($raw) . " bytes</p>";

        // Check first bytes for BOM
        $first4 = bin2hex(substr($raw, 0, 4));
        echo "<p>First 4 bytes (hex): $first4</p>";

        if (substr($raw, 0, 3) === "\xEF\xBB\xBF")
            echo "<p>✅ UTF-8 BOM detected</p>";
        elseif (substr($raw, 0, 2) === "\xFF\xFE")
            echo "<p>⚠️ UTF-16 LE BOM detected</p>";
        elseif (substr($raw, 0, 2) === "\xFE\xFF")
            echo "<p>⚠️ UTF-16 BE BOM detected</p>";
        else
            echo "<p>No BOM detected</p>";

        // mb_detect_encoding results
        $enc1 = mb_detect_encoding($raw, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
        echo "<p>mb_detect_encoding: <strong>$enc1</strong></p>";

        $isUtf8 = mb_check_encoding($raw, 'UTF-8');
        echo "<p>Valid UTF-8: " . ($isUtf8 ? 'YES' : 'NO') . "</p>";

        // Show hex dump of first 500 bytes
        echo "<h3>Hex Dump (first 500 bytes)</h3>";
        echo "<pre>";
        $chunk = substr($raw, 0, 500);
        for ($i = 0; $i < strlen($chunk); $i++) {
            echo sprintf('%02X ', ord($chunk[$i]));
            if (($i + 1) % 16 === 0) {
                echo "  ";
                for ($j = $i - 15; $j <= $i; $j++) {
                    $c = ord($chunk[$j]);
                    echo ($c >= 32 && $c < 127) ? chr($c) : '.';
                }
                echo "\n";
            }
        }
        echo "</pre>";

        // Try to convert and show first 3 lines
        // Strip BOM
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            $converted = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
            echo "<h3>Converted from UTF-16LE:</h3>";
        } elseif (substr($raw, 0, 2) === "\xFE\xFF") {
            $converted = mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
            echo "<h3>Converted from UTF-16BE:</h3>";
        } elseif (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
            $converted = substr($raw, 3);
            echo "<h3>After BOM strip (UTF-8):</h3>";
        } else {
            $converted = $raw;
            echo "<h3>Raw content (no conversion):</h3>";
        }

        $lines = explode("\n", $converted);
        echo "<pre>";
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]) . "\n";
        }
        echo "</pre>";

    endif; ?>
</body>

</html>
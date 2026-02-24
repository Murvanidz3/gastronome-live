<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$inputData = json_encode(['id' => 1]);
file_put_contents('php://input', $inputData);

// Note: $_SESSION is not set in CLI, so delete_invoice.php will fail at line 25 where it checks $_SESSION['user_id']
echo "Mocking deletion.\n";

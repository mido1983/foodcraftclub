<?php
// This will show the path to the error log
echo "PHP error log path: " . ini_get('error_log');

// Add a test error message
error_log("Test error message from find_error_log.php");
echo "\n\nA test message has been written to the error log.";

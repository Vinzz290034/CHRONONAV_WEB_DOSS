<?php
// Test script to diagnose the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Faculty Verification Codes Page...\n\n";

// Test 1: Check if file exists
echo "1. File exists: " . (file_exists(__DIR__ . '/pages/admin/faculty_verification_codes.php') ? 'YES' : 'NO') . "\n";

// Test 2: Check middleware file
echo "2. Auth check middleware exists: " . (file_exists(__DIR__ . '/middleware/auth_check.php') ? 'YES' : 'NO') . "\n";

// Test 3: Check db_connect
echo "3. DB connect exists: " . (file_exists(__DIR__ . '/config/db_connect.php') ? 'YES' : 'NO') . "\n";

// Test 4: Try to include just db_connect
echo "4. Testing DB connection...\n";
try {
    require_once __DIR__ . '/config/db_connect.php';
    echo "   DB Connection: SUCCESS\n";
    echo "   Conn type: " . gettype($conn) . "\n";
} catch (Exception $e) {
    echo "   DB Connection: FAILED - " . $e->getMessage() . "\n";
}

// Test 5: Check faculty_verification_codes table
echo "5. Checking database table...\n";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM faculty_verification_codes");
    $row = $result->fetch_assoc();
    echo "   Faculty codes count: " . $row['count'] . "\n";
} catch (Exception $e) {
    echo "   Table check: FAILED - " . $e->getMessage() . "\n";
}

echo "\nDone!";
?>

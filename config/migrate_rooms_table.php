<?php
/**
 * Migration script to add map-related columns to rooms table
 * This script adds: is_available, map_x, map_y columns
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chrononav_web_doss";

// Create connection using the MySQL socket
$conn = new mysqli($servername, $username, $password, $dbname, 3306, '/opt/lampp/var/mysql/mysql.sock');

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

try {
    // Check if columns already exist
    $result = $conn->query("SHOW COLUMNS FROM rooms LIKE 'is_available'");
    $is_available_exists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM rooms LIKE 'map_x'");
    $map_x_exists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM rooms LIKE 'map_y'");
    $map_y_exists = $result->num_rows > 0;
    
    $changes_made = false;
    
    // Add is_available column if it doesn't exist
    if (!$is_available_exists) {
        if ($conn->query("ALTER TABLE rooms ADD COLUMN is_available TINYINT(1) DEFAULT 1 AFTER location_description")) {
            echo "✓ Added 'is_available' column to rooms table<br>";
            $changes_made = true;
        } else {
            echo "❌ Error adding 'is_available': " . $conn->error . "<br>";
        }
    } else {
        echo "✓ 'is_available' column already exists<br>";
    }
    
    // Add map_x column if it doesn't exist
    if (!$map_x_exists) {
        if ($conn->query("ALTER TABLE rooms ADD COLUMN map_x INT(11) DEFAULT NULL AFTER is_available")) {
            echo "✓ Added 'map_x' column to rooms table<br>";
            $changes_made = true;
        } else {
            echo "❌ Error adding 'map_x': " . $conn->error . "<br>";
        }
    } else {
        echo "✓ 'map_x' column already exists<br>";
    }
    
    // Add map_y column if it doesn't exist
    if (!$map_y_exists) {
        if ($conn->query("ALTER TABLE rooms ADD COLUMN map_y INT(11) DEFAULT NULL AFTER map_x")) {
            echo "✓ Added 'map_y' column to rooms table<br>";
            $changes_made = true;
        } else {
            echo "❌ Error adding 'map_y': " . $conn->error . "<br>";
        }
    } else {
        echo "✓ 'map_y' column already exists<br>";
    }
    
    echo "<hr>";
    if ($changes_made) {
        echo "✓ Database migration completed successfully!<br>";
    } else {
        echo "ℹ All columns already exist. No changes needed.<br>";
    }
    
    // Verify the columns exist
    $result = $conn->query("DESCRIBE rooms");
    echo "<hr>";
    echo "<h3>Current rooms table structure:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage();
}

$conn->close();
?>

<?php
echo "<h1>Restaurant Delivery System - System Test</h1>";
echo "<pre>";

// Test PHP Version
echo "\n=== PHP Version ===\n";
$required_php_version = "7.4.0";
$current_php_version = PHP_VERSION;
echo "Required: $required_php_version\n";
echo "Current:  $current_php_version\n";
if (version_compare($current_php_version, $required_php_version, '>=')) {
    echo "✅ PHP version requirement met\n";
} else {
    echo "❌ PHP version too low\n";
}

// Test Required PHP Extensions
echo "\n=== Required PHP Extensions ===\n";
$required_extensions = [
    'mysqli',
    'pdo',
    'pdo_mysql',
    'gd',
    'json',
    'session',
    'fileinfo'
];

foreach ($required_extensions as $extension) {
    if (extension_loaded($extension)) {
        echo "✅ $extension extension loaded\n";
    } else {
        echo "❌ $extension extension missing\n";
    }
}

// Test Directory Permissions
echo "\n=== Directory Permissions ===\n";
$directories = [
    'assets/images/menu',
    'config',
    'includes'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        echo "📁 Creating $dir directory...\n";
        mkdir($dir, 0755, true);
    }
    
    if (is_writable($dir)) {
        echo "✅ $dir is writable\n";
    } else {
        echo "❌ $dir is not writable\n";
    }
}

// Test Database Connection
echo "\n=== Database Connection ===\n";
require_once __DIR__ . '/config/database.php';

try {
    $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    echo "✅ Database connection successful\n";
    
    if ($test_conn->select_db(DB_NAME)) {
        echo "✅ Database '".DB_NAME."' exists\n";
        
        // Test required tables
        $required_tables = ['users', 'menu_items', 'orders', 'order_items'];
        foreach ($required_tables as $table) {
            $result = $test_conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "❌ Table '$table' missing\n";
            }
        }
    } else {
        echo "❌ Database '".DB_NAME."' does not exist\n";
    }
    
    $test_conn->close();
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test Session Configuration
echo "\n=== Session Configuration ===\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session save path: " . session_save_path() . "\n";
if (is_writable(session_save_path())) {
    echo "✅ Session directory is writable\n";
} else {
    echo "❌ Session directory is not writable\n";
}

// Test File Upload Configuration
echo "\n=== File Upload Configuration ===\n";
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
echo "Upload max filesize: $upload_max_filesize\n";
echo "Post max size: $post_max_size\n";

// Test Server Software
echo "\n=== Server Information ===\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";

// Test URL Rewriting
echo "\n=== URL Rewriting ===\n";
if (isset($_SERVER['HTTP_MOD_REWRITE']) || in_array('mod_rewrite', apache_get_modules())) {
    echo "✅ URL Rewriting is available\n";
} else {
    echo "⚠️ Could not determine URL Rewriting status\n";
}

// Summary
echo "\n=== System Test Summary ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
echo "Client IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

echo "</pre>";

// Add some basic styling
?>
<style>
    body {
        font-family: monospace;
        padding: 20px;
        background: #f5f5f5;
    }
    pre {
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    h1 {
        color: #333;
    }
</style>

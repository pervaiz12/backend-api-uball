<?php
// Temporary file to check PHP configuration
// Access via: http://localhost:8000/phpinfo_test.php
// DELETE THIS FILE after checking!

echo "<h1>PHP Configuration for Upload</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$settings = [
    'upload_max_filesize' => ['expected' => '512M', 'actual' => ini_get('upload_max_filesize')],
    'post_max_size' => ['expected' => '512M', 'actual' => ini_get('post_max_size')],
    'memory_limit' => ['expected' => '256M', 'actual' => ini_get('memory_limit')],
    'max_execution_time' => ['expected' => '300', 'actual' => ini_get('max_execution_time')],
    'max_input_time' => ['expected' => '300', 'actual' => ini_get('max_input_time')],
    'file_uploads' => ['expected' => '1', 'actual' => ini_get('file_uploads')],
];

foreach ($settings as $name => $data) {
    $status = ($data['actual'] == $data['expected'] || ($name == 'memory_limit' && $data['actual'] == '-1')) ? '✅' : '❌';
    echo "<tr>";
    echo "<td><strong>{$name}</strong></td>";
    echo "<td>{$data['actual']}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>PHP Info Location</h2>";
echo "<p>Loaded php.ini: <strong>" . php_ini_loaded_file() . "</strong></p>";
echo "<p>Additional .ini files: " . (php_ini_scanned_files() ?: 'None') . "</p>";

echo "<h2>Storage Paths</h2>";
$storagePath = dirname(__DIR__) . '/storage/app/public/clips';
echo "<p>Clips directory: <strong>{$storagePath}</strong></p>";
echo "<p>Exists: " . (is_dir($storagePath) ? '✅ Yes' : '❌ No') . "</p>";
echo "<p>Writable: " . (is_writable($storagePath) ? '✅ Yes' : '❌ No') . "</p>";

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ DELETE THIS FILE AFTER TESTING!</strong></p>";
echo "<p>Run: <code>rm public/phpinfo_test.php</code></p>";

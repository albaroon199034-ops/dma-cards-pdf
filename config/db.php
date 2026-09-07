<?php
$host = 'sql212.infinityfree.com';
$dbname = 'if0_37574528_print_system';
$username = 'if0_37574528';
$password = 'LklW26HKtyhD';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<?php
require_once 'config.php';

header('Content-Type: application/json');

function getDbConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }
    return $conn;
}

function getLatestPrices() {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT ram_price, cpu_price FROM prices ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    } else {
        // Return default prices if no records found
        return array('ram_price' => 5.00, 'cpu_price' => 10.00);
    }
}

$prices = getLatestPrices();
$prices['exchange_rate'] = 35.5; // You should fetch this from a reliable source or your database

echo json_encode($prices);
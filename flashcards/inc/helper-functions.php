<?php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "dev.eng-app";

function getDatabaseConnection() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}


function getFileStatuses($file_name) {
    $conn = getDatabaseConnection();
    $sql = "SELECT word, learned FROM word_statuses WHERE file_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $file_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $statuses = array();
    while ($row = $result->fetch_assoc()) {
        $statuses[$row['word']] = $row['learned'];
    }

    $stmt->close();
    $conn->close();

    return $statuses;
}

function updateFileStatus($file_name, $word, $learned) {
    $conn = getDatabaseConnection();
    $sql = "INSERT INTO word_statuses (file_name, word, learned) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE learned = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $file_name, $word, $learned, $learned);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

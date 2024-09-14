<?php
include 'inc/helper-functions.php';

$file_name = $_POST['file_name'];
$statuses = getFileStatuses($file_name);

echo json_encode($statuses);
?>

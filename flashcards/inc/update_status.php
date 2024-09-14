<?php
include '../inc/helper-functions.php';

$file_name = $_POST['file_name'];
$word = $_POST['word'];
$learned = $_POST['learned'];

updateFileStatus($file_name, $word, $learned);
echo "Status updated";
?>

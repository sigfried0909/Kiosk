<?php
$uploadDir = __DIR__ . "/chunks/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (!isset($_FILES["chunk"])) {
    http_response_code(400);
    exit("No chunk received");
}

$chunk = $_FILES["chunk"]["tmp_name"];
$offset = intval($_POST["offset"]);
$name = basename($_POST["name"]);

$chunkFile = $uploadDir . $name . ".part";

// Append chunk safely
$fp = fopen($chunkFile, $offset === 0 ? "wb" : "ab");
fwrite($fp, file_get_contents($chunk));
fclose($fp);

echo "OK";

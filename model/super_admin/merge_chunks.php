<?php
$uploadDir = __DIR__ . "/chunks/";
$finalDir = __DIR__ . "/ads/";

if (!is_dir($finalDir)) mkdir($finalDir, 0777, true);

$name = basename($_GET["name"]);
$tempFile = $uploadDir . $name . ".part";
$finalFile = $finalDir . "ad1.mp4";

if (!file_exists($tempFile)) {
    exit("Temp file missing");
}

// Replace old file
if (file_exists($finalFile)) unlink($finalFile);

// Fast rename
rename($tempFile, $finalFile);

echo "Video upload complete";

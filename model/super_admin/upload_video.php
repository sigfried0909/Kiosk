<?php
header("Content-Type: application/json");

$targetDir = __DIR__ . "/ads/";
$targetFile = $targetDir . "ad1.mp4";

// Check if upload folder exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Check if a file is uploaded
if (!isset($_FILES["video"]) || $_FILES["video"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "No video file uploaded or upload error."]);
    exit;
}

// Delete the existing ad first
if (file_exists($targetFile)) {
    unlink($targetFile);
}

// Move new upload and rename to ad1.mp4
if (move_uploaded_file($_FILES["video"]["tmp_name"], $targetFile)) {
    echo json_encode([
        "success" => true,
        "message" => "Video updated successfully.",
        "file" => basename($targetFile)
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save uploaded video."]);
}
?>
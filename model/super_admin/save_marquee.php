<?php
header("Content-Type: application/json");

$marqueeTexts = $_POST['marquee'] ?? [];
$content = json_encode($marqueeTexts, JSON_PRETTY_PRINT);

if (file_put_contents("ads/marquee.json", $content)) {
    echo json_encode(["success" => true, "message" => "Marquee announcements saved successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save marquee announcements."]);
}

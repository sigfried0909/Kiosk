<?php
require_once("../model/db_connect.php");
// Graceful connection check
if (!isset($conn) || $conn->connect_error) {
  error_log("DB ERROR: " . ($conn->connect_error ?? "Unknown"));
  die("<h3 style='color:red;text-align:center;margin-top:50px;'>Database connection failed. Please try again later.</h3>");
}

// --- Departments ---
$departments = [
  "General Consultation" => "GC",
  "Maternal & Child Health Services" => "MCHS",
  "Laboratory Services" => "LS",
  "Dental Services" => "DS",
  "Pharmacy & Dispensing Area" => "PDA",
  "Animal Bite Treatment Center" => "ABTC",
  "Medical Records" => "MR"
];

// --- Ads Paths ---
$adsPath = "../model/super_admin/ads/";
$videoPath = $adsPath . "ad1.mp4";
$marqueeFile = $adsPath . "marquee.json";

// --- Load Marquees Safely ---
$marquees = [];
if (file_exists($marqueeFile)) {
  $content = @file_get_contents($marqueeFile);
  $decoded = json_decode($content, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    $marquees = $decoded;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Z-Kiosk Pro | Now Serving</title>
  <link rel="icon" type="image" href="../assets/images/logo.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../style/TvDisplay.css">
  <style>
    video {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>

<body>
  <!-- === TOPBAR === -->
  <div class="topbar">
    <div class="d-flex align-items-center">
      <img src="../assets/images/logo.jpg" class="logo" alt="Logo" style="height: 40px;">
      <h1 class="fs-4 text-white m-2">Z-Kiosk Pro</h1>
    </div>
    <div>
      <span id="date" class="fw-semibold"></span> |
      <span id="time" class="fw-semibold"></span>
    </div>
  </div>

  <!-- === MAIN CONTAINER === -->
  <div class="main-container">
    <div id="queue-grid">
      <?php $i = 0;
      foreach ($departments as $dept => $code):
        $i++; ?>
        <div class="queue-card <?= ($i == 7) ? 'bottom-center' : '' ?>" id="<?= $code ?>">
          <h5><?= strtoupper($dept) ?></h5>
          <h2>—</h2>
          <p>NEXT: ---</p>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- === AD PANEL === -->
    <div class="ad-panel">
      <div class="video-wrapper" style="position: relative; pointer-events:auto;">

      <video id="adVideo" autoplay muted loop playsinline>
        <source src="<?= $videoPath ?>?v=<?= filemtime($videoPath) ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    
      <!-- VIDEO SOUND CONTROLS -->
      <div id="videoControls"
      style="
        position:absolute;
        bottom:10px;
        left:10px;
        background:rgba(0,0,0,0.6);
        padding:8px 10px;
        border-radius:8px;
        display:flex;
        gap:8px;
        align-items:center;
        opacity:1;
        transition:opacity .4s ease;
        pointer-events:auto;   /* IMPORTANT */
        z-index:10;
      ">
    
        <button id="videoSoundToggle"
          style="background:#198754;border:none;padding:6px 10px;border-radius:5px;color:white;cursor:pointer;">
          Sound: OFF
        </button>
    
        <input type="range" id="videoVolume" min="0" max="1" step="0.05" value="0"
          style="width:100px;">
      </div>
    
    </div>


      <?php if (!empty($marquees)): ?>
        <div class="marquee-container">
          <div class="marquee-slider">
            <?php foreach ($marquees as $text): ?>
              <div class="marquee-item"><?= htmlspecialchars($text) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <!--<button id="fullscreenBtn"-->
    <!--  style="position:fixed; bottom:10px; left:10px; z-index:9999; background:grey; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">-->
    <!--  ⛶-->
    <!--</button>-->
  </div>

  <script src="../controller/TvDisplay.js"></script>
  <script>
    setInterval(() => {
      const video = document.getElementById("adVideo");
      if (video) {
        const src = video.querySelector("source");
        const newSrc = src.src.split("?")[0] + "?v=" + Date.now();
        fetch(newSrc, { method: "HEAD" })
          .then(res => { if (res.ok) src.src = newSrc; video.load(); })
          .catch(() => { });
      }
    }, 60000); // check every 60 seconds
  </script>
</body>

</html>
// === GLOBAL PROTECTION ===
window.addEventListener("error", e => console.warn("TV JS Error:", e.message));
window.addEventListener("unhandledrejection", e => console.warn("Promise Error:", e.reason));

// === VIDEO AUDIO CONTROL WITH FULL AUTO-HIDE FIX ===
const adVideo = document.getElementById("adVideo");
const videoWrapper = document.querySelector(".video-wrapper");
const videoControls = document.getElementById("videoControls");
const videoSoundToggle = document.getElementById("videoSoundToggle");
const videoVolume = document.getElementById("videoVolume");

let videoSoundEnabled = false;
let hideTimeout;

// Apply volume/mute
function updateVideoSound() {
  adVideo.muted = !videoSoundEnabled;
  adVideo.volume = videoVolume.value;
}

// === AUTO-HIDE FUNCTION ===
function scheduleHide() {
  clearTimeout(hideTimeout);
  hideTimeout = setTimeout(() => {
    videoControls.style.opacity = "0";
  }, 3000);
}

// Show controls instantly
function showControls() {
  videoControls.style.opacity = "1";
  scheduleHide();
}

// === MOUSE DETECTION ON ENTIRE VIDEO AREA ===
videoWrapper.addEventListener("mousemove", showControls);
videoWrapper.addEventListener("mouseenter", showControls);
videoWrapper.addEventListener("mouseleave", scheduleHide);

// Prevent hiding while using the controls
videoControls.addEventListener("mouseenter", () => {
  clearTimeout(hideTimeout);
  videoControls.style.opacity = "1";
});

// === BUTTON EVENTS ===
videoSoundToggle.addEventListener("click", () => {
  videoSoundEnabled = !videoSoundEnabled;

  videoSoundToggle.textContent = videoSoundEnabled ? "Sound: ON" : "Sound: OFF";
  videoSoundToggle.style.background = videoSoundEnabled ? "#0d6efd" : "#198754";

  updateVideoSound();
  showControls();
});

videoVolume.addEventListener("input", () => {
  updateVideoSound();
  showControls();
});

// Init
updateVideoSound();
scheduleHide();


// === SAFE FETCH WRAPPER ===
async function safeFetch(url, options = {}) {
  try {
    const res = await fetch(url, { cache: "no-store", ...options });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (err) {
    console.warn("Fetch failed:", url, err.message);
    return [];
  }
}

// === MARQUEE SLIDER ===
const slides = document.querySelectorAll(".marquee-item");
let current = 0;
if (slides.length > 0) {
  slides[current].classList.add("active");
  setInterval(() => {
    slides[current].classList.remove("active");
    current = (current + 1) % slides.length;
    slides[current].classList.add("active");
  }, 6000);
}

// === FONT AUTO-ADJUST ===
function adjustMarqueeFontSize() {
  document.querySelectorAll(".marquee-item").forEach(item => {
    const len = item.textContent.trim().length;
    item.style.fontSize = len < 60 ? "2rem" : len < 120 ? "1.6rem" : len < 200 ? "1.3rem" : "1rem";
  });
}
adjustMarqueeFontSize();

// === DATE & TIME ===
function updateDateTime() {
  const now = new Date();
  const dateEl = document.getElementById("date");
  const timeEl = document.getElementById("time");
  if (dateEl) dateEl.textContent = now.toLocaleDateString("en-US");
  if (timeEl) timeEl.textContent = now.toLocaleTimeString("en-US");
}
updateDateTime();
setInterval(updateDateTime, 1000);

// === FETCH QUEUE DATA ===
async function fetchQueueData() {
  const data = await safeFetch("../model/fetch_queue.php");
  if (!data || typeof data !== "object") return;
  Object.entries(data).forEach(([dept, info]) => {
    const card = document.getElementById(info.code);
    if (!card) return;
    const h2 = card.querySelector("h2");
    const p = card.querySelector("p");
    if (h2) h2.textContent = info.serving || "—";
    if (p)
      p.innerHTML = info.waiting
        ? `NEXT: ${info.waiting}`
        : info.pending_priority
          ? `NEXT: <strong class='priority-text'>P - ${info.pending_priority}</strong>`
          : "NEXT: ---";
  });
}

// === PAGING TRIGGER ===
const lastTrigger = {};
async function checkPaging() {
  const data = await safeFetch("../model/check_page_trigger.php");
  if (!Array.isArray(data)) return;

  data.forEach(({ Department, Ticket_Num, Triggered_At }) => {
    if (lastTrigger[Department] === Triggered_At) return;
    lastTrigger[Department] = Triggered_At;

    const beep = new Audio("../assets/sounds/beep.mp3");
    beep.play().catch(() => { });

    const card = document.getElementById(Department);
    if (card) {
      card.classList.add("highlight");
      const h2 = card.querySelector("h2");
      if (h2) h2.textContent = Ticket_Num;
      setTimeout(() => card.classList.remove("highlight"), 5000);

      const speakDept = card.querySelector("h5")?.textContent || "";
      speechSynthesis.cancel(); // stop overlapping voices
      const utter = new SpeechSynthesisUtterance(`Now serving ticket number ${Ticket_Num}, ${speakDept}.`);
      utter.rate = 0.9;
      speechSynthesis.speak(utter);
    }
  });
}

// === POLLING INTERVALS ===
fetchQueueData();
checkPaging();
setInterval(fetchQueueData, 1000); // 1 sec.
setInterval(checkPaging, 1000); // 1 sec.

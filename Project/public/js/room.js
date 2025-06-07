// Get user ID from global variable
const userId = window.userId || "";
let evtSource = null;
let reconnectAttempts = 0;
let currentCommandId = null;
let reconnectTimer = null;
let processedCommands = new Set(); // Track processed commands to prevent duplicates

console.log("Room.js loaded");

// Function to establish SSE connection
function connectSSE() {
  if (evtSource) {
    evtSource.close();
  }

  evtSource = new EventSource("/sse");

  evtSource.onopen = function () {
    console.log("SSE connection established");
    reconnectAttempts = 0;
  };

  evtSource.onerror = function (e) {
    console.error("SSE connection error", e);

    evtSource.close();
    evtSource = null;

    if (reconnectAttempts < 10) {
      const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
      console.log(`Attempting to reconnect in ${delay / 1000} seconds...`);

      clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(() => {
        reconnectAttempts++;
        connectSSE();
      }, delay);
    } else {
      alert("Connection lost. Please refresh the page to reconnect.");
    }
  };

  evtSource.addEventListener("connected", function (event) {
    console.log("Connected to SSE server", JSON.parse(event.data));
  });

  evtSource.addEventListener("heartbeat", function (event) {
    const data = JSON.parse(event.data);
    console.log("Heartbeat received", data);

    if (data && typeof data.activeUsers !== "undefined") {
      document.getElementById("audienceResponders").textContent = data.activeUsers;
    }
  });

  evtSource.addEventListener("command", function (event) {
    const data = JSON.parse(event.data);
    console.log("Command received:", data);

    // Skip if we've already processed this command
    if (!data.id || processedCommands.has(data.id)) {
      console.log(`Skipping duplicate command: ${data.id}`);
      return;
    }

    // Mark this command as processed
    processedCommands.add(data.id);
    currentCommandId = data.id;

    // Limit the size of the processedCommands set to prevent memory issues
    if (processedCommands.size > 50) {
      const iterator = processedCommands.values();
      processedCommands.delete(iterator.next().value);
    }

    document.getElementById("commandText").textContent =
      data.type + (data.message ? ": " + data.message : "");

    if (data.countdown) {
      startCountdown(data.countdown);
    } else {
      document.getElementById("countdownDisplay").textContent = "Now!";
    }

    document.getElementById("audienceIntensity").textContent = data.intensity + "%";
    document.getElementById("audienceVolume").textContent =
      Math.round(data.intensity * 0.8) + " dB";
  });
}

function startCountdown(seconds) {
  let countdown = parseInt(seconds);
  const countdownElement = document.getElementById("countdownDisplay");

  countdownElement.textContent = countdown;

  const countdownInterval = setInterval(() => {
    countdown--;

    if (countdown <= 0) {
      clearInterval(countdownInterval);
      countdownElement.textContent = "Now!";
    } else {
      countdownElement.textContent = countdown;
    }
  }, 1000);
}

// Initialize
connectSSE();

window.addEventListener("beforeunload", () => {
  if (evtSource) evtSource.close();
  clearTimeout(reconnectTimer);
});

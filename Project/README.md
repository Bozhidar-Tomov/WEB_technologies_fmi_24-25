# CrowdPulse Project

## Overview

CrowdPulse is a web application for directing and managing audience emotions (applause, cheering, booing, murmuring, stomping, etc.) in a coordinated or choreographed manner. It supports both live and simulated audience modes, real-time admin controls and audience segmentation.

## Project Structure

The project follows an MVC flow:

- User visits: `http://localhost/Project/public/`
- `public/index.php` → front controller (routes all requests)
- `app/Controllers/HomeController.php` → business logic
- `app/Views/index.php` → HTML page (user sees this)

```plaintext
composer.json           # Project dependencies
README.md               # Project documentation

app/
├── Components/         # Reusable UI elements (e.g. Blade emotion buttons)
├── Controllers/        # Handle HTTP requests (route entrypoints)
├── Helpers/            # Common utility logic (formatters, validators)
├── Models/             # Eloquent models (DB representations like User, Event)
├── Services/           # App logic (EmotionMixer, PointTracker)
├── Sockets/            # Real-time server logic (WebSocket broadcast handlers)
└── Views/              # Blade views or templates shown to users

config/
└── broadcasting.php    # WebSocket + broadcasting config

database/
├── migrations/         # Create/modify database schema
└── seeders/            # Seed sample data into DB

public/
├── index.php           # App entry point
├── css/                # CSS styles
├── js/                 # JavaScript (add socket clients here)
└── media/              # Audio/video assets for emotions

routes/
└── routes.php          # Route definitions (connect URLs to controllers)
└── Router.php          # Core routing logic that maps HTTP requests to controllers
```

Apache server start point: `Project/public`

## Features & Functional Overview

### Audience Reaction Commands

- Admins can send predefined reaction commands (e.g., "Applaud now", "Boo loudly in 3...2...1", "Cheer for 5 seconds").
- Commands can be triggered by countdowns, on-screen instructions, gestures, or manual admin input.

### Visual & Audio Cues

- Commands appear on audience screens (mobile/web-based).
- Optional synchronized lighting/sound cues (e.g., flash or tone before a reaction).

### Simulated Audience Mode

- Virtual audience members have pre-recorded sound files for each reaction type.
- System randomly selects a variation, plays it at a randomized volume and time offset, creating a realistic ripple effect.

### Audience Segmentation

- Participants can be grouped by gender, seating zone, arrival time, or custom tags (VIPs, fans, guests).
- Reactions can be targeted to specific groups.

### Gamification & Points System

- In live mode, audience members earn points for participation and timing.
- Points unlock new reactions, customize avatars/sounds, or can be gifted.
- Leaderboards and statistics encourage engagement.

### Roles & Permissions

- Roles: Active Participant, Passive Viewer, Group Leader.
- Invitations can be based on points, participation, or admin selection.

### Admin & Moderation Panel

- Schedule/configure events, assign roles, send live commands, monitor engagement, view analytics, and manage participants.

### Sound Intensity Monitoring

- Each reaction has an intensity scale (0-100).
- Optional live decibel monitoring for physical/hybrid events.

### Public Participation Link

- Quick-join link for each event.
- Participants can join as active audience or observer (if not eligible).

## Command Structure

Commands follow this structure:

```json
{
  "type": "command",
  "command": "<command_type>",
  "countdown": <countdown_seconds>,
  "intensity": <intensity_value>,
  "duration": <duration_seconds>,
  "targetGroups": ["group1", "group2"],
  "message": "<custom_message>"
}
```

## Available Commands

- clap/applaud: Users clap their hands
- cheer: Users cheer vocally
- boo: Users express disapproval
- murmur: Users talk quietly among themselves
- stomp: Users stomp their feet
- silence: Users remain quiet

## How It Works

- Admin commands are stored in a JSON file.
- Users' browsers connect to an SSE endpoint that streams commands in real-time.
- Browsers without SSE support fall back to periodic polling.
- No dependencies required: uses Server-Sent Events (SSE) instead of WebSockets.

## Browser Compatibility

Server-Sent Events are supported by all modern browsers:

- Chrome 9+
- Firefox 6+
- Safari 5+
- Edge 12+
- Opera 11.5+

For older browsers (e.g., IE), the app falls back to JSON polling.

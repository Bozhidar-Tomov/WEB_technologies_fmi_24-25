# CrowdPulse Project

## Project code structure

The structure follows MVC Flow.

User visits: `http://localhost/Project/public/`
         ↓
public/index.php → front controller
         ↓
app/Controllers/HomeController.php → business logic
         ↓
app/Views/index.php → HTML page (user sees this)

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
└── web.php             # Route definitions (connect URLs to controllers)
```

Apache server start point `Project/public`

### Concept Overview

The project aims to create a system for directing and managing audience emotions such as applause, cheering, booing, murmuring, stomping, and more, in a coordinated or choreographed manner.

### Functional requirements

Core Features and Functional Requirements

1. Audience Reaction Commands
    The system sends predefined reaction commands to the audience, such as:

    - "Applaud now"
    - "Boo loudly in 3...2...1"
    - "Cheer for 5 seconds"
    - Signals can be triggered by:
    - Countdown timer (visual/audio)
    - On-screen instructions
    - Gestures from a presenter or performer
    - Manual trigger from an event admin

2. Visual & Audio Cues
    Commands appear on audience screens (mobile/web-based)
    Optional synchronized lighting/sound cues (e.g., flash or tone before a reaction)

3. Simulated Audience Mode

    - Each virtual audience member has one or more pre-recorded sound files for each reaction type
    - Upon receiving a command, the system:
    - Randomly selects a variation
    - Plays it at a randomized volume and time offset
    - This creates the effect of a real audience ("Mexican wave", ripple reactions, etc.)

4. Audience Segmentation- Participants can be organized into dynamic or static groups:

    - Gender (male/female)
    - Seating zones
    - Arrival time (e.g., first 50 attendees)
    - Custom tags (e.g., VIPs, fans, guests)
    - Reactions can be targeted to specific groups

5. Gamification & Points System- When in live (non-simulation) mode:

    - Each audience member is scored based on:
    - How often they respond
    - Accuracy/timing of their responses
    - Earned points can be used to:
    - Unlock new reaction types/sounds
    - Customize avatar or sound profile
    - Transfer or gift points to others
    - Leaderboards and statistics may be shown for engagement

6. Audience Roles and Permissions- Roles include:

    - Active Participant (can react)
    - Passive Viewer (can observe, not interact)
    - Group Leader (leads smaller groups)
    - Participants may be invited to specific events based on:
    - Point threshold
    - Prior participation
    - Admin invitation

7. Admin and Event Moderation Panel- Admins can:

    - Schedule and configure events
    - Assign roles (audience, host, co-host)
    - Send live commands to audience
    - Monitor engagement and sound levels in real-time
    - View reaction analytics (when, how many responded, intensity, etc.)
    - Accept or reject participant applications based on ranking or availability

8. Sound Intensity Monitoring- Each reaction has an intensity scale (0-100):

    - Example: Applause intensity 5 = weak clapping, 90 = thunderous ovation
    - Optional live decibel monitoring using microphones:
    - Real-time measurement of audience volume
    - Useful for physical venues or hybrid events

9. Public Participation Link- A quick-join link is generated for each event:

    - Participants can join as:
    - Active audience
    - Observer (audience of the audience) - if not eligible for participation

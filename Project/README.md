# CrowdPulse Project

## Project code structure

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

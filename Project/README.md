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
README.md               # Project documentation

app/
├── Controllers/        # Handle HTTP requests (route entrypoints)
├── Database/           # Database connection and management
├── Models/             # Models (DB representations like User, Command)
├── Services/           # App logic (CommandService, ValidationService)
└── Views/              # Views or templates shown to users

config/
├── database.php        # Database configuration
├── schema.sql          # SQL schema for database setup
├── init_db.php         # Database initialization script

public/
├── index.php           # App entry point
├── css/                # CSS styles
├── js/                 # JavaScript for client-side functionality
├── api/                # API endpoints
├── audio/              # Audio files for reactions
└── media/              # Images and other media assets

routes/
└── routes.php          # Route definitions (connect URLs to controllers)
└── Router.php          # Core routing logic that maps HTTP requests to controllers
```

Apache server start point: `Project/public`

## Database Setup

The application uses MySQL for data storage. Follow these steps to set up the database:

1. Make sure you have XAMPP installed with MySQL service running
2. Update database configuration in `config/database.php` if needed (default: localhost, root, no password)
3. Run the database initialization script:
   ```
   php config/init_db.php
   ```

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
  "targetTags": ["tag1", "tag2"],
  "targetGender": "<gender>",
  "message": "<custom_message>"
}
```

## Available Commands

- clap/applause: Users clap their hands
- cheer: Users cheer vocally
- boo: Users express disapproval
- murmur: Users talk quietly among themselves
- stomp: Users stomp their feet
- silence: Users remain quiet

## How It Works

- Admin commands are stored in the database.
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

## Recent Optimizations

The codebase has been cleaned up to remove:
- Unused placeholder files
- Redundant code in the SSE server
- Unnecessary user agent tracking
- Optimized database queries in the admin panel
- Improved performance in the CommandService

## Portable Setup Instructions

This project can be run on any machine with XAMPP installed without any configuration changes. Follow these steps:

### Prerequisites
- XAMPP installed with PHP 7.4+ and MySQL
- PHP must be in your system PATH (Important for the scripts to work)
- MySQL server must be running (start it from XAMPP control panel)

### Running the Application

#### Windows
1. Clone or download this project to any directory on your machine
2. Start MySQL from your XAMPP control panel
3. Double-click the `run.bat` file
   - If you get an error about PHP not being found, see troubleshooting below
4. Open your browser and navigate to http://localhost:8080

#### Mac/Linux
1. Clone or download this project to any directory on your machine
2. Start MySQL from your XAMPP control panel
3. Make the run script executable: `chmod +x run.sh`
4. Execute the script: `./run.sh`
5. Open your browser and navigate to http://localhost:8080

### Database Setup
1. Make sure MySQL is running from your XAMPP control panel
2. After starting the server with the script above, visit: http://localhost:8080/setup.php
3. Click "Test Connection" to verify your database connection
4. Click "Initialize Database" to set up the database and tables
5. Once complete, return to http://localhost:8080 to start using the application

### Troubleshooting

#### PHP Not Found
If you get an error that PHP is not found:

1. Make sure XAMPP is installed
2. Add PHP to your system PATH:
   - Windows: Add `C:\xampp\php` to your PATH environment variable
   - Mac/Linux: Run `export PATH=$PATH:/Applications/XAMPP/xamppfiles/bin` (or your XAMPP location)
3. Verify by running `php -v` in a new command prompt/terminal

#### Database Connection Issues
1. Ensure MySQL is running in your XAMPP control panel
2. Default credentials are:
   - Username: root
   - Password: (empty)
   - If you've changed these, edit `config/database.php` to match your settings

#### Port Already in Use
If port 8080 is already in use:
1. Edit `run.bat` or `run.sh` to change the port number (e.g., to 8081)
2. Update the URL in your browser accordingly

### Stopping the Server
- Press `Ctrl+C` in the terminal/command prompt window to stop the PHP server

### Notes
- The project will use port 8080 by default
- No Apache virtual hosts configuration is needed
- You can run this project from any directory

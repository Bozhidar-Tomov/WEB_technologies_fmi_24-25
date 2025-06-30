# Project Overview

This project is a web application for managing student queues and meetings in an educational setting. It provides functionality for teachers to organize exam/defense sessions and for students to join queues and participate in meetings.

## Project Structure

The project follows the MVC (Model-View-Controller) architecture pattern and is organized as follows:

```
Project/
  app/
    Controllers/  - Contains all controller classes
    Models/       - Contains all model classes
    Services/     - Contains service classes
    Views/        - Contains view templates
  config/         - Configuration files
  public/         - Publicly accessible files
    api/          - API endpoints
    audio/        - Audio files
    css/          - Stylesheets
      components/ - Component-specific styles
      layouts/    - Layout styles
      views/      - View-specific styles
    js/           - JavaScript files
    media/        - Media files
  routes/         - Route definitions
```

## Documentation

For detailed documentation about the project structure and functionality, please refer to:

- [Documentation (English)](ДОКУМЕНТАЦИЯ.md)
- [Documentation (Bulgarian)](ДОКУМЕНТАЦИЯ_БГ.md)

## Key Features

- User management with different roles (administrator, teacher, student)
- Room management for exams/defenses
- Dynamic queue system with estimated waiting times
- Group support in queues
- Meeting management with grading and feedback functionality
- Chat system for communication during meetings

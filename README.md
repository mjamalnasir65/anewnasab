# AnewNasab

AnewNasab is a web application for managing family trees and member information. It provides user authentication, member management, and a modern frontend with modular components.

## Features

- User registration, login, and logout
- Add, view, and manage family members
- Family tree database schema
- Responsive UI with modular HTML components
- RESTful API endpoints (PHP)
- Service worker for PWA support

## Folder Structure

- **api/**: PHP backend API endpoints (login, logout, register, member management)
- **assets/**
  - **css/**: Stylesheets
  - **icons/**: App icons
  - **js/**: Frontend JavaScript
- **components/**: HTML UI components (forms, member details, etc.)
- **database/**: SQL files for database schema and sample data
- **sample/**: (Purpose inferred as sample data or files)
- **index.php**: Main entry point
- **manifest.json**: PWA manifest
- **service-worker.js**: Service worker for offline support

## Setup

1. Import the SQL schema from `database/anewnasab.sql` or `database/family_tree_schema.sql` into your MySQL server.
2. Place the project in your web server directory (e.g., `www` for WAMP).
3. Configure database credentials in the API PHP files if needed.
4. Access the app via `http://localhost/anewnasab/` in your browser.

## Usage

- Register a new user or log in.
- Add and manage family members.
- View member details and family relationships.

---

**Note:** This project uses PHP for the backend and vanilla JS/HTML/CSS for the frontend. Make sure your server supports PHP and MySQL.

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

## Frontend Format & Coding Standards (Strict)

All frontend development must strictly adhere to the following format and conventions:

- **Component-based HTML**: UI must be built using modular HTML components in the `components/` folder. Each form, panel, or major UI section should be a separate HTML file and loaded dynamically as needed.
- **Consistent Styling**: Use Tailwind CSS utility classes for all UI elements. The `assets/css/style.css` file should only be used for custom overrides or unique styles not covered by Tailwind. If not needed, it can be removed.
- **UI Layout Requirement**: All forms must appear inside a form card at the top of the page. The treeview (family tree visualization) must always be displayed below the draggable form card.
- **JavaScript Structure**:
  - All main logic must reside in `assets/js/main.js`.
  - Use clear, descriptive function names and keep functions focused on a single responsibility.
  - Use `window.DEBUG`, `logDebug`, and `logError` for all debug and error logging.
  - All API calls must use the Fetch API with JSON payloads and handle errors gracefully.
  - UI updates must be performed by manipulating the DOM or loading HTML components dynamically.
- **Form Handling**:
  - All forms must validate required fields before submission.
  - Use semantic HTML for form elements and labels.
  - Show user-friendly error messages for validation and API errors.
- **User Experience**:
  - All navigation and state changes must be reflected in the UI without full page reloads.
  - Use loading indicators or disabled states for buttons during async operations.
  - All modals, forms, and panels must be accessible and mobile-friendly.
- **PWA Support**:
  - Do not break service worker or manifest functionality.
  - All static assets must be referenced with correct relative paths.

## Family Tree View Requirements

- The tree view must render each member as a node displaying their name. Each name is clickable and opens the corresponding form for that member.
- Nodes are organized as part of a hierarchical family tree, which is dynamically updated when new members are inserted via the form.
- Supported relationships:
  - Child to parent (mother, father)
  - Partner
  - Siblings
  - Parent (mother, father) to child
- Relationships are visually marked by connecting lines, and the tree should appear in a structure similar to:

  Member
  ├── Father
  ├── Mother
  │   └── Siblings
  ├── Partner
  │   └── Child

- The tree must be expandable and collapsible, allowing users to show or hide branches as needed.

> **Note:** Any new UI or feature must follow these standards. Code reviews should check for strict adherence to this format.

---

**Note:** This project uses PHP for the backend and vanilla JS/HTML/CSS (with Tailwind CSS) for the frontend. Make sure your server supports PHP and MySQL.

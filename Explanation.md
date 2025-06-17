# The Problem

User requires a plugin to be able to detect visible links above the fold for every user visit on different screen sizes to enable them make decisions on the layout.

# Technical Specification

## How It Works

### Backend

The backend leverages **WordPress REST API** to expose endpoints for data collection and reporting:

- **Data Submission Endpoint**
  - **`POST wp-json/user-story/links`**
  - Accepts a payload containing:
    - A list of URLs (with associated metadata)
    - Device information (user agent, screen size, etc.)
  - **Behavior:**
    - If the device is new, a unique device ID is generated and returned to the frontend.
    - The submitted URLs are parsed, normalized, and persisted to the database through the `Link{}` Object.

---

- **Admin Data Retrieval Endpoints**
  - **`GET wp-json/user-story/links`**
    - Requires `manage_options` capability (administrator access).
    - Returns analytical reports based on stored link data, supporting filters like device type, screen size, or URL patterns.
  
  - **`GET wp-json/user-story/links/filter-data`**
    - Provides available filter options for the reporting interface (e.g., available screen sizes, device types, domains).

---

### Frontend

The frontend logic is designed for efficient and minimal data collection:

- The link-tracking script is embedded **only on the homepage**.
- The script sends data:
  - On the **first visit** to a tab (per session).
  - Whenever it detects a change in the page state compared to the last stored snapshot.  
    This includes:
    - Dynamic content updates (e.g., text or link changes)
    - Link position or name changes
    - Changes in screen size or orientation

- Change detection is handled using hashing of link data (URL, name, position) and comparison against `sessionStorage`.  
- If a change is detected, an update is triggered to the backend, and the device/session is tracked accordingly.

---

## Key Features

✅ REST API secured for admin access  
✅ Minimal network load — data sent only on meaningful change  
✅ Device and session-aware tracking  
✅ Flexible filterable reporting for admins

# Technical Decision

## System Design

## Database Design Overview

To support comprehensive link-level analytics and accommodate future requirements beyond simple screen size tracking, I designed the database to be highly extensible and efficient for querying link interactions. The design ensures that we can capture detailed information about user devices, their IP addresses, and the precise links visible to each user, including their on-screen position relative to the viewport.

Additionally, URLs are decomposed into their constituent parts — **scheme**, **host**, **path**, **query**, and **fragment** — and stored in separate, indexed columns. This allows for highly efficient querying, such as filtering for external links, specific domains, or URLs with certain paths (e.g., `path = 'logout'`).

---

### Core Tables

#### `wp_devices`
- **Columns:** `uuid (PK)`, `user_id`, `user_agent`, `created_at`
- **Description:** Stores a unique record for each distinct device that has accessed the site. The `uuid` serves as an anonymized identifier to obscure public metrics regarding device counts. The `user_agent` field captures browser and device details to facilitate device type analysis.

---

#### `wp_device_ips`
- **Columns:** `id (PK)`, `device_uuid (FK → wp_devices.uuid)`, `ip`, `created_at`
- **Description:** Records all IP addresses used by each device. This enables tracking of device location changes or multi-IP access patterns over time.

---

#### `wp_visible_links`
- **Columns:** `id (PK)`, `scheme`, `hostname`, `path`, `query`, `fragment`, `name`
- **Indexes:** Multiple-Column indexes on `scheme`, `hostname`, `path`, `query`, `fragment` for efficient URL component queries
- **Description:** Stores metadata about each unique link visible above-the-fold for users on various devices and screen sizes. This supports analysis of which links are most commonly presented and interacted with across different viewports.

---

#### `wp_visible_link_visits`
- **Columns:** `id (PK)`, `visible_link_id (FK → wp_visible_links.id)`, `device_ip_id (FK → wp_device_ips.id)`, `height`, `width`, `position_xy`, `created_at`
- **Description:** Captures individual link visibility events, including screen dimensions (`height`, `width`) and the link’s position (`position_xy`) on the screen. The table maintains foreign key relationships to both the link metadata and the device-IP association to tie visibility events to specific devices and sessions.

---

### Key Features of the Design

✅ **Extensibility:** The structure allows for easy incorporation of additional device or link properties without major schema changes.

✅ **Efficient querying:** With URL parts indexed separately, queries like "find all external links", "links to a specific domain", or "links with a specific path (e.g., logout)" are performant.

✅ **Granular tracking:** The position and dimensions data enables precise heatmapping and analysis of above-the-fold content engagement.


## Backend Design Overview

The backend follows a custom architecture I designed, built around the concept of **Components**. Each Component encapsulates a specific domain of functionality (e.g., Devices, Links) and serves as the interface between the WordPress environment and the PHP abstraction layer.

#### Components

- **Definition:** A Component represents a functional module that manages:
  - Associated REST API routes
  - Cron jobs
  - WP-CLI commands
  - Database interaction (via Objects)
  - Caching and data persistence

- **Purpose:** Components are responsible for retrieving, persisting, and caching data. They act as managers that bridge the gap between WordPress and custom PHP abstractions.

- **Structure:** Components contain and coordinate **Objects**, which represent individual database rows or records in PHP.

---

#### Core Architecture

- **Base Class:**  
  All Components are initialized and loaded by a central base plugin class (`plugin.php`). This base class orchestrates the loading process and ensures that all Component attributes (REST routes, cron jobs, etc.) are registered correctly at runtime.

---

#### Directory Structure

##### `Components/`
Contains all domain-specific managers (Components), e.g. `Links`, `Devices`.  
Each Component manages its domain logic, data handling, and integrations.

---

##### `Cronies/`
Contains cron job classes.  
Cron jobs are associated with their respective Components via the `Component::cronies()` method and are scheduled/registered accordingly.

---

##### `Exceptions/`
Holds custom exception classes used throughout the plugin for consistent error handling.

---

##### `Objects/`
Defines PHP objects that serve as representations of database records.  
These classes provide methods to manipulate and interact with individual rows.

---

##### `Routes/`
Contains REST API route definitions.  
Each route can be linked to its related Component using the `Component::rest_routes()` method.

---

##### `Traits/`
Provides reusable traits that offer shared functionality across multiple Components or classes.

---

#### Key Features

✅ **Modular design:** Components decouple domain logic, making the codebase extensible and easier to maintain.

✅ **Clean WordPress integration:** Components serve as clean abstractions over WordPress internals (REST API, cron, CLI).

✅ **Scalability:** The architecture supports adding new Components, routes, or jobs with minimal impact on existing code.


## Frontend Design Overview

The frontend consists of two primary JavaScript modules: `embed.js` and `settings.js`. Each serves a distinct purpose within the system for both tracking and admin interface functionality.

---

### `embed.js`

- **Purpose:** Captures and tracks visible links on the page dynamically.
  
- **How it works:**
  - Hooks into the `window.onload` event to ensure the DOM is fully loaded.
  - Uses the `IntersectionObserver` API to detect and collect links that appear above-the-fold (visible in the initial viewport).
  - Each link’s data — including URL, name, and X/Y screen position — is combined and hashed using **SHA-1** (same method as Git) to produce a unique fingerprint for that link state.
  - The generated hash is compared against a stored hash in `sessionStorage`.  
    - **If the hash differs** (e.g., due to a link position, URL, or name change), an update is triggered and the new hash is stored.
    - **If the hash matches**, no action is taken — reducing unnecessary updates and load on the backend.

- **Benefit:**  
  Ensures that updates are only sent when actual content or layout changes occur, improving performance and minimizing redundant network activity.

---

### `settings.js`

- **Purpose:** Powers the admin interface for managing and visualizing data.

- **How it works:**
  - Leverages WordPress’s native React support to build a lightweight admin page interface.
  - Implements a basic React component that retrieves and displays data from the backend via REST API endpoints.

- **Benefit:**  
  Provides a modern, maintainable UI that cleanly integrates with the WordPress admin panel.

---

## Key Features

✅ **Efficient tracking:** Uses hashing and `sessionStorage` to avoid unnecessary updates.

✅ **Modern standards:** Employs `IntersectionObserver` and React for optimal performance and maintainability.

✅ **Change-driven updates:** Updates are only sent when meaningful changes are detected (link position, name, or URL).



# The Solution

The plugin fulfills the requirements of the user story by automatically detecting and capturing **visible links above the fold** on the homepage. These links are structured, normalized, and efficiently stored in the database for reporting purposes.

Administrators can access detailed reports through the WordPress admin interface. Reports provide insights on:
- **Visible links**, segmented by screen size and visit date
- **Additional filters**, including specific screen sizes and domains
- **Date range selection**, allowing admins to view data over custom time periods

To manage storage and ensure optimal performance, link data older than **7 days** is automatically purged from the system during scheduled clean-up routines.

# Development Limitations

While developing the plugin, I encountered several limitations and made practical compromises due to technical constraints or project deadlines.

---

## Key Limitations

### MySQL Key Size Limit  
- **Challenge:**  
  In an effort to index link-related columns in `wp_visible_links` for faster data retrieval, I had to reduce the size of certain columns to comply with MySQL’s maximum index key size restrictions.  

- **Impact:**  
  The `query` and `fragment` columns were truncated to smaller sizes than what a browser URL might contain. This means longer query strings or fragments may not be fully indexed, potentially impacting certain queries.

---

### PHPCS Rule Exceptions  
- **Challenge:**  
  Some WordPress coding standard (PHPCS) rules were not fully adhered to due to practicality or time constraints.

- **Ignored Rules & Reasons:**
  - `WordPress.DB.DirectDatabaseQuery`:  
    The plugin creates and manages custom tables beyond the default WordPress schema, which necessitates direct database queries.
  
  - `WordPress.DB.PreparedSQL.NotPrepared`:  
    Some dynamic queries involved complex PHP concatenation and manipulations. PHPCS could not detect the preparation state accurately, even though queries were handled securely.
  
  - `WordPress.PHP.DevelopmentFunctions.error_log_error_log`:  
    Due to time limitations, a proper logging mechanism wasn’t implemented, and `error_log()` was temporarily used for debugging, despite being discouraged in WordPress coding standards.

---

## Summary

✅ The decisions made were necessary trade-offs to deliver functional and performant code within the available timeline.  
✅ These limitations can be addressed in future iterations to further align with best practices and standards.


# Potential Improvements

During development, I identified several areas where the design could be enhanced for better maintainability, performance, and reliability. However, these improvements were postponed due to time constraints.

---

## Suggested Enhancements

### `AbstractObject::save()` Refactor  
- **Current Issue:**  
  The `save()` logic is duplicated across individual object classes, leading to code repetition and potential maintenance challenges.

- **Proposed Solution:**  
  Move the common `save()` logic into the `AbstractObject` class. This would provide a centralized, reusable save implementation that child classes can extend or override where necessary.

---

### Implement Object Watchers  
- **Current Issue:**  
  Cached object instances may unintentionally overwrite database records that have been modified by newer object instances, leading to stale or conflicting data writes.

- **Proposed Solution:**  
  Introduce an **Object Watchers** trait that:
  - Tracks changes to object fields during the object’s lifetime.
  - On `save()`, persists only the fields that have actually changed.
  - Reduces the risk of overwriting newer data with stale cached values.

---

### Split Large Link Data Payloads  
- **Current Issue:**  
  Pages with a high number of visible links (e.g., link-heavy homepages) can generate a large JavaScript object. This may exceed `PHP_POST_MAX_SIZE` limits during transmission.

- **Proposed Solution:**  
  Enhance the frontend JavaScript to:
  - Automatically split large link data into smaller chunks.
  - Send these chunks in multiple POST requests to the server.
  - Prevent POST size errors and improve reliability for link-heavy pages.

---

## Benefits of These Improvements

✅ **Cleaner codebase:** Reduces redundancy and makes `save()` logic easier to maintain.

✅ **Safer data writes:** Prevents cache-related data loss and improves data integrity.

✅ **Scalable frontend handling:** Enables robust handling of large link datasets without server-side POST errors.

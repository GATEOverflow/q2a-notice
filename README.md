# q2a-notice
Plugin to send notices to users on a Q2A site based on their level. Notices are shown only when a user login and only admins can send them. (Page relative url is: notice-plugin-page)

A powerful **Notice Board / Announcement** plugin for **Question2Answer** that allows administrators to create, schedule, and target notices to specific audiences.  
Supports **public, minimum user level, and specific user handle-based targeting**, along with an animated scrolling widget with mark-as-read capability. (Management page URL: `notice-board`)

## 🚀 Notice Board Features

| Feature | Description |
|--------|-------------|
| Create multiple notices | Add / edit / delete notices dynamically with smooth UI |
| Audience control | Public, Min. user level, or specific users |
| User handle search | Autocomplete search to select users without remembering IDs |
| Scheduling | Start & end date-time with validation |
| Scrolling widget | Smooth auto-scrolling sidebar/main widget with pause on hover |
| Mark as read | Logged-in users can dismiss notices; state persists in localStorage |
| Smart restore | When all notices are read, users see an "All caught up" message with a "Show all notices" link |
| Auto-cleanup | localStorage entries are purged automatically when admins remove or expire notices |
| Dark mode | Full dark mode support for both the widget and the management page |
| Lazy loading | CSS and JS assets load non-blocking (`defer` / `media="print"` pattern) |
| Centralized versioning | Asset cache-busting version managed from a single constant |

# Audience Types

| Audience | Meaning |
|----------|---------|
| Public | Visible to everyone (including guests) |
| Min. user level | Visible only to logged-in users at or above the required level |
| Specific users | Visible only to selected user handles |

## 🔕 Mark as Read (Dismiss) Behavior

- **Guests** see all active notices scrolling — no dismiss option.
- **Logged-in users** see a ✕ button on hover for each notice.
- Clicking ✕ fades the notice out and saves it to `localStorage` (key: `qa_notices_read_{userid}`).
- On next page load, dismissed notices stay hidden.
- When all notices are dismissed, an *"All caught up!"* banner appears with a **"Show all notices"** link that clears the read state.
- When an admin deletes or expires a notice, its localStorage entry is automatically cleaned up — no stale data accumulates.

## 🛠 Installation

1. Copy the `notice` folder into `qa-plugin/`.
2. Go to **Admin → Layout** and place the **"Notice Board (Scrolling)"** widget in a sidebar or main region.
3. Go to **Admin → Plugins** to configure the minimum user level allowed to manage notices.
4. Visit `yoursite.com/notice-board` to create and manage notices.

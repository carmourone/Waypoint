# Waypoint

Waypoint is a coached progression engine for skill-heavy physical coaching, built on WordPress. It tracks what each participant is actually learning through coached practice — the messy middle between membership management and performance outcomes.

Sailing is the first domain. The architecture is built to support any sport or discipline where participants develop skills through coached sessions over time.

## What it does

- **Courses** group participants into coached programs with a defined schedule and curriculum
- **Sessions** record what was planned, what actually happened, and who attended
- **Multi-group sessions** allow combined cohorts (e.g. two levels running concurrently) with separate skill sets and per-sailor attendance
- **Skills & progress** track each participant's development against a structured curriculum
- **Observations** capture coach judgement in the moment — individual or group, with confidence levels
- **Training plans** generate targeted follow-up for missed sessions, skill gaps, or coach-identified needs
- **Role-based access** gives coaches, assistant coaches, sailors, and parents the right view of the right data

## Architecture

```
wp-content/plugins/
  wpnt/               Core engine — people, sessions, attendance, skills,
                      progress, observations, training plans, REST API
  wpnt-sailing/       Sailing domain pack — boat class + wind range
                      taxonomies, conditions meta fields, seeded curriculum

wp-content/themes/
  waypoint/           Presentation layer — stays deliberately thin;
                      all domain logic lives in wpnt
```

### Domain packs

A domain pack is a WordPress plugin that `Requires Plugins: wpnt` and hooks into `wpnt_packs_init`. It can:

- Register sport-specific taxonomies (e.g. boat class, position group, surface type)
- Add custom meta fields to core post types using the `_wpnts_` namespace convention
- Seed a curriculum tree from a `data/curriculum.json` file on activation

The core curriculum post types (`wpnt_curriculum`, `wpnt_node`, `wpnt_skill`) remain fully editable outside any pack. Packs provide a starting point, not a locked structure.

To add a new sport:

```
wp-content/plugins/wpnt-{sport}/
  wpnt-{sport}.php           Requires Plugins: wpnt
  includes/
    class-wpnt-{sport}-taxonomies.php
    class-wpnt-{sport}-meta.php
    class-wpnt-{sport}-importer.php
  data/
    curriculum.json
```

## Requirements

- WordPress 6.4+
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- BuddyPress 12+ (optional — for member profiles and course groups)

## Installation

1. Clone this repository into your WordPress installation
2. Activate **Waypoint (wpnt)** from the Plugins screen
3. Activate **Waypoint — Sailing Pack** (or any other domain pack)
4. Create pages and assign the provided page templates:
   - Coach Dashboard → `page-templates/template-coach-dashboard.php`
   - Sailor Dashboard → `page-templates/template-sailor-dashboard.php`
   - Parent Dashboard → `page-templates/template-parent-dashboard.php`
5. Add the shortcode `[waypoint_course_list]` to a courses listing page
6. Configure club name and location under **Waypoint → Settings**

## Custom database tables

The plugin creates three tables alongside standard WordPress post meta:

| Table | Purpose |
|---|---|
| `wp_wpnt_session_groups` | Cohort/level groups within a session |
| `wp_wpnt_attendance` | Per-sailor, per-group attendance records |
| `wp_wpnt_progress` | Structured skill assessment records |
| `wp_wpnt_observations` | Coach notes against a session, sailor, or course |

## REST API

All dynamic operations go through `wpnt/v1/`. Key endpoints:

```
GET  /sessions/today
GET  /sessions/upcoming
GET  /sessions/{id}/groups
POST /sessions/{id}/groups
PUT  /session-groups/{id}
POST /session-groups/{id}/attendance
POST /session-groups/{id}/add-sailor
POST /attendance
POST /observations
POST /progress
POST /course/{id}/generate-sessions
POST /course/{id}/enroll
```

## Roles

| Role | Description |
|---|---|
| `wpnt_club_admin` | Full access to all data and settings |
| `wpnt_coach` | Manage courses, sessions, attendance, observations, training plans |
| `wpnt_asst_coach` | Mark attendance and add observations; cannot manage courses |
| `wpnt_parent` | Read-only view of their child's sessions, progress, and approved feedback |
| `wpnt_sailor` | Read-only view of their own courses, progress, and published feedback |

## Development

Branch: `claude/wordpress-app-1t7hyr`

The theme (`waypoint`) is intentionally thin — it renders data provided by the plugin but contains no domain logic. All business logic, custom tables, REST endpoints, and role definitions live in `wpnt`.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).

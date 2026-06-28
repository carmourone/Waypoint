# Waypoint

Waypoint is a coached progression engine for skill-heavy physical coaching, built on WordPress + BuddyPress. It tracks what each participant is actually learning through coached practice — the messy middle between membership management and performance outcomes.

The core plugin is domain-agnostic. Sailing is the first domain; the architecture supports any sport or discipline where participants develop skills through coached sessions over time.

## What it does

- **Courses** group participants into coached programs with a defined schedule and curriculum
- **Sessions** record what was planned, what actually happened, and who attended
- **Multi-group sessions** allow combined cohorts running concurrently with separate skill sets and per-group attendance
- **Skills & progress** track each participant's development against a structured curriculum
- **Observations** capture coach judgement in the moment — individual or group, with confidence levels and evidence types
- **Training plans** generate targeted follow-up for missed sessions, skill gaps, or coach-identified needs
- **Role-based access** gives coaches, assistant coaches, athletes, and parents the right view of the right data

## Architecture

```
wp-content/plugins/
  wpnt-core/          Core engine — people, sessions, attendance, skills,
                      progress, observations, training plans, REST API
  wpnt-sailing/       Sailing domain pack — boat class + wind range
                      taxonomies, conditions meta fields, seeded curriculum

wp-content/themes/
  waypoint/           Presentation layer — stays deliberately thin;
                      all domain logic lives in wpnt-core
```

### Content model

WordPress and BuddyPress provide primary storage. Custom tables hold only typed relationship data.

| Layer | Stores |
|---|---|
| WP Posts | Sessions, courses, skills, training plans, observations (CPTs) |
| WP Users | Athletes, coaches, parents, org admins |
| BP Groups | Course cohorts — enrollment IS BP group membership |
| BP Friends | Parent ↔ athlete relationship (drives parent read-access) |
| `wpnt_u2p` | User → Post edges (attendance, skill assessment) |
| `wpnt_p2p` | Post → Post edges (session→course, session→skill, plan→skill) |
| `wpnt_g2p` | BP Group → Post edges (squad enrolled in course) |
| `wpnt_types` | Edge type registry — domain packs add types here |

### Domain packs

A domain pack is a WordPress plugin that `Requires Plugins: wpnt-core` and hooks into `wpnt_packs_init`. It can:

- Override participant and org labels (`participant_label`, `participant_label_plural`, `org_label`)
- Register sport-specific taxonomies and meta fields using the `_wpnts_` namespace
- Register new graph edge types via `WPNT_Graph::register_type()` — no schema migration needed
- Seed a curriculum tree on activation

```php
add_action( 'wpnt_packs_init', function() {
    WPNT_Pack::register( 'wpnt-sailing', [
        'participant_label'        => 'Sailor',
        'participant_label_plural' => 'Sailors',
        'org_label'                => 'Club',
    ] );
} );
```

The core defaults to "Athlete" / "Athletes" / "Organisation". Labels resolve at runtime via `WPNT_Pack::get_active_label()`.

To add a new sport:

```
wp-content/plugins/wpnt-{sport}/
  wpnt-{sport}.php           Requires Plugins: wpnt-core
  includes/
    class-wpnt-{sport}-taxonomies.php
    class-wpnt-{sport}-meta.php
  data/
    curriculum.json
```

### Authorization

`WPNT_Graph::can_view_athlete_data(int $viewer_id, int $athlete_id)` resolves access:

1. Own data → allow
2. `manage_options` capability → allow
3. Coach: viewer is admin/mod of a BP group the athlete belongs to → allow
4. Parent: viewer has `wpnt_parent` role AND is a confirmed BP friend of the athlete → allow

## Requirements

- WordPress 6.4+
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- BuddyPress 12+ (optional — for member profiles, course groups, and parent authorization)

## Installation

1. Clone this repository into your WordPress installation
2. Activate **Waypoint Core (wpnt-core)** from the Plugins screen
3. Optionally activate **Waypoint — Sailing Pack** or another domain pack
4. Create pages and assign the provided page templates:
   - Coach Dashboard → `page-templates/template-coach-dashboard.php`
   - Athlete Dashboard → `page-templates/template-athlete-dashboard.php`
   - Parent Dashboard → `page-templates/template-parent-dashboard.php`
5. Configure organisation name and location under **Waypoint → Settings**

## Custom database tables

| Table | Purpose |
|---|---|
| `wp_wpnt_types` | Edge type registry — extensible by domain packs |
| `wp_wpnt_u2p` | User → Post edges (attendance, assessment) |
| `wp_wpnt_p2p` | Post → Post edges (session→course, session→skill, plan→skill) |
| `wp_wpnt_g2p` | BP Group → Post edges (squad enrolled in course) |

The schema is fixed at v5 for all new installs. Installs upgrading from v1–v4 run incremental migrations then land on v5; legacy tables are left in place for manual data review.

## REST API

All dynamic operations go through `wpnt/v1/`. Key endpoints:

```
GET  /sessions/today
GET  /sessions/upcoming
GET  /sessions/{id}/groups
POST /sessions/{id}/groups
PUT  /session-groups/{id}
POST /session-groups/{id}/attendance
POST /session-groups/{id}/add-athlete
POST /attendance
POST /observations
POST /progress
POST /course/{id}/generate-sessions
POST /course/{id}/enroll
GET  /athletes
GET  /athletes/{id}
```

## Roles

| Role | Description |
|---|---|
| `wpnt_org_admin` | Full access to all data and settings |
| `wpnt_coach` | Manage courses, sessions, attendance, observations, training plans |
| `wpnt_asst_coach` | Mark attendance and add observations; cannot manage courses |
| `wpnt_parent` | Read-only view of their child's sessions, progress, and approved feedback |
| `wpnt_athlete` | Read-only view of their own courses, progress, and published feedback |

## Development

See [CLAUDE.md](CLAUDE.md) for full architecture notes, class inventory, and conventions.

Active branch: `claude/wordpress-app-1t7hyr`

The theme (`waypoint`) is intentionally thin — it renders data provided by the plugin but contains no domain logic. All business logic, graph tables, REST endpoints, and role definitions live in `wpnt-core`.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).

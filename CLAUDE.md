# CLAUDE.md

This file gives Claude Code the context needed to work effectively in this repository.

## Project overview

Waypoint is a coached progression engine built on WordPress + BuddyPress. It tracks participant development through coached sessions over time. The core plugin (`wpnt-core`) is domain-agnostic; domain packs (e.g. `wpnt-sailing`) add sport-specific vocabulary and curriculum.

## Repository layout

```
wp-content/
  plugins/
    wpnt-core/          Core engine — people, sessions, skills, progress, REST API
      includes/         PHP classes
      admin/            Admin screens and views
      assets/           CSS + JS
    wpnt-sailing/       Sailing domain pack — boat class, wind range, seeded curriculum
  themes/
    waypoint/           Thin presentation layer — no business logic lives here
```

## Key classes

| Class | File | Role |
|---|---|---|
| `WPNT_Graph` | `includes/class-wpnt-graph.php` | Graph edge CRUD (u2p, p2p, g2p) and authorization |
| `WPNT_DB` | `includes/class-wpnt-db.php` | Table creation and schema migrations |
| `WPNT_Pack` | `includes/class-wpnt-pack.php` | Domain pack registry and label resolution |
| `WPNT_Activator` | `includes/class-wpnt-activator.php` | Activation hooks, multisite provisioning |
| `WPNT_Post_Types` | `includes/class-wpnt-post-types.php` | CPT registration |
| `WPNT_Taxonomies` | `includes/class-wpnt-taxonomies.php` | Taxonomy registration |
| `WPNT_Roles` | `includes/class-wpnt-roles.php` | Role and capability setup |
| `WPNT_Attendance` | `includes/class-wpnt-attendance.php` | Attendance marking and rendering |
| `WPNT_Session_Group` | `includes/class-wpnt-session-group.php` | Multi-cohort session management |
| `WPNT_Course` | `includes/class-wpnt-course.php` | Course and enrollment logic |
| `WPNT_Session` | `includes/class-wpnt-session.php` | Session queries |
| `WPNT_REST_API` | `includes/class-wpnt-rest-api.php` | REST endpoint registration |
| `WPNT_BuddyPress` | `includes/class-wpnt-buddypress.php` | BuddyPress integration |
| `WPNT_Meta_Boxes` | `includes/class-wpnt-meta-boxes.php` | Admin meta box registration and save |
| `WPNT_Admin` | `admin/class-wpnt-admin.php` | Admin menu and screen rendering |

## Database schema

Four custom tables. Older installs may also have legacy tables (`wpnt_attendance`, `wpnt_progress`, `wpnt_session_groups`) left in place after the v6 migration — the application no longer writes to them.

### `wpnt_types` — edge type registry
```
id, pack, table_kind ENUM(u2p|p2p|g2p), name, label, label_plural, schema_def
UNIQUE (pack, name)
```

### `wpnt_u2p` — User → Post edges
```
id, type_id, user_id, post_id, context_id DEFAULT 0, data JSON, created_at, updated_at
UNIQUE (type_id, user_id, post_id, context_id)
```
`context_id` is an optional discriminator — used for group-scoped attendance (set to the BP group ID). Zero means ungrouped / single-cohort session.

Built-in types: `attended`, `assessed`

### `wpnt_p2p` — Post → Post edges
```
id, type_id, source_id, target_id, data JSON, created_at, updated_at
UNIQUE (type_id, source_id, target_id)
```
No built-in types. Domain packs register types here via `WPNT_Graph::register_type()`.

### `wpnt_g2p` — BP Group → Post edges
```
id, type_id, group_id, post_id, data JSON, created_at, updated_at
UNIQUE (type_id, group_id, post_id)
```
Built-in types: `session_group` (BP cohort group participating in a session — replaces the old `wpnt_session_groups` table)

`session_group` edge data: `{label, planned_skills[], actual_skills[], adhoc_athlete_ids[], display_order}`
Group-scoped attendance uses `context_id = bp_group_id` in `wpnt_u2p`.

## Content model

WordPress and BuddyPress provide primary storage — custom tables only hold typed relationship data.

| Layer | Stores |
|---|---|
| WP Posts | Sessions, courses, skills, training plans, observations (CPTs) |
| WP Users | Athletes, coaches, parents, org admins |
| WP Post Meta | `_wpnt_*` fields on posts |
| BP Groups | Course cohorts — enrollment IS group membership (`bp_groups_members`) |
| BP Friends | Parent ↔ athlete social relationship (drives parent authorization) |
| `wpnt_u2p` | Attendance (`attended`), skill assessment (`assessed`) |
| `wpnt_p2p` | Domain pack edge types only — no built-in core types |
| `wpnt_g2p` | Session groups (`session_group`) — which BP cohort attends which session |

## Authorization

`WPNT_Graph::can_view_athlete_data(int $viewer_id, int $athlete_id): bool`

Checks in order:
1. Own data → allow
2. `manage_options` capability → allow
3. Viewer is admin/mod of a BP group the athlete belongs to → allow (coach case)
4. Viewer has `wpnt_parent` role AND is a confirmed BP friend of the athlete → allow

## Roles

| Role | Description |
|---|---|
| `wpnt_org_admin` | Full access to all data and settings |
| `wpnt_coach` | Manage courses, sessions, attendance, observations, training plans |
| `wpnt_asst_coach` | Mark attendance and add observations; cannot manage courses |
| `wpnt_parent` | Read-only view of all coach–child communications and shared content (scoped via BP friendship) |
| `wpnt_athlete` | Read-only view of own courses, progress, and published feedback |

## Child safety and privacy

**Non-negotiable.** Waypoint must comply with Australian child safe standards and EU GDPR for minors. These rules cannot be overridden by club admins, domain packs, or feature flags.

### The core principle

Anything a coach shares with a minor athlete is also visible to that athlete's parent or guardian by default, without any opt-out.

A parent is a protective layer, not an optional audience. The system must never create a communication channel between a coach and a minor that bypasses the parent.

### Visibility rules

| Content type | Athlete | Parent/guardian | Coach | Org admin |
|---|---|---|---|---|
| Attendance records | ✓ | ✓ always | ✓ | ✓ |
| Session notes shared with athlete | ✓ | ✓ always | ✓ | ✓ |
| Coach observations shared with athlete | ✓ | ✓ always | ✓ | ✓ |
| Training plan content (athlete-facing) | ✓ | ✓ always | ✓ | ✓ |
| Coach responses to diary entries | ✓ | ✓ always | ✓ | ✓ |
| Internal coach professional notes (not shared with athlete) | — | — | ✓ | ✓ |
| Athlete diary entry (athlete-authored) | ✓ | configurable per club/age | coach-review only | ✓ |

The critical distinction:
- **Communication** = any content the coach addresses to or shares with the athlete. Always parent-visible. No exceptions.
- **Internal note** = professional record the coach keeps for their own purposes, never shared with the athlete. These are legitimately coach-only and are not communications.

If a note is visible to the athlete, it is visible to the parent. There is no middle state.

### Athlete diary privacy

Diary entries are athlete-authored. They are not coach communications. Club admins may configure whether parents can read diary entries, subject to:
- Age of the athlete (older teens may have greater diary privacy)
- Club safeguarding policy
- Applicable jurisdiction rules

However, **coach responses to a diary entry are always parent-visible**, regardless of whether the underlying diary entry is.

### Implementation rules

1. Every content-producing feature (observations, training plan summaries, diary responses, session feedback) must carry a `_wpnt_visibility` field with at minimum two states: `shared` (athlete + parent + coach) and `internal` (coach + org_admin only).
2. The default for any coach-authored content shared with an athlete is `shared`. Code must never default to `internal` for coach-athlete communications.
3. `WPNT_Graph::can_view_athlete_data()` already grants parent access via confirmed BP friendship. All new content queries must respect this — never bypass it with direct `get_posts()` or `get_post_meta()` calls without a visibility check.
4. No feature, setting, or domain pack may introduce a mechanism that allows coach-to-minor communications to be hidden from that minor's parent.
5. Data retention: parents may request erasure of their minor child's data. Build with the assumption that records will need to be hard-deleted, not just soft-deleted, per GDPR Art. 17.

### Compliance context

- **Australia**: National Principles for Child Safe Organisations (2019), state child safe standards, Privacy Act 1988 (Australian Privacy Principles). Principle 4 (families and communities) and Principle 6 (complaints) require transparent family communication and oversight.
- **EU/UK**: GDPR Art. 8 (children under 16 require parental consent for data processing in most member states), Art. 15 (parents have access rights to data processed about their minor child), Art. 17 (right to erasure).
- **Minimum age threshold**: treat anyone under 18 as a minor for the purposes of these rules unless jurisdiction-specific guidance requires a lower threshold.

## Domain packs

A pack is a WordPress plugin that hooks into `wpnt_packs_init` and calls `WPNT_Pack::register()`:

```php
add_action( 'wpnt_packs_init', function() {
    WPNT_Pack::register( 'wpnt-sailing', [
        'participant_label'        => 'Sailor',
        'participant_label_plural' => 'Sailors',
        'org_label'                => 'Club',
    ] );
} );
```

Labels resolve at runtime via `WPNT_Pack::get_active_label( 'participant_label', 'Athlete' )`.

Packs register new graph edge types via `WPNT_Graph::register_type()` — no schema migration needed. Packs may also add CPT taxonomies and meta fields using the `_wpnts_` namespace.

## Conventions

- Custom table prefix: `{$wpdb->prefix}wpnt_` (e.g. `wp_wpnt_u2p`)
- Post meta: `_wpnt_` prefix (private, underscore-led). Never rename existing meta keys — add new ones.
- Option keys: `wpnt_` prefix
- REST namespace: `wpnt/v1/` — domain-agnostic routes only
- No hardcoded domain language in core — always use `WPNT_Pack::get_active_label()` for UI strings
- DB version: `WPNT_DB_VERSION` constant in `wpnt-core.php`; bump and add `upgrade_to_vN()` in `WPNT_DB`

## Development

- Active branch: `claude/wordpress-app-1t7hyr`
- PHP 8.1+, WordPress 6.4+, MySQL 5.7+ / MariaDB 10.4+
- BuddyPress 12+ optional — all BP calls are guarded with `function_exists()`
- `WPNT_Graph` uses a static `$type_cache` array; clear it by calling `WPNT_Graph::clear_type_cache()` in tests

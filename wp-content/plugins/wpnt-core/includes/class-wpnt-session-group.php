<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A Session Group represents one level/cohort within a session.
 *
 * A single session can contain multiple groups — e.g. Tackers 1 and Tackers 2
 * running concurrently. Each group has its own:
 *   - linked course (which provides the sailor list)
 *   - planned skills (set before)
 *   - actual skills (updated after — may differ completely if conditions changed)
 *   - attendance records (per-sailor, per-group)
 *   - ad-hoc sailors (added after the fact, outside normal enrolment)
 */
class WPNT_Session_Group {

	// -------------------------------------------------------------------------
	// CRUD
	// -------------------------------------------------------------------------

	public static function create( int $session_id, array $args ): int|false {
		global $wpdb;

		$data = array(
			'session_id'         => $session_id,
			'course_id'          => absint( $args['course_id'] ?? 0 ) ?: null,
			'curriculum_node_id' => absint( $args['curriculum_node_id'] ?? 0 ) ?: null,
			'label'              => sanitize_text_field( $args['label'] ?? '' ),
			'planned_skills'     => self::encode_ids( $args['planned_skills'] ?? array() ),
			'actual_skills'      => self::encode_ids( $args['actual_skills'] ?? array() ) ?: null,
			'adhoc_sailor_ids'   => self::encode_ids( $args['adhoc_sailor_ids'] ?? array() ) ?: null,
			'display_order'      => absint( $args['display_order'] ?? 0 ),
		);

		$result = $wpdb->insert( $wpdb->prefix . 'wpnt_session_groups', $data );
		return $result ? $wpdb->insert_id : false;
	}

	public static function update( int $group_id, array $args ): bool {
		global $wpdb;

		$data = array();
		if ( isset( $args['label'] ) ) {
			$data['label'] = sanitize_text_field( $args['label'] );
		}
		if ( isset( $args['course_id'] ) ) {
			$data['course_id'] = absint( $args['course_id'] ) ?: null;
		}
		if ( isset( $args['curriculum_node_id'] ) ) {
			$data['curriculum_node_id'] = absint( $args['curriculum_node_id'] ) ?: null;
		}
		if ( isset( $args['planned_skills'] ) ) {
			$data['planned_skills'] = self::encode_ids( $args['planned_skills'] );
		}
		if ( isset( $args['actual_skills'] ) ) {
			$data['actual_skills'] = self::encode_ids( $args['actual_skills'] );
		}
		if ( empty( $data ) ) {
			return false;
		}

		return (bool) $wpdb->update(
			$wpdb->prefix . 'wpnt_session_groups',
			$data,
			array( 'id' => $group_id )
		);
	}

	public static function delete( int $group_id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wpnt_attendance', array( 'session_group_id' => $group_id ) );
		return (bool) $wpdb->delete( $wpdb->prefix . 'wpnt_session_groups', array( 'id' => $group_id ) );
	}

	public static function get( int $group_id ): ?object {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_session_groups WHERE id = %d",
			$group_id
		) );
	}

	public static function get_for_session( int $session_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_session_groups
			 WHERE session_id = %d
			 ORDER BY display_order ASC, id ASC",
			$session_id
		) );
	}

	// -------------------------------------------------------------------------
	// Participants
	// -------------------------------------------------------------------------

	/**
	 * All participants for a group — enrolled via the linked course + any ad-hoc additions.
	 */
	public static function get_athletes( object $group ): array {
		$athletes = array();

		if ( $group->course_id ) {
			$athletes = WPNT_Course::get_enrolled_athletes( (int) $group->course_id );
		}

		if ( $group->adhoc_sailor_ids ) {
			$adhoc_ids = array_filter( array_map( 'absint', json_decode( $group->adhoc_sailor_ids, true ) ?? array() ) );
			if ( $adhoc_ids ) {
				$existing_ids = array_map( fn( $u ) => $u->ID, $athletes );
				$adhoc_users  = get_users( array( 'include' => $adhoc_ids, 'orderby' => 'display_name' ) );
				foreach ( $adhoc_users as $u ) {
					if ( ! in_array( $u->ID, $existing_ids, true ) ) {
						$athletes[] = $u;
					}
				}
			}
		}

		return $athletes;
	}

	/**
	 * Add an athlete to a group outside normal course enrolment.
	 * Also optionally enrols them in the linked course.
	 */
	public static function add_adhoc_athlete( int $group_id, int $athlete_id, bool $enroll_in_course = false ): bool {
		global $wpdb;

		$group = self::get( $group_id );
		if ( ! $group ) {
			return false;
		}

		$current_ids = $group->adhoc_sailor_ids
			? array_filter( array_map( 'absint', json_decode( $group->adhoc_sailor_ids, true ) ?? array() ) )
			: array();

		if ( in_array( $athlete_id, $current_ids, true ) ) {
			return true;
		}

		$current_ids[] = $athlete_id;

		$ok = (bool) $wpdb->update(
			$wpdb->prefix . 'wpnt_session_groups',
			array( 'adhoc_sailor_ids' => wp_json_encode( array_values( $current_ids ) ) ),
			array( 'id' => $group_id )
		);

		if ( $ok && $enroll_in_course && $group->course_id ) {
			WPNT_Course::enroll_athlete( (int) $group->course_id, $athlete_id );
		}

		return $ok;
	}

	/**
	 * Remove an ad-hoc athlete from a group (does not unenrol from course).
	 */
	public static function remove_adhoc_athlete( int $group_id, int $athlete_id ): bool {
		global $wpdb;

		$group = self::get( $group_id );
		if ( ! $group || ! $group->adhoc_sailor_ids ) {
			return false;
		}

		$ids = array_filter( array_map( 'absint', json_decode( $group->adhoc_sailor_ids, true ) ?? array() ) );
		$ids = array_values( array_diff( $ids, array( $athlete_id ) ) );

		return (bool) $wpdb->update(
			$wpdb->prefix . 'wpnt_session_groups',
			array( 'adhoc_sailor_ids' => wp_json_encode( $ids ) ),
			array( 'id' => $group_id )
		);
	}

	// -------------------------------------------------------------------------
	// Skills
	// -------------------------------------------------------------------------

	public static function get_planned_skills( object $group ): array {
		return self::decode_skill_posts( $group->planned_skills );
	}

	public static function get_actual_skills( object $group ): array {
		return self::decode_skill_posts( $group->actual_skills );
	}

	// -------------------------------------------------------------------------
	// Attendance helpers (group-scoped)
	// -------------------------------------------------------------------------

	public static function get_attendance( int $group_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE session_group_id = %d",
			$group_id
		) );

		$indexed = array();
		foreach ( $rows as $row ) {
			$indexed[ (int) $row->sailor_id ] = $row;
		}
		return $indexed;
	}

	public static function save_group_attendance( int $session_id, int $group_id, array $records ): array {
		$results = array();
		foreach ( $records as $record ) {
			// Accept both legacy 'sailor_id' key (from old JS) and new 'athlete_id'.
			$athlete_id = absint( $record['athlete_id'] ?? $record['sailor_id'] ?? 0 );
			$status     = sanitize_text_field( $record['status'] ?? '' );
			$notes      = sanitize_textarea_field( $record['notes'] ?? '' );
			if ( ! $athlete_id || ! $status ) {
				continue;
			}
			$results[ $athlete_id ] = self::upsert_group_attendance( $session_id, $group_id, $athlete_id, $status, $notes );
		}
		return $results;
	}

	private static function upsert_group_attendance( int $session_id, int $group_id, int $athlete_id, string $status, string $notes = '' ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_attendance';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE session_id = %d AND sailor_id = %d AND session_group_id = %d",
			$session_id, $athlete_id, $group_id
		) );

		$data = array(
			'status'           => $status,
			'notes'            => $notes,
			'recorded_by'      => get_current_user_id(),
			'session_group_id' => $group_id,
		);

		if ( $existing ) {
			return (bool) $wpdb->update( $table, $data, array( 'id' => (int) $existing ) );
		}

		$data['session_id'] = $session_id;
		$data['sailor_id']  = $athlete_id;
		return (bool) $wpdb->insert( $table, $data );
	}

	// -------------------------------------------------------------------------
	// Rendering helpers
	// -------------------------------------------------------------------------

	/**
	 * Render the combined skill + attendance block for a group.
	 * Used on both the Today admin screen and the front-end session template.
	 */
	public static function render_group_block( object $group, bool $editable = true ): string {
		$athletes        = self::get_athletes( $group );
		$planned_skills  = self::get_planned_skills( $group );
		$actual_skills   = self::get_actual_skills( $group );
		$attendance      = self::get_attendance( $group->id );
		$actual_ids      = array_map( fn( $s ) => $s->ID, $actual_skills );
		$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
		$participants_lbl = WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'wpnt' ) );

		$course_label = $group->label;
		if ( ! $course_label && $group->course_id ) {
			$course_label = get_the_title( $group->course_id );
		}

		ob_start();
		?>
		<div class="wpnt-group-block" data-group-id="<?php echo esc_attr( $group->id ); ?>">

			<div class="wpnt-group-header">
				<h3 class="wpnt-group-title"><?php echo esc_html( $course_label ?: __( 'Group', 'wpnt' ) ); ?></h3>
				<div class="wpnt-group-actions">
					<?php if ( $editable ) : ?>
						<button class="button wpnt-toggle-edit-plan"><?php esc_html_e( 'Edit Skills / Plan', 'wpnt' ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<!-- Skills summary -->
			<div class="wpnt-skills-summary">
				<div class="wpnt-skills-row">
					<span class="wpnt-skills-label"><?php esc_html_e( 'Planned:', 'wpnt' ); ?></span>
					<?php if ( $planned_skills ) : ?>
						<?php foreach ( $planned_skills as $skill ) :
							$covered = in_array( $skill->ID, $actual_ids, true );
						?>
							<span class="wpnt-skill-chip <?php echo $covered ? 'covered' : 'uncovered'; ?>">
								<?php echo $covered ? '✓' : '○'; ?>
								<?php echo esc_html( $skill->post_title ); ?>
							</span>
						<?php endforeach; ?>
					<?php else : ?>
						<span class="wpnt-skills-none"><?php esc_html_e( 'No skills set', 'wpnt' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<!-- Editable plan panel (hidden by default) -->
			<?php if ( $editable ) : ?>
			<div class="wpnt-edit-plan-panel" style="display:none">
				<div class="wpnt-edit-plan-inner">
					<div class="wpnt-plan-col">
						<label><?php esc_html_e( 'Planned Skills', 'wpnt' ); ?></label>
						<div class="wpnt-skill-picker" data-field="planned_skills">
							<?php
							$all_skills = get_posts( array( 'post_type' => 'wpnt_skill', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
							$planned_ids = array_map( fn( $s ) => $s->ID, $planned_skills );
							foreach ( $all_skills as $sk ) :
							?>
								<label class="wpnt-skill-check">
									<input type="checkbox" name="planned_skills[]" value="<?php echo esc_attr( $sk->ID ); ?>"
										<?php checked( in_array( $sk->ID, $planned_ids, true ) ); ?>>
									<?php echo esc_html( $sk->post_title ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="wpnt-plan-col">
						<label><?php esc_html_e( 'Actual Skills Covered', 'wpnt' ); ?></label>
						<div class="wpnt-skill-picker" data-field="actual_skills">
							<?php foreach ( $all_skills as $sk ) : ?>
								<label class="wpnt-skill-check">
									<input type="checkbox" name="actual_skills[]" value="<?php echo esc_attr( $sk->ID ); ?>"
										<?php checked( in_array( $sk->ID, $actual_ids, true ) ); ?>>
									<?php echo esc_html( $sk->post_title ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="wpnt-plan-col wpnt-plan-col-full">
						<button class="button button-primary wpnt-save-plan"
							data-group-id="<?php echo esc_attr( $group->id ); ?>">
							<?php esc_html_e( 'Save Plan', 'wpnt' ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Combined attendance + skill table -->
			<table class="wpnt-group-att-table widefat" data-session-id="<?php echo esc_attr( $group->session_id ); ?>" data-group-id="<?php echo esc_attr( $group->id ); ?>">
				<thead>
					<tr>
						<th><?php echo esc_html( $participant_lbl ); ?></th>
						<th><?php esc_html_e( 'Status', 'wpnt' ); ?></th>
						<?php foreach ( $planned_skills as $skill ) : ?>
							<th title="<?php echo esc_attr( $skill->post_title ); ?>" class="wpnt-skill-col">
								<?php echo esc_html( mb_strimwidth( $skill->post_title, 0, 14, '…' ) ); ?>
							</th>
						<?php endforeach; ?>
						<th><?php esc_html_e( 'Notes', 'wpnt' ); ?></th>
						<?php if ( $editable ) : ?><th></th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $athletes ) ) : ?>
						<tr><td colspan="<?php echo 3 + count( $planned_skills ) + ( $editable ? 1 : 0 ); ?>">
							<?php printf( esc_html__( 'No %s in this group yet.', 'wpnt' ), esc_html( strtolower( $participants_lbl ) ) ); ?>
						</td></tr>
					<?php else : ?>
						<?php foreach ( $athletes as $athlete ) :
							$att = $attendance[ $athlete->ID ] ?? null;
							$att_status = $att ? $att->status : '';
							$att_notes  = $att ? $att->notes : '';
							$is_adhoc   = ! $group->course_id || ! in_array( $athlete->ID, array_map( fn( $u ) => $u->ID, WPNT_Course::get_enrolled_athletes( (int) $group->course_id ) ), true );
						?>
							<tr class="wpnt-group-att-row" data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">
								<td>
									<?php echo esc_html( $athlete->display_name ); ?>
									<?php if ( $is_adhoc && $group->course_id ) : ?>
										<span class="wpnt-adhoc-tag"><?php esc_html_e( 'ad-hoc', 'wpnt' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $editable ) : ?>
										<select class="wpnt-att-status-select" data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">
											<option value=""><?php esc_html_e( '—', 'wpnt' ); ?></option>
											<?php foreach ( array( 'attended', 'absent', 'partial', 'excused', 'late', 'left_early' ) as $st ) : ?>
												<option value="<?php echo esc_attr( $st ); ?>"<?php selected( $att_status, $st ); ?>>
													<?php echo esc_html( ucfirst( str_replace( '_', ' ', $st ) ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php else : ?>
										<span class="wpnt-status-chip wpnt-att-<?php echo esc_attr( $att_status ); ?>">
											<?php echo esc_html( ucfirst( str_replace( '_', ' ', $att_status ) ) ); ?>
										</span>
									<?php endif; ?>
								</td>
								<?php foreach ( $planned_skills as $skill ) :
									$progress = WPNT_DB::get_progress_for_athlete_skill( $athlete->ID, $skill->ID );
									$checked  = $progress && in_array( $progress->status, array( 'practising', 'competent_with_help', 'competent_independently' ), true );
								?>
									<td class="wpnt-skill-cell">
										<?php if ( $editable && ( ! $att_status || $att_status === 'attended' || $att_status === 'partial' || $att_status === 'late' ) ) : ?>
											<input type="checkbox"
												class="wpnt-skill-check-input"
												data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>"
												data-skill-id="<?php echo esc_attr( $skill->ID ); ?>"
												<?php checked( $checked ); ?>
												title="<?php echo esc_attr( $skill->post_title ); ?>">
										<?php elseif ( $att_status === 'absent' || $att_status === 'excused' ) : ?>
											<span class="wpnt-skill-na">—</span>
										<?php else : ?>
											<span class="wpnt-skill-indicator <?php echo $checked ? 'done' : 'not-done'; ?>">
												<?php echo $checked ? '✓' : '○'; ?>
											</span>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
								<td>
									<?php if ( $editable ) : ?>
										<input type="text" class="wpnt-att-notes-input" value="<?php echo esc_attr( $att_notes ); ?>" placeholder="…">
									<?php else : ?>
										<?php echo esc_html( $att_notes ); ?>
									<?php endif; ?>
								</td>
								<?php if ( $editable && $is_adhoc && $group->course_id ) : ?>
									<td>
										<button class="button button-small wpnt-remove-adhoc"
											data-group-id="<?php echo esc_attr( $group->id ); ?>"
											data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">✕</button>
									</td>
								<?php elseif ( $editable ) : ?>
									<td></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $editable ) : ?>
				<div class="wpnt-group-footer">
					<!-- Add athlete -->
					<div class="wpnt-add-athlete-row">
						<input type="text" class="wpnt-athlete-search" placeholder="<?php echo esc_attr( sprintf( __( 'Search %s to add…', 'wpnt' ), strtolower( $participant_lbl ) ) ); ?>">
						<select class="wpnt-athlete-select" style="display:none">
							<option value=""><?php esc_html_e( '— Select —', 'wpnt' ); ?></option>
							<?php
							$all_users = get_users( array( 'role' => 'wpnt_athlete', 'orderby' => 'display_name', 'number' => 200 ) );
							foreach ( $all_users as $u ) :
							?>
								<option value="<?php echo esc_attr( $u->ID ); ?>"><?php echo esc_html( $u->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
						<button class="button wpnt-add-athlete-btn"
							data-group-id="<?php echo esc_attr( $group->id ); ?>">
							<?php printf( esc_html__( '+ Add %s', 'wpnt' ), esc_html( $participant_lbl ) ); ?>
						</button>
						<label class="wpnt-enroll-check">
							<input type="checkbox" class="wpnt-enroll-in-course" <?php echo $group->course_id ? '' : 'disabled'; ?>>
							<?php esc_html_e( 'Also enrol in course', 'wpnt' ); ?>
						</label>
					</div>

					<!-- Save attendance -->
					<button class="button button-primary wpnt-save-group-att"
						data-session-id="<?php echo esc_attr( $group->session_id ); ?>"
						data-group-id="<?php echo esc_attr( $group->id ); ?>">
						<?php esc_html_e( 'Save Attendance', 'wpnt' ); ?>
					</button>
				</div>
			<?php endif; ?>

		</div><!-- .wpnt-group-block -->
		<?php
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private static function encode_ids( mixed $ids ): string {
		if ( ! $ids ) {
			return '[]';
		}
		return wp_json_encode( array_values( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	}

	private static function decode_skill_posts( ?string $json ): array {
		if ( ! $json ) {
			return array();
		}
		$ids = array_filter( array_map( 'absint', json_decode( $json, true ) ?? array() ) );
		if ( ! $ids ) {
			return array();
		}
		return get_posts( array(
			'post_type'      => 'wpnt_skill',
			'include'        => $ids,
			'posts_per_page' => -1,
			'orderby'        => 'post__in',
		) );
	}
}

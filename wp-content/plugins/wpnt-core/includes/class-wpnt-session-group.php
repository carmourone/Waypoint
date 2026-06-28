<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A session group is one BP cohort's participation in a session.
 *
 * Stored as a g2p edge: group_id = BP group ID, post_id = session ID.
 * Edge data holds: label, planned_skills, actual_skills, adhoc_athlete_ids, display_order.
 *
 * Group-scoped attendance is stored in wpnt_u2p with context_id = bp_group_id.
 * Athletes come from BP group membership plus any adhoc_athlete_ids in the edge data.
 */
class WPNT_Session_Group {

	// -------------------------------------------------------------------------
	// CRUD — identity: (bp_group_id, session_id)
	// -------------------------------------------------------------------------

	/**
	 * Create or update a session group edge.
	 */
	public static function upsert( int $bp_group_id, int $session_id, array $args ): bool {
		$current      = self::get( $bp_group_id, $session_id );
		$current_data = $current ? WPNT_Graph::decode_data( $current->data ) : array();

		$data = array(
			'label'             => sanitize_text_field( $args['label'] ?? $current_data['label'] ?? '' ),
			'planned_skills'    => array_values( array_filter( array_map( 'absint', (array) ( $args['planned_skills'] ?? $current_data['planned_skills'] ?? array() ) ) ) ),
			'actual_skills'     => array_values( array_filter( array_map( 'absint', (array) ( $args['actual_skills'] ?? $current_data['actual_skills'] ?? array() ) ) ) ),
			'adhoc_athlete_ids' => array_values( array_filter( array_map( 'absint', (array) ( $args['adhoc_athlete_ids'] ?? $current_data['adhoc_athlete_ids'] ?? array() ) ) ) ),
			'display_order'     => (int) ( $args['display_order'] ?? $current_data['display_order'] ?? 0 ),
		);

		return WPNT_Graph::upsert_g2p( 'session_group', $bp_group_id, $session_id, $data );
	}

	/**
	 * Delete a session group edge and its group-scoped attendance records.
	 */
	public static function delete( int $bp_group_id, int $session_id ): bool {
		global $wpdb;
		$att_type_id = WPNT_Graph::get_type_id( 'attended' );
		if ( $att_type_id ) {
			$wpdb->delete( $wpdb->prefix . 'wpnt_u2p', array(
				'type_id'    => $att_type_id,
				'post_id'    => $session_id,
				'context_id' => $bp_group_id,
			) );
		}
		return WPNT_Graph::delete_g2p( 'session_group', $bp_group_id, $session_id );
	}

	/**
	 * Fetch a single session group edge.
	 */
	public static function get( int $bp_group_id, int $session_id ): ?object {
		global $wpdb;
		$type_id = WPNT_Graph::get_type_id( 'session_group' );
		if ( ! $type_id ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_g2p WHERE type_id = %d AND group_id = %d AND post_id = %d",
			$type_id,
			$bp_group_id,
			$session_id
		) );
	}

	/**
	 * Return all session group edges for a session, sorted by display_order.
	 */
	public static function get_for_session( int $session_id ): array {
		$edges = WPNT_Graph::get_g2p( 'session_group', array( 'post_id' => $session_id ) );

		usort( $edges, function ( $a, $b ) {
			$da = WPNT_Graph::decode_data( $a->data );
			$db = WPNT_Graph::decode_data( $b->data );
			return (int) ( $da['display_order'] ?? 0 ) <=> (int) ( $db['display_order'] ?? 0 );
		} );

		return $edges;
	}

	// -------------------------------------------------------------------------
	// Participants
	// -------------------------------------------------------------------------

	/**
	 * All athletes for a group — BP group members + any ad-hoc additions.
	 *
	 * @param object $edge  A g2p edge row (group_id = bp_group_id, post_id = session_id).
	 */
	public static function get_athletes( object $edge ): array {
		$athletes = array();
		$data     = WPNT_Graph::decode_data( $edge->data );

		if ( function_exists( 'groups_get_group_members' ) ) {
			$result = groups_get_group_members( array(
				'group_id'            => (int) $edge->group_id,
				'per_page'            => 200,
				'exclude_admins_mods' => false,
			) );
			if ( ! empty( $result['members'] ) ) {
				foreach ( $result['members'] as $member ) {
					$user = get_userdata( $member->user_id );
					if ( $user ) {
						$athletes[] = $user;
					}
				}
			}
		}

		$adhoc_ids = array_values( array_filter( array_map( 'absint', $data['adhoc_athlete_ids'] ?? array() ) ) );
		if ( $adhoc_ids ) {
			$existing_ids = array_map( fn( $u ) => $u->ID, $athletes );
			$adhoc_users  = get_users( array( 'include' => $adhoc_ids, 'orderby' => 'display_name' ) );
			foreach ( $adhoc_users as $u ) {
				if ( ! in_array( $u->ID, $existing_ids, true ) ) {
					$athletes[] = $u;
				}
			}
		}

		return array_values( $athletes );
	}

	/**
	 * Add an athlete ad-hoc to a group outside BP group membership.
	 */
	public static function add_adhoc_athlete( int $bp_group_id, int $session_id, int $athlete_id, bool $enroll_in_course = false ): bool {
		$edge = self::get( $bp_group_id, $session_id );
		if ( ! $edge ) {
			return false;
		}

		$data = WPNT_Graph::decode_data( $edge->data );
		$ids  = array_values( array_filter( array_map( 'absint', $data['adhoc_athlete_ids'] ?? array() ) ) );

		if ( in_array( $athlete_id, $ids, true ) ) {
			return true;
		}

		$ids[]                     = $athlete_id;
		$data['adhoc_athlete_ids'] = $ids;

		$ok = WPNT_Graph::upsert_g2p( 'session_group', $bp_group_id, $session_id, $data );

		if ( $ok && $enroll_in_course ) {
			$course_id = (int) get_post_meta( $session_id, '_wpnt_course_id', true );
			if ( $course_id ) {
				WPNT_Course::enroll_athlete( $course_id, $athlete_id );
			}
		}

		return $ok;
	}

	/**
	 * Remove an ad-hoc athlete from a group (does not remove BP group membership).
	 */
	public static function remove_adhoc_athlete( int $bp_group_id, int $session_id, int $athlete_id ): bool {
		$edge = self::get( $bp_group_id, $session_id );
		if ( ! $edge ) {
			return false;
		}

		$data = WPNT_Graph::decode_data( $edge->data );
		$ids  = array_values( array_filter( array_map( 'absint', $data['adhoc_athlete_ids'] ?? array() ) ) );
		$ids  = array_values( array_diff( $ids, array( $athlete_id ) ) );

		$data['adhoc_athlete_ids'] = $ids;
		return WPNT_Graph::upsert_g2p( 'session_group', $bp_group_id, $session_id, $data );
	}

	// -------------------------------------------------------------------------
	// Skills
	// -------------------------------------------------------------------------

	public static function get_planned_skills( object $edge ): array {
		$data = WPNT_Graph::decode_data( $edge->data );
		return self::fetch_skill_posts( $data['planned_skills'] ?? array() );
	}

	public static function get_actual_skills( object $edge ): array {
		$data = WPNT_Graph::decode_data( $edge->data );
		return self::fetch_skill_posts( $data['actual_skills'] ?? array() );
	}

	// -------------------------------------------------------------------------
	// Attendance helpers — context_id = bp_group_id
	// -------------------------------------------------------------------------

	public static function get_attendance( int $bp_group_id, int $session_id ): array {
		return WPNT_Attendance::get_session_attendance( $session_id, $bp_group_id );
	}

	public static function save_group_attendance( int $bp_group_id, int $session_id, array $records ): array {
		return WPNT_Attendance::bulk_mark( $session_id, $records, $bp_group_id );
	}

	// -------------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------------

	/**
	 * Render the combined skill + attendance block for a group.
	 *
	 * @param object $edge     A g2p edge row (group_id = bp_group_id, post_id = session_id).
	 * @param bool   $editable Whether to render edit controls.
	 */
	public static function render_group_block( object $edge, bool $editable = true ): string {
		$bp_group_id     = (int) $edge->group_id;
		$session_id      = (int) $edge->post_id;
		$data            = WPNT_Graph::decode_data( $edge->data );
		$athletes        = self::get_athletes( $edge );
		$planned_skills  = self::get_planned_skills( $edge );
		$actual_skills   = self::get_actual_skills( $edge );
		$attendance      = self::get_attendance( $bp_group_id, $session_id );
		$actual_ids      = array_map( fn( $s ) => $s->ID, $actual_skills );
		$participant_lbl  = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
		$participants_lbl = WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'wpnt' ) );

		$group_label = $data['label'] ?? '';
		if ( ! $group_label && function_exists( 'groups_get_group' ) ) {
			$bp_group    = groups_get_group( $bp_group_id );
			$group_label = $bp_group ? $bp_group->name : '';
		}

		ob_start();
		?>
		<div class="wpnt-group-block" data-session-id="<?php echo esc_attr( $session_id ); ?>" data-bp-group-id="<?php echo esc_attr( $bp_group_id ); ?>">

			<div class="wpnt-group-header">
				<h3 class="wpnt-group-title"><?php echo esc_html( $group_label ?: __( 'Group', 'wpnt' ) ); ?></h3>
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
							$all_skills  = get_posts( array( 'post_type' => 'wpnt_skill', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
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
							data-session-id="<?php echo esc_attr( $session_id ); ?>"
							data-bp-group-id="<?php echo esc_attr( $bp_group_id ); ?>">
							<?php esc_html_e( 'Save Plan', 'wpnt' ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Combined attendance + skill table -->
			<table class="wpnt-group-att-table widefat">
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
							$att        = $attendance[ $athlete->ID ] ?? null;
							$att_status = $att ? $att->status : '';
							$att_notes  = $att ? $att->notes : '';
							$adhoc_ids  = array_map( 'absint', $data['adhoc_athlete_ids'] ?? array() );
							$is_adhoc   = in_array( $athlete->ID, $adhoc_ids, true );
						?>
							<tr class="wpnt-group-att-row" data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">
								<td>
									<?php echo esc_html( $athlete->display_name ); ?>
									<?php if ( $is_adhoc ) : ?>
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
									$progress_row  = WPNT_Graph::get_u2p_row( 'assessed', $athlete->ID, $skill->ID );
									$progress_data = $progress_row ? WPNT_Graph::decode_data( $progress_row->data ) : array();
									$checked       = ! empty( $progress_data['status'] ) && in_array( $progress_data['status'], array( 'practising', 'competent_with_help', 'competent_independently' ), true );
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
								<?php if ( $editable && $is_adhoc ) : ?>
									<td>
										<button class="button button-small wpnt-remove-adhoc"
											data-session-id="<?php echo esc_attr( $session_id ); ?>"
											data-bp-group-id="<?php echo esc_attr( $bp_group_id ); ?>"
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
							data-session-id="<?php echo esc_attr( $session_id ); ?>"
							data-bp-group-id="<?php echo esc_attr( $bp_group_id ); ?>">
							<?php printf( esc_html__( '+ Add %s', 'wpnt' ), esc_html( $participant_lbl ) ); ?>
						</button>
						<label class="wpnt-enroll-check">
							<input type="checkbox" class="wpnt-enroll-in-course">
							<?php esc_html_e( 'Also enrol in course', 'wpnt' ); ?>
						</label>
					</div>

					<!-- Save attendance -->
					<button class="button button-primary wpnt-save-group-att"
						data-session-id="<?php echo esc_attr( $session_id ); ?>"
						data-bp-group-id="<?php echo esc_attr( $bp_group_id ); ?>">
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

	private static function fetch_skill_posts( array $ids ): array {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
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

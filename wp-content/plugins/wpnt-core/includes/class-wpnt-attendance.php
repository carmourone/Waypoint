<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Attendance {

	/**
	 * Mark attendance for a single athlete on a session.
	 *
	 * @param int $context_id Optional session_groups.id for group-scoped records. Zero = ungrouped.
	 */
	public static function mark( int $session_id, int $athlete_id, string $status, string $notes = '', int $context_id = 0 ): bool {
		$allowed = array( 'attended', 'absent', 'partial', 'excused', 'late', 'left_early' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		return WPNT_Graph::upsert_u2p( 'attended', $athlete_id, $session_id, array(
			'status'      => $status,
			'notes'       => sanitize_textarea_field( $notes ),
			'recorded_by' => get_current_user_id(),
		), $context_id );
	}

	public static function bulk_mark( int $session_id, array $records, int $context_id = 0 ): array {
		$results = array();
		foreach ( $records as $record ) {
			$athlete_id = absint( $record['athlete_id'] ?? 0 );
			$status     = sanitize_text_field( $record['status'] ?? '' );
			$notes      = sanitize_textarea_field( $record['notes'] ?? '' );
			if ( ! $athlete_id || ! $status ) {
				continue;
			}
			$results[ $athlete_id ] = self::mark( $session_id, $athlete_id, $status, $notes, $context_id );
		}
		return $results;
	}

	/**
	 * Return attendance for a session, keyed by athlete (user) ID.
	 * Each row has synthetic ->status and ->notes properties decoded from the JSON data column.
	 */
	public static function get_session_attendance( int $session_id, int $context_id = 0 ): array {
		$args = array( 'post_id' => $session_id );
		if ( $context_id ) {
			$args['context_id'] = $context_id;
		}

		$rows    = WPNT_Graph::get_u2p( 'attended', $args );
		$indexed = array();
		foreach ( $rows as $row ) {
			$data         = WPNT_Graph::decode_data( $row->data );
			$row->status  = $data['status'] ?? '';
			$row->notes   = $data['notes']  ?? '';
			$indexed[ (int) $row->user_id ] = $row;
		}
		return $indexed;
	}

	/**
	 * Return all attendance edges for an athlete, optionally filtered to sessions in a course.
	 */
	public static function get_athlete_attendance( int $athlete_id, int $course_id = 0 ): array {
		if ( $course_id ) {
			$session_ids = get_posts( array(
				'post_type'      => 'wpnt_session',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_wpnt_course_id',
				'meta_value'     => $course_id,
			) );
			if ( empty( $session_ids ) ) {
				return array();
			}

			global $wpdb;
			$type_id = WPNT_Graph::get_type_id( 'attended' );
			if ( ! $type_id ) {
				return array();
			}
			$placeholders = implode( ',', array_fill( 0, count( $session_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpnt_u2p WHERE type_id = %d AND user_id = %d AND post_id IN ($placeholders)",
				array_merge( array( $type_id, $athlete_id ), $session_ids )
			) );
		}

		return WPNT_Graph::get_u2p( 'attended', array( 'user_id' => $athlete_id ) );
	}

	/**
	 * Render an attendance checklist for a session.
	 * Returns HTML for use in admin views or theme templates.
	 */
	public static function render_checklist( int $session_id ): string {
		$course_id = (int) get_post_meta( $session_id, '_wpnt_course_id', true );
		if ( ! $course_id ) {
			return '<p>' . esc_html__( 'No course linked to this session.', 'wpnt' ) . '</p>';
		}

		$athletes         = self::get_course_athletes( $course_id );
		$participants_lbl = WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'wpnt' ) );

		if ( empty( $athletes ) ) {
			return '<p>' . esc_html( sprintf( __( 'No %s enrolled in this course.', 'wpnt' ), strtolower( $participants_lbl ) ) ) . '</p>';
		}

		$attendance      = self::get_session_attendance( $session_id );
		$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );

		$statuses = array(
			'attended'   => __( 'Attended', 'wpnt' ),
			'absent'     => __( 'Absent', 'wpnt' ),
			'partial'    => __( 'Partial', 'wpnt' ),
			'excused'    => __( 'Excused', 'wpnt' ),
			'late'       => __( 'Late', 'wpnt' ),
			'left_early' => __( 'Left Early', 'wpnt' ),
		);

		ob_start();
		?>
		<table class="wpnt-attendance-table widefat" data-session-id="<?php echo esc_attr( $session_id ); ?>">
			<thead>
				<tr>
					<th><?php echo esc_html( $participant_lbl ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'wpnt' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $athletes as $athlete ) :
				$att    = $attendance[ $athlete->ID ] ?? null;
				$status = $att ? $att->status : '';
				$notes  = $att ? $att->notes  : '';
			?>
				<tr class="wpnt-att-row" data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">
					<td><?php echo esc_html( $athlete->display_name ); ?></td>
					<td>
						<select class="wpnt-att-status" data-athlete-id="<?php echo esc_attr( $athlete->ID ); ?>">
							<option value=""><?php esc_html_e( '— Mark —', 'wpnt' ); ?></option>
							<?php foreach ( $statuses as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $status, $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<input type="text" class="wpnt-att-notes"
							placeholder="<?php esc_attr_e( 'Optional note…', 'wpnt' ); ?>"
							value="<?php echo esc_attr( $notes ); ?>">
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<button class="button button-primary wpnt-save-attendance" data-session-id="<?php echo esc_attr( $session_id ); ?>">
			<?php esc_html_e( 'Save Attendance', 'wpnt' ); ?>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resolve the athlete list for a course — prefers BP group membership when available.
	 */
	private static function get_course_athletes( int $course_id ): array {
		$group_id = (int) get_post_meta( $course_id, '_wpnt_bp_group_id', true );
		if ( $group_id && function_exists( 'groups_get_group_members' ) ) {
			$result = groups_get_group_members( array( 'group_id' => $group_id, 'per_page' => -1 ) );
			if ( ! empty( $result['members'] ) ) {
				return $result['members'];
			}
		}
		return WPNT_Course::get_enrolled_athletes( $course_id );
	}
}

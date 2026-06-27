<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Attendance {

	public static function mark( int $session_id, int $sailor_id, string $status, string $notes = '' ): bool {
		$allowed = array( 'attended', 'absent', 'partial', 'excused', 'late', 'left_early' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		return WPNT_DB::upsert_attendance( $session_id, $sailor_id, $status, $notes );
	}

	public static function bulk_mark( int $session_id, array $records ): array {
		$results = array();
		foreach ( $records as $record ) {
			$sailor_id = absint( $record['sailor_id'] ?? 0 );
			$status    = sanitize_text_field( $record['status'] ?? '' );
			$notes     = sanitize_textarea_field( $record['notes'] ?? '' );
			if ( ! $sailor_id || ! $status ) {
				continue;
			}
			$results[ $sailor_id ] = self::mark( $session_id, $sailor_id, $status, $notes );
		}
		return $results;
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

		$sailors    = WPNT_Course::get_enrolled_sailors( $course_id );
		$attendance = WPNT_DB::get_session_attendance( $session_id );
		$att_by_sailor = array();
		foreach ( $attendance as $row ) {
			$att_by_sailor[ (int) $row->sailor_id ] = $row;
		}

		if ( empty( $sailors ) ) {
			return '<p>' . esc_html__( 'No sailors enrolled in this course.', 'wpnt' ) . '</p>';
		}

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
					<th><?php esc_html_e( 'Sailor', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'wpnt' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $sailors as $sailor ) :
				$att    = $att_by_sailor[ $sailor->ID ] ?? null;
				$status = $att ? $att->status : '';
				$notes  = $att ? $att->notes : '';
			?>
				<tr class="wpnt-att-row" data-sailor-id="<?php echo esc_attr( $sailor->ID ); ?>">
					<td><?php echo esc_html( $sailor->display_name ); ?></td>
					<td>
						<select class="wpnt-att-status" data-sailor-id="<?php echo esc_attr( $sailor->ID ); ?>">
							<option value=""><?php esc_html_e( '— Mark —', 'wpnt' ); ?></option>
							<?php foreach ( $statuses as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $status, $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<input type="text" class="wpnt-att-notes" placeholder="<?php esc_attr_e( 'Optional note…', 'wpnt' ); ?>" value="<?php echo esc_attr( $notes ); ?>">
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
}

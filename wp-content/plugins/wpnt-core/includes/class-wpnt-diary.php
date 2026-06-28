<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Diary {

	const EVENT_TYPES = array( 'training', 'competition', 'event' );

	const SCALE_TYPES = array(
		'agreement_5' => array(
			'strongly_disagree' => 'Strongly disagree',
			'somewhat_disagree' => 'Somewhat disagree',
			'neutral'           => 'Neither agree nor disagree',
			'somewhat_agree'    => 'Somewhat agree',
			'strongly_agree'    => 'Strongly agree',
		),
		'quality_5' => array(
			'very_poor' => 'Very poor',
			'poor'      => 'Poor',
			'okay'      => 'Okay',
			'good'      => 'Good',
			'excellent' => 'Excellent',
		),
		'intensity_5' => array(
			'very_low'  => 'Very low',
			'low'       => 'Low',
			'moderate'  => 'Moderate',
			'high'      => 'High',
			'very_high' => 'Very high',
		),
	);

	// -------------------------------------------------------------------------
	// Responses
	// -------------------------------------------------------------------------

	public static function save_responses( int $entry_id, array $responses ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_diary_responses';

		foreach ( $responses as $response ) {
			$question_id   = sanitize_key( $response['question_id'] ?? '' );
			$question_type = sanitize_key( $response['question_type'] ?? 'short_text' );
			if ( ! $question_id ) {
				continue;
			}

			$value = null;
			$text  = null;

			if ( in_array( $question_type, array( 'likert', 'single_choice', 'number' ), true ) ) {
				$value = sanitize_text_field( (string) ( $response['response_value'] ?? '' ) );
			} else {
				$raw  = $response['response_text'] ?? $response['response_value'] ?? '';
				$text = is_array( $raw ) ? wp_json_encode( $raw ) : sanitize_textarea_field( (string) $raw );
			}

			$visibility = in_array( $response['visibility'] ?? 'shared', array( 'shared', 'private' ), true )
				? $response['visibility'] : 'shared';

			$row = array(
				'entry_id'       => $entry_id,
				'question_id'    => $question_id,
				'question_type'  => $question_type,
				'scale_type'     => sanitize_key( $response['scale_type'] ?? '' ) ?: null,
				'response_value' => $value,
				'response_text'  => $text,
				'visibility'     => $visibility,
			);

			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE entry_id = %d AND question_id = %s",
				$entry_id, $question_id
			) );

			if ( $existing ) {
				$wpdb->update( $table, $row, array( 'id' => (int) $existing ) );
			} else {
				$wpdb->insert( $table, $row );
			}
		}
		return true;
	}

	public static function get_responses( int $entry_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_diary_responses WHERE entry_id = %d ORDER BY id ASC",
			$entry_id
		) );
	}

	// -------------------------------------------------------------------------
	// Entry lifecycle
	// -------------------------------------------------------------------------

	public static function submit( int $entry_id ): bool {
		$post = get_post( $entry_id );
		if ( ! $post || $post->post_type !== 'wpnt_diary_entry' ) {
			return false;
		}
		$current = get_post_meta( $entry_id, '_wpnt_status', true );
		if ( $current !== '' && $current !== 'draft' ) {
			return false;
		}
		update_post_meta( $entry_id, '_wpnt_status', 'submitted' );
		return true;
	}

	/**
	 * Save a coach response. Always parent-visible when published — child safety non-negotiable.
	 *
	 * @param string $publish 'yes' to publish immediately, 'no' to save as draft.
	 */
	public static function coach_review( int $entry_id, string $response_text, string $publish = 'no' ): bool {
		$post = get_post( $entry_id );
		if ( ! $post || $post->post_type !== 'wpnt_diary_entry' ) {
			return false;
		}
		update_post_meta( $entry_id, '_wpnt_coach_response', sanitize_textarea_field( $response_text ) );
		update_post_meta( $entry_id, '_wpnt_coach_id', get_current_user_id() );
		update_post_meta( $entry_id, '_wpnt_status', 'reviewed' );
		update_post_meta( $entry_id, '_wpnt_coach_response_status', $publish === 'yes' ? 'published' : 'draft' );
		return true;
	}

	/**
	 * Publish a saved coach response. Once published it is visible to athlete and parent.
	 */
	public static function publish_coach_response( int $entry_id ): bool {
		$post = get_post( $entry_id );
		if ( ! $post || $post->post_type !== 'wpnt_diary_entry' ) {
			return false;
		}
		update_post_meta( $entry_id, '_wpnt_coach_response_status', 'published' );
		return true;
	}

	// -------------------------------------------------------------------------
	// Queries
	// -------------------------------------------------------------------------

	public static function get_for_athlete( int $athlete_id, array $args = array() ): array {
		$meta_query = array(
			array( 'key' => '_wpnt_athlete_id', 'value' => $athlete_id ),
		);
		if ( ! empty( $args['status'] ) ) {
			$meta_query[] = array( 'key' => '_wpnt_status', 'value' => $args['status'] );
		}
		if ( ! empty( $args['session_id'] ) ) {
			$meta_query[] = array( 'key' => '_wpnt_session_id', 'value' => absint( $args['session_id'] ) );
		}
		return get_posts( array(
			'post_type'      => 'wpnt_diary_entry',
			'posts_per_page' => $args['per_page'] ?? 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => $meta_query,
		) );
	}

	/**
	 * Return submitted entries pending coach review.
	 * In v0 all coaches can see all submitted entries — scoping by coach assignment is post-v0.
	 */
	public static function get_pending_review(): array {
		return get_posts( array(
			'post_type'      => 'wpnt_diary_entry',
			'posts_per_page' => 30,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array( 'key' => '_wpnt_status', 'value' => 'submitted' ),
			),
		) );
	}

	public static function can_view( int $entry_id, int $viewer_id ): bool {
		$athlete_id = (int) get_post_meta( $entry_id, '_wpnt_athlete_id', true );
		if ( ! $athlete_id ) {
			return false;
		}
		return WPNT_Graph::can_view_athlete_data( $viewer_id, $athlete_id );
	}

	// -------------------------------------------------------------------------
	// Templates
	// -------------------------------------------------------------------------

	public static function get_templates(): array {
		return get_posts( array(
			'post_type'      => 'wpnt_diary_template',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		) );
	}

	public static function get_template_questions( int $template_id ): array {
		$json      = get_post_meta( $template_id, '_wpnt_questions', true );
		$questions = $json ? json_decode( $json, true ) : null;
		return is_array( $questions ) ? $questions : array();
	}

	public static function get_scale_labels( string $scale_type ): array {
		return self::SCALE_TYPES[ $scale_type ] ?? array();
	}
}

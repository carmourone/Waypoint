<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds the sailing curriculum from data/curriculum.json on first activation.
 *
 * Idempotent: checks for an existing curriculum post with the same slug
 * before creating anything. Re-running after a manual deletion will re-seed.
 */
class WPNT_Sailing_Importer {

	private static string $data_file = WPNT_SAILING_DIR . 'data/curriculum.json';

	public static function maybe_seed(): void {
		if ( ! file_exists( self::$data_file ) ) {
			return;
		}

		$json = file_get_contents( self::$data_file );
		$data = json_decode( $json, true );

		if ( empty( $data['curricula'] ) || ! is_array( $data['curricula'] ) ) {
			return;
		}

		foreach ( $data['curricula'] as $curriculum ) {
			self::import_curriculum( $curriculum );
		}
	}

	private static function import_curriculum( array $curriculum ): void {
		$slug = sanitize_title( $curriculum['slug'] ?? $curriculum['title'] ?? '' );
		if ( ! $slug ) {
			return;
		}

		// Skip if a curriculum with this slug already exists.
		$existing = get_page_by_path( $slug, OBJECT, 'wpnt_curriculum' );
		if ( $existing ) {
			return;
		}

		$curriculum_id = wp_insert_post( array(
			'post_type'   => 'wpnt_curriculum',
			'post_title'  => sanitize_text_field( $curriculum['title'] ),
			'post_name'   => $slug,
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $curriculum_id ) || ! $curriculum_id ) {
			return;
		}

		if ( ! empty( $curriculum['org'] ) ) {
			update_post_meta( $curriculum_id, '_wpnt_org', sanitize_text_field( $curriculum['org'] ) );
		}
		if ( ! empty( $curriculum['version'] ) ) {
			update_post_meta( $curriculum_id, '_wpnt_version', sanitize_text_field( $curriculum['version'] ) );
		}

		foreach ( $curriculum['nodes'] ?? array() as $order => $node ) {
			self::import_node( $node, $curriculum_id, 0, $order + 1 );
		}
	}

	private static function import_node( array $node, int $curriculum_id, int $parent_id, int $order ): void {
		$slug = sanitize_title( $node['slug'] ?? $node['title'] ?? '' );

		$node_id = wp_insert_post( array(
			'post_type'   => 'wpnt_node',
			'post_title'  => sanitize_text_field( $node['title'] ),
			'post_name'   => $slug,
			'post_status' => 'publish',
			'post_parent' => $parent_id,
		) );

		if ( is_wp_error( $node_id ) || ! $node_id ) {
			return;
		}

		update_post_meta( $node_id, '_wpnt_curriculum_id', $curriculum_id );
		update_post_meta( $node_id, '_wpnt_node_type', sanitize_key( $node['type'] ?? 'module' ) );
		update_post_meta( $node_id, '_wpnt_order', $order );

		foreach ( $node['skills'] ?? array() as $skill_data ) {
			self::import_skill( $skill_data, $node_id );
		}

		foreach ( $node['nodes'] ?? array() as $child_order => $child_node ) {
			self::import_node( $child_node, $curriculum_id, $node_id, $child_order + 1 );
		}
	}

	private static function import_skill( array $skill_data, int $node_id ): void {
		$skill_id = wp_insert_post( array(
			'post_type'    => 'wpnt_skill',
			'post_title'   => sanitize_text_field( $skill_data['title'] ),
			'post_status'  => 'publish',
			'post_content' => sanitize_textarea_field( $skill_data['criteria'] ?? '' ),
		) );

		if ( is_wp_error( $skill_id ) || ! $skill_id ) {
			return;
		}

		update_post_meta( $skill_id, '_wpnt_node_id', $node_id );

		if ( ! empty( $skill_data['criteria'] ) ) {
			update_post_meta( $skill_id, '_wpnt_assessment_criteria', sanitize_textarea_field( $skill_data['criteria'] ) );
		}
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Roles {

	// Capability sets per CPT action.
	private static array $cpt_caps = array(
		'wpnt_curriculum', 'wpnt_node', 'wpnt_skill', 'wpnt_drill',
		'wpnt_session_tmpl', 'wpnt_course', 'wpnt_session', 'wpnt_training_plan',
		'wpnt_observation',
	);

	public static function add_roles(): void {
		self::add_org_admin();
		self::add_coach();
		self::add_assistant_coach();
		self::add_parent();
		self::add_athlete();
		self::extend_administrator();
	}

	private static function add_org_admin(): void {
		remove_role( 'wpnt_org_admin' );
		add_role( 'wpnt_org_admin', __( 'Org Admin', 'wpnt' ), self::build_caps( array(
			'manage' => self::$cpt_caps,
			'view'   => self::$cpt_caps,
		) ) );
	}

	private static function add_coach(): void {
		remove_role( 'wpnt_coach' );
		add_role( 'wpnt_coach', __( 'Coach', 'wpnt' ), self::build_caps( array(
			'manage' => array( 'wpnt_session', 'wpnt_training_plan' ),
			'edit'   => array( 'wpnt_course' ),
			'view'   => self::$cpt_caps,
		) ) );
	}

	private static function add_assistant_coach(): void {
		remove_role( 'wpnt_asst_coach' );
		add_role( 'wpnt_asst_coach', __( 'Assistant Coach', 'wpnt' ), self::build_caps( array(
			'edit'   => array( 'wpnt_session' ),
			'view'   => array( 'wpnt_course', 'wpnt_session', 'wpnt_skill', 'wpnt_drill' ),
		) ) );
	}

	private static function add_parent(): void {
		remove_role( 'wpnt_parent' );
		add_role( 'wpnt_parent', __( 'Parent', 'wpnt' ), array(
			'read'                 => true,
			'wpnt_view_child_data' => true,
		) );
	}

	private static function add_athlete(): void {
		remove_role( 'wpnt_athlete' );
		add_role( 'wpnt_athlete', __( 'Athlete', 'wpnt' ), array(
			'read'               => true,
			'wpnt_view_own_data' => true,
		) );
	}

	private static function extend_administrator(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::$cpt_caps as $type ) {
			foreach ( self::cap_verbs() as $verb ) {
				$admin->add_cap( "{$verb}_{$type}s" );
				$admin->add_cap( "{$verb}_{$type}" );
				$admin->add_cap( "{$verb}_others_{$type}s" );
				$admin->add_cap( "delete_{$type}s" );
				$admin->add_cap( "delete_{$type}" );
				$admin->add_cap( "delete_others_{$type}s" );
				$admin->add_cap( "publish_{$type}s" );
				$admin->add_cap( "read_private_{$type}s" );
			}
		}
	}

	private static function cap_verbs(): array {
		return array( 'edit', 'read', 'delete', 'publish' );
	}

	private static function build_caps( array $spec ): array {
		$caps = array( 'read' => true );
		foreach ( $spec as $level => $types ) {
			foreach ( $types as $type ) {
				switch ( $level ) {
					case 'manage':
						$caps["edit_{$type}s"]          = true;
						$caps["edit_{$type}"]           = true;
						$caps["edit_others_{$type}s"]   = true;
						$caps["publish_{$type}s"]       = true;
						$caps["read_private_{$type}s"]  = true;
						$caps["delete_{$type}s"]        = true;
						$caps["delete_{$type}"]         = true;
						$caps["delete_others_{$type}s"] = true;
						break;
					case 'edit':
						$caps["edit_{$type}s"] = true;
						$caps["edit_{$type}"]  = true;
						break;
					case 'view':
						$caps["read_private_{$type}s"] = true;
						break;
				}
			}
		}
		return $caps;
	}
}

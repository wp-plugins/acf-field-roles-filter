<?php

/*
Plugin Name: Advanced Custom Fields: Field Roles Filter
Description: This plugin will let you filter each field according to user's role.
Version: 1.0.0
Author: Martin Babinec
Text Domain: acf-field-roles-filter
Domain Path: /lang
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class ACFFieldRolesFilter
{
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'add_role_metabox' ), 20 );
		add_action( 'save_post', array( $this, 'save_role_metabox' ) );
		add_action( 'acf/field_group/get_fields', array( $this, 'hide_fields' ), 20, 2 );
	}

	private function get_roles() {
		global $wp_roles;

		$roles = array();

		foreach ( $wp_roles->role_names as $name => $role ) {
			$roles[ $name ] = translate_user_role( $role );
		}

		return $roles;
	}

	function load_textdomain() {
		load_plugin_textdomain( 'acf-field-roles-filter', false, plugin_dir_url( __FILE__ ) . 'lang/' );
	}

	public function enqueue_scripts() {
		// cares only about ACF edit pages
		if ( 'acf' !== get_post_type() ) {
			return;
		}

		wp_enqueue_style( 'acf-field-roles-style', plugin_dir_url( __FILE__ ) . 'css/acf_roles.css' );

		wp_enqueue_script( 'acf-field-roles', plugin_dir_url( __FILE__ ) . 'js/acf_roles.js', array( 'jquery', 'acf-field-group' ) );
		wp_localize_script( 'acf-field-roles', 'roles', $this->get_roles() );
	}

	public function add_role_metabox() {
		global $wp_meta_boxes;

		add_meta_box( 'acf_roles', __( 'User Roles Fields Filters', 'acf-field-roles-filter' ), array( $this, 'roles_metabox' ) , 'acf', 'normal', 'high' );

		// reorder metaboxes
		$roles_metabox = $wp_meta_boxes['acf']['normal']['high']['acf_roles'];
		unset( $wp_meta_boxes['acf']['normal']['high']['acf_roles'] );
		array_splice( $wp_meta_boxes['acf']['normal']['high'], 1, 0, array( $roles_metabox ) );
	}

	public function roles_metabox() {
		global $post, $wp_roles;

		$saved_roles = get_post_meta( $post->ID, 'roles', true );

		$fields = apply_filters( 'acf/field_group/get_fields', array(), $post->ID ); ?>
		<table class="acf_input widefat">
			<tbody>
			<?php foreach ( $fields as $field ) : ?>
				<tr data-id="<?php echo $field['key']; ?>">
					<td class="label">
						<label class="label"><?php echo $field['label']; ?></label>
					</td>
					<td>
						<ul class="acf-checkbox-list checkbox horizontal">
							<?php foreach ( $wp_roles->role_names as $name => $role ) : ?>
							<li>
								<label>
									<?php $check = ( ! isset( $saved_roles[ $field['key'] ] ) || in_array( $name, $saved_roles[ $field['key'] ] ) ? 'checked="checked" ' : '' ); ?>
									<input <?php echo $check; ?>
										type="checkbox"
										class="checkbox"
										name="roles[<?php echo $field['key']; ?>][]"
										value="<?php echo $name; ?>"><?php echo translate_user_role( $role ); ?>
								</label>
							</li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public function save_role_metabox( $post_id ) {
		// cares only about ACF edit pages
		if ( 'acf' !== get_post_type() ) {
			return;
		}

		// do not save if this is an auto save routine
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// verify nonce
		if ( ! isset( $_POST['acf_nonce'] ) || ! wp_verify_nonce( $_POST['acf_nonce'], 'field_group' ) ) {
			return;
		}

		// Only save once! WordPress save's a revision as well.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// save role filter
		if ( isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ) {
			update_post_meta( $post_id, 'roles', $_POST['roles'] );
		}
	}

	public function hide_fields( $fields, $post_id ) {
		global $current_user;

		// don't hide anything when you're on ACF edit page
		if ( 'acf' === get_post_type() ) {
			return $fields;
		}

		$field_roles = get_post_meta( $post_id, 'roles', true );

		// hide (remove) fields
		foreach ( $fields as $key => $field ) {
			$found = false;
			foreach ( $current_user->roles as $role ) {
				if ( in_array( $role, $field_roles[ $field['key'] ] ) ) {
					$found = true;
					break;
				}
			}
			if ( $found ) {
				continue;
			}
			unset( $fields[ $key ] );
		}

		return $fields;
	}
}
new ACFFieldRolesFilter();

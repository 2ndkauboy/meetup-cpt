<?php
/*
 * Plugin Name: Meetup CPT
 * Plugin URI: https://github.com/2ndkauboy/meetup-cpt
 * Description: Adding a Custom Post Type for meetups
 * Version: 0.1
 * Author: Bernhard Kau
 * Author URI: http://kau-boys.de
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */


add_action( 'init', 'meetup_cpt_register_post_type' );
add_action( 'init', 'meetup_cpt_register_taxonomy' );
add_action( 'add_meta_boxes', 'meetup_cpt_init_meta_boxes' );
add_action( 'save_post', 'meetup_cpt_meta_box_dates_save' );
add_action( 'plugins_loaded', 'meetup_cpt_load_textdomain' );
add_filter( 'pre_get_posts', 'add_post_types_to_query' );
add_filter( 'manage_meetup_posts_columns', 'meetup_date_column' );
add_filter( 'manage_meetup_posts_custom_column', 'meetup_columns', 10, 2 );


function meetup_cpt_register_post_type() {

	$args = array(
		'labels'      => array(
			'name'          => __( 'Meetups', 'meetup-cpt' ),
			'singular_name' => __( 'Meetup', 'meetup-cpt' )
		),
		'public'      => true,
		'has_archive' => true,
		'rewrite'     => array( 'slug' => 'meetups' ),
	);

	register_post_type( 'meetup', $args );
}

function meetup_cpt_register_taxonomy() {

	$args = array(
		'labels' => array(
			'name'          => _x( 'Meetup formats', 'Taxonomy General Name', 'meetup-cpt' ),
			'singular_name' => _x( 'Meetup format', 'Taxonomy Singular Name', 'meetup-cpt' ),
		),
		'public' => true,
	);

	register_taxonomy( 'meetup_format', array( 'meetup' ), $args );
}

function meetup_cpt_init_meta_boxes() {
	add_meta_box( 'meetup_dates', __( 'Meetup dates', 'meetup-cpt' ), 'meetup_cpt_meta_box_dates_render', 'meetup', 'side', 'low' );
}

function meetup_cpt_meta_box_dates_render( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'meetup_dates_box', 'meetup_dates_box_nonce' );

	$meetup_start = get_post_meta( $post->ID, '_meetup_start', true );
	$meetup_end   = get_post_meta( $post->ID, '_meetup_end', true );

	// Echo out the fields
	?>
	<p>
		<label for="meetup_start"><?php _e( 'Meetup start', 'meetup-cpt' ) ?></label><br/>
		<input type="datetime-local" id="meetup_start" name="meetup_start" value="<?php echo esc_attr( $meetup_start ) ?>" class="widefat" />
	</p>
	<p>
		<label for="meetup_end"><?php _e( 'Meetup end', 'meetup-cpt' ) ?></label><br/>
		<input type="datetime-local" id="meetup_end" name="meetup_end" value="<?php echo esc_attr( $meetup_end ) ?>" class="widefat" />
	</p>
<?php
}

function meetup_cpt_meta_box_dates_save( $post_id ) {

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST[ 'meetup_dates_box_nonce' ], 'meetup_dates_box' ) ) {
		return false;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	// Check the user's permissions.
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return false;
	}

	// Sanitize the user input.
	$meetup_start = sanitize_text_field( $_POST[ 'meetup_start' ] );
	$meetup_end = sanitize_text_field( $_POST[ 'meetup_end' ] );

	// Update the meta field.
	update_post_meta( $post_id, '_meetup_start', $meetup_start );
	update_post_meta( $post_id, '_meetup_end', $meetup_end );
}

function meetup_cpt_load_textdomain() {
	load_plugin_textdomain( 'meetup-cpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function add_post_types_to_query( $query ) {

	if ( ! is_admin() && $query->is_main_query() && ( is_home() || ( is_archive() && ! is_post_type_archive( 'meetup' ) ) ) ) {
		$query->set( 'post_type', array( 'post', 'meetup' ) );
	}
}

function meetup_date_column( $default ) {
	$default[ 'meetup_start' ] = __( 'Meetup', 'meetup-cpt' );

	return $default;
}

function meetup_columns(  $column, $post_id  ) {
	if ( 'meetup_start' == $column ) {
		echo get_post_meta( $post_id, '_meetup_start', true );
	}
}
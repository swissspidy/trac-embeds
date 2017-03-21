<?php
/**
 * Trac Embeds.
 *
 * @wordpress-plugin
 *
 * Plugin Name: Trac Embeds
 * Plugin URI:  https://required.com
 * Description: Allows embedding tickets from a Trac instance via WordPress.
 * Version:     1.0.0
 * Author:      Pascal Birchler
 * Author URI:  https://pascalbirchler.com
 * Text Domain: trac-embeds
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package trac-embeds
 */

/**
 * Returns a list of trac sites that can be embedded.
 *
 * @since 1.0.0
 * @access public
 *
 * @return array Enabled trac sites. Key is the URL, value is the title.
 */
function trac_embeds_get_sites() {
	/**
	 * Filters the list of trac sites that can be embedded.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sites Enabled trac sites. Key is the URL, value is the title.
	 */
	return apply_filters( 'trac_embeds_sites', [
		'https://core.trac.wordpress.org' => __( 'WordPress Core Trac' ),
		'https://meta.trac.wordpress.org' => __( 'WordPress Meta Trac' ),
	] );
}

/**
 * Filters the requested oEmbed post ID.
 *
 * @since 1.0.0
 * @access public
 *
 * @param int    $post_id The post ID.
 * @param string $url     The embedded URL.
 * @return int The modified post ID.
 */
function trac_embeds_oembed_request_post_id( $post_id, $url ) {
	$trac_url = explode( '/ticket', $url )[0];

	if ( ! trac_embeds_get_post_id() || ! array_key_exists( $trac_url, trac_embeds_get_sites() ) ) {
		return $post_id;
	}

	$trac_title = trac_embeds_get_sites()[ $trac_url ];
	$ticket     = trac_embeds_get_ticket_data( $url );

	if ( ! is_array( $ticket ) ) {
		return $post_id;
	}

	// Appends the trac URL to the ticket for use in the embed template.
	add_filter( 'post_embed_url', function ( $embed_url, $post ) use ( $url ) {
		if ( (int) trac_embeds_get_post_id() === $post->ID ) {
			return add_query_arg( 'url', $url, $embed_url );
		}

		return $embed_url;
	}, 10, 2 );

	// Replaces the post title with the ticket title everywhere.
	add_filter( 'the_title', function ( $permalink, $post_id ) use ( $ticket ) {
		if ( (int) trac_embeds_get_post_id() === $post_id ) {
			return $ticket['summary'];
		}

		return $permalink;
	}, 10, 2 );

	// Replaces the site title with the trac title everywhere.
	add_filter( 'embed_html', function ( $html ) use ( $trac_title ) {
		return str_replace( get_bloginfo( 'name' ), $trac_title, $html );
	} );

	// Adds ticket info to oEmbed response data.
	add_filter( 'oembed_response_data', function ( $data, $post ) use ( $ticket, $url, $trac_url, $trac_title ) {
		if ( (int) trac_embeds_get_post_id() === $post->ID ) {
			$data['provider_name'] = $trac_title;
			$data['provider_url']  = $trac_url;
			$data['author_name']   = $ticket['reporter'];
			$data['author_url']    = $url;
			$data['title']         = $ticket['summary'];
		}

		return $data;
	}, 10, 3 );

	return (int) trac_embeds_get_post_id();
}

add_filter( 'oembed_request_post_id', 'trac_embeds_oembed_request_post_id', 10, 2 );

/**
 * Filters the embed template path for trac embeds.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $template The embed template path.
 * @return string The modified template path.
 */
function trac_embeds_embed_template( $template ) {
	if ( ! isset( $_GET['url'] ) ) {
		return $template;
	}

	$ticket = trac_embeds_get_ticket_data( sanitize_text_field( $_GET['url'] ) );

	if ( ! empty( $ticket ) ) {
		return plugin_dir_path( __FILE__ ) . '/inc/embed.php';
	}

	return $template;
}

add_filter( 'embed_template', 'trac_embeds_embed_template' );

/**
 * Fetches data for a given trac ticket.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $url The trac ticket URL.
 * @return array Ticket data.
 */
function trac_embeds_get_ticket_data( $url ) {
	$data = get_transient( 'trac_embeds_' . md5( $url ) );

	if ( ! $data ) {
		$data = wp_remote_retrieve_body( wp_remote_get( add_query_arg( 'format', 'csv', $url ) ) );

		if ( empty( $data ) ) {
			$data = [];
		} else {
			// Remove BOM.
			$data    = str_replace( "\xEF\xBB\xBF", '', $data );
			$data    = sanitize_textarea_field( $data );
			$data    = str_getcsv( $data, "\n" );
			$headers = str_getcsv( array_shift( $data ) );
			$content = str_getcsv( implode( "\n", $data ) );

			$data = array_combine( $headers, $content );
		}

		set_transient( 'trac_embeds_' . md5( $url ), $data, DAY_IN_SECONDS );
	}

	return $data;
}

/**
 * Filters the user's capabilities to disallow editing our fake post.
 *
 * @since 1.0.0
 * @access public
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 *
 * @param array  $caps    The user's actual capabilities.
 * @param string $cap     Capability name.
 * @param int    $user_id The user ID.
 * @param array  $args    The post ID, if available.
 * @return array The modified capabilities array.
 */
function trac_embeds_filter_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( in_array( $cap, [ 'read_post', 'edit_post', 'delete_post', 'publish_post' ], true ) &&
	     (int) trac_embeds_get_post_id() === (int) $args[0]
	) {
		$caps[] = 'do_not_allow';
	}

	return $caps;
}

add_filter( 'map_meta_cap', 'trac_embeds_filter_map_meta_cap', 10, 4 );

/**
 * Creates a dummy post that acts as a placeholder for trac embeds.
 *
 * @since 1.0.0
 * @access public
 */
function trac_embeds_create_dummy_post() {
	$post_id = wp_insert_post( [
		'post_title'  => __( 'Hidden Trac Embed Post' ),
		'post_name'   => 'trac-embed',
		'post_status' => 'publish',
	] );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_option( 'trac_embeds_post_id', $post_id, false );
	}
}

/**
 * Returns the dummy embed post ID.
 *
 * @since 1.0.0
 * @access public
 *
 * @return int The embed post ID. 0 if there's none.
 */
function trac_embeds_get_post_id() {
	return (int) get_option( 'trac_embeds_post_id', 0 );
}

/**
 * Plugin activation callback.
 *
 * @since 1.0.0
 * @access public
 */
function trac_embeds_activation() {
	if ( ! trac_embeds_get_post_id() ) {
		trac_embeds_create_dummy_post();
	}
}

register_activation_hook( __FILE__, 'trac_embeds_activation' );

/**
 * Plugin uninstall callback.
 *
 * Completely removes the dummy post and the relevant option.
 *
 * @since 1.0.0
 * @access public
 *
 * @todo Delete all transients.
 */
function trac_embeds_uninstall() {
	if ( trac_embeds_get_post_id() ) {
		wp_delete_post( trac_embeds_get_post_id() );
	}

	delete_option( 'trac_embeds_post_id' );
}

register_uninstall_hook( __FILE__, 'trac_embeds_uninstall' );

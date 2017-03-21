<?php
/**
 * Contains the embed template for trac embeds.
 *
 * @package trac-embeds
 * @since   1.0.0
 */

if ( ! headers_sent() ) {
	header( 'X-WP-embed: true' );
}

$ticket_url = sanitize_text_field( $_GET['url'] );
$trac_url   = explode( '/ticket', $ticket_url )[0];
$ticket     = trac_embeds_get_ticket_data( $ticket_url );
$trac_title = trac_embeds_get_sites()[ $trac_url ];

remove_action( 'embed_footer', 'print_embed_sharing_dialog' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<title><?php echo wp_get_document_title(); ?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<?php
	/** This action is documented in wp-includes/theme-compat/header-embed.php */
	do_action( 'embed_head' );
	?>
</head>
<body <?php body_class(); ?>>
<div class="wp-embed">
	<p class="wp-embed-heading">
		<a href="<?php echo esc_url( $ticket_url ); ?>" target="_top">
			<?php echo esc_html(  $ticket['summary'] ); ?>
		</a>
	</p>

	<div class="wp-embed-excerpt"><?php echo wpautop( esc_html( $ticket['description'] ) ); ?></div>

	<div class="wp-embed-footer">
		<div class="wp-embed-site-title">
			<a href="<?php echo esc_url( $trac_url ); ?>" target="_top">
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . '/assets/trac-logo.svg' ); ?>" width="32" height="32" alt="" class="wp-embed-site-icon"/>
				<span><?php echo esc_html( $trac_title ); ?></span>
			</a>
		</div>
	</div>
</div>
<?php
/** This action is documented in wp-includes/theme-compat/footer-embed.php */
do_action( 'embed_footer' );
?>
</body>
</html>

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
	<style type="text/css">
		p.wp-embed-heading {
			font-size: 20px;
			font-weight: 400;
		}

		.wp-embed-heading .trac-embed-ticket-id {
			font-family: monospace, serif;
			font-weight: normal;
			color: #0073aa;
		}

		.trac-embed-info {
			column-count: 2;
			margin: 0;
		}

		.trac-embed-info dt {
			font-weight: bold;
		}

		.trac-embed-info dd {
			margin: 0 0 10px;
			break-before: avoid;
			-webkit-column-break-before: avoid;
			column-break-before: avoid;
		}

		.trac-embed-tag-list {
			margin: 0;
			padding: 0;
			list-style: none;
			line-height: 2;
		}

		.trac-embed-tag {
			background: #eee;
			padding: 2px 5px;
			border-radius: 3px;
			display: inline;
			margin: 0 5px 0 0;
		}
	</style>
</head>
<body <?php body_class(); ?>>
<div class="wp-embed">
	<p class="wp-embed-heading">
		<a href="<?php echo esc_url( $ticket_url ); ?>" target="_top" class="trac-embed-ticket-id"><?php echo esc_html( '#' . $ticket['id'] ); ?></a>
		<a href="<?php echo esc_url( $ticket_url ); ?>" target="_top">
			<?php echo esc_html( $ticket['summary'] ); ?>
		</a>
	</p>

	<div class="wp-embed-excerpt">
		<dl class="trac-embed-info">
			<dt><?php _e( 'Type', 'trac-embeds' ); ?></dt>
			<dd><?php echo esc_html( $ticket['type'] ); ?></dd>
			<?php if ( empty( $ticket['focuses'] ) ) : ?>
				<dt><?php _e( 'Component', 'trac-embeds' ); ?></dt>
				<dd><?php echo esc_html( $ticket['component'] ); ?></dd>
			<?php else : ?>
				<dt><?php _e( 'Component (Focuses)', 'trac-embeds' ); ?></dt>
				<dd>
					<?php echo esc_html( $ticket['component'] ); ?>
					<ul class="trac-embed-tag-list">
						<?php foreach ( explode( ',', $ticket['focuses'] ) as $focus ) : ?>
							<li class="trac-embed-tag"><?php echo esc_html( trim( $focus ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				</dd>
			<?php endif; ?>
			<?php if ( ! empty( $ticket['milestone'] ) ) : ?>
				<dt><?php _e( 'Milestone', 'trac-embeds' ); ?></dt>
				<dd><?php echo esc_html( $ticket['milestone'] ); ?></dd>
			<?php endif; ?>
			<dt><?php _e( 'Status', 'trac-embeds' ); ?></dt>
			<dd><?php echo esc_html( $ticket['status'] ); ?></dd>
			<?php if ( ! empty( $ticket['version'] ) ) : ?>
				<dt><?php _e( 'Version', 'trac-embeds' ); ?></dt>
				<dd><?php echo esc_html( $ticket['version'] ); ?></dd>
			<?php endif; ?>
			<?php if ( ! empty( $ticket['keywords'] ) ) : ?>
				<dt><?php _e( 'Keywords', 'trac-embeds' ); ?></dt>
				<dd>
					<ul class="trac-embed-tag-list">
						<?php foreach ( explode( ' ', $ticket['keywords'] ) as $keyword ) : ?>
							<li class="trac-embed-tag"><?php echo esc_html( trim( $keyword ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				</dd>
			<?php endif; ?>
		</dl>
	</div>

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

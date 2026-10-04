<?php
/** Read-only submission details. Editing/status actions arrive in stage three. @package Cammino */
defined( 'ABSPATH' ) || exit;
$tip = $GLOBALS['cammino_tipsters_current_tip'];
$status = get_post_meta( $tip->ID, '_cammino_tip_status', true );
$statuses = cammino_tipsters_status_labels();
$files = (array) get_post_meta( $tip->ID, CAMMINO_TIP_FILES_META, true );
?>
<p><a href="<?php echo esc_url( cammino_tipsters_url() ); ?>">&larr; <?php esc_html_e( 'Moje tipy', 'cammino' ); ?></a></p>
<article class="cammino-tipsters__card cammino-tipsters__detail">
	<div class="cammino-tipsters__section-heading">
		<h2><?php echo esc_html( $tip->post_title ); ?></h2>
		<span class="cammino-tipsters__status"><?php echo esc_html( $statuses[ $status ] ); ?></span>
	</div>
	<p class="cammino-tipsters__dates"><?php echo esc_html( sprintf( __( 'Odoslané: %1$s · Aktualizované: %2$s', 'cammino' ), get_post_time( get_option( 'date_format' ) . ' H:i', false, $tip, true ), get_post_modified_time( get_option( 'date_format' ) . ' H:i', false, $tip, true ) ) ); ?></p>
	<p class="cammino-tipsters__notice"><?php esc_html_e( 'Tip bol uložený. V tejto fáze je formulár uzamknutý a komunikácia nie je otvorená.', 'cammino' ); ?></p>
	<h3><?php esc_html_e( 'Krátky popis', 'cammino' ); ?></h3>
	<div class="cammino-tipsters__text"><?php echo esc_html( $tip->post_excerpt ); ?></div>
	<h3><?php esc_html_e( 'Podrobný popis', 'cammino' ); ?></h3>
	<div class="cammino-tipsters__text"><?php echo esc_html( $tip->post_content ); ?></div>
	<h3><?php esc_html_e( 'Prílohy', 'cammino' ); ?></h3>
	<?php if ( ! $files ) : ?>
		<p><?php esc_html_e( 'Bez príloh.', 'cammino' ); ?></p>
	<?php else : ?>
		<ul class="cammino-tipsters__files">
			<?php foreach ( $files as $file ) : ?>
				<?php if ( ! is_array( $file ) || empty( $file['id'] ) ) { continue; } ?>
				<li><a href="<?php echo esc_url( cammino_tipsters_url( 'download', $tip->ID, $file['id'] ) ); ?>"><?php echo esc_html( $file['name'] ); ?></a> <span>(<?php echo esc_html( size_format( $file['size'] ) ); ?>)</span></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</article>

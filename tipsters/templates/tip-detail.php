<?php
/** Private tip details with discussion-only editing and confirmed owner deletion. @package Cammino */
defined( 'ABSPATH' ) || exit;
$tip = $GLOBALS['cammino_tipsters_current_tip'];
$record = cammino_tipsters_tip_record( $tip->ID );
$status = $record['status'];
$statuses = cammino_tipsters_status_labels();
$files = $record['files'];
?>
<p><a href="<?php echo esc_url( cammino_tipsters_url() ); ?>">&larr; <?php esc_html_e( 'Moje tipy', 'cammino' ); ?></a></p>
<article class="cammino-tipsters__card cammino-tipsters__detail">
	<div class="cammino-tipsters__section-heading">
		<h2><?php echo esc_html( $record['title'] ); ?></h2>
		<span class="cammino-tipsters__status"><?php echo esc_html( $statuses[ $status ] ); ?></span>
	</div>
	<p class="cammino-tipsters__dates"><?php echo esc_html( sprintf( __( 'Odoslané: %1$s · Aktualizované: %2$s', 'cammino' ), get_post_time( get_option( 'date_format' ) . ' H:i', false, $tip, true ), get_date_from_gmt( $record['updated_at'], get_option( 'date_format' ) . ' H:i' ) ) ); ?></p>
	<p class="cammino-tipsters__notice"><?php echo esc_html( 'discussion' === $status ? __( 'Administrátor otvoril diskusiu. Formulár môžete upraviť a správu odoslať nižšie.', 'cammino' ) : ( 'approved' === $status ? __( 'Tip je schválený. Formulár je uzamknutý. Komunikácia zostáva otvorená.', 'cammino' ) : __( 'Tip čaká na kontrolu administrátorom. Formulár je uzamknutý.', 'cammino' ) ) ); ?></p>
	<?php if ( 'discussion' !== $status && ! empty( $GLOBALS['cammino_tipsters_form_errors'] ) ) : ?><p class="cammino-tipsters__error" role="alert"><?php echo esc_html( $GLOBALS['cammino_tipsters_form_errors']->get_error_message() ); ?></p><?php endif; ?>
	<?php if ( ! empty( $GLOBALS['cammino_tipsters_tip_action_error'] ) ) : ?><p class="cammino-tipsters__error" role="alert"><?php echo esc_html( $GLOBALS['cammino_tipsters_tip_action_error']->get_error_message() ); ?></p><?php endif; ?>
	<h3><?php esc_html_e( 'Krátky popis', 'cammino' ); ?></h3>
	<div class="cammino-tipsters__text"><?php echo esc_html( $record['short_description'] ); ?></div>
	<h3><?php esc_html_e( 'Podrobný popis', 'cammino' ); ?></h3>
	<div class="cammino-tipsters__text"><?php echo esc_html( $record['long_description'] ); ?></div>
	<h3><?php esc_html_e( 'Odkaz na súbory', 'cammino' ); ?></h3>
	<?php if ( ! empty( $record['file_link'] ) ) : ?><p><a href="<?php echo esc_url( $record['file_link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $record['file_link'] ); ?></a></p><?php else : ?><p><?php esc_html_e( 'Bez odkazu.', 'cammino' ); ?></p><?php endif; ?>
	<?php if ( $files ) : ?>
	<h3><?php esc_html_e( 'Staršie prílohy', 'cammino' ); ?></h3>
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
	<?php endif; ?>
</article>
<?php if ( cammino_tipsters_can_edit_tip( $tip->ID ) ) : ?>
	<?php $GLOBALS['cammino_tipsters_editing'] = true; require __DIR__ . '/new-tip.php'; unset( $GLOBALS['cammino_tipsters_editing'] ); ?>
<?php endif; ?>
<?php cammino_tipsters_render_conversation( $tip->ID ); ?>
<section class="cammino-tipsters__card cammino-tipsters__delete">
	<h2><?php esc_html_e( 'Odstrániť tip', 'cammino' ); ?></h2>
	<p><?php esc_html_e( 'Tip zmizne z vášho účtu a nebudete k nemu mať prístup. Administrátorovi zostane zachovaný vrátane príloh a histórie. Obnovenie nie je dostupné.', 'cammino' ); ?></p>
	<form method="post" action="<?php echo esc_url( cammino_tipsters_url( 'tip', $tip->ID ) ); ?>">
		<?php wp_nonce_field( 'cammino_delete_tip_' . $tip->ID ); ?>
		<input type="hidden" name="operation" value="delete_tip"><input type="hidden" name="tip_version" value="<?php echo esc_attr( $record['version'] ); ?>">
		<label><input type="checkbox" name="confirm_delete_tip" value="yes" required> <?php esc_html_e( 'Potvrdzujem odstránenie tohto tipu z môjho účtu.', 'cammino' ); ?></label>
		<button type="submit" class="button button--cream"><?php esc_html_e( 'Odstrániť tip', 'cammino' ); ?></button>
	</form>
</section>

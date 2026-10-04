<?php
/** Private submission form. @package Cammino */
defined( 'ABSPATH' ) || exit;
$errors = $GLOBALS['cammino_tipsters_form_errors'] ?? new WP_Error();
?>
<p><a href="<?php echo esc_url( cammino_tipsters_url() ); ?>">&larr; <?php esc_html_e( 'Moje tipy', 'cammino' ); ?></a></p>
<section class="cammino-tipsters__card" aria-labelledby="tipster-form-title">
	<h2 id="tipster-form-title"><?php esc_html_e( 'Odoslať nový tip', 'cammino' ); ?></h2>
	<p><?php esc_html_e( 'Vyplňte všetky textové polia. Po odoslaní bude tip uzamknutý, kým ho administrátor neotvorí na diskusiu.', 'cammino' ); ?></p>
	<?php if ( is_wp_error( cammino_tipsters_storage_root() ) ) : ?>
		<p class="cammino-tipsters__notice"><?php esc_html_e( 'Prílohy momentálne nie sú dostupné. Tip môžete odoslať bez súborov alebo kontaktovať administrátora.', 'cammino' ); ?></p>
	<?php endif; ?>
	<?php if ( $errors->has_errors() ) : ?>
		<div class="cammino-tipsters__error" role="alert">
			<p><?php esc_html_e( 'Tip nebol odoslaný. Skontrolujte označené polia. Prílohy je potrebné vybrať znova.', 'cammino' ); ?></p>
			<?php foreach ( $errors->get_error_codes() as $code ) : ?>
				<?php if ( ! in_array( $code, array( 'title', 'short_description', 'long_description' ), true ) ) : ?>
					<p><?php echo esc_html( $errors->get_error_message( $code ) ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( cammino_tipsters_url( 'new' ) ); ?>">
		<?php wp_nonce_field( 'cammino_submit_tip' ); ?>
		<input type="hidden" name="operation" value="submit_tip">
		<input type="hidden" name="submission_token" value="<?php echo esc_attr( $GLOBALS['cammino_tipsters_submission_token'] ); ?>">
		<?php foreach ( array( 'title' => array( __( 'Názov', 'cammino' ), 200, 1 ), 'short_description' => array( __( 'Krátky popis', 'cammino' ), 1000, 4 ), 'long_description' => array( __( 'Podrobný popis', 'cammino' ), 20000, 10 ) ) as $field => $config ) : ?>
			<label for="tip-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $config[0] ); ?></label>
			<?php if ( 'title' === $field ) : ?>
				<input id="tip-title" name="title" type="text" maxlength="200" required aria-describedby="tip-title-help<?php echo $errors->get_error_message( $field ) ? ' tip-title-error' : ''; ?>" <?php echo $errors->get_error_message( $field ) ? 'aria-invalid="true"' : ''; ?> value="<?php echo esc_attr( cammino_tipsters_input( $field ) ); ?>">
			<?php else : ?>
				<textarea id="tip-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" rows="<?php echo esc_attr( $config[2] ); ?>" maxlength="<?php echo esc_attr( $config[1] ); ?>" required aria-describedby="tip-<?php echo esc_attr( $field ); ?>-help<?php echo $errors->get_error_message( $field ) ? ' tip-' . esc_attr( $field ) . '-error' : ''; ?>" <?php echo $errors->get_error_message( $field ) ? 'aria-invalid="true"' : ''; ?>><?php echo esc_textarea( cammino_tipsters_input( $field ) ); ?></textarea>
			<?php endif; ?>
			<p class="cammino-tipsters__field-help" id="tip-<?php echo esc_attr( $field ); ?>-help"><?php echo esc_html( sprintf( __( 'Najviac %d znakov. Bez formátovania.', 'cammino' ), $config[1] ) ); ?></p>
			<?php if ( $errors->get_error_message( $field ) ) : ?>
				<p class="cammino-tipsters__field-error" id="tip-<?php echo esc_attr( $field ); ?>-error"><?php echo esc_html( $errors->get_error_message( $field ) ); ?></p>
			<?php endif; ?>
		<?php endforeach; ?>
		<label for="tip-files"><?php esc_html_e( 'Prílohy (nepovinné)', 'cammino' ); ?></label>
		<input id="tip-files" name="tip_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" aria-describedby="tip-files-help">
		<p class="cammino-tipsters__field-help" id="tip-files-help"><?php echo esc_html( sprintf( __( 'Najviac 5 súborov, každý do %s. PDF, JPG/JPEG, PNG, WEBP, DOC a DOCX. Celková veľkosť musí vyhovovať limitu hostingu.', 'cammino' ), size_format( cammino_tipsters_upload_limit() ) ) ); ?></p>
		<div class="cammino-tipsters__actions">
			<button type="submit" class="button button--coral"><?php esc_html_e( 'Odoslať tip', 'cammino' ); ?></button>
			<a class="button button--cream" href="<?php echo esc_url( cammino_tipsters_url() ); ?>"><?php esc_html_e( 'Späť na moje tipy', 'cammino' ); ?></a>
		</div>
	</form>
</section>

<?php
/** Private submission form. @package Cammino */
defined( 'ABSPATH' ) || exit;
$errors = $GLOBALS['cammino_tipsters_form_errors'] ?? new WP_Error();
$editing = ! empty( $GLOBALS['cammino_tipsters_editing'] );
$edit_record = $editing ? cammino_tipsters_tip_record( $GLOBALS['cammino_tipsters_current_tip']->ID ) : array();
$value = static function ( string $field ) use ( $editing, $edit_record ): string {
	return ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && 'edit_tip' === cammino_tipsters_input( 'operation' ) ) || ! $editing ? cammino_tipsters_input( $field ) : ( $edit_record[ $field ] ?? '' );
};
?>
<?php if ( ! $editing ) : ?>
<p><a href="<?php echo esc_url( cammino_tipsters_url() ); ?>">&larr; <?php esc_html_e( 'Moje tipy', 'cammino' ); ?></a></p>
<?php endif; ?>
<section class="cammino-tipsters__card" aria-labelledby="tipster-form-title">
	<h2 id="tipster-form-title"><?php echo esc_html( $editing ? __( 'Upraviť tip', 'cammino' ) : __( 'Odoslať nový tip', 'cammino' ) ); ?></h2>
	<p><?php echo esc_html( $editing ? __( 'V diskusii môžete zmeniť texty a prílohy. Uložením zostane tip v diskusii.', 'cammino' ) : __( 'Vyplňte všetky textové polia. Po odoslaní bude tip uzamknutý, kým ho administrátor neotvorí na diskusiu.', 'cammino' ) ); ?></p>
	<?php if ( is_wp_error( cammino_tipsters_storage_root() ) ) : ?>
		<p class="cammino-tipsters__notice"><?php esc_html_e( 'Prílohy momentálne nie sú dostupné. Tip môžete odoslať bez súborov alebo kontaktovať administrátora.', 'cammino' ); ?></p>
	<?php endif; ?>
	<?php if ( $errors->has_errors() ) : ?>
		<div class="cammino-tipsters__error" role="alert">
			<p><?php echo esc_html( $editing ? __( 'Zmeny neboli uložené. Skontrolujte formulár. Nové prílohy je potrebné vybrať znova.', 'cammino' ) : __( 'Tip nebol odoslaný. Skontrolujte označené polia. Prílohy je potrebné vybrať znova.', 'cammino' ) ); ?></p>
			<?php foreach ( $errors->get_error_codes() as $code ) : ?>
				<?php if ( ! in_array( $code, array( 'title', 'short_description', 'long_description' ), true ) ) : ?>
					<p><?php echo esc_html( $errors->get_error_message( $code ) ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $editing ) : ?><p><a href="<?php echo esc_url( cammino_tipsters_url( 'tip', $GLOBALS['cammino_tipsters_current_tip']->ID ) ); ?>"><?php esc_html_e( 'Obnoviť aktuálny tip', 'cammino' ); ?></a></p><?php endif; ?>
		</div>
	<?php endif; ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $editing ? cammino_tipsters_url( 'tip', $GLOBALS['cammino_tipsters_current_tip']->ID ) : cammino_tipsters_url( 'new' ) ); ?>">
		<?php wp_nonce_field( $editing ? 'cammino_edit_tip_' . $GLOBALS['cammino_tipsters_current_tip']->ID : 'cammino_submit_tip' ); ?>
		<input type="hidden" name="operation" value="<?php echo $editing ? 'edit_tip' : 'submit_tip'; ?>">
		<?php if ( $editing ) : ?>
			<input type="hidden" name="tip_version" value="<?php echo esc_attr( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? cammino_tipsters_input( 'tip_version' ) : $edit_record['version'] ); ?>">
		<?php else : ?>
			<input type="hidden" name="submission_token" value="<?php echo esc_attr( $GLOBALS['cammino_tipsters_submission_token'] ); ?>">
		<?php endif; ?>
		<?php foreach ( array( 'title' => array( __( 'Názov', 'cammino' ), 200, 1 ), 'short_description' => array( __( 'Krátky popis', 'cammino' ), 1000, 4 ), 'long_description' => array( __( 'Podrobný popis', 'cammino' ), 20000, 10 ) ) as $field => $config ) : ?>
			<label for="tip-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $config[0] ); ?></label>
			<?php if ( 'title' === $field ) : ?>
				<input id="tip-title" name="title" type="text" maxlength="200" required aria-describedby="tip-title-help<?php echo $errors->get_error_message( $field ) ? ' tip-title-error' : ''; ?>" <?php echo $errors->get_error_message( $field ) ? 'aria-invalid="true"' : ''; ?> value="<?php echo esc_attr( $value( $field ) ); ?>">
			<?php else : ?>
				<textarea id="tip-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" rows="<?php echo esc_attr( $config[2] ); ?>" maxlength="<?php echo esc_attr( $config[1] ); ?>" required aria-describedby="tip-<?php echo esc_attr( $field ); ?>-help<?php echo $errors->get_error_message( $field ) ? ' tip-' . esc_attr( $field ) . '-error' : ''; ?>" <?php echo $errors->get_error_message( $field ) ? 'aria-invalid="true"' : ''; ?>><?php echo esc_textarea( $value( $field ) ); ?></textarea>
			<?php endif; ?>
			<p class="cammino-tipsters__field-help" id="tip-<?php echo esc_attr( $field ); ?>-help"><?php echo esc_html( sprintf( __( 'Najviac %d znakov. Bez formátovania.', 'cammino' ), $config[1] ) ); ?></p>
			<?php if ( $errors->get_error_message( $field ) ) : ?>
				<p class="cammino-tipsters__field-error" id="tip-<?php echo esc_attr( $field ); ?>-error"><?php echo esc_html( $errors->get_error_message( $field ) ); ?></p>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if ( $editing && $edit_record['files'] ) : ?>
			<fieldset class="cammino-tipsters__existing-files"><legend><?php esc_html_e( 'Aktuálne prílohy', 'cammino' ); ?></legend>
				<p><?php esc_html_e( 'Označené prílohy zmiznú z vášho formulára. Administrátor si ich ponechá.', 'cammino' ); ?></p>
				<?php foreach ( $edit_record['files'] as $file ) : if ( empty( $file['id'] ) ) { continue; } ?>
					<label><input type="checkbox" name="remove_files[]" value="<?php echo esc_attr( $file['id'] ); ?>" <?php checked( isset( $_POST['remove_files'] ) && is_array( $_POST['remove_files'] ) && in_array( $file['id'], $_POST['remove_files'], true ) ); ?>> <?php echo esc_html( sprintf( __( 'Odstrániť: %s', 'cammino' ), $file['name'] ) ); ?></label>
				<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>
		<label for="tip-files"><?php esc_html_e( 'Prílohy (nepovinné)', 'cammino' ); ?></label>
		<input id="tip-files" name="tip_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" aria-describedby="tip-files-help">
		<p class="cammino-tipsters__field-help" id="tip-files-help"><?php echo esc_html( sprintf( __( 'Najviac 5 súborov, každý do %s. PDF, JPG/JPEG, PNG, WEBP, DOC a DOCX. Celková veľkosť musí vyhovovať limitu hostingu.', 'cammino' ), size_format( cammino_tipsters_upload_limit() ) ) ); ?></p>
		<div class="cammino-tipsters__actions">
			<button type="submit" class="button button--coral"><?php echo esc_html( $editing ? __( 'Uložiť zmeny', 'cammino' ) : __( 'Odoslať tip', 'cammino' ) ); ?></button>
			<a class="button button--cream" href="<?php echo esc_url( cammino_tipsters_url() ); ?>"><?php esc_html_e( 'Späť na moje tipy', 'cammino' ); ?></a>
		</div>
	</form>
</section>

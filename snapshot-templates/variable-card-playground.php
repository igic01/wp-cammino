<?php
/**
 * Snapshot Name: Variable Card Playground
 *
 * Demonstrates a snapshot-native number variable that controls editable cards.
 *
 * @package NStarter
 */

$nstarter_page_title = $nstarter_page instanceof WP_Post && get_the_title( $nstarter_page )
	? get_the_title( $nstarter_page )
	: __( 'Variable Card Playground', 'nstarter' );
$nstarter_image      = NSTARTER_URL . '/assets/images/starter-placeholder.svg';
$nstarter_card_count = 3;
?>
<style>
<?php include NSTARTER_PATH . '/snapshot-assets/variable-card-playground.css'; ?>
</style>

<div class="variable-playground">
	<header class="variable-header">
		<div class="variable-shell variable-header__inner">
			<a class="variable-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">NStarter</a>
			<span><?php esc_html_e( 'Snapshot-native section variables', 'nstarter' ); ?></span>
		</div>
	</header>

	<main>
		<section class="variable-hero">
			<div class="variable-shell">
				<p class="variable-kicker"><?php esc_html_e( 'Visual editor playground', 'nstarter' ); ?></p>
				<h1><?php echo esc_html( $nstarter_page_title ); ?></h1>
				<p class="variable-hero__copy"><?php esc_html_e( 'Use the purple pen on the card section to choose how many cards it contains. Existing cards keep their edits; new cards begin with the reusable template below.', 'nstarter' ); ?></p>
			</div>
		</section>

		<section class="variable-card-section"<?php
		nstarter_variable_section_attributes(
			'card_count',
			array(
				'label'   => __( 'Number of cards', 'nstarter' ),
				'type'    => 'number',
				'control' => 'repeat',
				'value'   => $nstarter_card_count,
				'min'     => 0,
				'max'     => 12,
				'step'    => 1,
			)
		);
		?>>
			<div class="variable-shell">
				<div class="variable-section-heading">
					<div>
						<p class="variable-kicker"><?php esc_html_e( 'Editable collection', 'nstarter' ); ?></p>
						<h2><?php esc_html_e( 'Cards controlled by one value.', 'nstarter' ); ?></h2>
					</div>
					<p><?php esc_html_e( 'Text and media inside every card remain independently editable. Reducing the number removes cards only after confirmation.', 'nstarter' ); ?></p>
				</div>

				<div class="variable-empty" data-nstarter-variable-empty-state>
					<strong><?php esc_html_e( '0 cards', 'nstarter' ); ?></strong>
					<span><?php esc_html_e( 'The section stays visible so its purple edit pen is always available.', 'nstarter' ); ?></span>
				</div>

				<div class="variable-card-grid" data-nstarter-variable-items>
					<?php for ( $nstarter_index = 1; $nstarter_index <= $nstarter_card_count; $nstarter_index++ ) : ?>
						<article class="variable-card" data-nstarter-variable-item>
							<div class="variable-card__media">
								<img src="<?php echo esc_url( $nstarter_image ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Replaceable image for card %d', 'nstarter' ), $nstarter_index ) ); ?>">
								<span><?php echo esc_html( str_pad( (string) $nstarter_index, 2, '0', STR_PAD_LEFT ) ); ?></span>
							</div>
							<div class="variable-card__content">
								<p><?php esc_html_e( 'Editable card', 'nstarter' ); ?></p>
								<h3><?php echo esc_html( sprintf( __( 'Card number %d', 'nstarter' ), $nstarter_index ) ); ?></h3>
								<span><?php esc_html_e( 'Edit this copy in Text mode and replace the image in Media mode.', 'nstarter' ); ?></span>
							</div>
						</article>
					<?php endfor; ?>
				</div>

				<template data-nstarter-variable-template>
					<article class="variable-card" data-nstarter-variable-item>
						<div class="variable-card__media">
							<img src="<?php echo esc_url( $nstarter_image ); ?>" alt="Replaceable image for card {{index}}">
							<span>{{index_padded}}</span>
						</div>
						<div class="variable-card__content">
							<p><?php esc_html_e( 'Editable card', 'nstarter' ); ?></p>
							<h3><?php esc_html_e( 'Card number', 'nstarter' ); ?> {{index}}</h3>
							<span><?php esc_html_e( 'Edit this copy in Text mode and replace the image in Media mode.', 'nstarter' ); ?></span>
						</div>
					</article>
				</template>
			</div>
		</section>

		<section class="variable-instructions">
			<div class="variable-shell variable-instructions__grid">
				<h2><?php esc_html_e( 'How to test it.', 'nstarter' ); ?></h2>
				<div class="variable-steps">
					<div><strong>01</strong><p><?php esc_html_e( 'Open the page visual editor and click the purple pen in the card section.', 'nstarter' ); ?></p></div>
					<div><strong>02</strong><p><?php esc_html_e( 'Enter a value from 0 to 12 and press Save in the popup. The preview changes immediately.', 'nstarter' ); ?></p></div>
					<div><strong>03</strong><p><?php esc_html_e( 'Edit card text or images, then use the main editor Save button to persist the snapshot.', 'nstarter' ); ?></p></div>
					<div><strong>04</strong><p><?php esc_html_e( 'Set the value to 0: the cards disappear, but the section and its edit pen remain available.', 'nstarter' ); ?></p></div>
				</div>
			</div>
		</section>
	</main>
</div>

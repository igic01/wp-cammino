<?php
/** Elementor widget: homepage donation callout. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Donate extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-donate'; }
	public function get_title(): string { return __( 'Cammino – Donate', 'cammino' ); }
	public function get_icon(): string { return 'eicon-paypal-button'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Pomôžte nám meniť dobré nápady na <em>skutočné príležitosti</em>' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Váš príspevok podporí vzdelávanie, praktické dielne, komunitné aktivity a priamu pomoc tam, kde je práve najviac potrebná.' );
		$this->add_inline_text_control( 'button_label', __( 'Button label', 'cammino' ), 'Chcem pomôcť', 'text' );
		$this->add_link_control( 'button_link', __( 'Button link', 'cammino' ), CAMMINO_DONATE_URL );
		$this->add_inline_text_control( 'note', __( 'Small note', 'cammino' ), 'Podpora vzdelávania, komunít a solidarity', 'text' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		?>
		<section class="section donate" id="donate"><div class="container"><div class="donate-card" data-reveal="scale">
			<div class="donate-step donate-step--one" aria-hidden="true"></div><div class="donate-step donate-step--two" aria-hidden="true"></div>
			<div class="donate-copy"><?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'copy', 'p' ); ?></div>
			<div class="donate-action"><a <?php echo $this->link_attributes( 'donate_link', (array) $s['button_link'], 'button button--cream' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'button_label', 'span' ); ?> <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-heart"></i></span></a><?php $this->inline( 'note', 'small' ); ?></div>
		</div></div></section>
		<?php
	}
}


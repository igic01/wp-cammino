<?php
/** Elementor widget: homepage newsletter placeholder. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Newsletter extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-newsletter'; }
	public function get_title(): string { return __( 'Cammino – Newsletter', 'cammino' ); }
	public function get_icon(): string { return 'eicon-mail'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Dobré správy rovno do vašej schránky' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Raz za mesiac pošleme výber príbehov, príležitostí a noviniek z Cammina.' );
		$this->add_control( 'placeholder', array( 'label' => __( 'Email placeholder', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'vas@email.sk' ) );
		$this->add_inline_text_control( 'button_label', __( 'Button label', 'cammino' ), 'Chcem novinky', 'text' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$field_id = 'cammino-subscribe-' . $this->get_id();
		?>
		<section class="section news-subscribe home-subscribe"><div class="container"><div class="subscribe-card" data-reveal="scale">
			<div class="subscribe-card__icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></div><div class="subscribe-card__copy"><?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'copy', 'p' ); ?></div>
			<form class="subscribe-form" action="#" method="post" data-newsletter-placeholder><label class="sr-only" for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Your email', 'cammino' ); ?></label><input id="<?php echo esc_attr( $field_id ); ?>" type="email" name="email" placeholder="<?php echo esc_attr( (string) $s['placeholder'] ); ?>" required><button class="button button--cream" type="submit"><?php $this->inline( 'button_label', 'span' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button><span class="newsletter-status" role="status" data-newsletter-status></span></form>
		</div></div></section>
		<?php
	}
}


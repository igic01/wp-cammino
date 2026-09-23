<?php
/** Elementor widget: homepage impact story. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Story extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-story'; }
	public function get_title(): string { return __( 'Cammino – Story', 'cammino' ); }
	public function get_icon(): string { return 'eicon-testimonial'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Zo skicára vznikla <em>prvá vlastná výstava</em>' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Nina svoje kresby dlho nikomu neukazovala. Bezpečný priestor, trpezlivá mentorka a skupina rovesníkov jej pomohli veriť vlastnému pohľadu a ukázať svoj talent.' );
		$this->add_inline_text_control( 'tag', __( 'Image label', 'cammino' ), 'Príbeh so skutočným dopadom', 'text' );
		$this->add_inline_text_control( 'button_label', __( 'Button label', 'cammino' ), 'Prečítať celý príbeh', 'text' );
		$this->add_link_control( 'button_link', __( 'Button link', 'cammino' ), home_url( '/pribeh/' ) );
		$this->add_media_control( 'image', __( 'Image', 'cammino' ), NSTARTER_URL . '/assets/images/placeholder.webp' );
		$this->add_control( 'image_alt', array( 'label' => __( 'Image alternative text', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Nina pri práci na svojom tvorivom projekte' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$image = $this->image_url( (array) $s['image'], NSTARTER_URL . '/assets/images/placeholder.webp' );
		?>
		<section class="section story-section" id="stories">
			<div class="story-blob" aria-hidden="true"></div>
			<div class="container story-grid">
				<div class="story-visual" data-reveal="left"><div class="story-image"><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( (string) $s['image_alt'] ); ?>" loading="lazy"></div><div class="quote-mark" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></div><div class="story-tag"><span aria-hidden="true"><i class="fa-solid fa-heart"></i></span> <?php $this->inline( 'tag', 'span' ); ?></div></div>
				<div class="story-copy" data-reveal="right" data-delay="160"><?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'copy', 'p' ); ?><a <?php echo $this->link_attributes( 'story_link', (array) $s['button_link'], 'button button--cream' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'button_label', 'span' ); ?> <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a></div>
			</div>
		</section>
		<?php
	}
}


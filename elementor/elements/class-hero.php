<?php
/** Elementor widget: homepage hero. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Hero extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-hero'; }
	public function get_title(): string { return __( 'Cammino – Hero', 'cammino' ); }
	public function get_icon(): string { return 'eicon-banner'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Priestor, kde sa ľudia spájajú pre <em>pozitívnu zmenu</em>' );
		$this->add_inline_text_control( 'lead', __( 'Introduction', 'cammino' ), 'OZ Cammino prepája vzdelávanie, osobný rozvoj a komunitnú spoluprácu. Vytvárame príležitosti, vďaka ktorým môžu mladí ľudia rozvíjať svoj potenciál a aktívne meniť svoje okolie.' );
		$this->add_inline_text_control( 'primary_label', __( 'Primary button', 'cammino' ), 'Podporte naše aktivity', 'text' );
		$this->add_link_control( 'primary_link', __( 'Primary button link', 'cammino' ), CAMMINO_DONATE_URL );
		$this->add_inline_text_control( 'secondary_label', __( 'Secondary link', 'cammino' ), 'Spoznajte Cammino', 'text' );
		$this->add_link_control( 'secondary_link', __( 'Secondary link target', 'cammino' ), '#about' );
		$this->end_controls_section();

		$this->start_controls_section( 'visual', array( 'label' => __( 'Image and notes', 'cammino' ) ) );
		$this->add_media_control( 'image', __( 'Hero image', 'cammino' ), NSTARTER_URL . '/assets/images/placeholder.webp' );
		$this->add_control( 'image_alt', array( 'label' => __( 'Image alternative text', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Ľudia spolupracujú počas komunitnej aktivity OZ Cammino' ) );
		$this->add_media_control( 'logo', __( 'Note logo', 'cammino' ), NSTARTER_URL . '/assets/logos/new_logo.svg' );
		$this->add_inline_text_control( 'top_note', __( 'Top note', 'cammino' ), '<strong>Rozvíjame potenciál</strong> ľudí aj komunít' );
		$this->add_inline_text_control( 'bottom_note', __( 'Bottom note', 'cammino' ), '<strong>Krok za krokom</strong> Spoločne' );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$image = $this->image_url( (array) $s['image'], NSTARTER_URL . '/assets/images/placeholder.webp' );
		$logo  = $this->image_url( (array) $s['logo'], NSTARTER_URL . '/assets/logos/new_logo.svg' );
		?>
		<section class="hero section" aria-label="<?php esc_attr_e( 'Cammino introduction', 'cammino' ); ?>">
			<div class="container hero-grid">
				<div class="hero-copy" data-reveal="left">
					<?php $this->inline( 'title', 'h1' ); ?>
					<?php $this->inline( 'lead', 'p', 'hero-lead' ); ?>
					<div class="hero-actions">
						<a <?php echo $this->link_attributes( 'primary_link_attr', (array) $s['primary_link'], 'button button--coral' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'primary_label', 'span' ); ?> <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
						<a <?php echo $this->link_attributes( 'secondary_link_attr', (array) $s['secondary_link'], 'text-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'secondary_label', 'span' ); ?> <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
					</div>
				</div>
				<div class="hero-visual" data-hero-reveal data-delay="140">
					<div class="hero-image-wrap"><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( (string) $s['image_alt'] ); ?>" decoding="async"><div class="image-tint" aria-hidden="true"></div></div>
					<div class="hero-note hero-note--top" aria-hidden="true"><img src="<?php echo esc_url( $logo ); ?>" alt=""><?php $this->inline( 'top_note', 'span' ); ?></div>
					<div class="hero-note hero-note--bottom"><img src="<?php echo esc_url( $logo ); ?>" alt=""><?php $this->inline( 'bottom_note', 'span' ); ?></div>
					<svg class="hero-path" viewBox="0 0 170 120" aria-hidden="true"><path d="M8 104C35 110 37 63 70 69C105 75 97 18 154 14" pathLength="1"/><path class="path-arrow" d="m145 5 11 9-12 7" pathLength="1"/></svg>
				</div>
			</div>
		</section>
		<?php
	}
}


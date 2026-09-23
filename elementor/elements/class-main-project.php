<?php
/** Elementor widget: homepage Darujme usmev callout. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Main_Project extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-main-project'; }
	public function get_title(): string { return __( 'Cammino – Main project', 'cammino' ); }
	public function get_icon(): string { return 'eicon-call-to-action'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), '<span>S Darujeme úsmev</span><em>už podporili</em>' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Darujme úsmev je komunitná iniciatíva, ktorá spája ľudí z celého Slovenska, aby prinášali radosť a konkrétnu pomoc deťom a rodinám v náročných životných situáciách.' );
		$this->add_control( 'children_count', array( 'label' => __( 'Children count', 'cammino' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 5361, 'min' => 0 ) );
		$this->add_control( 'families_count', array( 'label' => __( 'Families count', 'cammino' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 1562, 'min' => 0 ) );
		$this->add_inline_text_control( 'button_label', __( 'Button label', 'cammino' ), 'Viac o projekte', 'text' );
		$this->add_link_control( 'button_link', __( 'Button link', 'cammino' ), $this->default_page_url( 'main-project', '/darujme-usmev/' ) );
		$this->add_media_control( 'image', __( 'Image', 'cammino' ), NSTARTER_URL . '/assets/images/logopng-1.webp' );
		$this->add_control( 'image_alt', array( 'label' => __( 'Image alternative text', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Ľudia spojení komunitnými aktivitami OZ Cammino' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$image = $this->image_url( (array) $s['image'], NSTARTER_URL . '/assets/images/logopng-1.webp' );
		?>
		<section class="section community-cta community-cta--main-project">
			<div class="container"><div class="community-cta-card">
				<div class="community-cta-media" data-reveal="left"><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( (string) $s['image_alt'] ); ?>" loading="lazy"></div>
				<div class="community-cta-copy" data-reveal="right" data-delay="120">
					<?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'copy', 'p' ); ?>
					<div class="smile-impact-stats" aria-label="<?php esc_attr_e( 'Project impact', 'cammino' ); ?>"><div class="smile-impact-stat"><span>DETÍ</span><strong><span data-impact-counter><?php echo esc_html( number_format_i18n( (int) $s['children_count'] ) ); ?></span></strong></div><div class="smile-impact-stat"><span>RODÍN</span><strong><span data-impact-counter><?php echo esc_html( number_format_i18n( (int) $s['families_count'] ) ); ?></span></strong></div></div>
					<a <?php echo $this->link_attributes( 'main_project_link', (array) $s['button_link'], 'button button--coral' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'button_label', 'span' ); ?> <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
				</div><div class="community-cta-shape" aria-hidden="true"></div>
			</div></div>
		</section>
		<?php
	}
}


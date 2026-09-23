<?php
/** Elementor widget: homepage involvement callout. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Involvement extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-involvement'; }
	public function get_title(): string { return __( 'Cammino – Get involved', 'cammino' ); }
	public function get_icon(): string { return 'eicon-heart-o'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Staňte sa súčasťou <em>dobrej zmeny</em>' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Darujte svoj čas, spojte s nami svoju organizáciu alebo podporte pomoc tam, kde je najviac potrebná.' );
		$this->add_inline_text_control( 'button_label', __( 'Button label', 'cammino' ), 'Chcem sa zapojiť', 'text' );
		$this->add_link_control( 'button_link', __( 'Button link', 'cammino' ), $this->default_page_url( 'contact2', '/zapojte-sa/' ) );
		$this->end_controls_section();

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'label', array( 'label' => __( 'Label', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'icon', array( 'label' => __( 'Font Awesome class', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->start_controls_section( 'paths_section', array( 'label' => __( 'Ways to participate', 'cammino' ) ) );
		$this->add_control( 'paths', array( 'label' => __( 'Items', 'cammino' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'Dobrovoľníctvo', 'icon' => 'fa-solid fa-people-group' ),
			array( 'label' => 'Partnerstvo', 'icon' => 'fa-solid fa-handshake' ),
			array( 'label' => 'Podpora', 'icon' => 'fa-solid fa-hand-holding-heart' ),
		) ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		?>
		<section class="section home-involvement"><div class="container"><div class="home-involvement__card" data-reveal="scale">
			<div class="home-involvement__copy"><?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'copy', 'p' ); ?><a <?php echo $this->link_attributes( 'involvement_link', (array) $s['button_link'], 'button button--coral' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'button_label', 'span' ); ?> <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a></div>
			<ul class="home-involvement__paths" aria-label="<?php esc_attr_e( 'Ways to participate', 'cammino' ); ?>"><?php foreach ( array_slice( (array) $s['paths'], 0, 3 ) as $item ) : ?><li><span><i class="<?php echo esc_attr( (string) $item['icon'] ); ?>" aria-hidden="true"></i></span><strong><?php echo esc_html( (string) $item['label'] ); ?></strong></li><?php endforeach; ?></ul>
			<div class="home-involvement__shape" aria-hidden="true"></div>
		</div></div></section>
		<?php
	}
}


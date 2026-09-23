<?php
/** Elementor widget: homepage partner logos. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Partners extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-partners'; }
	public function get_title(): string { return __( 'Cammino – Partners', 'cammino' ); }
	public function get_icon(): string { return 'eicon-logo'; }

	private function default_partners(): array {
		$items = array(
			array( 'logo-02-fma.webp', 'FMA', 'http://www.salezianky.sk' ),
			array( 'logo-03-domka-00.webp', 'DOMKA', 'https://www.domka.sk/' ),
			array( 'logo-04-vdb.webp', 'VDB', 'http://www.vdb.sk' ),
			array( 'logo-05-slovo-plus-02.webp', 'Slovo+', 'http://www.slovoplus.sk' ),
			array( 'male_Logo_CB_transparent.webp', 'Exallievi Don Bosca', 'https://www.exallievi.sk/' ),
			array( 'zlate_zrnko_logo-1.webp', 'Zlaté Zrnko', 'https://www.zlatezrnko.sk/' ),
			array( 'logo-06-dm.webp', 'dm', 'https://www.mojadm.sk/' ),
			array( 'PM-Profimarket-logo.webp', 'ProfiMarket', 'https://www.pmprofimarket.sk/' ),
			array( 'logoeasydeal.webp', 'Easy Deal', '' ),
			array( 'logo-07-final-cd.webp', 'FINAL-CD', 'http://www.finalcd.sk' ),
			array( 'GrapePR_CMYK_logo-1536x418.webp', 'Grape PR', 'https://grapepr.sk/' ),
		);
		return array_map( static function ( array $item ): array { return array( 'name' => $item[1], 'image' => array( 'url' => NSTARTER_URL . '/assets/partners/' . $item[0] ), 'link' => array( 'url' => $item[2], 'is_external' => true ) ); }, $items );
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'heading_section', array( 'label' => __( 'Heading', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Partneri', 'text' );
		$this->end_controls_section();
		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'name', array( 'label' => __( 'Partner name', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'image', array( 'label' => __( 'Logo', 'cammino' ), 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$repeater->add_control( 'link', array( 'label' => __( 'Website', 'cammino' ), 'type' => \Elementor\Controls_Manager::URL ) );
		$this->start_controls_section( 'partners_section', array( 'label' => __( 'Partners', 'cammino' ) ) );
		$this->add_control( 'partners', array( 'label' => __( 'Partner logos', 'cammino' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ name }}}', 'default' => $this->default_partners() ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		?>
		<section class="section home-partners"><div class="container"><div class="home-partners__box"><div class="home-partners__heading" data-reveal="up"><?php $this->inline( 'title', 'h2' ); ?></div><ul class="home-partners__grid" aria-label="<?php esc_attr_e( 'Partner logos', 'cammino' ); ?>">
			<?php foreach ( (array) $s['partners'] as $index => $partner ) : $image = $this->image_url( (array) $partner['image'] ); if ( '' === $image ) { continue; } ?>
				<li class="home-partner" data-reveal="up" data-delay="<?php echo esc_attr( (string) ( 35 * ( $index % 8 ) ) ); ?>"><?php if ( ! empty( $partner['link']['url'] ) ) : ?><a <?php echo $this->link_attributes( 'partner_link_' . $index, (array) $partner['link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php endif; ?><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Logo of %s', 'cammino' ), (string) $partner['name'] ) ); ?>" loading="lazy" decoding="async"><?php if ( ! empty( $partner['link']['url'] ) ) : ?></a><?php endif; ?></li>
			<?php endforeach; ?>
		</ul></div></div></section>
		<?php
	}
}


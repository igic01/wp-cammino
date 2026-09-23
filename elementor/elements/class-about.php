<?php
/** Elementor widget: homepage about and values section. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_About extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-about'; }
	public function get_title(): string { return __( 'Cammino – About', 'cammino' ); }
	public function get_icon(): string { return 'eicon-info-box'; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => __( 'Introduction', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Cammino znamená<br><em>cesta</em>' );
		$this->add_inline_text_control( 'copy', __( 'Text', 'cammino' ), 'Sme občianske združenie zamerané na vzdelávanie, osobnostný rozvoj a solidaritu. Prostredníctvom projektov, workshopov a komunitných aktivít spájame ľudí rôzneho veku a pomáhame im aktívne prispievať k pozitívnej zmene.' );
		$this->add_inline_text_control( 'link_label', __( 'Link label', 'cammino' ), 'Viac o našom poslaní', 'text' );
		$this->add_link_control( 'link', __( 'Link', 'cammino' ), $this->default_page_url( 'about-us', '/o-nas/' ) );
		$this->end_controls_section();

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'title', array( 'label' => __( 'Title', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'text', array( 'label' => __( 'Text', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXTAREA ) );
		$repeater->add_control( 'icon', array( 'label' => __( 'Font Awesome class', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$repeater->add_control( 'link_label', array( 'label' => __( 'Link label', 'cammino' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Objaviť viac' ) );
		$repeater->add_control( 'link', array( 'label' => __( 'Link', 'cammino' ), 'type' => \Elementor\Controls_Manager::URL ) );
		$this->start_controls_section( 'cards_section', array( 'label' => __( 'Value cards', 'cammino' ) ) );
		$this->add_control( 'cards', array(
			'label' => __( 'Cards', 'cammino' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'title' => 'Inklúzia', 'text' => 'Vytvárame otvorený priestor a podporujeme rovnaké príležitosti pre všetkých mladých ľudí.', 'icon' => 'fa-solid fa-shield-heart', 'link_label' => 'Objaviť viac', 'link' => array( 'url' => $this->default_page_url( 'about-us', '/o-nas/' ) ) ),
				array( 'title' => 'Vzdelávanie', 'text' => 'Pripravujeme projekty, workshopy a príležitosti, ktoré podporujú osobný rozvoj a praktické zručnosti.', 'icon' => 'fa-solid fa-lightbulb', 'link_label' => 'Objaviť viac', 'link' => array( 'url' => '#events' ) ),
				array( 'title' => 'Spolupráca', 'text' => 'Prepájame ľudí, organizácie a komunity doma aj v zahraničí, aby mohli spoločne tvoriť pozitívne zmeny.', 'icon' => 'fa-solid fa-seedling', 'link_label' => 'Objaviť viac', 'link' => array( 'url' => '#stories' ) ),
			),
		) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$variants = array( 'support-card--sage', 'support-card--apricot', 'support-card--cream' );
		?>
		<section class="section about" id="about">
			<div class="container about-grid">
				<div class="section-heading" data-reveal="left"><?php $this->inline( 'title', 'h2' ); ?></div>
				<div class="about-copy" data-reveal="right" data-delay="100"><?php $this->inline( 'copy', 'p' ); ?><a <?php echo $this->link_attributes( 'about_link', (array) $s['link'], 'text-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'link_label', 'span' ); ?> <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a></div>
			</div>
			<div class="container support-grid">
				<?php foreach ( array_slice( (array) $s['cards'], 0, 3 ) as $index => $card ) : ?>
					<article class="support-card <?php echo esc_attr( $variants[ $index ] ?? 'support-card--cream' ); ?>" data-reveal="up" data-delay="<?php echo esc_attr( (string) ( 120 * $index ) ); ?>">
						<span class="support-number"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span><div class="support-icon" aria-hidden="true"><i class="<?php echo esc_attr( (string) $card['icon'] ); ?>"></i></div>
						<h3><?php echo esc_html( (string) $card['title'] ); ?></h3><p><?php echo esc_html( (string) $card['text'] ); ?></p>
						<a <?php echo $this->link_attributes( 'card_link_' . $index, (array) $card['link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( (string) $card['link_label'] ); ?> <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}


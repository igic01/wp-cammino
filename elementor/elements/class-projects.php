<?php
/** Elementor widget: selected homepage projects. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Projects extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-projects'; }
	public function get_title(): string { return __( 'Cammino – Projects', 'cammino' ); }
	public function get_icon(): string { return 'eicon-posts-grid'; }

	protected function register_controls(): void {
		$options = array();
		$defaults = array();
		if ( function_exists( 'cammino_get_project_picker_posts' ) ) {
			foreach ( cammino_get_project_picker_posts() as $post ) {
				$options[ (string) $post->ID ] = get_the_title( $post );
				if ( count( $defaults ) < 3 ) { $defaults[] = (string) $post->ID; }
			}
		}
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Projekty, ktoré menia <em>možnosti na realitu</em>' );
		$this->add_inline_text_control( 'intro', __( 'Introduction', 'cammino' ), 'Vybrané iniciatívy, na ktorých práve pracujeme.' );
		$this->add_inline_text_control( 'link_label', __( 'Archive link label', 'cammino' ), 'Všetky projekty', 'text' );
		$this->add_link_control( 'link', __( 'Archive link', 'cammino' ), $this->default_page_url( 'projects', '/projekty/' ) );
		$this->add_control( 'project_ids', array( 'label' => __( 'Selected projects (maximum 3)', 'cammino' ), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => $options, 'default' => $defaults, 'label_block' => true ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$ids = array_slice( array_map( 'absint', (array) $s['project_ids'] ), 0, 3 );
		?>
		<section class="section home-projects">
			<div class="container">
				<div class="section-topline home-projects__heading" data-reveal="up"><div><?php $this->inline( 'title', 'h2' ); ?><?php $this->inline( 'intro', 'p' ); ?></div><a <?php echo $this->link_attributes( 'projects_link', (array) $s['link'], 'text-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'link_label', 'span' ); ?> <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a></div>
				<div class="home-projects__grid">
					<?php
					$html = function_exists( 'cammino_render_home_projects' ) ? cammino_render_home_projects( array( 'ids' => $ids ) ) : '';
					echo '' !== $html ? $html : '<p>' . esc_html__( 'Select published Project posts in this widget.', 'cammino' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</section>
		<?php
	}
}


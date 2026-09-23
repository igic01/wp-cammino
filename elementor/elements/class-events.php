<?php
/** Elementor widget: selected homepage events. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Cammino_Elementor_Events extends Cammino_Elementor_Home_Widget {
	public function get_name(): string { return 'cammino-home-events'; }
	public function get_title(): string { return __( 'Cammino – Events', 'cammino' ); }
	public function get_icon(): string { return 'eicon-calendar'; }

	protected function register_controls(): void {
		$options = array();
		$defaults = array();
		if ( function_exists( 'cammino_get_event_picker_posts' ) ) {
			foreach ( cammino_get_event_picker_posts() as $post ) {
				$options[ (string) $post->ID ] = get_the_title( $post );
				if ( count( $defaults ) < 4 ) { $defaults[] = (string) $post->ID; }
			}
		}
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'cammino' ) ) );
		$this->add_inline_text_control( 'title', __( 'Heading', 'cammino' ), 'Stretnutia, ktoré nás <em>spájajú</em>' );
		$this->add_inline_text_control( 'link_label', __( 'Archive link label', 'cammino' ), 'Všetky podujatia', 'text' );
		$this->add_link_control( 'link', __( 'Archive link', 'cammino' ), $this->default_page_url( 'events', '/podujatia/' ) );
		$this->add_control( 'event_ids', array( 'label' => __( 'Selected events (maximum 4)', 'cammino' ), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => $options, 'default' => $defaults, 'label_block' => true ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$ids = array_slice( array_map( 'absint', (array) $s['event_ids'] ), 0, 4 );
		?>
		<section class="section events" id="events">
			<div class="container">
				<div class="section-topline" data-reveal="up"><div><?php $this->inline( 'title', 'h2' ); ?></div><a <?php echo $this->link_attributes( 'events_link', (array) $s['link'], 'text-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php $this->inline( 'link_label', 'span' ); ?> <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a></div>
				<div class="event-grid">
					<?php
					$html = function_exists( 'cammino_render_home_events' ) ? cammino_render_home_events( array( 'ids' => $ids ) ) : '';
					echo '' !== $html ? $html : '<p>' . esc_html__( 'Select published Event posts in this widget.', 'cammino' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</section>
		<?php
	}
}


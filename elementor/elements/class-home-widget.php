<?php
/** Shared helpers for Cammino Elementor homepage widgets. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Cammino_Elementor_Home_Widget extends \Elementor\Widget_Base {
	public function get_categories(): array {
		return array( 'cammino-home' );
	}

	public function get_style_depends(): array {
		return array( 'cammino-elementor-home-bridge' );
	}

	public function get_script_depends(): array {
		return array( 'cammino-elementor-home' );
	}

	public function get_icon(): string {
		return 'eicon-site-identity';
	}

	protected function add_inline_text_control( string $name, string $label, string $default, string $type = 'textarea' ): void {
		$control_type = 'text' === $type ? \Elementor\Controls_Manager::TEXT : \Elementor\Controls_Manager::TEXTAREA;
		$this->add_control(
			$name,
			array(
				'label'       => $label,
				'type'        => $control_type,
				'default'     => $default,
				'label_block' => true,
			)
		);
	}

	protected function add_link_control( string $name, string $label, string $default = '' ): void {
		$this->add_control(
			$name,
			array(
				'label'       => $label,
				'type'        => \Elementor\Controls_Manager::URL,
				'default'     => array( 'url' => $default ),
				'placeholder' => 'https://',
			)
		);
	}

	protected function add_media_control( string $name, string $label, string $default ): void {
		$this->add_control(
			$name,
			array(
				'label'   => $label,
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array( 'url' => $default ),
			)
		);
	}

	protected function inline( string $name, string $tag, string $class = '' ): void {
		$settings = $this->get_settings_for_display();
		$this->add_render_attribute( $name, 'class', $class );
		$this->add_inline_editing_attributes( $name, 'basic' );
		printf(
			'<%1$s %2$s>%3$s</%1$s>',
			tag_escape( $tag ),
			$this->get_render_attribute_string( $name ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_kses_post( (string) ( $settings[ $name ] ?? '' ) )
		);
	}

	protected function link_attributes( string $name, array $link, string $class = '' ): string {
		if ( '' !== $class ) {
			$this->add_render_attribute( $name, 'class', $class );
		}
		$this->add_link_attributes( $name, $link );
		return $this->get_render_attribute_string( $name );
	}

	protected function image_url( array $media, string $fallback = '' ): string {
		if ( ! empty( $media['id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $media['id'], 'full' );
			if ( is_string( $url ) ) {
				return $url;
			}
		}
		return ! empty( $media['url'] ) ? (string) $media['url'] : $fallback;
	}

	protected function default_page_url( string $slug, string $path ): string {
		return function_exists( 'nstarter_get_source_page_url' )
			? nstarter_get_source_page_url( $slug, $path )
			: home_url( $path );
	}
}

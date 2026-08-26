<?php
/**
 * Editable header markup used by snapshot source templates.
 *
 * Expected optional variables:
 * - $nstarter_brand_name
 * - $nstarter_primary_cta_label
 * - $nstarter_primary_cta_url
 *
 * @package NStarter
 */

$nstarter_brand_name        = isset( $nstarter_brand_name ) ? $nstarter_brand_name : get_bloginfo( 'name' );
$nstarter_primary_cta_label = isset( $nstarter_primary_cta_label ) ? $nstarter_primary_cta_label : __( 'Start a project', 'nstarter' );
$nstarter_primary_cta_url   = isset( $nstarter_primary_cta_url ) ? $nstarter_primary_cta_url : '#contact';
?>
<header class="test-header" data-test-header>
    <div class="test-shell test-header__inner">
        <a class="test-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $nstarter_brand_name ); ?> home">
            <span class="test-logo__mark" aria-hidden="true">N</span>
            <span><?php echo esc_html( $nstarter_brand_name ); ?></span>
        </a>

        <button class="test-menu-toggle" type="button" aria-expanded="false" aria-controls="test-primary-menu" data-test-menu-toggle>
            <span></span><span></span>
            <span class="screen-reader-text"><?php esc_html_e( 'Toggle navigation', 'nstarter' ); ?></span>
        </button>

        <nav class="test-navigation" id="test-primary-menu" aria-label="<?php esc_attr_e( 'Primary navigation', 'nstarter' ); ?>" data-test-menu>
            <a href="#work"><?php esc_html_e( 'Work', 'nstarter' ); ?></a>
            <a href="#services"><?php esc_html_e( 'Services', 'nstarter' ); ?></a>
            <a href="#journal"><?php esc_html_e( 'Journal', 'nstarter' ); ?></a>
            <a href="#about"><?php esc_html_e( 'About', 'nstarter' ); ?></a>
        </nav>

        <a class="test-button test-button--small test-header__cta" href="<?php echo esc_url( $nstarter_primary_cta_url ); ?>">
            <?php echo esc_html( $nstarter_primary_cta_label ); ?>
            <span aria-hidden="true">↗</span>
        </a>
    </div>
</header>

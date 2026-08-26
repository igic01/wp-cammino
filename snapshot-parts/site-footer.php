<?php
/**
 * Editable footer markup used by snapshot source templates.
 *
 * Expected optional variable: $nstarter_brand_name.
 *
 * @package NStarter
 */

$nstarter_brand_name = isset( $nstarter_brand_name ) ? $nstarter_brand_name : get_bloginfo( 'name' );
?>
<footer class="test-footer">
    <div class="test-shell">
        <div class="test-footer__lead">
            <div>
                <p class="test-eyebrow test-eyebrow--light"><?php esc_html_e( 'Have a project in mind?', 'nstarter' ); ?></p>
                <h2><?php esc_html_e( 'Let’s make something memorable.', 'nstarter' ); ?></h2>
            </div>
            <a class="test-footer__circle" href="mailto:hello@example.com" aria-label="<?php esc_attr_e( 'Email us', 'nstarter' ); ?>">↗</a>
        </div>

        <div class="test-footer__grid">
            <div class="test-footer__brand">
                <a class="test-logo test-logo--light" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <span class="test-logo__mark" aria-hidden="true">N</span>
                    <span><?php echo esc_html( $nstarter_brand_name ); ?></span>
                </a>
                <p><?php esc_html_e( 'Independent digital studio creating expressive brands and useful experiences.', 'nstarter' ); ?></p>
            </div>

            <div class="test-footer__column">
                <h3><?php esc_html_e( 'Explore', 'nstarter' ); ?></h3>
                <a href="#work"><?php esc_html_e( 'Selected work', 'nstarter' ); ?></a>
                <a href="#services"><?php esc_html_e( 'Services', 'nstarter' ); ?></a>
                <a href="#journal"><?php esc_html_e( 'Journal', 'nstarter' ); ?></a>
            </div>

            <div class="test-footer__column">
                <h3><?php esc_html_e( 'Social', 'nstarter' ); ?></h3>
                <a href="#">Instagram</a>
                <a href="#">LinkedIn</a>
                <a href="#">Behance</a>
            </div>

            <div class="test-footer__column">
                <h3><?php esc_html_e( 'Visit', 'nstarter' ); ?></h3>
                <p>Budapest, Hungary<br>47.4979° N, 19.0402° E</p>
            </div>
        </div>

        <div class="test-footer__bottom">
            <p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $nstarter_brand_name ); ?></p>
            <a href="#"><?php esc_html_e( 'Privacy', 'nstarter' ); ?></a>
            <a href="#"><?php esc_html_e( 'Credits', 'nstarter' ); ?></a>
            <a class="test-footer__top" href="#top"><?php esc_html_e( 'Back to top', 'nstarter' ); ?> ↑</a>
        </div>
    </div>
</footer>

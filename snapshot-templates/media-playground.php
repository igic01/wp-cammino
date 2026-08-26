<?php
/**
 * Snapshot Name: Media Playground
 *
 * A focused page for testing image/video replacement and video options.
 *
 * @package NStarter
 */

$nstarter_page_title = $nstarter_page instanceof WP_Post && get_the_title( $nstarter_page )
    ? get_the_title( $nstarter_page )
    : __( 'Media Playground', 'nstarter' );
$nstarter_image      = NSTARTER_URL . '/assets/images/starter-placeholder.svg';
$nstarter_videos     = get_posts(
    array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => 'video',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    )
);
$nstarter_video_url  = $nstarter_videos ? wp_get_attachment_url( $nstarter_videos[0] ) : '';
?>
<style>
<?php include NSTARTER_PATH . '/snapshot-assets/media-playground.css'; ?>
</style>

<div class="media-playground">
    <header class="media-header">
        <div class="media-shell media-header__inner">
            <a class="media-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">NStarter</a>
            <span>Image ↔ Video testing</span>
        </div>
    </header>

    <main>
        <section class="media-hero">
            <div class="media-shell">
                <p class="media-kicker">Media mode playground</p>
                <h1><?php echo esc_html( $nstarter_page_title ); ?></h1>
                <p class="media-hero__copy">Switch the editor to Media editing, then click either preview. Choose an image or video from WordPress—even when the original element is the opposite type.</p>
            </div>
        </section>

        <section class="media-shell media-grid">
            <article class="media-card">
                <div class="media-card__preview">
                    <img src="<?php echo esc_url( $nstarter_image ); ?>" alt="Replaceable image example">
                </div>
                <div class="media-card__content">
                    <span>Starting element · Image</span>
                    <h2>Image test</h2>
                    <p>Replace this with another image, or choose a video to convert the element and configure its playback.</p>
                </div>
            </article>

            <article class="media-card">
                <div class="media-card__preview">
                    <video controls muted playsinline preload="metadata" poster="<?php echo esc_url( $nstarter_image ); ?>"<?php echo $nstarter_video_url ? ' src="' . esc_url( $nstarter_video_url ) . '"' : ''; ?>></video>
                </div>
                <div class="media-card__content">
                    <span>Starting element · Video</span>
                    <h2>Video test</h2>
                    <p>Replace this with another video and choose Auto start or Muted, or select an image to convert it.</p>
                </div>
            </article>
        </section>

        <section class="media-interactions">
            <div class="media-shell">
                <div class="media-section-heading">
                    <h2>Interaction mode tests.</h2>
                    <p>These controls should do nothing while Text or Media editing is active. Switch to Interaction mode to use them.</p>
                </div>

                <div class="interaction-grid">
                    <article class="interaction-card">
                        <h3>Expandable section</h3>
                        <p>Open and close these rows. Their temporary state will not be written into the saved snapshot.</p>
                        <div class="interaction-accordion" data-client-accordion>
                            <div class="interaction-accordion__item">
                                <button class="interaction-accordion__button" type="button" aria-expanded="false">What happens when this opens?<span>+</span></button>
                                <div class="interaction-accordion__panel" hidden>The section expands in Interaction mode. Saving the page restores its original closed state.</div>
                            </div>
                            <div class="interaction-accordion__item">
                                <button class="interaction-accordion__button" type="button" aria-expanded="false">Can this text be edited?<span>+</span></button>
                                <div class="interaction-accordion__panel" hidden>Yes. Switch back to Text editing and edit the visible text directly.</div>
                            </div>
                        </div>
                    </article>

                    <article class="interaction-card">
                        <h3>Popup test</h3>
                        <p>This button opens a modal only in Interaction mode. Text mode blocks the click so editing is not interrupted.</p>
                        <button class="interaction-button" type="button" data-client-popup-open>Open popup</button>
                    </article>
                </div>
            </div>
        </section>

        <section class="media-live">
            <div class="media-shell">
                <div class="media-section-heading">
                    <h2>Locked live section.</h2>
                    <p>These values come from the current WordPress database. They update without changing or resaving the surrounding page.</p>
                </div>
                <?php nstarter_live_section( 'site_metrics' ); ?>
            </div>
        </section>

        <section class="media-instructions">
            <div class="media-shell media-instructions__grid">
                <div><p class="media-kicker">Test checklist</p><h2>Try every direction.</h2></div>
                <div class="media-steps">
                    <div class="media-step"><strong>01</strong><p>Click the image and replace it with a different image.</p></div>
                    <div class="media-step"><strong>02</strong><p>Click the image and replace it with a video. Test Auto start, Muted, and Show controls.</p></div>
                    <div class="media-step"><strong>03</strong><p>Click the video and replace it with an image.</p></div>
                    <div class="media-step"><strong>04</strong><p>Click the video and choose another video with different playback options.</p></div>
                    <div class="media-step"><strong>05</strong><p>Save, reload the page, and verify the selected media and video attributes remain.</p></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="media-footer"><div class="media-shell">NStarter Media Playground</div></footer>

    <dialog class="client-popup" data-client-popup data-nstarter-transient-attributes="open">
        <h2>Popup works</h2>
        <p>This modal demonstrates why Interaction mode exists. Close it, switch to Text editing, and the same trigger will no longer activate.</p>
        <button class="interaction-button" type="button" data-client-popup-close>Close popup</button>
    </dialog>

    <script>
<?php include NSTARTER_PATH . '/snapshot-assets/media-playground.js'; ?>
    </script>
</div>

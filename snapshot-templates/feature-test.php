<?php
/**
 * Snapshot Name: Feature test — HTML merge and history
 *
 * @package Cammino
 */

// Change the default from 1 to 2 to simulate deploying a redesigned template.
// The constant allows automated tests to exercise both layouts without file edits.
$cammino_test_layout = defined( 'CAMMINO_FEATURE_TEST_LAYOUT' ) ? CAMMINO_FEATURE_TEST_LAYOUT : 1;
$cammino_test_heading = 2 === $cammino_test_layout ? 'h2' : 'h1';
?>
<main id="main-content" class="feature-test feature-test--layout-<?php echo (int) $cammino_test_layout; ?>">
    <section id="feature-test-hero" class="feature-test__panel">
        <?php if ( 2 === $cammino_test_layout ) : ?><div class="feature-test__new-wrapper"><?php endif; ?>
        <<?php echo $cammino_test_heading; ?> id="feature-test-title">A page for testing saved content</<?php echo $cammino_test_heading; ?>>
        <p id="feature-test-intro">Edit this introduction, save it, and restore an earlier version through History.</p>
        <a id="feature-test-link" class="button button--coral" href="https://example.com/">Edit this link and its destination</a>
        <?php if ( 2 === $cammino_test_layout ) : ?></div><?php endif; ?>
        <img id="feature-test-image" src="<?php echo esc_url( NSTARTER_URL . '/assets/images/placeholder.webp' ); ?>" alt="Replace this test image" width="1200" height="900">
    </section>
    <section id="feature-test-anonymous" class="feature-test__panel">
        <h2 id="feature-test-anonymous-title">Tags without IDs or classes</h2>
        <?php if ( 2 === $cammino_test_layout ) : ?><div class="feature-test__new-wrapper"><?php endif; ?>
        <p>First anonymous paragraph. Edit this text to test matching by position.</p>
        <p>Second anonymous paragraph. Keep the same number of paragraphs when changing their wrappers.</p>
        <?php if ( 2 === $cammino_test_layout ) : ?></div><?php endif; ?>
    </section>
    <section id="feature-test-cards" class="feature-test__panel" <?php
    nstarter_variable_section_attributes( 'feature_test_count', array(
        'label' => 'Number of test cards', 'type' => 'number', 'control' => 'repeat',
        'value' => 2, 'min' => 0, 'max' => 20, 'step' => 1, 'token' => 'card',
    ) );
    ?>>
        <h2 id="feature-test-cards-title">Repeatable content</h2>
        <div class="feature-test__cards" data-nstarter-variable-items>
            <article class="feature-test__card" data-nstarter-variable-item><h3>First test card</h3><p>Edit this card, then change the number of cards.</p></article>
            <article class="feature-test__card" data-nstarter-variable-item><h3>Second test card</h3><p>Saved cards should keep their content after a layout update.</p></article>
        </div>
        <template data-nstarter-variable-template><article class="feature-test__card" data-nstarter-variable-item><h3>Test card {{card}}</h3><p>Add your card content here.</p></article></template>
    </section>
    <?php if ( 2 === $cammino_test_layout ) : ?>
    <section id="feature-test-new-section" class="feature-test__panel"><h2 id="feature-test-new-title">New section from layout two</h2><p id="feature-test-new-copy">This newly added content should appear alongside your existing edits.</p></section>
    <?php endif; ?>
</main>

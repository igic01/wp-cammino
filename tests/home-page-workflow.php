<?php
/** Run with `php tests/home-page-workflow.php`. No WordPress/database changes. */

$root     = dirname( __DIR__ );
$template = file_get_contents( $root . '/snapshot-templates/home.php' );
$styles   = file_get_contents( $root . '/assets/css/pages/home.css' );
$script   = file_get_contents( $root . '/assets/js/pages/home.js' );
$editor   = file_get_contents( $root . '/assets/js/editor.js' );
$variables = file_get_contents( $root . '/inc/variable-sections.php' );
$checks   = 0;

function home_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$community_start = strpos( $template, '<section class="section community-cta"' );
$community_end   = false !== $community_start ? strpos( $template, '</section>', $community_start ) : false;
$community       = false !== $community_start && false !== $community_end
	? substr( $template, $community_start, $community_end - $community_start )
	: '';

home_expect( ! str_contains( $template, 'story-section smile-impact' ), 'The former standalone Darujme úsmev story section is removed.' );
home_expect( ! str_contains( $template, 'smile-impact-title' ), 'The removed section heading is not retained.' );
home_expect( 1 === substr_count( $template, 'class="smile-impact-stats"' ), 'The impact statistics render only once.' );
home_expect( str_contains( $community, 'class="smile-impact-stats"' ), 'Impact statistics are inside the community CTA.' );
home_expect( str_contains( $community, "'darujme_usmev_deti'" ) && str_contains( $community, "'darujme_usmev_rodiny'" ), 'Both editable counters moved together.' );
home_expect( strpos( $community, 'class="smile-impact-stats"' ) < strpos( $community, '>Viac o projekte <' ), 'The statistics appear above the project button.' );
home_expect( 2 === substr_count( $community, 'data-impact-counter' ), 'Both public counter animations remain connected.' );
home_expect( 2 === substr_count( $community, 'data-nstarter-variable-output' ), 'Both visual-editor values remain connected.' );
home_expect( ! str_contains( $styles, '.story-section.smile-impact' ) && ! str_contains( $styles, '.smile-impact .story-' ), 'Styles for the removed standalone section are cleaned up.' );
home_expect( str_contains( $styles, '.community-cta .smile-impact-stat:hover' ), 'The relocated cards have CTA-specific interaction styling.' );
home_expect( str_contains( $script, '[data-impact-counter]' ), 'The homepage counter script still discovers the relocated values.' );
home_expect( str_contains( $template, "'home_selected_projects'" ) && str_contains( $template, "'control' => 'project-picker'" ), 'The homepage exposes a dedicated selected-projects variable.' );
home_expect( str_contains( $template, "'max'     => 3" ), 'The homepage project picker is limited to three projects.' );
home_expect( str_contains( $template, "nstarter_live_section( 'cammino_home_projects'" ), 'Selected project cards are rendered from a live section.' );
home_expect( str_contains( $editor, 'populateProjectPicker' ) && str_contains( $editor, 'updateProjectPickerSection' ), 'The visual editor supports selecting and previewing existing projects.' );
home_expect( str_contains( $variables, "'projects'" ) && str_contains( $variables, "'project-picker'" ), 'The variable schema accepts project-picker controls.' );
home_expect( str_contains( $styles, '.home-projects__grid > .nstarter-live-section' ) && str_contains( $styles, '.home-project-card' ), 'The selected-project section and cards are styled.' );
home_expect( str_contains( $template, "nstarter_get_source_page_url( 'contact2', '/zapojte-sa/' )" ), 'The involvement CTA resolves the page using the contact2 template.' );
home_expect( str_contains( $template, 'class="section home-involvement"' ) && str_contains( $template, 'Chcem sa zapojiť' ), 'The homepage includes a clear involvement call to action.' );
home_expect( str_contains( $template, 'Dobrovoľníctvo' ) && str_contains( $template, 'Partnerstvo' ) && str_contains( $template, 'Podpora' ), 'The involvement CTA presents all three participation paths.' );
home_expect( str_contains( $styles, '.home-involvement__card' ) && str_contains( $styles, '.home-involvement__paths' ), 'The involvement CTA has responsive homepage styling.' );
home_expect( str_contains( $template, 'class="home-partners__box"' ) && str_contains( $styles, '.home-partners__box' ), 'The partner title and logos are grouped in a distinct visual panel.' );
home_expect( str_contains( $template, '<h2 id="home-partners-title">Partneri</h2>' ), 'The partner section uses the concise requested title.' );
home_expect( str_contains( $template, "'home_partner_count'" ) && str_contains( $template, "'label'   => 'Počet partnerov'" ), 'The visual editor exposes the partner count.' );
home_expect( str_contains( $template, 'data-nstarter-variable-items' ) && str_contains( $template, '<template data-nstarter-variable-template>' ), 'Partner logos support repeat resizing.' );
home_expect( str_contains( $styles, '.home-partners[data-nstarter-variable-value="0"]' ), 'A partner section with no logos is hidden outside the editor.' );
home_expect( str_contains( $styles, 'background: #fff;' ) && str_contains( $styles, 'border: 1px solid rgba(100, 54, 75, 0.24);' ), 'The compact partner panel is white with a visible border.' );
home_expect( ! str_contains( $styles, '.home-partners > .container' ), 'The partner panel keeps the standard homepage container width.' );
home_expect( str_contains( $styles, 'justify-content: center;' ) && str_contains( $styles, 'text-align: center;' ), 'The partner heading and wrapped logo rows are centered.' );
home_expect( str_contains( $styles, '--home-section-space: clamp(2.25rem, 4vw, 3.5rem);' ) && str_contains( $styles, '#main-content > .section:not(.hero)' ), 'All reorderable homepage sections use one shared compact vertical spacing value.' );
home_expect( ! str_contains( $styles, '.story-section + .donate' ), 'Donation spacing no longer depends on the preceding section.' );
home_expect( ! str_contains( $template, 'class="story-person"' ) && ! str_contains( $styles, '.story-person' ), 'The Nina attribution element and its unused styles are removed.' );
home_expect( str_contains( $template, '<main id="main-content" class="home-gradient-concept">' ), 'The homepage opts into the isolated gradient concept.' );
home_expect( str_contains( $styles, '--home-pink:' ) && str_contains( $styles, '--home-blue:' ), 'The homepage defines its gradient concept color system.' );
home_expect( substr_count( $styles, 'linear-gradient(' ) >= 15, 'The concept uses layered highlights and gradients throughout the page.' );
home_expect( str_contains( $styles, '.home-gradient-concept .hero-path' ) && str_contains( $styles, '.home-gradient-concept .story-blob' ) && str_contains( $styles, 'display: none;' ), 'Large circle and path decorations are removed from the concept.' );

$ordered_sections = array(
	'class="section about"',
	'class="section home-projects"',
	'class="section events"',
	'class="section story-section"',
	'class="section community-cta"',
	'class="section home-involvement"',
	'class="section donate"',
	'class="section home-partners"',
	'class="section news-subscribe home-subscribe"',
);
$last_section_position = -1;
foreach ( $ordered_sections as $section_marker ) {
	$section_position = strpos( $template, $section_marker );
	home_expect( false !== $section_position && $section_position > $last_section_position, 'Homepage sections follow the intended narrative order.' );
	$last_section_position = $section_position;
}

echo "Passed $checks home page checks.\n";

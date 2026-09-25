<?php
/** Run with `php tests/html-merge-workflow.php`. No WordPress/database changes. */
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/inc/html-merge.php';
$checks = 0;
function merge_expect( bool $condition, string $message ): void {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function merged( string $fresh, string $old ): array {
	return nstarter_merge_page_html( $fresh, $old );
}

$result = merged(
	'<main id="main-content"><section id="hero" class="new"><div class="wrapper"><h2 id="title" class="large">Default</h2><p>Description</p><a id="cta" class="button new" href="/default" target="_blank">Default label<span class="arrow" aria-hidden="true">NEW ICON</span></a></div></section></main>',
	'<main id="main-content"><section id="hero" class="old"><h1 id="title">Život &amp; úsmev</h1><p>Client description</p><a id="cta" class="button" href="/support">Client label<span class="arrow" aria-hidden="true">OLD ICON</span></a></section></main>'
);
merge_expect( str_contains( $result['html'], '<h2 id="title" class="large">Život &amp; úsmev</h2>' ), 'Stable IDs retain Unicode text when a heading type and its wrappers change.' );
merge_expect( str_contains( $result['html'], '<p>Client description</p>' ), 'Anonymous content matches inside a new wrapper.' );
merge_expect( str_contains( $result['html'], 'href="/support" target="_blank"' ) && str_contains( $result['html'], 'Client label' ), 'Saved link values retain new presentation and behavior attributes.' );
merge_expect( str_contains( $result['html'], 'NEW ICON' ) && ! str_contains( $result['html'], 'OLD ICON' ), 'Decorative markup comes from the new template.' );
merge_expect( ! $result['conflicts'], 'A supported layout update merges without conflicts.' );

$result = merged( '<section id="copy"><p>New first</p><p>New second</p></section>', '<section id="copy"><p>Client paragraph</p></section>' );
merge_expect( str_contains( $result['html'], 'New first' ) && ! str_contains( $result['html'], 'Client paragraph' ) && count( $result['conflicts'] ) === 1, 'An inserted anonymous paragraph is flagged rather than receiving the wrong saved content.' );
$result = merged( '<section id="copy"><div><p>New intro</p></div><div><p>New body</p></div></section>', '<section id="copy"><p>Client body</p></section>' );
merge_expect( ! str_contains( $result['html'], 'Client body' ) && ! empty( $result['conflicts'] ), 'Inserted wrappers cannot disguise ambiguous anonymous paragraphs.' );
$result = merged( '<section id="copy"><p>Default</p></section>', '<section id="copy"><div><p>Client body</p></div></section>' );
merge_expect( str_contains( $result['html'], '<p>Client body</p>' ) && ! $result['conflicts'], 'Removing an anonymous wrapper retains its uniquely matched content.' );
$result = merged( '<section id="copy"><p>One</p><p>Two</p></section>', '<section id="copy"><p>Saved one</p><p>Saved two</p></section>' );
merge_expect( str_contains( $result['html'], '<p>Saved one</p><p>Saved two</p>' ) && ! $result['conflicts'], 'Unchanged anonymous sibling counts preserve positional content.' );
$result = merged( '<h2 id="title">New default</h2><p id="new">New content</p>', '<h1 id="title"></h1><p id="removed">Removed client content</p>' );
merge_expect( str_contains( $result['html'], '<h2 id="title"></h2>' ), 'An intentionally empty field stays empty.' );
merge_expect( str_contains( $result['html'], 'New content' ) && count( $result['conflicts'] ) === 1, 'New content survives and removed saved content is reported.' );
$result = merged( '<h2 id="title"><span class="new">Default</span></h2>', '<h1 id="title">Client title</h1>' );
merge_expect( str_contains( $result['html'], '<span class="new">Client title</span>' ) && ! $result['conflicts'], 'A new inline wrapper retains the outer element’s saved text.' );
$result = merged( '<a id="cta" href="/new">Default<span aria-hidden="true">NEW ICON</span></a>', '<a id="cta" href="/client"><span aria-hidden="true">OLD ICON</span></a>' );
merge_expect( str_contains( $result['html'], 'href="/client"><span aria-hidden="true">NEW ICON</span>' ) && ! $result['conflicts'], 'Empty button labels stay empty while their decorative icons update.' );
$result = merged( '<p id="copy" class="new">Default</p>', '<p id="copy">Client <strong>bold</strong> <a href="/story" onclick="bad()">story</a></p>' );
merge_expect( str_contains( $result['html'], '<strong>bold</strong>' ) && str_contains( $result['html'], 'href="/story"' ) && ! str_contains( $result['html'], 'onclick' ), 'Client inline formatting is preserved without layout or event attributes.' );
$result = merged( '<a id="cta" href="/safe">Default</a>', '<a id="cta" href="javascript:bad()">Saved</a>' );
merge_expect( str_contains( $result['html'], 'href="/safe"' ), 'Unsafe saved link protocols cannot replace template destinations.' );
$result = merged( '<h2 id="duplicate">New</h2>', '<h1 id="duplicate">Old one</h1><h1 id="duplicate">Old two</h1>' );
merge_expect( str_contains( $result['html'], '>New</h2>' ) && ! empty( $result['conflicts'] ), 'Duplicate IDs are not silently matched.' );

$result = merged( '<img id="cover" class="new" width="800" src="/default.jpg" srcset="/default-large.jpg 2x" alt="Default">', '<img id="cover" class="old" src="/client.jpg" alt="" data-attachment-id="123">' );
merge_expect( str_contains( $result['html'], 'src="/client.jpg"' ) && str_contains( $result['html'], 'alt=""' ) && ! str_contains( $result['html'], 'srcset' ) && str_contains( $result['html'], 'width="800"' ), 'Saved media removes stale responsive sources but keeps current dimensions.' );
$result = merged( '<img id="cover" class="new" width="800" src="/default.jpg">', '<video id="cover" class="old" controls><source src="/client.mp4" type="video/mp4"></video>' );
merge_expect( str_contains( $result['html'], '<video id="cover" class="new" width="800" controls' ) && str_contains( $result['html'], 'src="/client.mp4"' ) && ! $result['conflicts'], 'Image-to-video replacements retain the new template presentation.' );
$result = merged( '<video id="cover" autoplay muted controls src="/new.mp4"></video>', '<video id="cover" controls src="/saved.mp4"></video>' );
merge_expect( ! str_contains( $result['html'], 'autoplay' ) && ! str_contains( $result['html'], 'muted' ), 'Turning off video options persists across template updates.' );
$result = merged( '<picture id="cover"><source srcset="/new.webp"><img class="new" src="/new.jpg" width="800"></picture>', '<img id="cover" src="/client.jpg" alt="Client">' );
merge_expect( str_contains( $result['html'], '<picture id="cover">' ) && str_contains( $result['html'], 'src="/client.jpg"' ) && ! str_contains( $result['html'], 'srcset' ) && ! $result['conflicts'], 'A new picture wrapper uses saved image content without a default source overriding it.' );

$result = merged(
	'<section data-nstarter-variable-section="projects" data-nstarter-variable-value="1" data-nstarter-variable-max="3"><div data-nstarter-live-section="cards" data-nstarter-live-args="new"></div></section>',
	'<section data-nstarter-variable-section="projects" data-nstarter-variable-value="2,3" data-nstarter-variable-max="99"><div data-nstarter-live-section="cards" data-nstarter-live-args="saved"><p>Stale card</p></div></section>'
);
merge_expect( str_contains( $result['html'], 'data-nstarter-variable-value="2,3"' ) && str_contains( $result['html'], 'data-nstarter-variable-max="3"' ) && str_contains( $result['html'], 'data-nstarter-live-args="saved"' ) && ! str_contains( $result['html'], 'Stale card' ), 'Picker selections survive while live HTML and variable definitions stay current.' );
$result = merged(
	'<section data-nstarter-variable-section="stories" data-nstarter-variable-value="0"><div data-nstarter-variable-items></div><template data-nstarter-variable-template><article class="new-card" data-nstarter-variable-item><h3>Default</h3><p>Default description</p><span aria-hidden="true">New decoration</span></article></template></section>',
	'<section data-nstarter-variable-section="stories" data-nstarter-variable-value="2"><div data-nstarter-variable-items><article class="old-card" data-nstarter-variable-item><h3>Story B</h3><p>Client B</p></article><article class="old-card" data-nstarter-variable-item><h3>Story A</h3><p>Client A</p></article></div><template data-nstarter-variable-template><article>Old prototype</article></template></section>'
);
merge_expect( substr_count( $result['html'], 'class="new-card"' ) === 3 && str_contains( $result['html'], '<h3>Story B</h3>' ) && str_contains( $result['html'], '<p>Client A</p>' ) && ! str_contains( $result['html'], 'old-card' ), 'Repeated items use new markup and keep their count, content and order.' );
merge_expect( ! $result['conflicts'], 'Repeated items do not create false unmatched-content warnings.' );
$result = merged(
	'<main id="main-content"><section id="a"><p>New A</p></section><section id="new"><p>Brand new</p></section><section id="b"><p>New B</p></section></main>',
	'<main id="main-content"><section id="b"><p>Saved B</p></section><section id="a"><p>Saved A</p></section></main>'
);
merge_expect( strpos( $result['html'], 'id="b"' ) < strpos( $result['html'], 'id="new"' ) && strpos( $result['html'], 'id="new"' ) < strpos( $result['html'], 'id="a"' ), 'Saved section ordering leaves new sections in their template slots.' );
merge_expect( str_contains( $result['html'], 'Saved A' ) && str_contains( $result['html'], 'Saved B' ) && str_contains( $result['html'], 'Brand new' ), 'Reordered sections receive their own saved content.' );

$story_text = 'Blížili sa Vianoce a v malom domčeku prala mamička posteľnú bielizeň ručne v umývadle – nová práčka bola pre nich v tom čase nedosiahnuteľným luxusom. Keď sme im ju nečakane priniesli, statný otec rodiny celú návštevu prečkal mlčaním. Vo dverách mi však zovrel ruku do svojej mozoľnatej dlane s takou nekontrolovateľnou silou, že v tom jedinom stisku bolo povedané všetko. Čo sa stalo o päť minút neskôr, nám ukázalo, že človek zvyčajne vycíti, komu v jeho okolí na ňom záleží. Prečítajte si tento príťažlivý príbeh.';
$result = merged(
	'<section id="stories"><h2>Title</h2><p>Default</p></section>',
	'<section id="stories"><h2>Title</h2><p><span class="clipboard-format" style="font-size:20px" onclick="bad()">' . $story_text . '</span><br><span class="clipboard-format">Ďalší riadok.</span></p></section>'
);
merge_expect( str_contains( $result['html'], $story_text ) && str_contains( $result['html'], 'Ďalší riadok.' ) && ! $result['conflicts'], 'Pasted Unicode paragraphs with formatted spans and line breaks merge without losing text or creating conflicts.' );
merge_expect( ! str_contains( $result['html'], 'clipboard-format' ) && ! str_contains( $result['html'], 'onclick' ) && ! str_contains( $result['html'], 'font-size:20px' ), 'Clipboard styling and event attributes are discarded during inline merging.' );

$result = merged(
	'<section data-nstarter-variable-section="stories"><div data-nstarter-variable-items><article class="impact-story" data-nstarter-variable-item><h2>Default title</h2><p>Default text</p></article></div><template data-nstarter-variable-template><article class="impact-story" data-nstarter-variable-item><h2>Title</h2><p>Text</p></article></template></section>',
	'<section data-nstarter-variable-section="stories"><div data-nstarter-variable-items><article class="impact-story" data-nstarter-variable-item><h2>Saved title</h2><p>Blížili sa Vianoce a v malom domčeku.</p><p>práčka bola pre nich nedosiahnuteľná.</p><div style="font-size:20px">Prečítajte si tento príťažlivý príbeh.</div></article></div></section>'
);
merge_expect( ! $result['conflicts'] && str_contains( $result['html'], 'Saved title' ) && str_contains( $result['html'], 'práčka bola pre nich nedosiahnuteľná.' ) && str_contains( $result['html'], 'Prečítajte si tento príťažlivý príbeh.' ), 'Impact story descriptions retain pasted paragraph blocks and browser-created divs without layout conflicts.' );
merge_expect( ! str_contains( $result['html'], 'font-size:20px' ), 'Multi-paragraph story text uses template styling.' );

$story_template = '<section data-nstarter-variable-section="stories"><div data-nstarter-variable-items><article class="impact-story" data-nstarter-variable-item><h2>Title</h2><p>Default</p></article></div><template data-nstarter-variable-template><article class="impact-story" data-nstarter-variable-item><h2>Title</h2><p>Default</p></article></template></section>';
$result = merged( $story_template, str_replace( '<p>Default</p>', '<p><span style="font-family:Arial">' . $story_text . '</span><div><div>Ďalší riadok.</div></div></p>', $story_template ) );
merge_expect( ! $result['conflicts'] && str_contains( $result['html'], $story_text ) && str_contains( $result['html'], 'Ďalší riadok.' ), 'The complete supplied story survives nested paragraph markup created by multiline pasting.' );

foreach ( array( 'div', 'section', 'article' ) as $container ) {
	$result = merged(
		'<' . $container . ' id="copy"><h2 id="heading">Default</h2><p id="description" class="new-copy">Default</p><a id="cta" href="/default">Default</a></' . $container . '>',
		'<' . $container . ' id="copy"><h2 id="heading">Saved heading</h2><p id="description">Blížili sa Vianoce.</p><div><span style="color:red">Práčka bola darom.</span></div><p>Prečítajte si tento príťažlivý príbeh.</p><a id="cta" href="/saved">Saved button</a></' . $container . '>'
	);
	merge_expect( ! $result['conflicts'] && str_contains( $result['html'], 'Práčka bola darom.' ) && str_contains( $result['html'], 'Prečítajte si tento príťažlivý príbeh.' ) && str_contains( $result['html'], 'Saved heading' ) && str_contains( $result['html'], 'href="/saved"' ), 'Multiline paragraphs merge in generic ' . $container . ' layouts while preserving surrounding content.' );
	merge_expect( substr_count( $result['html'], 'id="description"' ) === 1 && substr_count( $result['html'], 'class="new-copy"' ) === 3 && ! str_contains( $result['html'], 'color:red' ), 'Expanded paragraphs retain template styling without duplicating IDs in ' . $container . ' layouts.' );
}
$result = merged(
	'<section id="copy"><p>First default</p><p>Second default</p></section>',
	'<section id="copy"><p>' . $story_text . '</p><p>Second saved paragraph</p></section>'
);
merge_expect( ! $result['conflicts'] && str_contains( $result['html'], $story_text ) && str_contains( $result['html'], 'Second saved paragraph' ), 'Long pasted prose stays correctly associated on layouts with multiple paragraphs.' );

foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) as $tag ) {
	$result = merged(
		'<' . $tag . ' id="title" class="new-heading"><span class="title-copy">Default</span></' . $tag . '>',
		'<' . $tag . ' id="title">Prvý riadok<div style="font-size:20px"><strong>Druhý riadok</strong></div></' . $tag . '>'
	);
	merge_expect( ! $result['conflicts'] && str_contains( $result['html'], 'Prvý riadok<br><strong>Druhý riadok</strong>' ) && str_contains( $result['html'], 'class="title-copy"' ) && str_contains( $result['html'], 'class="new-heading"' ) && ! str_contains( $result['html'], 'font-size:20px' ), 'Enter-created title blocks merge as line breaks while retaining template wrappers for ' . $tag . '.' );
	$result = merged( '<' . $tag . ' id="title"><span class="title-copy">Default</span></' . $tag . '>', '<' . $tag . ' id="title">Prvý riadok<br>Druhý riadok</' . $tag . '>' );
	merge_expect( ! $result['conflicts'] && str_contains( $result['html'], '<span class="title-copy">Prvý riadok<br>Druhý riadok</span>' ), 'Explicit title line breaks survive template merging for ' . $tag . '.' );
}

$people_template = '<div class="info-card" data-nstarter-variable-section="about_people_count"><div class="contact-people" data-nstarter-variable-items><article class="contact-person" data-nstarter-variable-item><div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user"></i></div><div><span>Role</span><h3>Name</h3><a href="mailto:default@example.com">default@example.com</a></div></article></div><template data-nstarter-variable-template><article class="contact-person" data-nstarter-variable-item><div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user"></i></div><div><span>Role</span><h3>Name</h3><a href="mailto:default@example.com">default@example.com</a></div></article></template></div>';
$legacy_people = '<div class="info-card"><div class="contact-people"><article class="contact-person"><div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></div><div><span>Director</span><h3>Alex</h3><a href="mailto:alex@example.com">alex@example.com</a></div></article></div></div>';
$result = merged( $people_template, $legacy_people );
merge_expect( str_contains( $result['html'], 'Alex' ) && str_contains( $result['html'], 'fa-folder-open' ), 'Existing About Us people migrate into the repeatable section.' );
$saved_people = str_replace( '<i class="fa-solid fa-user"></i>', '<img src="https://example.com/person.jpg" alt="">', $people_template );
$result = merged( $people_template, $saved_people );
merge_expect( str_contains( $result['html'], 'src="https://example.com/person.jpg"' ), 'A chosen team photo survives template merging.' );
$first_person = '<article class="contact-person" data-nstarter-variable-item><div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div><div><span>Director</span><h3>Robert</h3><a href="mailto:robert@example.com">robert@example.com</a></div></article>';
$second_person = '<article class="contact-person" data-nstarter-variable-item><div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></div><div><span>Manager</span><h3>Alexandra</h3><a href="mailto:alexandra@example.com">alexandra@example.com</a></div></article>';
$two_people_template = '<div class="info-card" data-nstarter-variable-section="about_people_count"><div class="contact-people" data-nstarter-variable-items>' . $first_person . $second_person . '</div><template data-nstarter-variable-template>' . $first_person . '</template></div>';
$saved_two_people = str_replace( array( 'fa-user-tie', 'fa-folder-open' ), array( 'fa-heart', 'fa-graduation-cap' ), $two_people_template );
$result = merged( $two_people_template, $saved_two_people );
merge_expect( str_contains( $result['html'], 'fa-heart' ) && str_contains( $result['html'], 'fa-graduation-cap' ) && ! str_contains( $result['html'], 'fa-folder-open' ), 'The first two saved team icons appear on the published page.' );

echo "Passed $checks HTML merge checks.\n";

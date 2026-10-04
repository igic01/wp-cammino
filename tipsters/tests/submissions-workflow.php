<?php
/** Run only against the same opted-in disposable installation as accounts-workflow.php. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_file( $argv[1] ) ) {
	fwrite( STDERR, "Pass wp-load.php from a disposable WordPress test installation.\n" ); exit( 1 );
}
ob_start();
$_SERVER['HTTP_HOST'] = '127.0.0.1:8765';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $argv[1];
if ( ! defined( 'CAMMINO_TIPSTERS_TEST_INSTALLATION' ) || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION || ! defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) ) {
	fwrite( STDERR, "The installation has not opted into destructive test fixtures.\n" ); exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/user.php';
add_filter( 'pre_wp_mail', '__return_true' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$prefix = 'submit_test_' . bin2hex( random_bytes( 4 ) );
$users = $files = array();
$failure = null;
$checks = 0;
function expect_submission( bool $condition, string $message ): void {
	global $checks; ++$checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function upload_fixture( string $name, string $content ): array {
	global $files;
	$path = tempnam( sys_get_temp_dir(), 'cammino-upload-' );
	file_put_contents( $path, $content ); $files[] = $path;
	return array( 'name' => $name, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK );
}
try {
	wp_set_current_user( $admin->ID );
	$first = cammino_tipsters_create_account( $prefix, 'Abcd123!' ); $users[] = $first;
	$second = cammino_tipsters_create_account( $prefix . '_other', 'Abcd123!' ); $users[] = $second;
	$input = array( 'title' => 'Private submission', 'short_description' => "Short line one\nLine two", 'long_description' => 'Long text with \\ backslash and "quotation".' );
	expect_submission( is_wp_error( cammino_tipsters_create_tip( $input, array(), cammino_tipsters_submission_token() ) ), 'Administrator cannot submit as a tipster.' );
	wp_set_current_user( $first );
	$token = cammino_tipsters_submission_token();
	expect_submission( cammino_tipsters_valid_submission_token( $token ) && ! cammino_tipsters_valid_submission_token( $token . 'bad' ), 'Signed token detects tampering.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_tip( array() ) ), 'All text fields required.' );
	foreach ( array( 'title' => 200, 'short_description' => 1000, 'long_description' => 20000 ) as $field => $max ) {
		$oversized = $input; $oversized[ $field ] = str_repeat( 'é', $max + 1 );
		expect_submission( is_wp_error( cammino_tipsters_validate_tip( $oversized ) ), 'Character limit enforced: ' . $field );
	}
	$dirty = $input; $dirty['title'] = '<script>alert(1)</script>Clean title'; $dirty['long_description'] = 'Line one' . "\n" . '<b>Line two</b>';
	$clean = cammino_tipsters_validate_tip( $dirty );
	expect_submission( 'Clean title' === $clean['title'] && "Line one\nLine two" === $clean['long_description'], 'HTML stripped and line breaks retained.' );
	$tip = cammino_tipsters_create_tip( $input + array( 'owner' => $second, 'status' => 'approved' ), array(), $token );
	expect_submission( is_int( $tip ) && $tip > 0, 'Submission saves without requiring files or email.' );
	$post = get_post( $tip );
	expect_submission( (int) $post->post_author === $first && 'private' === $post->post_status && 'submitted' === get_post_meta( $tip, '_cammino_tip_status', true ), 'Server enforces owner, private storage, and submitted status.' );
	expect_submission( $post->post_excerpt === $input['short_description'] && $post->post_content === $input['long_description'], 'Descriptions preserve line breaks, quotes, and backslashes.' );
	expect_submission( ! cammino_tipsters_can_edit_tip( $tip ), 'New submission is locked.' );
	expect_submission( $tip === cammino_tipsters_create_tip( $input, array(), $token ), 'Retry of the same form returns the same tip.' );
	$lock = cammino_tipsters_write_lock( $first );
	expect_submission( ! is_wp_error( $lock ) && is_wp_error( cammino_tipsters_create_tip( $input, array(), cammino_tipsters_submission_token() ) ), 'Simultaneous account submission is blocked.' );
	wp_cache_delete( $lock['key'], 'options' );
	wp_cache_set( 'notoptions', array( $lock['key'] => true ), 'options' );
	expect_submission( is_wp_error( cammino_tipsters_write_lock( $first ) ), 'A stale missing-option cache cannot overwrite a live lock.' );
	wp_cache_delete( 'notoptions', 'options' );
	expect_submission( get_option( $lock['key'] ) === $lock['value'], 'Rejected lock acquisition preserves the original request token.' );
	wp_set_current_user( $admin->ID );
	expect_submission( is_wp_error( cammino_tipsters_set_enabled( $first, false ) ) && is_wp_error( cammino_tipsters_delete_account( $first ) ), 'Disable and purge cannot race a submission.' );
	cammino_tipsters_release_lock( $lock );
	wp_set_current_user( $first );
	for ( $i = 0; $i < 11; ++$i ) {
		$new = cammino_tipsters_create_tip( array_merge( $input, array( 'title' => 'Tip ' . $i ) ), array(), cammino_tipsters_submission_token() );
		expect_submission( is_int( $new ), 'Owner can submit multiple tips: ' . $i );
	}
	$page_one = cammino_tipsters_own_tips(); $page_two = cammino_tipsters_own_tips( 2 );
	expect_submission( 12 === (int) $page_one->found_posts && 10 === count( $page_one->posts ) && 2 === count( $page_two->posts ), 'Dashboard paginates twelve submissions.' );
	expect_submission( ! array_intersect( wp_list_pluck( $page_one->posts, 'ID' ), wp_list_pluck( $page_two->posts, 'ID' ) ), 'Pagination has no repeated tips.' );
	wp_set_current_user( $second );
	expect_submission( ! cammino_tipsters_valid_submission_token( $token ) && ! cammino_tipsters_can_read_tip( $tip ) && ! cammino_tipsters_own_tips()->posts, 'Other owner cannot reuse token, read tip, or discover it in dashboard.' );
	wp_set_current_user( 0 );
	expect_submission( ! cammino_tipsters_own_tips()->posts && ! cammino_tipsters_can_read_tip( $tip ), 'Anonymous queries and tip reads expose nothing.' );
	wp_set_current_user( $first );
	$before = cammino_tipsters_own_tips()->found_posts;
	$retry_token = cammino_tipsters_submission_token();
	$fail_save = static function ( $check, $object_id, $key, $value ) {
		return '_cammino_tip_status' === $key && 'submitted' === $value ? false : $check;
	};
	add_filter( 'update_post_metadata', $fail_save, 10, 4 );
	try {
		expect_submission( is_wp_error( cammino_tipsters_create_tip( $input, array(), $retry_token ) ), 'Failed final save returns an error.' );
	} finally { remove_filter( 'update_post_metadata', $fail_save, 10 ); }
	expect_submission( $before === cammino_tipsters_own_tips()->found_posts && $before === count( cammino_tipsters_account_records( $first )['tips'] ), 'Failed submission removes partial database records.' );
	expect_submission( false === get_option( 'cammino_tipster_write_' . $first ), 'Failed submission releases the account lock.' );
	$retried = cammino_tipsters_create_tip( $input, array(), $retry_token );
	expect_submission( is_int( $retried ) && wp_delete_post( $retried, true ), 'Same form can be retried successfully after a failed save.' );
	$fake = upload_fixture( 'pretend.pdf', 'Plain text is not a PDF.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( $fake ) ), 'Renaming text to PDF is rejected.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( upload_fixture( 'file.exe', 'MZ fake executable' ) ) ), 'Executable extension rejected.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( upload_fixture( 'file.png', '<?php echo "bad";' ) ) ), 'Fake image content rejected.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( upload_fixture( 'empty.pdf', '' ) ) ), 'Empty file rejected.' );
	$pdf = upload_fixture( 'report.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n" );
	expect_submission( ! is_wp_error( cammino_tipsters_validate_upload( $pdf ) ), 'PDF signature and MIME accepted.' );
	$png = upload_fixture( 'image.png', base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jV3sAAAAASUVORK5CYII=' ) );
	expect_submission( ! is_wp_error( cammino_tipsters_validate_upload( $png ) ), 'PNG content accepted.' );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( array_merge( $png, array( 'name' => 'image.jpg' ) ) ) ), 'Valid image with mismatched extension rejected.' );
	$huge = upload_fixture( 'too-large.pdf', '%PDF-' ); $handle = fopen( $huge['tmp_name'], 'ab' ); ftruncate( $handle, cammino_tipsters_upload_limit() + 1 ); fclose( $handle );
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( $huge ) ), 'Hosting-aware size limit enforced.' );
	$docx = upload_fixture( 'document.docx', '' ); $zip = new ZipArchive(); $zip->open( $docx['tmp_name'], ZipArchive::OVERWRITE );
	$zip->addFromString( '[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>' );
	$zip->addFromString( 'word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Test</w:t></w:r></w:p></w:body></w:document>' ); $zip->close();
	expect_submission( ! is_wp_error( cammino_tipsters_validate_upload( $docx ) ), 'Word DOCX package accepted.' );
	$zip->open( $docx['tmp_name'] ); $zip->addFromString( 'word/vbaProject.bin', 'macro' ); $zip->close();
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( $docx ) ), 'Macro package renamed DOCX rejected.' );
	$zip->open( $docx['tmp_name'] ); $zip->deleteName( 'word/vbaProject.bin' ); $zip->deleteName( 'word/document.xml' ); $zip->close();
	expect_submission( is_wp_error( cammino_tipsters_validate_upload( $docx ) ), 'Arbitrary ZIP renamed DOCX rejected.' );
	$multipart = array( 'name' => array_fill( 0, 6, 'report.pdf' ), 'tmp_name' => array_fill( 0, 6, $pdf['tmp_name'] ), 'error' => array_fill( 0, 6, UPLOAD_ERR_OK ), 'size' => array_fill( 0, 6, 50 ), 'type' => array_fill( 0, 6, 'application/pdf' ) );
	expect_submission( is_wp_error( cammino_tipsters_upload_inputs( $multipart ) ), 'Six files rejected.' );
	expect_submission( is_wp_error( cammino_tipsters_upload_inputs( array( 'name' => array( array( 'nested' ) ) ) ) ), 'Malformed nested multipart payload rejected.' );
	$multipart = array_map( static fn( $values ) => array_slice( $values, 0, 1 ), $multipart );
	expect_submission( is_wp_error( cammino_tipsters_create_tip( $input, $multipart, cammino_tipsters_submission_token() ) ) && $before === cammino_tipsters_own_tips()->found_posts, 'Local paths cannot bypass real upload checks; no partial record created.' );
	$private = cammino_tipsters_storage_root(); expect_submission( ! is_wp_error( $private ), 'Test private storage is configured outside served roots.' );
	$file_id = bin2hex( random_bytes( 16 ) ); $relative = $prefix . '.pdf'; $path = $private . $relative;
	copy( $pdf['tmp_name'], $path ); $files[] = $path;
	update_post_meta( $tip, CAMMINO_TIP_FILES_META, array( array( 'id' => $file_id, 'path' => $relative, 'name' => 'report.pdf', 'mime' => 'application/pdf', 'size' => filesize( $path ) ) ) );
	expect_submission( ! is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ), 'Owner resolves authorized private download.' );
	expect_submission( is_wp_error( cammino_tipsters_tip_file( $tip, str_repeat( 'a', 32 ) ) ) && is_wp_error( cammino_tipsters_tip_file( $tip, '../bad' ) ), 'Invalid or altered file IDs rejected.' );
	wp_set_current_user( $second ); expect_submission( is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ), 'Different owner cannot download a known file ID.' );
	wp_set_current_user( 0 ); expect_submission( is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ), 'Anonymous download rejected.' );
	wp_set_current_user( $admin->ID ); expect_submission( ! is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ), 'Administrator can download a private tip file.' );
	expect_submission( ! current_user_can( 'edit_post', $tip ) && ! current_user_can( 'delete_post', $tip ), 'Native editors cannot change or erase private submissions.' );
	$old_docroot = $_SERVER['DOCUMENT_ROOT'] ?? null; $_SERVER['DOCUMENT_ROOT'] = dirname( $private );
	expect_submission( is_wp_error( cammino_tipsters_storage_root() ), 'Storage inside the served root is rejected.' );
	if ( null === $old_docroot ) { unset( $_SERVER['DOCUMENT_ROOT'] ); } else { $_SERVER['DOCUMENT_ROOT'] = $old_docroot; }
	cammino_tipsters_set_enabled( $first, false ); wp_set_current_user( $first );
	expect_submission( ! cammino_tipsters_own_tips()->posts && is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ) && is_wp_error( cammino_tipsters_create_tip( $input, array(), $token ) ), 'Disabled account cannot list, download, or submit.' );
	wp_set_current_user( $admin->ID ); cammino_tipsters_set_enabled( $first, true );
	update_post_meta( $tip, '_cammino_tip_status', 'deleted_by_tipster' ); wp_set_current_user( $first );
	expect_submission( 11 === (int) cammino_tipsters_own_tips()->found_posts && is_wp_error( cammino_tipsters_tip_file( $tip, $file_id ) ), 'Soft-deleted tip disappears from owner dashboard and downloads.' );
	wp_set_current_user( $admin->ID );
	expect_submission( true === cammino_tipsters_delete_account( $first ) && ! get_post( $tip ) && ! file_exists( $path ), 'Account purge removes submissions and private file.' );
	$old = get_option( 'permalink_structure' ); update_option( 'permalink_structure', '' );
	expect_submission( str_contains( cammino_tipsters_url( 'new' ), 'cammino_tipsters=new' ) && str_contains( cammino_tipsters_url( 'download', 123, $file_id ), 'cammino_file_id=' . $file_id ), 'Plain-permalink form and download URLs supported.' );
	update_option( 'permalink_structure', $old );
	foreach ( array( 'new', 'tip', 'download' ) as $route ) {
		$GLOBALS['wp_query']->set( 'cammino_tipsters', $route );
		expect_submission( cammino_tipsters_route() === $route && cammino_use_site_shell( '' ) === NSTARTER_PATH . '/tipsters/templates/portal.php', 'Live template selected for private route ' . $route );
	}
} catch ( Throwable $error ) { $failure = $error; }
finally {
	if ( isset( $lock ) && is_array( $lock ) ) { cammino_tipsters_release_lock( $lock ); }
	wp_set_current_user( $admin->ID );
	foreach ( $users as $id ) { if ( get_userdata( $id ) ) { cammino_tipsters_delete_account( $id ); } }
	foreach ( $files as $path ) { if ( is_file( $path ) ) { unlink( $path ); } }
}
$output = ob_get_clean(); if ( $output ) { fwrite( STDERR, $output ); }
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
echo "Passed $checks WordPress submission/upload checks.\n";

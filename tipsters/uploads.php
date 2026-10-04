<?php
/** Private uploads, content validation, and permission-checked downloads. @package Cammino */
defined( 'ABSPATH' ) || exit;

function cammino_tipsters_upload_mimes(): array {
	return array(
		'pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
		'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
}

function cammino_tipsters_upload_limit(): int {
	return min( 10 * MB_IN_BYTES, wp_max_upload_size() );
}

/** Reject known served directories. Additional web-server aliases require host verification. */
function cammino_tipsters_storage_root() {
	$root = defined( 'CAMMINO_TIPSTERS_STORAGE_PATH' ) ? realpath( CAMMINO_TIPSTERS_STORAGE_PATH ) : false;
	if ( ! $root || ! is_dir( $root ) || ! is_writable( $root ) ) {
		return new WP_Error( 'storage_missing', __( 'Prílohy momentálne nie je možné uložiť. Kontaktujte administrátora alebo odošlite tip bez súborov.', 'cammino' ) );
	}
	$root = trailingslashit( wp_normalize_path( $root ) );
	foreach ( array( ABSPATH, WP_CONTENT_DIR, $_SERVER['DOCUMENT_ROOT'] ?? '' ) as $public ) {
		$public = $public ? realpath( $public ) : false;
		if ( $public && 0 === strpos( strtolower( $root ), strtolower( trailingslashit( wp_normalize_path( $public ) ) ) ) ) {
			return new WP_Error( 'public_storage', __( 'Úložisko príloh musí byť mimo verejného webového adresára. Kontaktujte administrátora.', 'cammino' ) );
		}
	}
	return $root;
}

/** Normalize PHP's multipart arrays without trusting names, browser MIME, or sizes. */
function cammino_tipsters_upload_inputs( array $files ) {
	if ( ! $files ) {
		return array();
	}
	foreach ( array( 'name', 'tmp_name', 'error', 'size', 'type' ) as $field ) {
		if ( ! isset( $files[ $field ] ) || ! is_array( $files[ $field ] ) ) {
			return new WP_Error( 'files', __( 'Neplatný zoznam súborov.', 'cammino' ) );
		}
	}
	$result = array();
	foreach ( $files['name'] as $key => $name ) {
		if ( ! is_string( $name ) || ! isset( $files['tmp_name'][ $key ], $files['error'][ $key ] ) || ! is_string( $files['tmp_name'][ $key ] ) || ! is_int( $files['error'][ $key ] ) ) {
			return new WP_Error( 'files', __( 'Neplatný zoznam súborov.', 'cammino' ) );
		}
		if ( UPLOAD_ERR_NO_FILE === $files['error'][ $key ] ) {
			continue;
		}
		$result[] = array( 'name' => $name, 'tmp_name' => $files['tmp_name'][ $key ], 'error' => $files['error'][ $key ] );
	}
	if ( count( $result ) > 5 ) {
		return new WP_Error( 'files', __( 'K jednému tipu môžete priložiť najviac 5 súborov.', 'cammino' ) );
	}
	return $result;
}

/** Inspect local file content; transfer additionally requires is_uploaded_file(). */
function cammino_tipsters_validate_upload( array $file ) {
	if ( UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) ) {
		return new WP_Error( 'files', __( 'Súbor sa nepodarilo nahrať. Skontrolujte jeho veľkosť a vyberte ho znova.', 'cammino' ) );
	}
	$path = $file['tmp_name'] ?? '';
	$name = sanitize_file_name( $file['name'] ?? '' );
	$size = is_file( $path ) ? filesize( $path ) : false;
	if ( ! $size || $size > cammino_tipsters_upload_limit() ) {
		return new WP_Error( 'files', sprintf( __( 'Každý súbor musí byť neprázdny a mať najviac %s.', 'cammino' ), size_format( cammino_tipsters_upload_limit() ) ) );
	}
	if ( ! class_exists( 'finfo' ) ) {
		return new WP_Error( 'files', __( 'Kontrola súborov nie je dostupná. Kontaktujte administrátora.', 'cammino' ) );
	}
	$expected = wp_check_filetype( $name, cammino_tipsters_upload_mimes() );
	$checked = wp_check_filetype_and_ext( $path, $name, cammino_tipsters_upload_mimes() );
	$mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $path );
	$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	$valid = $expected['ext'] && $checked['ext'] === $expected['ext'] && $checked['type'] === $expected['type'] && empty( $checked['proper_filename'] );
	if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
		$valid = $valid && wp_get_image_mime( $path ) === $expected['type'];
	} elseif ( 'pdf' === $ext ) {
		$valid = $valid && 'application/pdf' === $mime && str_starts_with( (string) file_get_contents( $path, false, null, 0, 5 ), '%PDF-' );
	} elseif ( 'doc' === $ext ) {
		$valid = $valid && in_array( $mime, array( 'application/msword', 'application/x-ole-storage', 'application/CDFV2' ), true )
			&& "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1" === file_get_contents( $path, false, null, 0, 8 );
	} elseif ( 'docx' === $ext ) {
		$valid = $valid && cammino_tipsters_valid_docx( $path );
	} else {
		$valid = false;
	}
	if ( ! $valid ) {
		return new WP_Error( 'files', sprintf( __( 'Súbor „%s“ nemá povolený formát alebo jeho obsah nezodpovedá prípone. Povolené: PDF, JPG, PNG, WEBP, DOC a DOCX.', 'cammino' ), $name ) );
	}
	return array( 'name' => $name, 'size' => $size, 'mime' => $expected['type'], 'extension' => $ext, 'tmp_name' => $path );
}

/** A DOCX must be a Word package, not an arbitrary ZIP or macro-enabled document. */
function cammino_tipsters_valid_docx( string $path ): bool {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path, ZipArchive::CHECKCONS ) ) {
		return false;
	}
	try {
		if ( $zip->numFiles > 2000 || false === $zip->locateName( 'word/document.xml' ) ) {
			return false;
		}
		$size = 0;
		for ( $i = 0; $i < $zip->numFiles; ++$i ) {
			$entry = $zip->statIndex( $i );
			$size += $entry['size'];
			if ( $size > 50 * MB_IN_BYTES || preg_match( '~(^/|\\\\|:|(^|/)\.\.(/|$)|vbaProject|\.(exe|com|bat|cmd|ps1|php|js|vbs|dll)$)~i', $entry['name'] ) ) {
				return false;
			}
		}
		$types = $zip->getFromName( '[Content_Types].xml', 65536 );
		return is_string( $types ) && str_contains( $types, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml' ) && ! str_contains( strtolower( $types ), 'macroenabled' );
	} finally {
		$zip->close();
	}
}

function cammino_tipsters_tip_file( int $tip, string $file_id ) {
	if ( ! cammino_tipsters_can_read_tip( $tip ) || ! cammino_tipsters_tip_ready( $tip ) || ! preg_match( '/^[a-f0-9]{32}$/', $file_id ) ) {
		return new WP_Error( 'file_not_found', __( 'Súbor nie je dostupný.', 'cammino' ), array( 'status' => 404 ) );
	}
	foreach ( cammino_tipsters_tip_files( $tip, cammino_tipsters_can_manage() ) as $file ) {
		if ( is_array( $file ) && ( $file['id'] ?? '' ) === $file_id ) {
			$root = cammino_tipsters_storage_root();
			$path = is_wp_error( $root ) ? $root : cammino_tipsters_private_file( $file['path'] );
			if ( is_wp_error( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
				break;
			}
			return array_merge( $file, array( 'absolute_path' => $path ) );
		}
	}
	return new WP_Error( 'file_not_found', __( 'Súbor nie je dostupný.', 'cammino' ), array( 'status' => 404 ) );
}

function cammino_tipsters_download(): void {
	if ( ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
		wp_die( esc_html__( 'Nepodporovaná požiadavka.', 'cammino' ), '', array( 'response' => 405 ) );
	}
	$file = cammino_tipsters_tip_file( absint( get_query_var( 'cammino_tip_id' ) ), (string) get_query_var( 'cammino_file_id' ) );
	if ( is_wp_error( $file ) ) {
		wp_die( esc_html( $file->get_error_message() ), '', array( 'response' => 404 ) );
	}
	$handle = fopen( $file['absolute_path'], 'rb' );
	if ( ! $handle ) {
		wp_die( esc_html__( 'Súbor nie je dostupný.', 'cammino' ), '', array( 'response' => 404 ) );
	}
	$stat = fstat( $handle );
	status_header( 200 );
	header( 'Content-Type: application/octet-stream' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Security-Policy: sandbox' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( remove_accents( $file['name'] ) ) . '"; filename*=UTF-8\'\'' . rawurlencode( $file['name'] ) );
	header( 'Content-Length: ' . $stat['size'] );
	if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		fpassthru( $handle );
	}
	fclose( $handle );
	exit;
}

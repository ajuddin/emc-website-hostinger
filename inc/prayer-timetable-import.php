<?php
/**
 * Masjidbox XLSX prayer timetable importer.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the active prayer data URL, falling back to the bundled timetable.
 *
 * @return string
 */
function emc_prayer_data_url() {
	$import = get_option( 'emc_prayer_timetable_import', array() );

	if ( is_array( $import ) && ! empty( $import['url'] ) && ! empty( $import['file'] ) && file_exists( $import['file'] ) ) {
		return add_query_arg( 'ver', (string) filemtime( $import['file'] ), $import['url'] );
	}

	return EMC_ASSETS . '/js/prayer-data.json';
}

/**
 * Register the importer under Settings.
 */
function emc_prayer_timetable_admin_menu() {
	add_submenu_page(
		null,
		__( 'Prayer Timetable Import', 'emc-theme' ),
		__( 'Prayer Timetable', 'emc-theme' ),
		'manage_options',
		'emc-prayer-timetable',
		'emc_prayer_timetable_admin_page'
	);
}
add_action( 'admin_menu', 'emc_prayer_timetable_admin_menu' );

/**
 * Read a reasonably sized XML member from an XLSX archive.
 *
 * @param ZipArchive $zip  Open XLSX archive.
 * @param string     $path Archive member path.
 * @return DOMDocument|WP_Error
 */
function emc_prayer_xlsx_xml( $zip, $path ) {
	$stat = $zip->statName( $path );
	if ( false === $stat || empty( $stat['size'] ) || $stat['size'] > 12 * MB_IN_BYTES ) {
		return new WP_Error( 'invalid_xlsx_part', sprintf( __( 'The workbook is missing or has an invalid %s section.', 'emc-theme' ), $path ) );
	}

	$contents = $zip->getFromName( $path );
	if ( false === $contents ) {
		return new WP_Error( 'unreadable_xlsx_part', __( 'The workbook could not be read.', 'emc-theme' ) );
	}

	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$loaded   = $document->loadXML( $contents, LIBXML_NONET | LIBXML_COMPACT );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return $loaded ? $document : new WP_Error( 'invalid_xlsx_xml', __( 'The workbook contains invalid spreadsheet data.', 'emc-theme' ) );
}

/**
 * Extract the XLSX shared-string table.
 *
 * @param ZipArchive $zip Open XLSX archive.
 * @return array|WP_Error
 */
function emc_prayer_xlsx_shared_strings( $zip ) {
	$document = emc_prayer_xlsx_xml( $zip, 'xl/sharedStrings.xml' );
	if ( is_wp_error( $document ) ) {
		return $document;
	}

	$xpath   = new DOMXPath( $document );
	$strings = array();
	foreach ( $xpath->query( '//*[local-name()="si"]' ) as $item ) {
		$value = '';
		foreach ( $xpath->query( './/*[local-name()="t"]', $item ) as $text ) {
			$value .= $text->textContent;
		}
		$strings[] = $value;
	}

	return $strings;
}

/**
 * Convert an Excel column reference to a zero-based array index.
 *
 * @param string $reference Cell reference such as AB12.
 * @return int
 */
function emc_prayer_xlsx_column_index( $reference ) {
	preg_match( '/^[A-Z]+/i', $reference, $matches );
	$letters = isset( $matches[0] ) ? strtoupper( $matches[0] ) : 'A';
	$index   = 0;

	for ( $i = 0, $length = strlen( $letters ); $i < $length; $i++ ) {
		$index = ( $index * 26 ) + ( ord( $letters[ $i ] ) - 64 );
	}

	return max( 0, $index - 1 );
}

/**
 * Read worksheet rows into arrays indexed by spreadsheet column.
 *
 * @param ZipArchive $zip            Open XLSX archive.
 * @param string     $path           Worksheet member path.
 * @param array      $shared_strings XLSX shared strings.
 * @return array|WP_Error
 */
function emc_prayer_xlsx_rows( $zip, $path, $shared_strings ) {
	$document = emc_prayer_xlsx_xml( $zip, $path );
	if ( is_wp_error( $document ) ) {
		return $document;
	}

	$xpath = new DOMXPath( $document );
	$rows  = array();

	foreach ( $xpath->query( '//*[local-name()="sheetData"]/*[local-name()="row"]' ) as $row_node ) {
		$row = array();
		foreach ( $xpath->query( './*[local-name()="c"]', $row_node ) as $cell ) {
			$index = emc_prayer_xlsx_column_index( $cell->getAttribute( 'r' ) );
			$type  = $cell->getAttribute( 't' );
			$value = '';
			$nodes = $xpath->query( './*[local-name()="v"]', $cell );

			if ( $nodes->length ) {
				$value = $nodes->item( 0 )->textContent;
				if ( 's' === $type ) {
					$shared_index = (int) $value;
					$value        = $shared_strings[ $shared_index ] ?? '';
				}
			} elseif ( 'inlineStr' === $type ) {
				$text_nodes = $xpath->query( './/*[local-name()="t"]', $cell );
				foreach ( $text_nodes as $text_node ) {
					$value .= $text_node->textContent;
				}
			}

			$row[ $index ] = trim( (string) $value );
		}
		$rows[] = $row;
	}

	return $rows;
}

/**
 * Normalize a Masjidbox date value.
 *
 * @param string $value Spreadsheet value.
 * @return string
 */
function emc_prayer_normalize_date( $value ) {
	$value = trim( (string) $value );

	if ( is_numeric( $value ) && false === strpos( $value, '/' ) ) {
		$timestamp = ( (int) floor( (float) $value ) - 25569 ) * DAY_IN_SECONDS;
		return gmdate( 'd/m/Y', $timestamp );
	}

	$date = DateTime::createFromFormat( '!d/m/Y', $value );
	return $date && $date->format( 'd/m/Y' ) === $value ? $value : '';
}

/**
 * Normalize a Masjidbox prayer time to 24-hour HH:MM.
 *
 * @param string $value Spreadsheet value.
 * @return string
 */
function emc_prayer_normalize_time( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( is_numeric( $value ) && false === strpos( $value, ':' ) ) {
		$minutes = (int) round( ( (float) $value - floor( (float) $value ) ) * 1440 );
		$minutes = $minutes % 1440;
		return sprintf( '%02d:%02d', (int) floor( $minutes / 60 ), $minutes % 60 );
	}

	if ( ! preg_match( '/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches ) ) {
		return '';
	}

	$hour   = (int) $matches[1];
	$minute = (int) $matches[2];
	return $hour <= 23 && $minute <= 59 ? sprintf( '%02d:%02d', $hour, $minute ) : '';
}

/**
 * Turn worksheet rows into date-keyed values.
 *
 * @param array  $rows        Worksheet rows.
 * @param array  $required    Required column names.
 * @param string $sheet_label User-facing sheet name.
 * @param bool   $hijri_only  Whether to return only Hijri values.
 * @return array|WP_Error
 */
function emc_prayer_parse_sheet_rows( $rows, $required, $sheet_label, $hijri_only = false ) {
	if ( empty( $rows[0] ) ) {
		return new WP_Error( 'empty_sheet', sprintf( __( 'The “%s” sheet is empty.', 'emc-theme' ), $sheet_label ) );
	}

	$headers = array();
	foreach ( $rows[0] as $index => $header ) {
		$headers[ strtolower( trim( $header ) ) ] = $index;
	}

	foreach ( $required as $column ) {
		if ( ! isset( $headers[ $column ] ) ) {
			return new WP_Error( 'missing_column', sprintf( __( 'The “%1$s” sheet is missing the “%2$s” column.', 'emc-theme' ), $sheet_label, $column ) );
		}
	}

	$data = array();
	foreach ( array_slice( $rows, 1 ) as $row ) {
		$date = emc_prayer_normalize_date( $row[ $headers['date'] ] ?? '' );
		if ( ! $date ) {
			continue;
		}

		if ( $hijri_only ) {
			$data[ $date ] = sanitize_text_field( $row[ $headers['hijri'] ] ?? '' );
			continue;
		}

		$entry = array();
		foreach ( $required as $column ) {
			if ( 'date' !== $column ) {
				$entry[ $column ] = emc_prayer_normalize_time( $row[ $headers[ $column ] ] ?? '' );
			}
		}
		$data[ $date ] = $entry;
	}

	return $data;
}

/**
 * Parse and validate a Masjidbox prayer XLSX workbook.
 *
 * @param string $file Temporary uploaded file path.
 * @return array|WP_Error Frontend prayer-data entries.
 */
function emc_parse_masjidbox_prayer_xlsx( $file ) {
	if ( ! class_exists( 'ZipArchive' ) || ! class_exists( 'DOMDocument' ) ) {
		return new WP_Error( 'missing_php_extensions', __( 'The server requires the PHP ZIP and DOM extensions to import XLSX files.', 'emc-theme' ) );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $file, ZipArchive::CHECKCONS ) ) {
		return new WP_Error( 'invalid_xlsx', __( 'The uploaded file is not a valid XLSX workbook.', 'emc-theme' ) );
	}

	$workbook = emc_prayer_xlsx_xml( $zip, 'xl/workbook.xml' );
	if ( is_wp_error( $workbook ) ) {
		$zip->close();
		return $workbook;
	}

	$relationships = emc_prayer_xlsx_xml( $zip, 'xl/_rels/workbook.xml.rels' );
	if ( is_wp_error( $relationships ) ) {
		$zip->close();
		return $relationships;
	}

	$relationship_paths = array();
	$relationships_xpath = new DOMXPath( $relationships );
	foreach ( $relationships_xpath->query( '//*[local-name()="Relationship"]' ) as $relationship ) {
		$target = str_replace( '\\', '/', $relationship->getAttribute( 'Target' ) );
		if ( 0 === strpos( $target, 'worksheets/' ) ) {
			$relationship_paths[ $relationship->getAttribute( 'Id' ) ] = 'xl/' . $target;
		}
	}

	$xpath       = new DOMXPath( $workbook );
	$sheet_paths = array();
	foreach ( $xpath->query( '//*[local-name()="sheet"]' ) as $sheet ) {
		$relationship_id = $sheet->getAttributeNS( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id' );
		if ( isset( $relationship_paths[ $relationship_id ] ) ) {
			$sheet_paths[ $sheet->getAttribute( 'name' ) ] = $relationship_paths[ $relationship_id ];
		}
	}

	$required_sheets = array( 'Athan with overwrite', 'Athan without overwrite', 'Iqamah' );
	foreach ( $required_sheets as $required_sheet ) {
		if ( ! isset( $sheet_paths[ $required_sheet ] ) ) {
			$zip->close();
			return new WP_Error( 'missing_sheet', sprintf( __( 'The workbook is missing the required “%s” sheet.', 'emc-theme' ), $required_sheet ) );
		}
	}

	$shared = emc_prayer_xlsx_shared_strings( $zip );
	if ( is_wp_error( $shared ) ) {
		$zip->close();
		return $shared;
	}

	$athan_rows  = emc_prayer_xlsx_rows( $zip, $sheet_paths['Athan with overwrite'], $shared );
	$hijri_rows  = emc_prayer_xlsx_rows( $zip, $sheet_paths['Athan without overwrite'], $shared );
	$iqamah_rows = emc_prayer_xlsx_rows( $zip, $sheet_paths['Iqamah'], $shared );
	$zip->close();

	foreach ( array( $athan_rows, $hijri_rows, $iqamah_rows ) as $rows ) {
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
	}

	$athan_columns  = array( 'date', 'fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah' );
	$iqamah_columns = array( 'date', 'fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah' );
	$athan  = emc_prayer_parse_sheet_rows( $athan_rows, $athan_columns, 'Athan with overwrite' );
	$hijri  = emc_prayer_parse_sheet_rows( $hijri_rows, array( 'date', 'hijri' ), 'Athan without overwrite', true );
	$iqamah = emc_prayer_parse_sheet_rows( $iqamah_rows, $iqamah_columns, 'Iqamah' );

	foreach ( array( $athan, $hijri, $iqamah ) as $parsed ) {
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}
	}

	if ( count( $athan ) < 365 || count( $iqamah ) < 365 ) {
		return new WP_Error( 'insufficient_dates', __( 'The workbook must contain a complete year (at least 365 valid dates) in both Athan and Iqamah sheets.', 'emc-theme' ) );
	}

	$years  = array();
	$output = array();
	foreach ( $athan as $date => $adhan_times ) {
		if ( ! isset( $iqamah[ $date ] ) ) {
			continue;
		}
		$years[] = substr( $date, -4 );
		$output[] = array(
			'date'    => $date,
			'hijri'   => $hijri[ $date ] ?? '',
			'adhan'   => $adhan_times,
			'iqamah'  => $iqamah[ $date ],
		);
	}

	$years = array_values( array_unique( $years ) );
	if ( count( $output ) < 365 || 1 !== count( $years ) ) {
		return new WP_Error( 'invalid_date_range', __( 'A timetable must contain a complete set of matching dates from one calendar year.', 'emc-theme' ) );
	}

	usort(
		$output,
		static function ( $left, $right ) {
			$left_date  = DateTime::createFromFormat( '!d/m/Y', $left['date'] );
			$right_date = DateTime::createFromFormat( '!d/m/Y', $right['date'] );
			return $left_date <=> $right_date;
		}
	);

	return $output;
}

/**
 * Persist generated prayer JSON in WordPress uploads.
 *
 * @param array  $data        Parsed timetable.
 * @param string $source_name Uploaded filename.
 * @return array|WP_Error Import metadata.
 */
function emc_save_prayer_timetable_data( $data, $source_name ) {
	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'uploads_unavailable', $upload['error'] );
	}

	$directory = trailingslashit( $upload['basedir'] ) . 'emc-prayer-times';
	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'directory_failed', __( 'WordPress could not create the prayer timetable upload directory.', 'emc-theme' ) );
	}

	$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json ) {
		return new WP_Error( 'json_failed', __( 'The imported timetable could not be encoded.', 'emc-theme' ) );
	}

	$year     = substr( $data[0]['date'], -4 );
	$filename = wp_unique_filename( $directory, 'prayer-data-' . $year . '-' . gmdate( 'YmdHis' ) . '.json' );
	$file     = trailingslashit( $directory ) . $filename;
	$written  = file_put_contents( $file, $json, LOCK_EX );
	if ( false === $written || strlen( $json ) !== $written ) {
		return new WP_Error( 'write_failed', __( 'WordPress could not save the generated prayer timetable.', 'emc-theme' ) );
	}

	$metadata = array(
		'file'        => $file,
		'url'         => trailingslashit( $upload['baseurl'] ) . 'emc-prayer-times/' . $filename,
		'source'      => sanitize_file_name( $source_name ),
		'year'        => (int) $year,
		'days'        => count( $data ),
		'imported_at' => current_time( 'mysql' ),
	);
	update_option( 'emc_prayer_timetable_import', $metadata, false );

	return $metadata;
}

/**
 * Process an XLSX upload from the timetable settings screen.
 *
 * @return array|WP_Error|null
 */
function emc_handle_prayer_timetable_upload() {
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'POST' !== $request_method || empty( $_POST['emc_prayer_timetable_upload'] ) ) {
		return null;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You do not have permission to import prayer times.', 'emc-theme' ) );
	}
	check_admin_referer( 'emc_prayer_timetable_upload' );

	if ( empty( $_FILES['emc_prayer_xlsx'] ) || ! is_array( $_FILES['emc_prayer_xlsx'] ) ) {
		return new WP_Error( 'missing_upload', __( 'Please choose a Masjidbox XLSX file.', 'emc-theme' ) );
	}

	$file = $_FILES['emc_prayer_xlsx'];
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'upload_failed', __( 'The XLSX upload did not complete. Please try again.', 'emc-theme' ) );
	}

	$name = sanitize_file_name( wp_unslash( $file['name'] ) );
	if ( ! preg_match( '/^masjidbox-athan-(\d{4})\.xlsx$/i', $name, $filename_matches ) ) {
		return new WP_Error( 'invalid_filename', __( 'Use the Masjidbox filename format: masjidbox-athan-YYYY.xlsx.', 'emc-theme' ) );
	}
	$actual_size = file_exists( $file['tmp_name'] ) ? filesize( $file['tmp_name'] ) : false;
	if ( false === $actual_size || $actual_size <= 0 || $actual_size > 10 * MB_IN_BYTES ) {
		return new WP_Error( 'invalid_size', __( 'The XLSX file must be smaller than 10 MB.', 'emc-theme' ) );
	}
	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'invalid_upload', __( 'WordPress could not verify the uploaded file.', 'emc-theme' ) );
	}

	$data = emc_parse_masjidbox_prayer_xlsx( $file['tmp_name'] );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$data_year = substr( $data[0]['date'], -4 );
	if ( $data_year !== $filename_matches[1] ) {
		return new WP_Error( 'year_mismatch', sprintf( __( 'The filename says %1$s, but the workbook contains prayer times for %2$s.', 'emc-theme' ), $filename_matches[1], $data_year ) );
	}

	return emc_save_prayer_timetable_data( $data, $name );
}

/**
 * Render the prayer timetable import page.
 */
function emc_prayer_timetable_admin_page() {
	$result  = emc_handle_prayer_timetable_upload();
	$current = get_option( 'emc_prayer_timetable_import', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Prayer Timetable Import', 'emc-theme' ); ?></h1>

		<?php if ( is_wp_error( $result ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
		<?php elseif ( is_array( $result ) ) : ?>
			<div class="notice notice-success"><p>
				<?php echo esc_html( sprintf( __( 'Prayer timetable updated: %1$d days imported for %2$d.', 'emc-theme' ), $result['days'], $result['year'] ) ); ?>
			</p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Upload the annual Masjidbox workbook to update the sitewide prayer bar, today’s prayer widget, and monthly timetable.', 'emc-theme' ); ?></p>

		<?php if ( ! empty( $current['source'] ) ) : ?>
			<table class="widefat striped" style="max-width:760px;margin:20px 0;">
				<tbody>
					<tr><th><?php esc_html_e( 'Active file', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['source'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Calendar year', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['year'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Dates imported', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['days'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Last imported', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['imported_at'] ); ?></td></tr>
				</tbody>
			</table>
		<?php else : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'The bundled prayer timetable is currently active.', 'emc-theme' ); ?></p></div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'emc_prayer_timetable_upload' ); ?>
			<input type="hidden" name="emc_prayer_timetable_upload" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="emc-prayer-xlsx"><?php esc_html_e( 'Masjidbox XLSX file', 'emc-theme' ); ?></label></th>
					<td>
						<input type="file" id="emc-prayer-xlsx" name="emc_prayer_xlsx" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
						<p class="description"><?php esc_html_e( 'Required filename: masjidbox-athan-YYYY.xlsx. Maximum size: 10 MB.', 'emc-theme' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Upload and Update Timetable', 'emc-theme' ) ); ?>
		</form>
	</div>
	<?php
}

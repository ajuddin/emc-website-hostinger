<?php
/**
 * Prayer timetable importer (Masjidbox XLSX workbooks and CSV files).
 *
 * Supports two modes:
 * - Update: merge selected times (for example only Iqamah Isha) into the
 *   active timetable, leaving everything else untouched.
 * - Replace: swap in a complete calendar year.
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

	// Version the bundled file too, so browsers and page caches pick up theme updates.
	$bundled = EMC_DIR . '/assets/js/prayer-data.json';
	return add_query_arg( 'ver', file_exists( $bundled ) ? (string) filemtime( $bundled ) : EMC_VERSION, EMC_ASSETS . '/js/prayer-data.json' );
}

/**
 * Prayer columns stored for each section.
 *
 * @return array<string,string[]>
 */
function emc_prayer_section_columns() {
	return array(
		'adhan'  => array( 'fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah' ),
		'iqamah' => array( 'fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah' ),
	);
}

/**
 * Load the timetable that is currently live on the website.
 *
 * @return array Date-ordered timetable entries.
 */
function emc_prayer_load_active_data() {
	$import = get_option( 'emc_prayer_timetable_import', array() );
	$file   = ( is_array( $import ) && ! empty( $import['file'] ) && file_exists( $import['file'] ) )
		? $import['file']
		: EMC_DIR . '/assets/js/prayer-data.json';

	$contents = file_exists( $file ) ? file_get_contents( $file ) : false;
	$data     = $contents ? json_decode( $contents, true ) : null;

	return is_array( $data ) ? $data : array();
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
 * Extract the XLSX shared-string table. Workbooks without one are valid.
 *
 * @param ZipArchive $zip Open XLSX archive.
 * @return array|WP_Error
 */
function emc_prayer_xlsx_shared_strings( $zip ) {
	if ( false === $zip->statName( 'xl/sharedStrings.xml' ) ) {
		return array();
	}

	$document = emc_prayer_xlsx_xml( $zip, 'xl/sharedStrings.xml' );
	if ( is_wp_error( $document ) ) {
		return $document;
	}

	$xpath   = new DOMXPath( $document );
	$strings = array();
	foreach ( $xpath->query( '/*[local-name()="sst"]/*[local-name()="si"]' ) as $item ) {
		$value = '';
		foreach ( $xpath->query( './/*[local-name()="t"][not(ancestor::*[local-name()="rPh"])]', $item ) as $text ) {
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
		$row  = array();
		$next = 0;
		foreach ( $xpath->query( './*[local-name()="c"]', $row_node ) as $cell ) {
			// Cell references are optional in the spec; fall back to position.
			$reference = $cell->getAttribute( 'r' );
			$index     = '' !== $reference ? emc_prayer_xlsx_column_index( $reference ) : $next;
			$next      = $index + 1;
			$type      = $cell->getAttribute( 't' );
			$value     = '';
			$nodes     = $xpath->query( './*[local-name()="v"]', $cell );

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
 * Resolve a workbook relationship target to a ZIP member path.
 *
 * Excel writes "worksheets/sheet1.xml"; Masjidbox, Numbers, Google Sheets
 * and other tools often write the absolute "/xl/worksheets/sheet1.xml".
 *
 * @param string $target Relationship Target attribute.
 * @return string
 */
function emc_prayer_xlsx_resolve_target( $target ) {
	$target = str_replace( '\\', '/', trim( $target ) );
	$path   = 0 === strpos( $target, '/' ) ? ltrim( $target, '/' ) : 'xl/' . $target;

	$parts = array();
	foreach ( explode( '/', $path ) as $segment ) {
		if ( '' === $segment || '.' === $segment ) {
			continue;
		}
		if ( '..' === $segment ) {
			array_pop( $parts );
			continue;
		}
		$parts[] = $segment;
	}

	return implode( '/', $parts );
}

/**
 * Normalize a sheet name for tolerant matching.
 *
 * @param string $name Sheet name.
 * @return string
 */
function emc_prayer_normalize_sheet_name( $name ) {
	return strtolower( trim( preg_replace( '/[\s_\-]+/', ' ', (string) $name ) ) );
}

/**
 * List workbook sheets as name => ZIP member path, in workbook order.
 *
 * @param ZipArchive $zip Open XLSX archive.
 * @return array|WP_Error
 */
function emc_prayer_xlsx_sheet_paths( $zip ) {
	$workbook = emc_prayer_xlsx_xml( $zip, 'xl/workbook.xml' );
	if ( is_wp_error( $workbook ) ) {
		return $workbook;
	}

	$relationships = emc_prayer_xlsx_xml( $zip, 'xl/_rels/workbook.xml.rels' );
	if ( is_wp_error( $relationships ) ) {
		return $relationships;
	}

	$relationship_paths  = array();
	$relationships_xpath = new DOMXPath( $relationships );
	foreach ( $relationships_xpath->query( '//*[local-name()="Relationship"]' ) as $relationship ) {
		$type = $relationship->getAttribute( 'Type' );
		$path = emc_prayer_xlsx_resolve_target( $relationship->getAttribute( 'Target' ) );
		if ( '/worksheet' === substr( $type, -10 ) || false !== strpos( $path, '/worksheets/' ) ) {
			$relationship_paths[ $relationship->getAttribute( 'Id' ) ] = $path;
		}
	}

	$xpath       = new DOMXPath( $workbook );
	$sheet_paths = array();
	foreach ( $xpath->query( '//*[local-name()="sheets"]/*[local-name()="sheet"]' ) as $sheet ) {
		// The relationship id is namespaced (transitional or strict OOXML).
		$relationship_id = '';
		foreach ( $sheet->attributes as $attribute ) {
			if ( 'id' === $attribute->localName && $attribute->namespaceURI ) {
				$relationship_id = $attribute->value;
				break;
			}
		}
		if ( isset( $relationship_paths[ $relationship_id ] ) ) {
			$sheet_paths[ $sheet->getAttribute( 'name' ) ] = $relationship_paths[ $relationship_id ];
		}
	}

	return $sheet_paths;
}

/**
 * Normalize a date value to d/m/Y.
 *
 * Accepts Excel serial numbers, UK day-first dates (01/02/2026, 1-2-2026,
 * 1.2.2026) and ISO dates (2026-02-01).
 *
 * @param string $value Spreadsheet value.
 * @return string Empty string when the value is not a valid date.
 */
function emc_prayer_normalize_date( $value ) {
	$value = trim( (string) $value );

	if ( is_numeric( $value ) ) {
		$serial = (int) floor( (float) $value );
		if ( $serial < 20000 || $serial > 80000 ) {
			return '';
		}
		return gmdate( 'd/m/Y', ( $serial - 25569 ) * DAY_IN_SECONDS );
	}

	// Ignore any time part, e.g. "2026-01-01 00:00:00".
	$value = preg_replace( '/[T\s].*$/', '', $value );

	if ( preg_match( '/^(\d{4})[\-\/.](\d{1,2})[\-\/.](\d{1,2})$/', $value, $matches ) ) {
		list( , $year, $month, $day ) = $matches;
	} elseif ( preg_match( '/^(\d{1,2})[\-\/.](\d{1,2})[\-\/.](\d{4})$/', $value, $matches ) ) {
		list( , $day, $month, $year ) = $matches;
	} else {
		return '';
	}

	return checkdate( (int) $month, (int) $day, (int) $year )
		? sprintf( '%02d/%02d/%04d', $day, $month, $year )
		: '';
}

/**
 * Normalize a prayer time to 24-hour HH:MM.
 *
 * @param string $value Spreadsheet value (19:00, 19:00:00, 7:00 PM, 7pm or an Excel time fraction).
 * @return string|false Normalized time, '' for an empty cell, false when invalid.
 */
function emc_prayer_normalize_time( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || '-' === $value ) {
		return '';
	}

	// Excel stores times as a fraction of a day (0.8333 = 20:00), or as a
	// date serial plus fraction. Values like "19.30" fall through to the regex.
	if ( is_numeric( $value ) && ( (float) $value < 1 || (float) $value > 20000 ) ) {
		$minutes = (int) round( ( (float) $value - floor( (float) $value ) ) * 1440 );
		$minutes = $minutes % 1440;
		return sprintf( '%02d:%02d', (int) floor( $minutes / 60 ), $minutes % 60 );
	}

	if ( ! preg_match( '/^(\d{1,2})(?:[:.](\d{2}))?(?::\d{2})?\s*([ap]\.?m\.?)?$/i', $value, $matches ) ) {
		return false;
	}

	$hour     = (int) $matches[1];
	$minute   = isset( $matches[2] ) && '' !== $matches[2] ? (int) $matches[2] : 0;
	$meridiem = isset( $matches[3] ) ? strtolower( $matches[3][0] ) : '';

	if ( '' === $meridiem && ( ! isset( $matches[2] ) || '' === $matches[2] ) ) {
		return false; // A bare number such as "7" is too ambiguous.
	}
	if ( $meridiem ) {
		if ( $hour < 1 || $hour > 12 ) {
			return false;
		}
		$hour = ( 12 === $hour ? 0 : $hour ) + ( 'p' === $meridiem ? 12 : 0 );
	}

	return $hour <= 23 && $minute <= 59 ? sprintf( '%02d:%02d', $hour, $minute ) : false;
}

/**
 * Map a column header to a section and field.
 *
 * Plain prayer names ("isha") use the sheet's default section. Combined
 * files can say which section a column belongs to, e.g. "iqamah_isha",
 * "Isha Iqamah", "adhan fajr" or "Fajr Athan".
 *
 * @param string      $header          Raw header.
 * @param string|null $default_section 'adhan', 'iqamah' or null.
 * @return array|null array( section, field ) or null when not recognised.
 */
function emc_prayer_map_header( $header, $default_section ) {
	$header = strtolower( trim( preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header ) ) );
	$words  = preg_split( '/[\s_\-\/()]+/', $header, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $words ) {
		return null;
	}

	$section_aliases = array(
		'adhan'   => 'adhan',
		'athan'   => 'adhan',
		'azan'    => 'adhan',
		'start'   => 'adhan',
		'begins'  => 'adhan',
		'iqamah'  => 'iqamah',
		'iqama'   => 'iqamah',
		'iqamat'  => 'iqamah',
		'iqamaah' => 'iqamah',
		'jamaat'  => 'iqamah',
		'jamat'   => 'iqamah',
		'jamah'   => 'iqamah',
	);
	$field_aliases   = array(
		'date'    => 'date',
		'day'     => null,
		'hijri'   => 'hijri',
		'fajr'    => 'fajr',
		'fajar'   => 'fajr',
		'sunrise' => 'sunrise',
		'shuruq'  => 'sunrise',
		'shurooq' => 'sunrise',
		'dhuhr'   => 'dhuhr',
		'duhr'    => 'dhuhr',
		'zuhr'    => 'dhuhr',
		'zohr'    => 'dhuhr',
		'thuhr'   => 'dhuhr',
		'asr'     => 'asr',
		'maghrib' => 'maghrib',
		'magrib'  => 'maghrib',
		'isha'    => 'isha',
		'esha'    => 'isha',
		'jumuah'  => 'jumuah',
		'jumah'   => 'jumuah',
		'jummah'  => 'jumuah',
		'jumma'   => 'jumuah',
		'jummuah' => 'jumuah',
		'friday'  => 'jumuah',
	);

	$section = null;
	$field   = null;
	foreach ( $words as $word ) {
		if ( isset( $section_aliases[ $word ] ) ) {
			$section = $section_aliases[ $word ];
		} elseif ( array_key_exists( $word, $field_aliases ) && null === $field ) {
			$field = $field_aliases[ $word ];
		}
	}

	if ( 'date' === $field || 'hijri' === $field ) {
		return array( null, $field );
	}
	if ( ! $field ) {
		return null;
	}

	$section  = $section ?: $default_section;
	$sections = emc_prayer_section_columns();
	if ( ! $section || ! in_array( $field, $sections[ $section ], true ) ) {
		return null;
	}

	return array( $section, $field );
}

/**
 * Parse sheet/CSV rows into section => date => field => time.
 *
 * @param array       $rows            Rows (first non-empty row holds headers).
 * @param string|null $default_section Section for plain prayer-name columns.
 * @param string      $label           User-facing sheet or file name.
 * @return array|WP_Error array( 'adhan' => [], 'iqamah' => [], 'hijri' => [], 'columns' => [] ).
 */
function emc_prayer_parse_rows( $rows, $default_section, $label ) {
	$rows = array_values(
		array_filter(
			$rows,
			static function ( $row ) {
				return '' !== trim( implode( '', array_map( 'strval', (array) $row ) ) );
			}
		)
	);
	if ( empty( $rows[0] ) ) {
		return new WP_Error( 'empty_sheet', sprintf( __( '“%s” is empty.', 'emc-theme' ), $label ) );
	}

	$map         = array();
	$date_column = null;
	$hijri_col   = null;
	$unassigned  = array();
	foreach ( $rows[0] as $index => $header ) {
		$mapped = emc_prayer_map_header( $header, $default_section );
		if ( ! $mapped ) {
			if ( null === $default_section && emc_prayer_map_header( $header, 'adhan' ) ) {
				$unassigned[] = $header;
			}
			continue;
		}
		if ( 'date' === $mapped[1] ) {
			$date_column = null === $date_column ? $index : $date_column;
		} elseif ( 'hijri' === $mapped[1] ) {
			$hijri_col = $index;
		} else {
			$map[ $index ] = $mapped;
		}
	}

	if ( null === $date_column ) {
		return new WP_Error( 'missing_column', sprintf( __( '“%s” needs a “date” column in its first row.', 'emc-theme' ), $label ) );
	}
	if ( $unassigned ) {
		return new WP_Error(
			'ambiguous_columns',
			sprintf(
				/* translators: 1: file name, 2: column list. */
				__( '“%1$s” has prayer columns (%2$s) without saying whether they are Athan or Iqamah times. Choose the timetable type in the form, or name the columns like “iqamah_isha” / “adhan_isha”.', 'emc-theme' ),
				$label,
				implode( ', ', $unassigned )
			)
		);
	}

	$result = array(
		'adhan'   => array(),
		'iqamah'  => array(),
		'hijri'   => array(),
		'columns' => array_values( array_unique( array_map( static function ( $mapped ) { return $mapped[0] . '.' . $mapped[1]; }, $map ) ) ),
	);
	if ( null !== $hijri_col ) {
		$result['columns'][] = 'hijri';
	}

	foreach ( array_slice( $rows, 1 ) as $row_number => $row ) {
		$date = emc_prayer_normalize_date( $row[ $date_column ] ?? '' );
		if ( ! $date ) {
			continue;
		}

		foreach ( $map as $index => $mapped ) {
			$time = emc_prayer_normalize_time( $row[ $index ] ?? '' );
			if ( false === $time ) {
				return new WP_Error(
					'invalid_time',
					sprintf(
						/* translators: 1: value, 2: column header, 3: date, 4: sheet name. */
						__( '“%1$s” in the “%2$s” column for %3$s (%4$s) is not a valid time. Use 24-hour times such as 19:00.', 'emc-theme' ),
						$row[ $index ],
						$rows[0][ $index ],
						$date,
						$label
					)
				);
			}
			$result[ $mapped[0] ][ $date ][ $mapped[1] ] = $time;
		}

		if ( null !== $hijri_col && '' !== trim( (string) ( $row[ $hijri_col ] ?? '' ) ) ) {
			$result['hijri'][ $date ] = sanitize_text_field( $row[ $hijri_col ] );
		}
	}

	return $result;
}

/**
 * Merge one parsed table into another (later values win).
 *
 * @param array $into Parsed data.
 * @param array $from Parsed data.
 * @return array
 */
function emc_prayer_merge_parsed( $into, $from ) {
	foreach ( array( 'adhan', 'iqamah' ) as $section ) {
		foreach ( $from[ $section ] as $date => $times ) {
			$into[ $section ][ $date ] = array_merge( $into[ $section ][ $date ] ?? array(), $times );
		}
	}
	foreach ( $from['hijri'] as $date => $hijri ) {
		$into['hijri'][ $date ] = $hijri;
	}
	$into['columns'] = array_values( array_unique( array_merge( $into['columns'], $from['columns'] ) ) );

	return $into;
}

/**
 * Parse a Masjidbox XLSX workbook (or any workbook with Athan/Iqamah sheets).
 *
 * @param string $file Uploaded file path.
 * @return array|WP_Error Parsed data (see emc_prayer_parse_rows()).
 */
function emc_prayer_parse_xlsx_file( $file ) {
	if ( ! class_exists( 'ZipArchive' ) || ! class_exists( 'DOMDocument' ) ) {
		return new WP_Error( 'missing_php_extensions', __( 'The server requires the PHP ZIP and DOM extensions to import XLSX files. Upload a CSV file instead.', 'emc-theme' ) );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $file ) ) {
		return new WP_Error( 'invalid_xlsx', __( 'The uploaded file is not a valid XLSX workbook. If it is an older .xls file, save it as .xlsx or CSV first.', 'emc-theme' ) );
	}

	$sheet_paths = emc_prayer_xlsx_sheet_paths( $zip );
	if ( is_wp_error( $sheet_paths ) ) {
		$zip->close();
		return $sheet_paths;
	}

	// Which section each recognised sheet holds. "Athan without overwrite"
	// only supplies Hijri dates; times come from "Athan with overwrite".
	$selected = array();
	foreach ( $sheet_paths as $name => $path ) {
		$normalized = emc_prayer_normalize_sheet_name( $name );
		if ( false !== strpos( $normalized, 'without' ) ) {
			$selected[] = array( $name, $path, 'hijri' );
		} elseif ( preg_match( '/iqam|jama?a?t/', $normalized ) ) {
			$selected[] = array( $name, $path, 'iqamah' );
		} elseif ( preg_match( '/athan|adhan|azan|begin|start/', $normalized ) ) {
			$selected[] = array( $name, $path, 'adhan' );
		}
	}

	// A single-sheet workbook with combined columns (iqamah_isha, ...) is fine too.
	if ( ! $selected && 1 === count( $sheet_paths ) ) {
		$selected[] = array( key( $sheet_paths ), reset( $sheet_paths ), null );
	}

	if ( ! $selected ) {
		$zip->close();
		return new WP_Error(
			'missing_sheet',
			sprintf(
				/* translators: %s: sheet names. */
				__( 'No Athan or Iqamah sheet was found. The workbook contains: %s. Name the sheets “Athan with overwrite” and/or “Iqamah”.', 'emc-theme' ),
				$sheet_paths ? implode( ', ', array_keys( $sheet_paths ) ) : __( 'no readable sheets', 'emc-theme' )
			)
		);
	}

	$shared = emc_prayer_xlsx_shared_strings( $zip );
	if ( is_wp_error( $shared ) ) {
		$zip->close();
		return $shared;
	}

	$parsed     = array( 'adhan' => array(), 'iqamah' => array(), 'hijri' => array(), 'columns' => array() );
	$hijri_only = array();
	foreach ( $selected as $sheet ) {
		list( $name, $path, $section ) = $sheet;
		$rows = emc_prayer_xlsx_rows( $zip, $path, $shared );
		if ( is_wp_error( $rows ) ) {
			$zip->close();
			return $rows;
		}

		if ( 'hijri' === $section ) {
			// Only take the Hijri column, ignore the un-overridden times.
			$header_row = $rows[0] ?? array();
			$keep       = array();
			foreach ( $header_row as $index => $header ) {
				$mapped = emc_prayer_map_header( $header, null );
				if ( $mapped && null === $mapped[0] ) {
					$keep[ $index ] = $header;
				}
			}
			$rows = array_map(
				static function ( $row ) use ( $keep ) {
					return array_intersect_key( $row, $keep );
				},
				$rows
			);
			$sheet_data = emc_prayer_parse_rows( $rows, 'adhan', $name );
			if ( ! is_wp_error( $sheet_data ) ) {
				$hijri_only = $sheet_data['hijri'];
			}
			continue;
		}

		$sheet_data = emc_prayer_parse_rows( $rows, $section, $name );
		if ( is_wp_error( $sheet_data ) ) {
			$zip->close();
			return $sheet_data;
		}
		$parsed = emc_prayer_merge_parsed( $parsed, $sheet_data );
	}
	$zip->close();

	// Prefer the Hijri dates from "Athan without overwrite" when present.
	if ( $hijri_only ) {
		$parsed['hijri'] = array_merge( $parsed['hijri'], $hijri_only );
		if ( ! in_array( 'hijri', $parsed['columns'], true ) ) {
			$parsed['columns'][] = 'hijri';
		}
	}

	return $parsed;
}

/**
 * Parse a CSV file.
 *
 * @param string      $file            Uploaded file path.
 * @param string|null $default_section Section for plain prayer-name columns.
 * @param string      $label           User-facing file name.
 * @return array|WP_Error Parsed data (see emc_prayer_parse_rows()).
 */
function emc_prayer_parse_csv_file( $file, $default_section, $label ) {
	$contents = file_get_contents( $file );
	if ( false === $contents || '' === trim( $contents ) ) {
		return new WP_Error( 'empty_csv', __( 'The CSV file is empty.', 'emc-theme' ) );
	}
	if ( false !== strpos( $contents, "\0" ) ) {
		return new WP_Error( 'invalid_csv', __( 'The uploaded file is not a plain-text CSV file. If it is a spreadsheet, upload it as .xlsx.', 'emc-theme' ) );
	}

	$contents = preg_replace( '/^\xEF\xBB\xBF/', '', $contents );
	if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $contents, 'UTF-8' ) ) {
		$contents = mb_convert_encoding( $contents, 'UTF-8', 'Windows-1252' );
	}

	// Detect the delimiter from the header line (Excel uses ";" in some locales).
	$first_line = strtok( $contents, "\r\n" );
	$delimiter  = ',';
	$best       = 0;
	foreach ( array( ',', ';', "\t" ) as $candidate ) {
		$count = substr_count( (string) $first_line, $candidate );
		if ( $count > $best ) {
			$best      = $count;
			$delimiter = $candidate;
		}
	}

	$handle = fopen( 'php://temp', 'r+' );
	fwrite( $handle, $contents );
	rewind( $handle );
	$rows = array();
	while ( false !== ( $row = fgetcsv( $handle, 0, $delimiter, '"', '' ) ) ) {
		if ( array( null ) !== $row ) {
			$rows[] = array_map( 'trim', array_map( 'strval', $row ) );
		}
	}
	fclose( $handle );

	return emc_prayer_parse_rows( $rows, $default_section, $label );
}

/**
 * Human-readable label for a timetable field key such as "iqamah.isha".
 *
 * @param string $key Field key.
 * @return string
 */
function emc_prayer_field_label( $key ) {
	if ( 'hijri' === $key ) {
		return __( 'Hijri date', 'emc-theme' );
	}
	list( $section, $field ) = explode( '.', $key );
	$names = array(
		'fajr'    => __( 'Fajr', 'emc-theme' ),
		'sunrise' => __( 'Sunrise', 'emc-theme' ),
		'dhuhr'   => __( 'Dhuhr', 'emc-theme' ),
		'asr'     => __( 'Asr', 'emc-theme' ),
		'maghrib' => __( 'Maghrib', 'emc-theme' ),
		'isha'    => __( 'Isha', 'emc-theme' ),
		'jumuah'  => __( 'Jumu’ah', 'emc-theme' ),
	);
	return ( 'adhan' === $section ? __( 'Athan', 'emc-theme' ) : __( 'Iqamah', 'emc-theme' ) ) . ' ' . ( $names[ $field ] ?? $field );
}

/**
 * Every selectable field key.
 *
 * @return string[]
 */
function emc_prayer_all_field_keys() {
	$keys = array();
	foreach ( emc_prayer_section_columns() as $section => $fields ) {
		foreach ( $fields as $field ) {
			$keys[] = $section . '.' . $field;
		}
	}
	$keys[] = 'hijri';
	return $keys;
}

/**
 * Build a complete-year timetable from parsed data (replace mode).
 *
 * @param array $parsed Parsed data.
 * @return array|WP_Error Date-ordered timetable entries.
 */
function emc_prayer_build_full_timetable( $parsed ) {
	$sections = emc_prayer_section_columns();
	$missing  = array();
	foreach ( $sections as $section => $fields ) {
		foreach ( $fields as $field ) {
			if ( ! in_array( $section . '.' . $field, $parsed['columns'], true ) ) {
				$missing[] = emc_prayer_field_label( $section . '.' . $field );
			}
		}
	}
	if ( $missing ) {
		return new WP_Error(
			'missing_columns',
			sprintf(
				/* translators: %s: column list. */
				__( 'Replacing the whole year needs every Athan and Iqamah column. Missing: %s. To change only some times, choose “Update selected times”.', 'emc-theme' ),
				implode( ', ', $missing )
			)
		);
	}

	$output = array();
	$years  = array();
	foreach ( $parsed['adhan'] as $date => $adhan_times ) {
		if ( ! isset( $parsed['iqamah'][ $date ] ) ) {
			continue;
		}
		$years[ substr( $date, -4 ) ] = true;

		$entry = array( 'date' => $date, 'hijri' => $parsed['hijri'][ $date ] ?? '', 'adhan' => array(), 'iqamah' => array() );
		foreach ( $sections as $section => $fields ) {
			foreach ( $fields as $field ) {
				$entry[ $section ][ $field ] = $parsed[ $section ][ $date ][ $field ] ?? '';
			}
		}
		$output[] = $entry;
	}

	if ( count( $output ) < 365 || 1 !== count( $years ) ) {
		return new WP_Error( 'invalid_date_range', sprintf( __( 'Replacing the whole year needs a complete set of matching Athan and Iqamah dates from one calendar year (found %d matching dates). To change only some dates, choose “Update selected times”.', 'emc-theme' ), count( $output ) ) );
	}

	usort(
		$output,
		static function ( $left, $right ) {
			return DateTime::createFromFormat( '!d/m/Y', $left['date'] ) <=> DateTime::createFromFormat( '!d/m/Y', $right['date'] );
		}
	);

	return $output;
}

/**
 * Apply selected fields from parsed data onto an existing timetable.
 *
 * @param array    $current Current timetable entries.
 * @param array    $parsed  Parsed upload.
 * @param string[] $fields  Field keys to apply, e.g. array( 'iqamah.isha' ).
 * @return array array( 'data' => entries, 'changes' => key => [count, first, last], 'unknown_dates' => int ).
 */
function emc_prayer_apply_updates( $current, $parsed, $fields ) {
	$index = array();
	foreach ( $current as $position => $entry ) {
		$index[ $entry['date'] ] = $position;
	}

	$changes       = array();
	$unknown_dates = array();
	$record        = static function ( $key, $date ) use ( &$changes ) {
		if ( ! isset( $changes[ $key ] ) ) {
			$changes[ $key ] = array( 'count' => 0, 'first' => $date, 'last' => $date );
		}
		$changes[ $key ]['count']++;
		$changes[ $key ]['last'] = $date;
	};

	foreach ( array( 'adhan', 'iqamah' ) as $section ) {
		foreach ( $parsed[ $section ] as $date => $times ) {
			if ( ! isset( $index[ $date ] ) ) {
				$unknown_dates[ $date ] = true;
				continue;
			}
			foreach ( $times as $field => $time ) {
				$key = $section . '.' . $field;
				if ( ! in_array( $key, $fields, true ) ) {
					continue;
				}
				$entry = &$current[ $index[ $date ] ];
				if ( ( $entry[ $section ][ $field ] ?? '' ) !== $time ) {
					$entry[ $section ][ $field ] = $time;
					$record( $key, $date );
				}
				unset( $entry );
			}
		}
	}

	if ( in_array( 'hijri', $fields, true ) ) {
		foreach ( $parsed['hijri'] as $date => $hijri ) {
			if ( isset( $index[ $date ] ) && ( $current[ $index[ $date ] ]['hijri'] ?? '' ) !== $hijri ) {
				$current[ $index[ $date ] ]['hijri'] = $hijri;
				$record( 'hijri', $date );
			}
		}
	}

	// Report changes in a stable, readable order and with date ranges sorted.
	$ordered = array();
	foreach ( emc_prayer_all_field_keys() as $key ) {
		if ( isset( $changes[ $key ] ) ) {
			$ordered[ $key ] = $changes[ $key ];
		}
	}

	return array(
		'data'          => $current,
		'changes'       => $ordered,
		'unknown_dates' => count( $unknown_dates ),
	);
}

/**
 * Compare two timetables field by field (used to summarise replacements).
 *
 * @param array $before Previous entries.
 * @param array $after  New entries.
 * @return array key => [count, first, last].
 */
function emc_prayer_diff( $before, $after ) {
	$parsed = array( 'adhan' => array(), 'iqamah' => array(), 'hijri' => array(), 'columns' => array() );
	foreach ( $after as $entry ) {
		$parsed['adhan'][ $entry['date'] ]  = $entry['adhan'];
		$parsed['iqamah'][ $entry['date'] ] = $entry['iqamah'];
		$parsed['hijri'][ $entry['date'] ]  = $entry['hijri'];
	}
	$result = emc_prayer_apply_updates( $before, $parsed, emc_prayer_all_field_keys() );
	return $result['changes'];
}

/**
 * Backwards-compatible wrapper: parse a full Masjidbox workbook.
 *
 * @param string $file Uploaded file path.
 * @return array|WP_Error Frontend prayer-data entries.
 */
function emc_parse_masjidbox_prayer_xlsx( $file ) {
	$parsed = emc_prayer_parse_xlsx_file( $file );
	return is_wp_error( $parsed ) ? $parsed : emc_prayer_build_full_timetable( $parsed );
}

/**
 * Sanity-check a timetable before it goes live.
 *
 * Catches mistakes such as an Iqamah earlier than its Athan (e.g. a time
 * pasted into the wrong column) so wrong times are never published.
 *
 * @param array $data Timetable entries.
 * @return true|WP_Error
 */
function emc_prayer_validate_timetable( $data ) {
	foreach ( $data as $entry ) {
		$adhan  = $entry['adhan'] ?? array();
		$iqamah = $entry['iqamah'] ?? array();

		foreach ( array( 'fajr', 'dhuhr', 'asr', 'maghrib', 'isha' ) as $prayer ) {
			if ( empty( $adhan[ $prayer ] ) ) {
				return new WP_Error( 'missing_time', sprintf( __( 'The Athan time for %1$s on %2$s is missing. Nothing was changed.', 'emc-theme' ), ucfirst( $prayer ), $entry['date'] ) );
			}
			if ( ! empty( $iqamah[ $prayer ] ) && $iqamah[ $prayer ] < $adhan[ $prayer ] ) {
				return new WP_Error(
					'iqamah_before_adhan',
					sprintf(
						/* translators: 1: prayer, 2: date, 3: iqamah, 4: adhan. */
						__( 'On %2$s the %1$s Iqamah (%3$s) would be before its Athan (%4$s). Please check the file – nothing was changed.', 'emc-theme' ),
						ucfirst( $prayer ),
						$entry['date'],
						$iqamah[ $prayer ],
						$adhan[ $prayer ]
					)
				);
			}
		}

		$order = array( 'fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha' );
		for ( $i = 1, $count = count( $order ); $i < $count; $i++ ) {
			if ( ! empty( $adhan[ $order[ $i ] ] ) && ! empty( $adhan[ $order[ $i - 1 ] ] ) && $adhan[ $order[ $i ] ] <= $adhan[ $order[ $i - 1 ] ] ) {
				return new WP_Error( 'times_out_of_order', sprintf( __( 'On %1$s the %2$s Athan (%3$s) is not after %4$s (%5$s). Please check the file – nothing was changed.', 'emc-theme' ), $entry['date'], ucfirst( $order[ $i ] ), $adhan[ $order[ $i ] ], ucfirst( $order[ $i - 1 ] ), $adhan[ $order[ $i - 1 ] ] ) );
			}
		}
	}

	return true;
}

/**
 * Persist generated prayer JSON in WordPress uploads.
 *
 * @param array  $data        Timetable entries.
 * @param string $source_name Uploaded filename.
 * @param array  $extra       Extra metadata (mode, changes).
 * @return array|WP_Error Import metadata.
 */
function emc_save_prayer_timetable_data( $data, $source_name, $extra = array() ) {
	$valid = emc_prayer_validate_timetable( $data );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'uploads_unavailable', $upload['error'] );
	}

	$directory = trailingslashit( $upload['basedir'] ) . 'emc-prayer-times';
	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'directory_failed', __( 'WordPress could not create the prayer timetable upload directory.', 'emc-theme' ) );
	}

	$json = wp_json_encode( array_values( $data ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
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

	// Keep a pointer to the previous version so an import can be undone.
	$previous = get_option( 'emc_prayer_timetable_import', array() );
	if ( is_array( $previous ) ) {
		unset( $previous['previous'] );
	}

	$metadata = array_merge(
		array(
			'file'        => $file,
			'url'         => trailingslashit( $upload['baseurl'] ) . 'emc-prayer-times/' . $filename,
			'source'      => sanitize_file_name( $source_name ),
			'year'        => (int) $year,
			'days'        => count( $data ),
			'imported_at' => current_time( 'mysql' ),
			'previous'    => ! empty( $previous['file'] ) ? $previous : array( 'bundled' => true ),
		),
		$extra
	);
	update_option( 'emc_prayer_timetable_import', $metadata, false );

	return $metadata;
}

/**
 * Process an upload from the timetable settings screen.
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

	if ( empty( $_FILES['emc_prayer_file'] ) || ! is_array( $_FILES['emc_prayer_file'] ) ) {
		return new WP_Error( 'missing_upload', __( 'Please choose an XLSX or CSV file.', 'emc-theme' ) );
	}

	$file = $_FILES['emc_prayer_file'];
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'upload_failed', __( 'The upload did not complete. Please try again.', 'emc-theme' ) );
	}

	$name      = sanitize_file_name( wp_unslash( $file['name'] ) );
	$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'xlsx', 'csv' ), true ) ) {
		return new WP_Error( 'invalid_filetype', __( 'Upload an .xlsx workbook or a .csv file.', 'emc-theme' ) );
	}
	$actual_size = file_exists( $file['tmp_name'] ) ? filesize( $file['tmp_name'] ) : false;
	if ( false === $actual_size || $actual_size <= 0 || $actual_size > 10 * MB_IN_BYTES ) {
		return new WP_Error( 'invalid_size', __( 'The file must be smaller than 10 MB.', 'emc-theme' ) );
	}
	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'invalid_upload', __( 'WordPress could not verify the uploaded file.', 'emc-theme' ) );
	}

	$mode    = isset( $_POST['emc_prayer_mode'] ) && 'replace' === $_POST['emc_prayer_mode'] ? 'replace' : 'update';
	$section = isset( $_POST['emc_prayer_csv_section'] ) ? sanitize_key( wp_unslash( $_POST['emc_prayer_csv_section'] ) ) : 'auto';
	$fields  = isset( $_POST['emc_prayer_fields'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['emc_prayer_fields'] ) ) : array();
	$fields  = array_values( array_intersect( emc_prayer_all_field_keys(), $fields ) );

	if ( 'csv' === $extension ) {
		if ( ! in_array( $section, array( 'adhan', 'iqamah' ), true ) ) {
			$section = preg_match( '/iqam|jamaat/i', $name ) ? 'iqamah' : ( preg_match( '/athan|adhan/i', $name ) ? 'adhan' : null );
		}
		$parsed = emc_prayer_parse_csv_file( $file['tmp_name'], $section, $name );
	} else {
		$parsed = emc_prayer_parse_xlsx_file( $file['tmp_name'] );
	}
	if ( is_wp_error( $parsed ) ) {
		return $parsed;
	}

	$current = emc_prayer_load_active_data();

	if ( 'replace' === $mode ) {
		$data = emc_prayer_build_full_timetable( $parsed );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$data_year = substr( $data[0]['date'], -4 );
		if ( preg_match( '/(?<!\d)(20\d{2})(?!\d)/', $name, $filename_year ) && $filename_year[1] !== $data_year ) {
			return new WP_Error( 'year_mismatch', sprintf( __( 'The filename says %1$s, but the file contains prayer times for %2$s.', 'emc-theme' ), $filename_year[1], $data_year ) );
		}

		$changes = emc_prayer_diff( $current, $data );
		return emc_save_prayer_timetable_data( $data, $name, array( 'mode' => 'replace', 'changes' => $changes ) );
	}

	if ( ! $current ) {
		return new WP_Error( 'no_active_timetable', __( 'There is no timetable to update yet. Choose “Replace the whole year” with a full Masjidbox workbook.', 'emc-theme' ) );
	}
	if ( ! $fields ) {
		return new WP_Error( 'no_fields', __( 'Tick at least one time to update.', 'emc-theme' ) );
	}

	$available = array_intersect( $fields, $parsed['columns'] );
	if ( ! $available ) {
		return new WP_Error(
			'no_matching_columns',
			sprintf(
				/* translators: 1: ticked fields, 2: columns in the file. */
				__( 'The file does not contain any of the ticked times (%1$s). It contains: %2$s.', 'emc-theme' ),
				implode( ', ', array_map( 'emc_prayer_field_label', $fields ) ),
				$parsed['columns'] ? implode( ', ', array_map( 'emc_prayer_field_label', $parsed['columns'] ) ) : __( 'no recognised columns', 'emc-theme' )
			)
		);
	}

	$result = emc_prayer_apply_updates( $current, $parsed, $fields );
	if ( ! $result['changes'] ) {
		return array(
			'no_changes'    => true,
			'unknown_dates' => $result['unknown_dates'],
		);
	}

	$metadata = emc_save_prayer_timetable_data( $result['data'], $name, array( 'mode' => 'update', 'changes' => $result['changes'] ) );
	if ( is_array( $metadata ) ) {
		$metadata['unknown_dates'] = $result['unknown_dates'];
	}
	return $metadata;
}

/**
 * Restore the timetable that was active before the last import.
 *
 * @return array|WP_Error|null
 */
function emc_handle_prayer_timetable_restore() {
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'POST' !== $request_method || empty( $_POST['emc_prayer_timetable_restore'] ) ) {
		return null;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You do not have permission to change prayer times.', 'emc-theme' ) );
	}
	check_admin_referer( 'emc_prayer_timetable_restore' );

	$current  = get_option( 'emc_prayer_timetable_import', array() );
	$previous = is_array( $current ) ? ( $current['previous'] ?? array() ) : array();

	if ( ! empty( $previous['bundled'] ) ) {
		delete_option( 'emc_prayer_timetable_import' );
		return array( 'restored' => __( 'the bundled timetable', 'emc-theme' ) );
	}
	if ( empty( $previous['file'] ) || ! file_exists( $previous['file'] ) ) {
		return new WP_Error( 'nothing_to_restore', __( 'There is no previous timetable to restore.', 'emc-theme' ) );
	}

	unset( $previous['changes'] );
	update_option( 'emc_prayer_timetable_import', $previous, false );
	return array( 'restored' => $previous['source'] . ' (' . $previous['imported_at'] . ')' );
}

/**
 * Download the active timetable as a CSV that can be edited and re-imported.
 */
function emc_prayer_timetable_download_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to download prayer times.', 'emc-theme' ), 403 );
	}
	check_admin_referer( 'emc_prayer_timetable_csv' );

	$data = emc_prayer_load_active_data();
	$year = $data ? substr( $data[0]['date'], -4 ) : gmdate( 'Y' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="prayer-timetable-' . $year . '.csv"' );

	$output  = fopen( 'php://output', 'w' );
	$columns = emc_prayer_section_columns();
	$header  = array( 'date' );
	foreach ( $columns as $section => $fields ) {
		foreach ( $fields as $field ) {
			$header[] = $section . '_' . $field;
		}
	}
	$header[] = 'hijri';

	fwrite( $output, "\xEF\xBB\xBF" ); // Lets Excel read the Arabic Hijri dates.
	fputcsv( $output, $header, ',', '"', '' );
	foreach ( $data as $entry ) {
		$row = array( $entry['date'] );
		foreach ( $columns as $section => $fields ) {
			foreach ( $fields as $field ) {
				$row[] = $entry[ $section ][ $field ] ?? '';
			}
		}
		$row[] = $entry['hijri'] ?? '';
		fputcsv( $output, $row, ',', '"', '' );
	}
	fclose( $output );
	exit;
}
add_action( 'admin_post_emc_prayer_timetable_csv', 'emc_prayer_timetable_download_csv' );

/**
 * Render a list of changed fields.
 *
 * @param array $changes key => [count, first, last].
 */
function emc_prayer_render_changes( $changes ) {
	if ( ! $changes ) {
		return;
	}
	echo '<ul style="list-style:disc;margin-left:20px;">';
	foreach ( $changes as $key => $change ) {
		echo '<li>' . esc_html(
			sprintf(
				/* translators: 1: field, 2: number of days, 3: first date, 4: last date. */
				_n( '%1$s: %2$d day changed (%3$s)', '%1$s: %2$d days changed (%3$s – %4$s)', $change['count'], 'emc-theme' ),
				emc_prayer_field_label( $key ),
				$change['count'],
				$change['first'],
				$change['last']
			)
		) . '</li>';
	}
	echo '</ul>';
}

/**
 * Render the prayer timetable import page.
 */
function emc_prayer_timetable_admin_page() {
	$restore = emc_handle_prayer_timetable_restore();
	$result  = $restore ?: emc_handle_prayer_timetable_upload();
	$current = get_option( 'emc_prayer_timetable_import', array() );

	$posted_fields = isset( $_POST['emc_prayer_fields'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['emc_prayer_fields'] ) ) : null;
	$posted_mode   = isset( $_POST['emc_prayer_mode'] ) && 'replace' === $_POST['emc_prayer_mode'] ? 'replace' : 'update';
	$posted_csv    = isset( $_POST['emc_prayer_csv_section'] ) ? sanitize_key( wp_unslash( $_POST['emc_prayer_csv_section'] ) ) : 'auto';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Prayer Timetable Import', 'emc-theme' ); ?></h1>

		<?php if ( is_wp_error( $result ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
		<?php elseif ( is_array( $result ) && isset( $result['restored'] ) ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Restored %s.', 'emc-theme' ), $result['restored'] ) ); ?></p></div>
		<?php elseif ( is_array( $result ) && ! empty( $result['no_changes'] ) ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'The file matches the current timetable for the ticked times, so nothing was changed.', 'emc-theme' ); ?>
				<?php if ( $result['unknown_dates'] ) : ?>
					<?php echo esc_html( sprintf( __( '%d dates in the file are not in the current timetable and were skipped.', 'emc-theme' ), $result['unknown_dates'] ) ); ?>
				<?php endif; ?>
			</p></div>
		<?php elseif ( is_array( $result ) ) : ?>
			<div class="notice notice-success">
				<p><strong><?php echo esc_html( 'replace' === ( $result['mode'] ?? '' ) ? sprintf( __( 'Prayer timetable replaced: %1$d days imported for %2$d.', 'emc-theme' ), $result['days'], $result['year'] ) : __( 'Prayer timetable updated.', 'emc-theme' ) ); ?></strong></p>
				<?php if ( ! empty( $result['changes'] ) ) : ?>
					<?php emc_prayer_render_changes( $result['changes'] ); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No times differ from the previous timetable.', 'emc-theme' ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $result['unknown_dates'] ) ) : ?>
					<p><?php echo esc_html( sprintf( __( '%d dates in the file are not in the current timetable and were skipped.', 'emc-theme' ), $result['unknown_dates'] ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Prayer times shown in the sitewide prayer bar, today’s prayer widget and the monthly timetable come from this timetable.', 'emc-theme' ); ?></p>

		<h2><?php esc_html_e( 'Current timetable', 'emc-theme' ); ?></h2>
		<?php if ( ! empty( $current['source'] ) ) : ?>
			<table class="widefat striped" style="max-width:760px;margin:12px 0;">
				<tbody>
					<tr><th><?php esc_html_e( 'Last file imported', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['source'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Calendar year', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['year'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Dates', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['days'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Last imported', 'emc-theme' ); ?></th><td><?php echo esc_html( $current['imported_at'] ); ?></td></tr>
				</tbody>
			</table>
		<?php else : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'The bundled prayer timetable is currently active.', 'emc-theme' ); ?></p></div>
		<?php endif; ?>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=emc_prayer_timetable_csv' ), 'emc_prayer_timetable_csv' ) ); ?>"><?php esc_html_e( 'Download current timetable (CSV)', 'emc-theme' ); ?></a>
			<span class="description"><?php esc_html_e( 'Edit the times you need in Excel, save as CSV and upload it below.', 'emc-theme' ); ?></span>
		</p>
		<?php if ( ! empty( $current['previous'] ) ) : ?>
			<form method="post" style="margin:8px 0 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Undo the last import and restore the previous timetable?', 'emc-theme' ) ); ?>');">
				<?php wp_nonce_field( 'emc_prayer_timetable_restore' ); ?>
				<input type="hidden" name="emc_prayer_timetable_restore" value="1">
				<?php submit_button( __( 'Undo last import', 'emc-theme' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>

		<h2 style="margin-top:28px;"><?php esc_html_e( 'Upload changes', 'emc-theme' ); ?></h2>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'emc_prayer_timetable_upload' ); ?>
			<input type="hidden" name="emc_prayer_timetable_upload" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="emc-prayer-file"><?php esc_html_e( 'Timetable file', 'emc-theme' ); ?></label></th>
					<td>
						<input type="file" id="emc-prayer-file" name="emc_prayer_file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
						<p class="description"><?php esc_html_e( 'The Masjidbox .xlsx workbook (any filename), or a .csv file with a “date” column (DD/MM/YYYY) and prayer columns such as “isha” or “iqamah_isha”. Maximum size: 10 MB.', 'emc-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'What to do', 'emc-theme' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="emc_prayer_mode" value="update" <?php checked( 'update', $posted_mode ); ?>> <?php esc_html_e( 'Update selected times only (recommended) – everything else stays as it is', 'emc-theme' ); ?></label><br>
							<label><input type="radio" name="emc_prayer_mode" value="replace" <?php checked( 'replace', $posted_mode ); ?>> <?php esc_html_e( 'Replace the whole year – needs a complete Masjidbox workbook', 'emc-theme' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Times to update', 'emc-theme' ); ?></th>
					<td>
						<fieldset>
							<?php
							foreach ( emc_prayer_section_columns() as $section => $fields ) :
								echo '<p style="margin:4px 0;"><strong>' . esc_html( 'adhan' === $section ? __( 'Athan', 'emc-theme' ) : __( 'Iqamah', 'emc-theme' ) ) . '</strong></p>';
								foreach ( $fields as $field ) :
									$key = $section . '.' . $field;
									?>
									<label style="margin-right:14px;display:inline-block;"><input type="checkbox" name="emc_prayer_fields[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( null === $posted_fields || in_array( $key, $posted_fields, true ) ); ?>> <?php echo esc_html( preg_replace( '/^\S+\s/', '', emc_prayer_field_label( $key ) ) ); ?></label>
									<?php
								endforeach;
							endforeach;
							?>
							<p style="margin:8px 0 0;"><label><input type="checkbox" name="emc_prayer_fields[]" value="hijri" <?php checked( null === $posted_fields || in_array( 'hijri', $posted_fields, true ) ); ?>> <?php esc_html_e( 'Hijri date', 'emc-theme' ); ?></label></p>
							<p class="description"><?php esc_html_e( 'Only ticked times are changed. Example: to change just the Isha Iqamah time, untick everything except Iqamah → Isha. Ignored when replacing the whole year.', 'emc-theme' ); ?></p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="emc-prayer-csv-section"><?php esc_html_e( 'CSV contains', 'emc-theme' ); ?></label></th>
					<td>
						<select id="emc-prayer-csv-section" name="emc_prayer_csv_section">
							<option value="auto" <?php selected( 'auto', $posted_csv ); ?>><?php esc_html_e( 'Detect from filename / column names', 'emc-theme' ); ?></option>
							<option value="iqamah" <?php selected( 'iqamah', $posted_csv ); ?>><?php esc_html_e( 'Iqamah times', 'emc-theme' ); ?></option>
							<option value="adhan" <?php selected( 'adhan', $posted_csv ); ?>><?php esc_html_e( 'Athan times', 'emc-theme' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Only used for CSV files whose columns are plain prayer names (fajr, isha…). XLSX sheets are detected by their tab names.', 'emc-theme' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Upload and Update Timetable', 'emc-theme' ) ); ?>
		</form>
	</div>
	<?php
}

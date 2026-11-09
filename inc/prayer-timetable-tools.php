<?php
/**
 * Prayer timetable maintenance tools: automatic forward extension (year
 * rollover) and a month-by-month manual editor, on top of the Masjidbox
 * XLSX/CSV importer in inc/prayer-timetable-import.php.
 *
 * Masjidbox exports one calendar year at a time, so without this the
 * timetable runs out at year-end and every page relying on it goes blank
 * until someone imports the next year's file.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'EMC_PRAYER_EXTEND_HOOK', 'emc_prayer_extend_timetable_cron' );
define( 'EMC_PRAYER_EXTEND_MIN_DAYS_AHEAD', 400 );

/**
 * Schedule the daily forward-extension check.
 */
function emc_prayer_schedule_extend() {
	if ( ! wp_next_scheduled( EMC_PRAYER_EXTEND_HOOK ) ) {
		wp_schedule_event( time(), 'daily', EMC_PRAYER_EXTEND_HOOK );
	}
}
add_action( 'init', 'emc_prayer_schedule_extend' );
add_action( EMC_PRAYER_EXTEND_HOOK, 'emc_prayer_extend_timetable' );

/** Stop the schedule if this theme is ever switched away from. */
function emc_prayer_unschedule_extend() {
	$timestamp = wp_next_scheduled( EMC_PRAYER_EXTEND_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, EMC_PRAYER_EXTEND_HOOK );
	}
}
add_action( 'switch_theme', 'emc_prayer_unschedule_extend' );

/**
 * Extend the active timetable forward so it always covers at least
 * EMC_PRAYER_EXTEND_MIN_DAYS_AHEAD days from today, generating any missing
 * dates by copying the same calendar date from one year earlier.
 *
 * Iqamah and Jumu'ah times are the mosque's own fixed schedule and carry
 * forward safely. Adhan times are astronomically calculated and only
 * approximate the next year's real times this way (typically within a
 * minute for the same calendar date, but not guaranteed) — every generated
 * entry is flagged 'carried_over' so the admin screen can surface it until
 * a real import or manual edit confirms it.
 *
 * Writes in place to the currently active file; this is routine
 * maintenance, not a tracked import, so it does not disturb the "undo last
 * import" chain.
 *
 * @return array{added:int,through:string}|null Null when nothing needed extending.
 */
function emc_prayer_extend_timetable() {
	$data = emc_prayer_load_active_data();
	if ( ! $data ) {
		return null; // Nothing imported yet; nothing to extend from.
	}

	$timezone = wp_timezone();
	$index    = array();
	$latest   = null;
	foreach ( $data as $entry ) {
		$index[ $entry['date'] ] = true;
		$date = DateTimeImmutable::createFromFormat( '!d/m/Y', $entry['date'], $timezone );
		if ( $date && ( ! $latest || $date > $latest ) ) {
			$latest = $date;
		}
	}

	$today  = new DateTimeImmutable( 'today', $timezone );
	$target = $today->modify( '+' . EMC_PRAYER_EXTEND_MIN_DAYS_AHEAD . ' days' );
	if ( ! $latest || $latest >= $target ) {
		return null; // Already covers far enough ahead.
	}

	$by_date = array();
	foreach ( $data as $entry ) {
		$by_date[ $entry['date'] ] = $entry;
	}

	$added  = 0;
	$cursor = $latest->modify( '+1 day' );
	while ( $cursor <= $target ) {
		$key = $cursor->format( 'd/m/Y' );
		if ( ! isset( $index[ $key ] ) ) {
			$source_key = $cursor->modify( '-1 year' )->format( 'd/m/Y' );
			if ( isset( $by_date[ $source_key ] ) ) {
				$source = $by_date[ $source_key ];
				$data[] = array(
					'date'         => $key,
					'hijri'        => '',
					'adhan'        => $source['adhan'],
					'iqamah'       => $source['iqamah'],
					'carried_over' => true,
				);
				$index[ $key ] = true;
				$added++;
			}
		}
		$cursor = $cursor->modify( '+1 day' );
	}

	if ( ! $added ) {
		return null;
	}

	usort(
		$data,
		static function ( $left, $right ) use ( $timezone ) {
			return DateTimeImmutable::createFromFormat( '!d/m/Y', $left['date'], $timezone )
				<=> DateTimeImmutable::createFromFormat( '!d/m/Y', $right['date'], $timezone );
		}
	);

	emc_prayer_write_active_file( $data );

	return array(
		'added'   => $added,
		'through' => $target->format( 'd/m/Y' ),
	);
}

/**
 * Overwrite the currently active timetable file in place, without creating
 * a new versioned file or touching the undo pointer. Used for routine
 * maintenance (forward extension) rather than an intentional import or
 * manual edit — those go through emc_save_prayer_timetable_data() instead.
 *
 * @param array $data Complete timetable entries.
 * @return bool
 */
function emc_prayer_write_active_file( $data ) {
	$import = get_option( 'emc_prayer_timetable_import', array() );
	if ( ! is_array( $import ) || empty( $import['file'] ) ) {
		return false; // Only the bundled fallback is active; nothing to overwrite safely.
	}

	$json = wp_json_encode( array_values( $data ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json ) {
		return false;
	}

	$written = file_put_contents( $import['file'], $json, LOCK_EX );
	if ( false === $written || strlen( $json ) !== $written ) {
		return false;
	}

	$import['days']        = count( $data );
	$import['extended_at'] = current_time( 'mysql' );
	update_option( 'emc_prayer_timetable_import', $import, false );

	return true;
}

/**
 * Count and date range of currently carried-over (unconfirmed) entries.
 *
 * @return array{count:int,first:string,last:string}|null
 */
function emc_prayer_carried_over_summary() {
	$data  = emc_prayer_load_active_data();
	$count = 0;
	$first = null;
	$last  = null;
	foreach ( $data as $entry ) {
		if ( empty( $entry['carried_over'] ) ) {
			continue;
		}
		$count++;
		if ( null === $first ) {
			$first = $entry['date'];
		}
		$last = $entry['date'];
	}
	return $count ? array( 'count' => $count, 'first' => $first, 'last' => $last ) : null;
}

/**
 * Render the "Edit a month directly" section of the Prayer Timetable screen.
 *
 * @param int $month 1-12.
 * @param int $year  Four-digit year.
 */
function emc_prayer_render_month_editor( $month, $year ) {
	$data = emc_prayer_load_active_data();
	$by_date = array();
	foreach ( $data as $entry ) {
		$by_date[ $entry['date'] ] = $entry;
	}

	$sections    = emc_prayer_section_columns();
	$days_in     = (int) gmdate( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	$prev        = $month > 1 ? array( $month - 1, $year ) : array( 12, $year - 1 );
	$next        = $month < 12 ? array( $month + 1, $year ) : array( 1, $year + 1 );
	$base_url    = admin_url( 'admin.php?page=emc-prayer-timetable' );
	?>
	<h2 style="margin-top:28px;"><?php esc_html_e( 'Edit a month directly', 'emc-theme' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Change just the times you need without preparing a file. Carried-over (unconfirmed) dates are highlighted — editing a row confirms it.', 'emc-theme' ); ?></p>
	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'edit_month' => $prev[0], 'edit_year' => $prev[1] ), $base_url ) ); ?>">&larr; <?php echo esc_html( gmdate( 'F Y', mktime( 0, 0, 0, $prev[0], 1, $prev[1] ) ) ); ?></a>
		<strong style="margin:0 10px;"><?php echo esc_html( gmdate( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></strong>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'edit_month' => $next[0], 'edit_year' => $next[1] ), $base_url ) ); ?>"><?php echo esc_html( gmdate( 'F Y', mktime( 0, 0, 0, $next[0], 1, $next[1] ) ) ); ?> &rarr;</a>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'emc_prayer_timetable_manual_edit' ); ?>
		<input type="hidden" name="action" value="emc_prayer_timetable_manual_edit">
		<input type="hidden" name="emc_prayer_month" value="<?php echo esc_attr( $month ); ?>">
		<input type="hidden" name="emc_prayer_year" value="<?php echo esc_attr( $year ); ?>">
		<div style="overflow-x:auto;">
			<table class="widefat striped" style="min-width:1100px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'emc-theme' ); ?></th>
						<?php foreach ( $sections as $section => $fields ) : foreach ( $fields as $field ) : ?>
							<th style="font-size:11px;white-space:nowrap;"><?php echo esc_html( emc_prayer_field_label( $section . '.' . $field ) ); ?></th>
						<?php endforeach; endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php for ( $d = 1; $d <= $days_in; $d++ ) :
						$date_key = sprintf( '%02d/%02d/%04d', $d, $month, $year );
						$entry    = $by_date[ $date_key ] ?? null;
						$carried  = ! empty( $entry['carried_over'] );
					?>
						<tr<?php echo $carried ? ' style="background:#fdf6e3;"' : ''; ?>>
							<td style="white-space:nowrap;">
								<?php echo esc_html( gmdate( 'D j', mktime( 0, 0, 0, $month, $d, $year ) ) ); ?>
								<?php if ( $carried ) : ?><br><span class="description" style="color:#8a6d1e;"><?php esc_html_e( 'carried over', 'emc-theme' ); ?></span><?php endif; ?>
							</td>
							<?php foreach ( $sections as $section => $fields ) : foreach ( $fields as $field ) : ?>
								<td><input type="text" size="6" style="width:100%;min-width:60px;" name="emc_prayer_rows[<?php echo esc_attr( $date_key ); ?>][<?php echo esc_attr( $section ); ?>][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $entry[ $section ][ $field ] ?? '' ); ?>"></td>
							<?php endforeach; endforeach; ?>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
		</div>
		<p class="description"><?php esc_html_e( 'Format HH:MM (24-hour) or h:mma, e.g. 13:15 or 1:15pm. Leave a field as it is to keep its current value.', 'emc-theme' ); ?></p>
		<?php submit_button( __( 'Save This Month', 'emc-theme' ) ); ?>
	</form>
	<?php
}

/**
 * Save edits submitted from the month editor.
 */
function emc_handle_prayer_timetable_manual_edit() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit prayer times.', 'emc-theme' ), 403 );
	}
	check_admin_referer( 'emc_prayer_timetable_manual_edit' );

	$month = max( 1, min( 12, absint( $_POST['emc_prayer_month'] ?? 0 ) ) );
	$year  = absint( $_POST['emc_prayer_year'] ?? 0 );
	if ( ! $month || $year < 2020 || $year > 2100 ) {
		wp_die( esc_html__( 'Invalid month.', 'emc-theme' ) );
	}

	$rows = isset( $_POST['emc_prayer_rows'] ) && is_array( $_POST['emc_prayer_rows'] ) ? wp_unslash( $_POST['emc_prayer_rows'] ) : array();
	$current = emc_prayer_load_active_data();
	$index   = array();
	foreach ( $current as $position => $entry ) {
		$index[ $entry['date'] ] = $position;
	}

	$sections = emc_prayer_section_columns();
	$changed  = 0;
	$invalid  = array();

	foreach ( $rows as $date => $fields ) {
		$date = sanitize_text_field( $date );
		if ( ! preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $date ) || ! is_array( $fields ) ) {
			continue;
		}
		$position = $index[ $date ] ?? null;
		$existing = null !== $position ? $current[ $position ] : null;
		$built    = array(
			'date'  => $date,
			'hijri' => $existing['hijri'] ?? '',
			'adhan' => array(),
			'iqamah' => array(),
		);
		$has_value = false;
		foreach ( $sections as $section => $section_fields ) {
			foreach ( $section_fields as $field ) {
				$raw  = $fields[ $section ][ $field ] ?? '';
				$time = emc_prayer_normalize_time( $raw );
				if ( false === $time ) {
					$invalid[] = $date . ' — ' . emc_prayer_field_label( $section . '.' . $field );
					$time      = $existing[ $section ][ $field ] ?? '';
				}
				$built[ $section ][ $field ] = $time;
				if ( '' !== $time ) {
					$has_value = true;
				}
			}
		}
		if ( ! $has_value ) {
			continue; // Blank row; nothing to save.
		}
		if ( null !== $existing && $existing['adhan'] === $built['adhan'] && $existing['iqamah'] === $built['iqamah'] ) {
			continue; // Unchanged.
		}
		if ( null !== $position ) {
			$current[ $position ] = $built;
		} else {
			$current[]      = $built;
			$index[ $date ] = count( $current ) - 1;
		}
		$changed++;
	}

	$redirect_args = array( 'page' => 'emc-prayer-timetable', 'edit_month' => $month, 'edit_year' => $year );

	if ( ! $changed ) {
		$redirect_args['edit_result'] = 'nochange';
		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	usort(
		$current,
		static function ( $left, $right ) {
			return DateTime::createFromFormat( '!d/m/Y', $left['date'] ) <=> DateTime::createFromFormat( '!d/m/Y', $right['date'] );
		}
	);

	emc_save_prayer_timetable_data(
		$current,
		sprintf( 'Manual edit — %s', gmdate( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) )
	);

	$redirect_args['edit_result'] = 'saved';
	$redirect_args['edit_count']  = $changed;
	if ( $invalid ) {
		set_transient( 'emc_prayer_manual_edit_invalid', $invalid, MINUTE_IN_SECONDS );
	}
	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_emc_prayer_timetable_manual_edit', 'emc_handle_prayer_timetable_manual_edit' );

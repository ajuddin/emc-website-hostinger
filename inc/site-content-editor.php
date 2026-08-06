<?php
/**
 * Central website content editor.
 *
 * Provides one searchable administration screen for theme wording and the
 * operational values which do not belong to a post, menu, or Customizer panel.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Return an administrator-managed structured website setting. */
function emc_site_setting( $key, $default = '' ) {
	$values = get_option( 'emc_site_content_values', array() );
	return array_key_exists( $key, $values ) && '' !== $values[ $key ] ? $values[ $key ] : $default;
}

/** Return administrator-managed wording which is not passed through gettext. */
function emc_content( $key, $default = '' ) {
	$values = get_option( 'emc_site_content_copy', array() );
	return array_key_exists( $key, $values ) && '' !== $values[ $key ] ? $values[ $key ] : $default;
}

/** Structured values displayed on the Website Content screen. */
function emc_site_content_fields() {
	return array(
		'Donations and bank details' => array(
			'emc_bank_pay_url'       => array( 'Bank payment URL', 'url', 'https://paymentrequest.natwestpayit.com/reusable-link/39ee348b-8fe1-41fe-aa6b-9109dc847445' ),
			'emc_bank_account_name'  => array( 'Bank account name', 'text', 'Essex Muslim Centre' ),
			'emc_bank_account_number'=> array( 'Bank account number', 'text', '31512852' ),
			'emc_bank_sort_code'     => array( 'Sort code', 'text', '56-00-18' ),
			'emc_bank_bic'           => array( 'BIC / SWIFT', 'text', 'NWBKGB2L' ),
			'emc_bank_iban'          => array( 'IBAN', 'text', 'GB38NWBK56001831512852' ),
			'emc_bank_post_address'  => array( 'Cheque postal address', 'textarea', "Essex Muslim Centre\nDairy Farm Cottage, Cuton Hall Lane\nChelmsford, CM2 6PB\nUnited Kingdom" ),
			'emc_donate_oneoff_amounts' => array( 'One-off preset amounts (comma-separated)', 'text', '5,10,25,50,100' ),
			'emc_donate_regular_amounts'=> array( 'Regular preset amounts (comma-separated)', 'text', '5,10,20,50' ),
		),
		'Ramadan giving' => array(
			'emc_ramadan_start_date' => array( 'Ramadan start date', 'date', '2027-02-08' ),
			'emc_ramadan_amounts'    => array( 'Daily preset amounts (comma-separated)', 'text', '1,2,3,5,10' ),
			'emc_ramadan_default_amount' => array( 'Default daily amount', 'number', '3' ),
			'emc_fitrana_rate'       => array( 'Fitrana rate per person', 'number', '7' ),
			'emc_fidya_rate'         => array( 'Fidya rate per day/person', 'number', '5' ),
			'emc_fidya_max_days'     => array( 'Maximum Fidya days', 'number', '30' ),
		),
		'Badr Wall campaign' => array(
			'emc_badr_tier1_label'   => array( 'Tier 1 name', 'text', 'Founder of the Centre' ),
			'emc_badr_tier1_amount'  => array( 'Tier 1 amount', 'number', '10000' ),
			'emc_badr_tier1_total'   => array( 'Tier 1 total tiles', 'number', '100' ),
			'emc_badr_tier1_desc'    => array( 'Tier 1 description', 'textarea', 'Your name permanently displayed on a premium founder tile.' ),
			'emc_badr_tier2_label'   => array( 'Tier 2 name', 'text', 'Co-Founder of the Centre' ),
			'emc_badr_tier2_amount'  => array( 'Tier 2 amount', 'number', '5000' ),
			'emc_badr_tier2_total'   => array( 'Tier 2 total tiles', 'number', '213' ),
			'emc_badr_tier2_desc'    => array( 'Tier 2 description', 'textarea', 'Your name permanently displayed on a co-founder tile.' ),
		),
		'Prayer header and contact map' => array(
			'emc_header_second_jumuah'=> array( 'Second Jumu’ah time', 'text', '14:15' ),
			'emc_contact_map_embed'  => array( 'Default map embed URL', 'url', 'https://www.google.com/maps?q=51.745083,0.507917&output=embed' ),
			'emc_contact_map_link'   => array( 'Open exact location URL', 'url', 'https://maps.app.goo.gl/ctL7XFdazy4xsHAA7' ),
			'emc_default_vacancy_location' => array( 'Default vacancy location', 'text', 'Essex Muslim Centre, Chelmsford, Essex' ),
		),
		'Display limits' => array(
			'emc_home_news_count'    => array( 'Homepage news items', 'number', '3' ),
			'emc_home_services_count'=> array( 'Homepage service items', 'number', '8' ),
			'emc_home_video_count'   => array( 'Homepage video items', 'number', '3' ),
			'emc_testimonial_count'  => array( 'Homepage testimonials', 'number', '6' ),
			'emc_events_page_count'  => array( 'Events page items', 'number', '12' ),
			'emc_media_gallery_count'=> array( 'Media page gallery items', 'number', '60' ),
			'emc_media_news_count'   => array( 'Media page news items', 'number', '6' ),
		),
	);
}

/** Copy blocks which are currently not naturally represented by gettext. */
function emc_site_content_copy_fields() {
	return array(
		'contact_directions_intro' => array( 'Contact directions introduction', 'Use the exact map pin for Essex Muslim Centre, Cuton Hall Lane, CM2 6PB. The postcode may not always land on the precise entrance, so use coordinates 51.745083, 0.507917 where possible.' ),
		'contact_directions_train' => array( 'Directions — train', 'Take a Greater Anglia service from London Liverpool Street to Chelmsford station, then continue by local bus or taxi to the exact map pin.' ),
		'contact_directions_bus'   => array( 'Directions — bus', 'From Chelmsford station, use a local service towards Springfield/Cuton Hall Lane and confirm the nearest stop before travelling.' ),
		'contact_directions_car'   => array( 'Directions — taxi/car', 'For door-to-door travel from London or Chelmsford station, share the exact map link or coordinates 51.745083, 0.507917 with your driver.' ),
	);
}

/** Apply saved replacements to all translatable theme strings. */
function emc_override_theme_string( $translation, $text, $domain ) {
	if ( 'emc-theme' !== $domain ) {
		return $translation;
	}
	$overrides = get_option( 'emc_theme_string_overrides', array() );
	return isset( $overrides[ $text ] ) && '' !== $overrides[ $text ] ? $overrides[ $text ] : $translation;
}
add_filter( 'gettext', 'emc_override_theme_string', 20, 3 );

function emc_override_theme_plural_string( $translation, $single, $plural, $number, $domain ) {
	if ( 'emc-theme' !== $domain ) {
		return $translation;
	}
	$overrides = get_option( 'emc_theme_string_overrides', array() );
	$source    = 1 === (int) $number ? $single : $plural;
	return isset( $overrides[ $source ] ) && '' !== $overrides[ $source ] ? $overrides[ $source ] : $translation;
}
add_filter( 'ngettext', 'emc_override_theme_plural_string', 20, 5 );

function emc_override_theme_context_string( $translation, $text, $context, $domain ) {
	return emc_override_theme_string( $translation, $text, $domain );
}
add_filter( 'gettext_with_context', 'emc_override_theme_context_string', 20, 4 );

/** Extract literal gettext source strings from all theme PHP files. */
function emc_theme_string_catalogue() {
	$functions = array( '__', '_e', '_x', '_n', '_nx', 'esc_html__', 'esc_html_e', 'esc_html_x', 'esc_attr__', 'esc_attr_e', 'esc_attr_x' );
	$strings   = array();
	$iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( EMC_DIR, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		$path = $file->getPathname();
		if (
			'php' !== strtolower( $file->getExtension() )
			|| false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR )
			|| false !== strpos( $path, DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR )
			|| false !== strpos( $path, DIRECTORY_SEPARATOR . 'license-server' . DIRECTORY_SEPARATOR )
		) {
			continue;
		}
		$tokens = token_get_all( file_get_contents( $path ) );
		$count  = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] || ! in_array( $tokens[ $i ][1], $functions, true ) ) {
				continue;
			}
			for ( $j = $i + 1; $j < min( $count, $i + 8 ); $j++ ) {
				if ( is_array( $tokens[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $j ][0] ) {
					$raw = $tokens[ $j ][1];
					$value = stripcslashes( substr( $raw, 1, -1 ) );
					if ( '' !== trim( $value ) ) {
						$strings[ $value ] = $value;
					}
					break;
				}
			}
		}
	}
	natcasesort( $strings );
	return $strings;
}

function emc_site_content_admin_menu() {
	add_submenu_page( null, 'Website Content', 'Website Content', 'manage_options', 'emc-site-content', 'emc_site_content_admin_page' );
}
add_action( 'admin_menu', 'emc_site_content_admin_menu' );

function emc_sanitize_site_content_values( $input ) {
	$output = array();
	foreach ( emc_site_content_fields() as $fields ) {
		foreach ( $fields as $key => $field ) {
			$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
			if ( 'url' === $field[1] ) {
				$output[ $key ] = esc_url_raw( $value );
			} elseif ( 'textarea' === $field[1] ) {
				$output[ $key ] = sanitize_textarea_field( $value );
			} elseif ( 'number' === $field[1] ) {
				$output[ $key ] = is_numeric( $value ) ? (string) $value : $field[2];
			} else {
				$output[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $output;
}

function emc_register_site_content_settings() {
	register_setting( 'emc_site_content', 'emc_site_content_values', array( 'sanitize_callback' => 'emc_sanitize_site_content_values' ) );
	register_setting( 'emc_site_content', 'emc_site_content_copy', array( 'sanitize_callback' => function( $input ) {
		$output = array();
		foreach ( emc_site_content_copy_fields() as $key => $field ) {
			$output[ $key ] = sanitize_textarea_field( wp_unslash( $input[ $key ] ?? '' ) );
		}
		return $output;
	} ) );
	register_setting( 'emc_site_content', 'emc_theme_string_overrides', array( 'sanitize_callback' => function( $input ) {
		$output = get_option( 'emc_theme_string_overrides', array() );
		foreach ( (array) $input as $encoded => $value ) {
			$source = base64_decode( $encoded, true );
			$value  = sanitize_textarea_field( wp_unslash( $value ) );
			if ( false !== $source && '' !== $value && $value !== $source ) {
				$output[ $source ] = $value;
			} elseif ( false !== $source ) {
				unset( $output[ $source ] );
			}
		}
		return $output;
	} ) );
	register_setting( 'emc_site_content', 'emc_frontend_phrase_replacements', array( 'sanitize_callback' => function( $input ) {
		$lines  = preg_split( '/\r\n|\r|\n/', wp_unslash( (string) $input ) );
		$clean  = array();
		foreach ( $lines as $line ) {
			if ( false === strpos( $line, '|' ) ) {
				continue;
			}
			list( $original, $replacement ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( '' !== $original && '' !== $replacement ) {
				$clean[] = sanitize_text_field( $original ) . ' | ' . sanitize_text_field( $replacement );
			}
		}
		return implode( "\n", $clean );
	} ) );
}
add_action( 'admin_init', 'emc_register_site_content_settings' );

function emc_site_content_admin_page() {
	$values    = get_option( 'emc_site_content_values', array() );
	$copy      = get_option( 'emc_site_content_copy', array() );
	$overrides = get_option( 'emc_theme_string_overrides', array() );
	$catalogue = emc_theme_string_catalogue();
	$search    = sanitize_text_field( wp_unslash( $_GET['string_search'] ?? '' ) );
	if ( $search ) {
		$catalogue = array_filter( $catalogue, static function( $string ) use ( $search ) {
			return false !== stripos( $string, $search );
		} );
	}
	$per_page    = 200;
	$string_page = max( 1, absint( $_GET['string_page'] ?? 1 ) );
	$total_pages = max( 1, (int) ceil( count( $catalogue ) / $per_page ) );
	$page_strings = array_slice( $catalogue, ( $string_page - 1 ) * $per_page, $per_page, true );
	?>
	<div class="wrap emc-content-admin">
		<h1>Website Content</h1>
		<p>Edit operational values and every theme-generated label, heading, button, instruction and message. Posts, pages, menus, events and media remain editable in their existing WordPress screens.</p>
		<form method="get" style="margin:1rem 0"><input type="hidden" name="page" value="emc-site-content"><input type="search" name="string_search" class="regular-text" value="<?php echo esc_attr( $search ); ?>" placeholder="Search all labels and sentences…"> <?php submit_button( 'Search wording', 'secondary', '', false ); ?></form>
		<form method="post" action="options.php">
			<?php settings_fields( 'emc_site_content' ); ?>
			<div class="emc-settings-groups">
			<?php $group_index = 0; foreach ( emc_site_content_fields() as $section => $fields ) : ?>
				<details class="emc-settings-group" <?php echo 0 === $group_index++ ? 'open' : ''; ?>><summary><?php echo esc_html( $section ); ?><span><?php echo esc_html( count( $fields ) ); ?> settings</span></summary><table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $field ) : $value = $values[ $key ] ?? $field[2]; ?>
				<tr><th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th><td>
				<?php if ( 'textarea' === $field[1] ) : ?><textarea class="large-text" rows="4" id="<?php echo esc_attr( $key ); ?>" name="emc_site_content_values[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?><input class="regular-text" type="<?php echo esc_attr( $field[1] ); ?>" id="<?php echo esc_attr( $key ); ?>" name="emc_site_content_values[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
				<?php endif; ?></td></tr><?php endforeach; ?></table></details>
			<?php endforeach; ?>
			<details class="emc-settings-group"><summary>Contact directions<span><?php echo esc_html( count( emc_site_content_copy_fields() ) ); ?> settings</span></summary><table class="form-table" role="presentation">
			<?php foreach ( emc_site_content_copy_fields() as $key => $field ) : ?><tr><th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th><td><textarea class="large-text" rows="3" id="<?php echo esc_attr( $key ); ?>" name="emc_site_content_copy[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $copy[ $key ] ?? $field[1] ); ?></textarea></td></tr><?php endforeach; ?></table></details>
			<details class="emc-settings-group emc-wording-group" <?php echo $search ? 'open' : ''; ?>><summary>All theme wording<span>Searchable labels, buttons and messages</span></summary>
			<p><span><?php echo esc_html( count( $catalogue ) ); ?> editable strings<?php echo $search ? ' matching your search' : ''; ?>. Showing up to <?php echo esc_html( $per_page ); ?> per page. Keep placeholders such as <code>%s</code>, <code>%d</code> and <code>%1$s</code> in replacements where they appear.</span></p>
			<table class="widefat striped" id="emc-string-table"><thead><tr><th style="width:45%">Original wording</th><th>Replacement wording</th></tr></thead><tbody>
			<?php foreach ( $page_strings as $source ) : $encoded = base64_encode( $source ); ?><tr><td><?php echo esc_html( $source ); ?></td><td><textarea class="large-text" rows="2" name="emc_theme_string_overrides[<?php echo esc_attr( $encoded ); ?>]" placeholder="Leave empty to use the original"><?php echo esc_textarea( $overrides[ $source ] ?? '' ); ?></textarea></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( array( 'page' => 'emc-site-content', 'string_search' => $search, 'string_page' => '%#%' ), admin_url( 'admin.php' ) ), 'format' => '', 'current' => $string_page, 'total' => $total_pages ) ) ); ?></details>
			<details class="emc-settings-group"><summary>Advanced phrase replacements<span>JavaScript, plugin and email wording</span></summary>
			<p>Use this for wording created by JavaScript or supplied by another component. Enter one replacement per line in the format <code>Original wording | New wording</code>.</p>
			<textarea class="large-text code" rows="8" name="emc_frontend_phrase_replacements" placeholder="Original wording | New wording"><?php echo esc_textarea( get_option( 'emc_frontend_phrase_replacements', '' ) ); ?></textarea></details>
			</div>
			<?php submit_button( 'Save Website Content' ); ?>
		</form>
	</div>
	<?php
}

/** Replace administrator-specified script-generated phrases as they appear. */
function emc_print_frontend_phrase_replacements() {
	if ( is_admin() ) {
		return;
	}
	$raw = get_option( 'emc_frontend_phrase_replacements', '' );
	if ( ! $raw ) {
		return;
	}
	$map = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		if ( false !== strpos( $line, '|' ) ) {
			list( $from, $to ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( $from && $to ) {
				$map[ $from ] = $to;
			}
		}
	}
	if ( ! $map ) {
		return;
	}
	?>
	<script id="emc-phrase-replacements">
	(function(){
		const replacements=<?php echo wp_json_encode( $map ); ?>;
		const attrs=['placeholder','title','aria-label'];
		function apply(root){
			if(!root||root.nodeType!==1)return;
			const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);
			let node;
			while((node=walker.nextNode())){
				if(/^(SCRIPT|STYLE|TEXTAREA|CODE|PRE)$/.test(node.parentElement?.tagName||''))continue;
				const value=node.nodeValue.trim();
				if(replacements[value]) node.nodeValue=node.nodeValue.replace(value,replacements[value]);
			}
			root.querySelectorAll('*').forEach(function(el){attrs.forEach(function(attr){const value=el.getAttribute(attr);if(value&&replacements[value])el.setAttribute(attr,replacements[value]);});});
		}
		apply(document.body);
		new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(function(node){if(node.nodeType===1)apply(node);});});}).observe(document.body,{childList:true,subtree:true});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'emc_print_frontend_phrase_replacements', 100 );

/** Apply custom phrase replacements to administrator and donor emails too. */
function emc_replace_outgoing_email_phrases( $args ) {
	$raw = get_option( 'emc_frontend_phrase_replacements', '' );
	$map = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		if ( false !== strpos( $line, '|' ) ) {
			list( $from, $to ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( $from && $to ) {
				$map[ $from ] = $to;
			}
		}
	}
	if ( $map ) {
		$args['subject'] = strtr( $args['subject'] ?? '', $map );
		$args['message'] = strtr( $args['message'] ?? '', $map );
	}
	return $args;
}
add_filter( 'wp_mail', 'emc_replace_outgoing_email_phrases', 20 );

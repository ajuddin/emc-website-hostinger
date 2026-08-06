<?php
/**
 * Dynamic annual reports manager and frontend data helpers.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default reports shown until the administrator saves the reports screen.
 *
 * @return array[]
 */
function emc_annual_report_defaults() {
    $description = __( 'Trustees\' report, financial statements, and impact summary.', 'emc-theme' );

    return array(
        array( 'title' => __( 'Annual Report 2024–25', 'emc-theme' ), 'description' => $description, 'url' => '', 'button_label' => __( 'Download', 'emc-theme' ) ),
        array( 'title' => __( 'Annual Report 2023–24', 'emc-theme' ), 'description' => $description, 'url' => '', 'button_label' => __( 'Download', 'emc-theme' ) ),
        array( 'title' => __( 'Annual Report 2022–23', 'emc-theme' ), 'description' => $description, 'url' => '', 'button_label' => __( 'Download', 'emc-theme' ) ),
    );
}

/**
 * Normalize report rows before storage or display.
 *
 * @param mixed $raw Raw report rows.
 * @return array[]
 */
function emc_sanitize_annual_reports( $raw ) {
    if ( ! is_array( $raw ) ) {
        return array();
    }

    $reports = array();
    foreach ( $raw as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $title        = sanitize_text_field( wp_unslash( $row['title'] ?? '' ) );
        $description  = sanitize_textarea_field( wp_unslash( $row['description'] ?? '' ) );
        $url          = esc_url_raw( wp_unslash( $row['url'] ?? '' ) );
        $button_label = sanitize_text_field( wp_unslash( $row['button_label'] ?? '' ) );

        if ( '' === $title && '' === $url ) {
            continue;
        }

        $reports[] = array(
            'title'        => $title ?: __( 'Annual Report', 'emc-theme' ),
            'description'  => $description,
            'url'          => $url,
            'button_label' => $button_label ?: __( 'Download', 'emc-theme' ),
        );
    }

    return $reports;
}

/**
 * Read reports, preserving legacy ACF uploads until the new manager is saved.
 *
 * @return array[]
 */
function emc_get_annual_reports() {
    $saved = get_option( 'emc_annual_reports', false );
    if ( false !== $saved ) {
        return emc_sanitize_annual_reports( $saved );
    }

    $about_page = get_page_by_path( 'about' );
    $about_id   = $about_page ? $about_page->ID : null;
    $legacy     = array();

    if ( $about_id ) {
        for ( $index = 1; $index <= 3; $index++ ) {
            $year = get_theme_mod( 'about_report_' . $index . '_year', '' );
            $desc = get_theme_mod( 'about_report_' . $index . '_desc', '' );
            $file = get_post_meta( $about_id, 'about_report_' . $index . '_file', true );

            if ( ! $year ) {
                $year = get_post_meta( $about_id, 'about_report_' . $index . '_year', true );
            }
            if ( ! $desc ) {
                $desc = get_post_meta( $about_id, 'about_report_' . $index . '_desc', true );
            }
            $url  = '';

            if ( is_array( $file ) && ! empty( $file['url'] ) ) {
                $url = $file['url'];
            } elseif ( is_numeric( $file ) ) {
                $url = wp_get_attachment_url( (int) $file );
            } elseif ( is_string( $file ) ) {
                $url = $file;
            }

            if ( $year || $url ) {
                $legacy[] = array(
                    'title'        => sprintf( __( 'Annual Report %s', 'emc-theme' ), $year ),
                    'description'  => $desc ?: __( 'Trustees\' report, financial statements, and impact summary.', 'emc-theme' ),
                    'url'          => $url,
                    'button_label' => __( 'Download', 'emc-theme' ),
                );
            }
        }
    }

    return $legacy ? emc_sanitize_annual_reports( $legacy ) : emc_annual_report_defaults();
}

/**
 * Build the Charity Commission search URL from the charity number configured
 * in the theme.
 *
 * @return string
 */
function emc_get_default_charity_register_url() {
    $charity_number = preg_replace( '/[^A-Za-z0-9-]/', '', (string) emc_option( 'emc_charity_number', '1209815' ) );

    return 'https://register-of-charities.charitycommission.gov.uk/en/charity-search/-/results/page/1/delta/20/keywords/'
        . rawurlencode( $charity_number ?: '1209815' );
}

/**
 * Return editable section copy.
 *
 * @return array
 */
function emc_get_annual_reports_section() {
    $register_url = esc_url_raw( (string) get_option( 'emc_annual_reports_register_url', '' ) );

    return array(
        'subtitle'       => sanitize_text_field( get_option( 'emc_annual_reports_subtitle', __( 'Accountability', 'emc-theme' ) ) ),
        'heading'        => sanitize_text_field( get_option( 'emc_annual_reports_heading', __( 'Annual Reports', 'emc-theme' ) ) ),
        'description'    => sanitize_textarea_field( get_option( 'emc_annual_reports_description', __( 'In line with our commitment to transparency, all annual reports and accounts are available for public download.', 'emc-theme' ) ) ),
        'register_text'  => sanitize_text_field( get_option( 'emc_annual_reports_register_text', __( 'Our charity is registered with the Charity Commission for England and Wales.', 'emc-theme' ) ) ),
        'register_label' => sanitize_text_field( get_option( 'emc_annual_reports_register_label', __( 'View on Charity Commission Register', 'emc-theme' ) ) ),
        'register_url'   => $register_url ?: emc_get_default_charity_register_url(),
    );
}

/**
 * Add the report manager beneath Pages.
 */
function emc_annual_reports_admin_menu() {
    global $emc_annual_reports_hook;

    $emc_annual_reports_hook = add_submenu_page(
        null,
        __( 'Annual Reports', 'emc-theme' ),
        __( 'Annual Reports', 'emc-theme' ),
        'edit_pages',
        'emc-annual-reports',
        'emc_annual_reports_admin_page'
    );
}
add_action( 'admin_menu', 'emc_annual_reports_admin_menu' );

/**
 * Load the Media Library and manager script only on this admin screen.
 *
 * @param string $hook Current admin hook.
 */
function emc_annual_reports_admin_assets( $hook ) {
    global $emc_annual_reports_hook;

    if ( ! $emc_annual_reports_hook || $hook !== $emc_annual_reports_hook ) {
        return;
    }

    wp_enqueue_media();
    $script = EMC_DIR . '/assets/js/admin-reports.js';
    wp_enqueue_script(
        'emc-admin-reports',
        EMC_ASSETS . '/js/admin-reports.js',
        array(),
        file_exists( $script ) ? filemtime( $script ) : EMC_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'emc_annual_reports_admin_assets' );

/**
 * Save the report manager form.
 */
function emc_save_annual_reports() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( esc_html__( 'You are not allowed to manage annual reports.', 'emc-theme' ) );
    }

    check_admin_referer( 'emc_save_annual_reports' );

    $reports = emc_sanitize_annual_reports( $_POST['reports'] ?? array() );
    update_option( 'emc_annual_reports', $reports, false );
    update_option( 'emc_annual_reports_subtitle', sanitize_text_field( wp_unslash( $_POST['section_subtitle'] ?? '' ) ), false );
    update_option( 'emc_annual_reports_heading', sanitize_text_field( wp_unslash( $_POST['section_heading'] ?? '' ) ), false );
    update_option( 'emc_annual_reports_description', sanitize_textarea_field( wp_unslash( $_POST['section_description'] ?? '' ) ), false );
    update_option( 'emc_annual_reports_register_text', sanitize_text_field( wp_unslash( $_POST['register_text'] ?? '' ) ), false );
    update_option( 'emc_annual_reports_register_label', sanitize_text_field( wp_unslash( $_POST['register_label'] ?? '' ) ), false );
    update_option( 'emc_annual_reports_register_url', esc_url_raw( wp_unslash( $_POST['register_url'] ?? '' ) ), false );

    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => 'page',
                'page'      => 'emc-annual-reports',
                'updated'   => '1',
            ),
            admin_url( 'edit.php' )
        )
    );
    exit;
}
add_action( 'admin_post_emc_save_annual_reports', 'emc_save_annual_reports' );

/**
 * Render one editable report row.
 *
 * @param array      $report Report values.
 * @param int|string $index  Form index.
 */
function emc_annual_report_admin_row( $report, $index ) {
    ?>
    <article class="emc-report-admin-row postbox" data-report-row>
        <div class="postbox-header">
            <h2 class="hndle"><span data-report-number></span> <span data-report-title><?php echo esc_html( $report['title'] ?? '' ); ?></span></h2>
            <div class="emc-report-row-actions">
                <button type="button" class="button" data-report-up aria-label="<?php esc_attr_e( 'Move report up', 'emc-theme' ); ?>">↑</button>
                <button type="button" class="button" data-report-down aria-label="<?php esc_attr_e( 'Move report down', 'emc-theme' ); ?>">↓</button>
                <button type="button" class="button-link-delete" data-report-remove><?php esc_html_e( 'Remove', 'emc-theme' ); ?></button>
            </div>
        </div>
        <div class="inside emc-report-fields">
            <label>
                <strong><?php esc_html_e( 'Report title', 'emc-theme' ); ?></strong>
                <input type="text" class="widefat" name="reports[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $report['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Annual Report 2025–26', 'emc-theme' ); ?>" required>
            </label>
            <label>
                <strong><?php esc_html_e( 'Description', 'emc-theme' ); ?></strong>
                <textarea class="widefat" name="reports[<?php echo esc_attr( $index ); ?>][description]" rows="2"><?php echo esc_textarea( $report['description'] ?? '' ); ?></textarea>
            </label>
            <label>
                <strong><?php esc_html_e( 'Download URL or PDF', 'emc-theme' ); ?></strong>
                <span class="emc-report-url-row">
                    <input type="url" class="widefat" data-report-url name="reports[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $report['url'] ?? '' ); ?>" placeholder="https://example.org/report.pdf">
                    <button type="button" class="button" data-report-media><?php esc_html_e( 'Select PDF', 'emc-theme' ); ?></button>
                </span>
            </label>
            <label>
                <strong><?php esc_html_e( 'Button label', 'emc-theme' ); ?></strong>
                <input type="text" class="widefat" name="reports[<?php echo esc_attr( $index ); ?>][button_label]" value="<?php echo esc_attr( $report['button_label'] ?? __( 'Download', 'emc-theme' ) ); ?>">
            </label>
        </div>
    </article>
    <?php
}

/**
 * Render Pages > Annual Reports.
 */
function emc_annual_reports_admin_page() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        return;
    }

    $reports = emc_get_annual_reports();
    $section = emc_get_annual_reports_section();
    ?>
    <div class="wrap emc-reports-admin">
        <h1><?php esc_html_e( 'Annual Reports', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Manage the reports displayed on the About page. Paste any download link or select a PDF from the Media Library.', 'emc-theme' ); ?></p>

        <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Annual reports updated.', 'emc-theme' ); ?></p></div>
        <?php endif; ?>

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="emc_save_annual_reports">
            <?php wp_nonce_field( 'emc_save_annual_reports' ); ?>

            <div class="postbox emc-report-section-settings">
                <div class="postbox-header"><h2><?php esc_html_e( 'Section content', 'emc-theme' ); ?></h2></div>
                <div class="inside emc-report-fields">
                    <label><strong><?php esc_html_e( 'Small heading', 'emc-theme' ); ?></strong><input type="text" class="widefat" name="section_subtitle" value="<?php echo esc_attr( $section['subtitle'] ); ?>"></label>
                    <label><strong><?php esc_html_e( 'Main heading', 'emc-theme' ); ?></strong><input type="text" class="widefat" name="section_heading" value="<?php echo esc_attr( $section['heading'] ); ?>"></label>
                    <label class="emc-report-field-full"><strong><?php esc_html_e( 'Introduction', 'emc-theme' ); ?></strong><textarea class="widefat" name="section_description" rows="3"><?php echo esc_textarea( $section['description'] ); ?></textarea></label>
                    <label class="emc-report-field-full"><strong><?php esc_html_e( 'Charity register text', 'emc-theme' ); ?></strong><input type="text" class="widefat" name="register_text" value="<?php echo esc_attr( $section['register_text'] ); ?>"></label>
                    <label><strong><?php esc_html_e( 'Charity register link label', 'emc-theme' ); ?></strong><input type="text" class="widefat" name="register_label" value="<?php echo esc_attr( $section['register_label'] ); ?>"></label>
                    <label>
                        <strong><?php esc_html_e( 'Charity register URL', 'emc-theme' ); ?></strong>
                        <input type="url" class="widefat" name="register_url" value="<?php echo esc_attr( get_option( 'emc_annual_reports_register_url', '' ) ); ?>" placeholder="<?php echo esc_attr( emc_get_default_charity_register_url() ); ?>">
                        <span class="description"><?php esc_html_e( 'Leave blank to build the link automatically from the charity number configured in the Customizer.', 'emc-theme' ); ?></span>
                    </label>
                </div>
            </div>

            <div id="emc-reports-list" data-next-index="<?php echo esc_attr( count( $reports ) ); ?>">
                <?php foreach ( $reports as $index => $report ) : ?>
                    <?php emc_annual_report_admin_row( $report, $index ); ?>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button button-secondary" id="emc-add-report">
                    <span aria-hidden="true">＋</span> <?php esc_html_e( 'Add another report', 'emc-theme' ); ?>
                </button>
            </p>

            <?php submit_button( __( 'Save Annual Reports', 'emc-theme' ) ); ?>
        </form>

        <template id="emc-report-row-template">
            <?php
            emc_annual_report_admin_row(
                array(
                    'title'        => '',
                    'description'  => __( 'Trustees\' report, financial statements, and impact summary.', 'emc-theme' ),
                    'url'          => '',
                    'button_label' => __( 'Download', 'emc-theme' ),
                ),
                '__INDEX__'
            );
            ?>
        </template>

        <style>
            .emc-reports-admin { max-width: 1050px; }
            .emc-report-section-settings { margin-top: 24px; }
            .emc-report-admin-row { margin-bottom: 14px; }
            .emc-report-admin-row .postbox-header { align-items: center; padding-right: 12px; }
            .emc-report-row-actions { display: flex; align-items: center; gap: 8px; }
            .emc-report-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
            .emc-report-fields label { display: flex; flex-direction: column; gap: 6px; }
            .emc-report-field-full { grid-column: 1 / -1; }
            .emc-report-url-row { display: flex; gap: 8px; }
            .emc-report-url-row .button { flex: 0 0 auto; }
            @media (max-width: 782px) {
                .emc-report-fields { grid-template-columns: 1fr; }
                .emc-report-field-full { grid-column: auto; }
                .emc-report-url-row { align-items: stretch; flex-direction: column; }
            }
        </style>
    </div>
    <?php
}

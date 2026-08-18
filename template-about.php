<?php
/**
 * Template Name: About Us
 * Template Post Type: page
 *
 * EMC Theme — About page template.
 * All content is editable via ACF fields (registered in inc/acf-fields.php).
 *
 * @package emc-theme
 */

get_header();

wp_enqueue_style( 'emc-page-about', EMC_ASSETS . '/css/about.css', array( 'emc-style' ), EMC_VERSION );
?>

<!-- Page Hero -->
<section class="about-hero">
    <div class="about-hero-bg"></div>
    <div class="container about-hero-inner">
        <div class="about-hero-text">
            <span class="badge"><i class="fas fa-star-and-crescent"></i> <?php esc_html_e( 'About Us', 'emc-theme' ); ?></span>
            <h1><?php echo esc_html( emc_acf( 'about_hero_title', __( 'Who We Are', 'emc-theme' ) ) ); ?></h1>
            <p><?php echo esc_html( emc_acf( 'about_hero_intro', __( 'Essex Muslim Centre is a registered UK charity dedicated to advancing Islamic faith, education, and community welfare in the heart of Chelmsford, Essex.', 'emc-theme' ) ) ); ?></p>
            <div class="about-stat-row">
                <?php
                $stat_defaults = array(
                    array( 'num' => '2018',                                               'label' => __( 'Founded', 'emc-theme' ) ),
                    array( 'num' => '500+',                                               'label' => __( 'Families Served', 'emc-theme' ) ),
                    array( 'num' => '12',                                                  'label' => __( 'Programmes', 'emc-theme' ) ),
                    array( 'num' => '#' . emc_option( 'emc_charity_number', '1209815' ), 'label' => __( 'Charity Number', 'emc-theme' ) ),
                );
                for ( $i = 1; $i <= 4; $i++ ) :
                    $num   = emc_acf( 'about_stat_' . $i . '_num',   $stat_defaults[ $i - 1 ]['num'] );
                    $label = emc_acf( 'about_stat_' . $i . '_label', $stat_defaults[ $i - 1 ]['label'] );
                ?>
                <div class="about-stat">
                    <span class="stat-num"><?php echo esc_html( $num ); ?></span>
                    <span class="stat-label"><?php echo esc_html( $label ); ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="about-hero-image">
            <?php
            $hero_url = emc_acf_image( 'about_hero_image', '' );
            if ( ! $hero_url ) {
                $hero_url = get_the_post_thumbnail_url( get_the_ID(), 'emc-hero' );
            }
            if ( ! $hero_url ) {
                $hero_url = EMC_ASSETS . '/gallery/Community Support Services/New-Muslim-600x338.jpeg';
            }
            ?>
            <img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php esc_attr_e( 'EMC Community', 'emc-theme' ); ?>" class="about-img-main">
            <div class="about-img-badge glass-card">
                <i class="fas fa-certificate"></i>
                <div>
                    <strong><?php esc_html_e( 'Registered Charity', 'emc-theme' ); ?></strong>
                    <small><?php printf( esc_html__( 'No. %s (England & Wales)', 'emc-theme' ), esc_html( emc_option( 'emc_charity_number', '1209815' ) ) ); ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Mission -->
<section class="mission-section section-padding">
    <div class="container">
        <div class="mission-layout">
            <div class="mission-text scroll-reveal">
                <div class="section-header left">
                    <span class="subtitle"><?php echo esc_html( emc_acf( 'about_mission_subtitle', __( 'Our Purpose', 'emc-theme' ) ) ); ?></span>
                    <h2><?php echo esc_html( emc_acf( 'about_mission_heading', __( 'Our Mission', 'emc-theme' ) ) ); ?></h2>
                </div>
                <p class="mission-quote">"<?php echo esc_html( emc_acf( 'about_mission_quote', __( 'Establishing and maintaining places of worship and providing religious education in accordance with the teachings of Islam.', 'emc-theme' ) ) ); ?>"</p>
                <?php
                $mission_body = emc_acf( 'about_mission_body', '' );
                if ( $mission_body ) :
                    echo wp_kses_post( $mission_body );
                else :
                ?>
                <p><?php esc_html_e( 'At Essex Muslim Centre, we believe that a strong, connected community is built on faith, knowledge, and mutual support. Our mission extends beyond the walls of the mosque — we are active partners in the wellbeing of Chelmsford\'s diverse population.', 'emc-theme' ); ?></p>
                <p><?php esc_html_e( 'We are committed to transparency, accountability, and delivering a lasting positive impact for the people of Essex and beyond.', 'emc-theme' ); ?></p>
                <?php endif; ?>

                <div class="mission-values">
                    <?php
                    $value_defaults = array(
                        array( 'icon' => 'fas fa-heart',      'title' => __( 'Compassion', 'emc-theme' ),   'desc' => __( 'Serving every person with dignity and care.', 'emc-theme' ) ),
                        array( 'icon' => 'fas fa-handshake',  'title' => __( 'Community', 'emc-theme' ),    'desc' => __( 'Building bridges across faiths and backgrounds.', 'emc-theme' ) ),
                        array( 'icon' => 'fas fa-book-open',  'title' => __( 'Education', 'emc-theme' ),    'desc' => __( 'Empowering through knowledge and faith.', 'emc-theme' ) ),
                        array( 'icon' => 'fas fa-eye',        'title' => __( 'Transparency', 'emc-theme' ), 'desc' => __( 'Accountable to our donors and the Charity Commission.', 'emc-theme' ) ),
                    );
                    for ( $i = 1; $i <= 4; $i++ ) :
                        $icon  = emc_acf( 'about_value_' . $i . '_icon',  $value_defaults[ $i - 1 ]['icon'] );
                        $title = emc_acf( 'about_value_' . $i . '_title', $value_defaults[ $i - 1 ]['title'] );
                        $desc  = emc_acf( 'about_value_' . $i . '_desc',  $value_defaults[ $i - 1 ]['desc'] );
                        if ( empty( $title ) && empty( $desc ) ) continue;
                    ?>
                    <div class="value-item">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                        <div>
                            <strong><?php echo esc_html( $title ); ?></strong>
                            <p><?php echo esc_html( $desc ); ?></p>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="mission-image-col scroll-reveal" style="transition-delay:0.15s">
                <div class="mission-img-wrap">
                    <?php $mission_img = emc_acf_image( 'about_mission_image', EMC_ASSETS . '/gallery/Friday Prayer/FPS-600x600.jpeg' ); ?>
                    <img src="<?php echo esc_url( $mission_img ); ?>" alt="<?php esc_attr_e( 'EMC Mosque Interior', 'emc-theme' ); ?>" class="mission-img">
                    <div class="mission-img-overlay">
                        <i class="fas fa-mosque"></i>
                        <span><?php esc_html_e( 'Our Home', 'emc-theme' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trustees & Team -->
<section class="trustees-section section-padding" style="background: var(--light-bg);">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><?php echo esc_html( emc_acf( 'about_trustees_subtitle', __( 'Leadership', 'emc-theme' ) ) ); ?></span>
            <h2><?php echo esc_html( emc_acf( 'about_trustees_heading', __( 'Trustees & Team', 'emc-theme' ) ) ); ?></h2>
            <?php $trustees_desc = emc_acf( 'about_trustees_desc', __( 'Our board of trustees are volunteers committed to the charity\'s mission. All trustees are appointed in accordance with our governing document and Charity Commission guidelines.', 'emc-theme' ) ); ?>
            <p style="color:var(--text-muted); margin-top:1rem; max-width:600px;"><?php echo esc_html( $trustees_desc ); ?></p>
        </div>

        <div class="trustees-grid">
            <?php
            $trustee_defaults = array(
                array( 'name' => 'Dr Hossain Hadi', 'role' => __( 'Trustee and Chairman', 'emc-theme' ), 'bio' => '', 'bg' => '0E6B47' ),
                array( 'name' => 'Dr Nurul Kabir',  'role' => __( 'Secretary', 'emc-theme' ),            'bio' => '', 'bg' => '1A2B4C' ),
                array( 'name' => 'Shafiqul Islam',  'role' => __( 'Vice-Chairman', 'emc-theme' ),        'bio' => '', 'bg' => 'D4AF37' ),
                array( 'name' => 'Abu Reaz Alam',   'role' => __( 'Treasurer', 'emc-theme' ),            'bio' => '', 'bg' => '0E6B47' ),
            );
            $bg_colors = array( '0E6B47', '1A2B4C', 'D4AF37', '0E6B47' );
            $delay = 0;
            for ( $i = 1; $i <= 4; $i++ ) :
                $d    = $trustee_defaults[ $i - 1 ];
                $name = emc_acf( 'about_trustee_' . $i . '_name', $d['name'] );
                $role = emc_acf( 'about_trustee_' . $i . '_role', $d['role'] );
                $bio  = emc_acf( 'about_trustee_' . $i . '_bio',  $d['bio'] );
                $img  = emc_acf_image( 'about_trustee_' . $i . '_image', '' );
                if ( empty( $name ) ) continue; // Skip empty slots
                $bg   = $bg_colors[ ( $i - 1 ) % count( $bg_colors ) ];
            ?>
            <div class="trustee-card scroll-reveal"<?php echo $delay ? ' style="transition-delay:' . esc_attr( $delay ) . 's"' : ''; ?>>
                <div class="trustee-avatar">
                    <?php if ( $img ) : ?>
                        <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $name ); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                    <?php else : ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo esc_attr( urlencode( $name ) ); ?>&background=<?php echo esc_attr( $bg ); ?>&color=fff&size=80&rounded=true" alt="<?php echo esc_attr( $name ); ?>">
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html( $name ); ?></h4>
                <p class="trustee-role"><?php echo esc_html( $role ); ?></p>
                <?php if ( $bio ) : ?><p class="trustee-bio"><?php echo esc_html( $bio ); ?></p><?php endif; ?>
            </div>
            <?php
                $delay = round( $delay + 0.1, 1 );
            endfor;
            ?>
        </div>
    </div>
</section>

<!-- Annual Reports -->
<section class="reports-section section-padding">
    <div class="container">
        <?php
        $report_section = emc_get_annual_reports_section();
        $annual_reports = emc_get_annual_reports();
        ?>
        <div class="section-header">
            <?php if ( $report_section['subtitle'] ) : ?>
            <span class="subtitle"><?php echo esc_html( $report_section['subtitle'] ); ?></span>
            <?php endif; ?>
            <h2><?php echo esc_html( $report_section['heading'] ); ?></h2>
            <?php if ( $report_section['description'] ) : ?>
            <p style="color:var(--text-muted); margin-top:1rem;"><?php echo esc_html( $report_section['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <div class="reports-grid">
            <?php
            $delay = 0;
            foreach ( $annual_reports as $report ) :
                $file_url = $report['url'] ?? '';
            ?>
            <div class="report-card scroll-reveal"<?php echo $delay ? ' style="transition-delay:' . esc_attr( $delay ) . 's"' : ''; ?>>
                <div class="report-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="report-info">
                    <h4><?php echo esc_html( $report['title'] ?? __( 'Annual Report', 'emc-theme' ) ); ?></h4>
                    <?php if ( ! empty( $report['description'] ) ) : ?>
                    <p><?php echo esc_html( $report['description'] ); ?></p>
                    <?php endif; ?>
                </div>
                <?php if ( $file_url ) : ?>
                <a href="<?php echo esc_url( $file_url ); ?>" class="report-download btn btn-outline" target="_blank" rel="noopener noreferrer" download>
                    <i class="fas fa-download" aria-hidden="true"></i>
                    <?php echo esc_html( $report['button_label'] ?? __( 'Download', 'emc-theme' ) ); ?>
                </a>
                <?php else : ?>
                <span class="report-download btn btn-outline is-disabled" aria-disabled="true">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    <?php echo esc_html( $report['button_label'] ?? __( 'Download', 'emc-theme' ) ); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php
                $delay = round( $delay + 0.1, 1 );
            endforeach;
            ?>
        </div>
        <p style="text-align:center; margin-top:2rem; font-size:var(--step--1); color:var(--text-muted);">
            <?php echo esc_html( $report_section['register_text'] ); ?>
            <?php if ( $report_section['register_url'] && $report_section['register_label'] ) : ?>
            <a href="<?php echo esc_url( $report_section['register_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $report_section['register_label'] ); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
            <?php endif; ?>
        </p>
    </div>
</section>

<!-- Vacancies CTA -->
<section class="section-padding" style="background: var(--light-bg);">
    <div class="container">
        <div style="background: linear-gradient(135deg, var(--deep-blue), #1A4A2E); border-radius: 24px; padding: clamp(2.5rem, 5vw, 4rem); display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;">
            <div>
                <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:var(--accent-gold);font-size:var(--step--1);font-weight:600;padding:5px 14px;border-radius:999px;margin-bottom:1rem;">
                    <i class="fas fa-users"></i> <?php echo esc_html( emc_acf( 'about_cta_badge', __( 'Join Our Team', 'emc-theme' ) ) ); ?>
                </span>
                <h2 style="color:var(--white);margin-bottom:0.75rem;"><?php echo esc_html( emc_acf( 'about_cta_heading', __( 'Vacancies & Volunteering', 'emc-theme' ) ) ); ?></h2>
                <p style="color:rgba(255,255,255,0.75);font-size:var(--step-0);max-width:520px;margin:0;"><?php echo esc_html( emc_acf( 'about_cta_desc', __( 'Whether you want a paid role or to give your time voluntarily, we always welcome passionate individuals. View our current openings and apply online.', 'emc-theme' ) ) ); ?></p>
            </div>
            <div style="display:flex;gap:1rem;flex-shrink:0;flex-wrap:wrap;">
                <?php
                $vacancies_page = get_page_by_path( 'vacancies' );
                $vacancies_url  = $vacancies_page ? get_permalink( $vacancies_page ) : home_url( '/vacancies/' );
                ?>
                <a href="<?php echo esc_url( $vacancies_url ); ?>" class="btn btn-primary"><i class="fas fa-briefcase"></i> <?php esc_html_e( 'View All Roles', 'emc-theme' ); ?></a>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'emc_volunteer_role' ) ); ?>" class="btn btn-outline" style="color:var(--white);border-color:rgba(255,255,255,0.5);"><?php esc_html_e( 'View Volunteer Opportunities', 'emc-theme' ); ?></a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>

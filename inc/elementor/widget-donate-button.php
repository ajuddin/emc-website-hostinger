<?php
/**
 * EMC Donate Button — Elementor Widget
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

class EMC_Widget_Donate_Button extends \Elementor\Widget_Base {

    public function get_name()  { return 'emc_donate_button'; }
    public function get_title() { return __( 'Donate Button', 'emc-theme' ); }
    public function get_icon()  { return 'eicon-heart'; }
    public function get_categories() { return array( 'emc-widgets' ); }
    public function get_keywords()   { return array( 'donate', 'button', 'charity', 'emc' ); }

    protected function register_controls() {

        /* ── Content ────────────────────────────────────────────────────── */
        $this->start_controls_section( 'section_content', array(
            'label' => __( 'Button', 'emc-theme' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ) );

        $this->add_control( 'label', array(
            'label'   => __( 'Label', 'emc-theme' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __( 'Donate Now', 'emc-theme' ),
        ) );

        $this->add_control( 'custom_url', array(
            'label'       => __( 'Custom URL (optional)', 'emc-theme' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => home_url( '/donations/' ),
            'description' => __( 'Leave blank to use the /donations/ page.', 'emc-theme' ),
        ) );

        $this->add_control( 'variant', array(
            'label'   => __( 'Style', 'emc-theme' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'btn-primary',
            'options' => array(
                'btn-primary' => __( 'Primary (Green)', 'emc-theme' ),
                'btn-outline' => __( 'Outline',         'emc-theme' ),
            ),
        ) );

        $this->add_control( 'show_icon', array(
            'label'        => __( 'Show Heart Icon', 'emc-theme' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'emc-theme' ),
            'label_off'    => __( 'No', 'emc-theme' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ) );

        $this->add_responsive_control( 'align', array(
            'label'     => __( 'Alignment', 'emc-theme' ),
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'emc-theme' ),   'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'emc-theme' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'emc-theme' ),  'icon' => 'eicon-text-align-right' ),
            ),
            'default'   => 'left',
            'selectors' => array( '{{WRAPPER}}' => 'text-align: {{VALUE}};' ),
        ) );

        $this->end_controls_section();
    }

    protected function render() {
        $s       = $this->get_settings_for_display();
        $label   = ! empty( $s['label'] ) ? $s['label'] : __( 'Donate Now', 'emc-theme' );
        $variant = $s['variant'] ?? 'btn-primary';
        $icon    = ( $s['show_icon'] ?? 'yes' ) === 'yes' ? '<i class="fas fa-heart" aria-hidden="true"></i> ' : '';

        if ( ! empty( $s['custom_url']['url'] ) ) {
            $url    = esc_url( $s['custom_url']['url'] );
            $target = ! empty( $s['custom_url']['is_external'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
        } else {
            $url    = esc_url( emc_get_donate_url() );
            $target = '';
        }

        printf(
            '<a href="%s" class="btn %s"%s>%s%s</a>',
            $url,
            esc_attr( $variant ),
            $target,
            $icon,
            esc_html( $label )
        );
    }

    protected function content_template() {
        ?>
        <# var icon = settings.show_icon === 'yes' ? '<i class="fas fa-heart" aria-hidden="true"></i> ' : ''; #>
        <a href="#" class="btn {{ settings.variant }}">
            {{{ icon }}}{{ settings.label || '<?php echo esc_js( __( 'Donate Now', 'emc-theme' ) ); ?>' }}
        </a>
        <?php
    }
}

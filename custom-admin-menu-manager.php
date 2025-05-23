<?php
/**
 * Plugin Name: Custom Admin Menu Manager
 * Description: Deaktiviert Admin-Menüeinträge für alle User außer User-ID 1. User 1 kann freischalten, umbenennen und sortieren.
 * Version: 1.0.1
 * Author: Dein Name
 * Text Domain: custom-admin-menu-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CAMM_Admin_Menu_Manager' ) ) :

/**
 * Class CAMM_Admin_Menu_Manager
 *
 * @since 1.0.1
 */
class CAMM_Admin_Menu_Manager {
    /**
     * Option name prefix
     * @var string
     */
    private $option_prefix = 'camm_menu_settings_';

    /**
     * Plugin slug
     * @var string
     */
    const SLUG = 'camm-settings';

    /**
     * Textdomain
     * @var string
     */
    const TEXTDOMAIN = 'custom-admin-menu-manager';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'maybe_modify_admin_menu' ], 999 );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain( self::TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     */
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_' . self::SLUG ) {
            return;
        }
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'camm-admin-js', plugin_dir_url( __FILE__ ) . 'js/camm-admin.js', [ 'jquery', 'jquery-ui-sortable' ], '1.0.1', true );
        wp_enqueue_style( 'camm-admin-css', plugin_dir_url( __FILE__ ) . 'css/camm-admin.css', [], '1.0.1' );
    }

    /**
     * Get user role
     *
     * @param int|null $user_id
     * @return string
     */
    private function get_user_role( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata( $user_id );
        return ! empty( $user->roles ) ? $user->roles[0] : 'none';
    }

    /**
     * Get option name for role
     *
     * @param string $role
     * @return string
     */
    private function get_option_name_for_role( $role ) {
        return $this->option_prefix . $role;
    }

    /**
     * Maybe modify admin menu for non-super-admins
     */
    public function maybe_modify_admin_menu() {
        if ( get_current_user_id() == 1 ) {
            return;
        }
        $role = $this->get_user_role();
        $option_name = $this->get_option_name_for_role( $role );
        $settings = get_option( $option_name, [] );
        global $menu;
        $allowed = isset( $settings['allowed'] ) ? $settings['allowed'] : [];
        $renames = isset( $settings['renames'] ) ? $settings['renames'] : [];
        $order = isset( $settings['order'] ) ? $settings['order'] : [];
        foreach ( $menu as $k => $item ) {
            $slug = $item[2];
            if ( ! in_array( $slug, $allowed, true ) ) {
                unset( $menu[ $k ] );
            }
        }
        foreach ( $menu as $k => $item ) {
            $slug = $item[2];
            if ( isset( $renames[ $slug ] ) && ! empty( $renames[ $slug ] ) ) {
                $menu[ $k ][0] = esc_html( $renames[ $slug ] );
            }
        }
        if ( ! empty( $order ) ) {
            usort( $menu, function( $a, $b ) use ( $order ) {
                $apos = array_search( $a[2], $order, true );
                $bpos = array_search( $b[2], $order, true );
                return ( $apos === false ? 999 : $apos ) - ( $bpos === false ? 999 : $bpos );
            } );
        }
    }

    /**
     * Add settings page for super admin
     */
    public function add_settings_page() {
        if ( get_current_user_id() != 1 ) {
            return;
        }
        add_menu_page(
            __( 'Admin Menü Manager', self::TEXTDOMAIN ),
            __( 'Menü Manager', self::TEXTDOMAIN ),
            'manage_options',
            self::SLUG,
            [ $this, 'settings_page_html' ],
            'dashicons-menu',
            80
        );
    }

    /**
     * Register settings for all roles
     */
    public function register_settings() {
        global $wp_roles;
        foreach ( $wp_roles->roles as $role => $role_data ) {
            register_setting( 'camm_settings_group', $this->get_option_name_for_role( $role ) );
        }
    }

    /**
     * Render settings page HTML
     */
    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', self::TEXTDOMAIN ) );
        }
        global $menu, $wp_roles;
        $roles = $wp_roles->roles;
        $selected_role = isset( $_GET['camm_role'] ) ? sanitize_key( $_GET['camm_role'] ) : 'administrator';
        $option_name = $this->get_option_name_for_role( $selected_role );
        $settings = get_option( $option_name, [] );
        $allowed = isset( $settings['allowed'] ) ? $settings['allowed'] : [];
        $renames = isset( $settings['renames'] ) ? $settings['renames'] : [];
        $order = isset( $settings['order'] ) ? $settings['order'] : [];
        $menu_slugs = array_map( function( $item ) { return $item[2]; }, $menu );
        $sorted_slugs = array_values( array_unique( array_merge( $order, $menu_slugs ) ) );
        $menu_map = [];
        foreach ( $menu as $item ) {
            $menu_map[ $item[2] ] = $item;
        }
        ?>
        <div class="wrap">
            <div class="camm-header">
                <h1>Admin Menü Manager</h1>
                <form method="get" action="" class="camm-role-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
                    <label for="camm_role"><?php esc_html_e( 'Rolle wählen:', self::TEXTDOMAIN ); ?> </label>
                    <select name="camm_role" id="camm_role" onchange="this.form.submit()">
                        <?php foreach ( $roles as $role => $role_data ) : ?>
                            <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $selected_role, $role ); ?>><?php echo esc_html( $role_data['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'camm_settings_group' ); ?>
                <input type="hidden" name="action" value="update">
                <ul id="camm-menu-list">
                <?php
                foreach ( $sorted_slugs as $slug ) {
                    if ( ! isset( $menu_map[ $slug ] ) ) continue;
                    $item = $menu_map[ $slug ];
                    $label = esc_html( strip_tags( $item[0] ) );
                    $checked = in_array( $slug, $allowed, true ) ? 'checked' : '';
                    $rename = isset( $renames[ $slug ] ) ? esc_attr( $renames[ $slug ] ) : '';
                    $icon_url = '';
                    global $submenu;
                    $has_submenu = isset($submenu[$slug]) && is_array($submenu[$slug]) && count($submenu[$slug]) > 0;
                    echo '<li data-order-name="' . esc_attr( $option_name ) . '[order]" >';
                    echo '<span class="camm-handle dashicons dashicons-menu" title="Drag & Drop"></span>';
                    echo '<span class="camm-settings dashicons dashicons-admin-generic" title="Optionen"></span>';
                    echo '<span class="camm-icon-wrap">';
                    if ( $icon_url ) {
                        echo '<img class="camm-icon" src="' . esc_url( $icon_url ) . '" alt="Icon">';
                    }
                    echo '<button class="camm-icon-upload dashicons dashicons-upload" title="Icon wählen"></button>';
                    echo '</span>';
                    if($has_submenu) {
                        echo '<button type="button" class="camm-submenu-toggle" aria-expanded="false" title="Untermenü anzeigen"><span class="dashicons dashicons-arrow-down"></span></button>';
                    }
                    echo '<label class="camm-switch"><input type="checkbox" name="' . esc_attr( $option_name ) . '[allowed][]" value="' . esc_attr( $slug ) . '" ' . $checked . '><span class="camm-slider"></span></label>';
                    echo '<span class="camm-label">' . $label . '</span>';
                    echo '<input type="text" name="' . esc_attr( $option_name ) . '[renames][' . esc_attr( $slug ) . ']" value="' . $rename . '" placeholder="' . esc_attr__( 'Umbenennen', self::TEXTDOMAIN ) . '">';
                    echo '<button type="button" class="camm-dropdown" title="Mehr"> </button>';
                    echo '<input type="hidden" class="camm-order" name="' . esc_attr( $option_name ) . '[order][]" value="' . esc_attr( $slug ) . '">';
                    // Untermenüs ausgeben
                    if($has_submenu) {
                        echo '<ul class="camm-submenu-list" style="display:none;">';
                        foreach($submenu[$slug] as $subitem) {
                            $sublabel = esc_html( strip_tags( $subitem[0] ) );
                            $subslug = $subitem[2];
                            echo '<li class="camm-submenu-item">';
                            echo '<span class="camm-label">' . $sublabel . '</span>';
                            // Hier können weitere Felder wie Switch, Rename etc. ergänzt werden
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                    echo '</li>';
                }
                ?>
                </ul>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        // Debug-Ausgabe nur für Admins und wenn WP_DEBUG aktiv ist
        if ( get_current_user_id() == 1 && defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
        <div style="margin-top:30px;padding:15px;background:#f9f9f9;border:1px solid #eee;">
            <strong>Debug:</strong><br>
            <b><?php esc_html_e( 'Aktuelle allowed-Slugs:', self::TEXTDOMAIN ); ?></b> <pre><?php print_r( $allowed ); ?></pre>
            <b><?php esc_html_e( 'Aktuelle Menü-Slugs:', self::TEXTDOMAIN ); ?></b> <pre><?php print_r( $menu_slugs ); ?></pre>
            <b><?php esc_html_e( 'Aktuelle Rolle:', self::TEXTDOMAIN ); ?></b> <pre><?php echo esc_html( $selected_role ); ?></pre>
        </div>
        <?php endif;
    }
}

new CAMM_Admin_Menu_Manager();

endif; 
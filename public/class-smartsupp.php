<?php
/**
 * Smartsupp Live Chat
 *
 * @package   Smartsupp
 * @author    Smartsupp <vladimir@smartsupp.com>
 * Version:   2.6
 * @copyright 2014 smartsupp.com
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 */

require_once __DIR__ . "/../vendor/autoload.php";

class Smartsupp
{
    /**
     * Plugin version
     *
     * @since   0.1.0
     * @var     string
     */
    const VERSION = '0.2.1';

    /**
     * Plugin slug
     *
     * @since    0.1.0
     *
     * @var      string
     */
    protected $plugin_slug = 'smartsupp';

    /**
     * Instance of this class.
     *
     * @since    0.1.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     0.1.0
     */
    private function __construct()
    {
        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        add_action('wp_footer', array($this, 'add_chat_script'));
    }

    /**
     * Return the plugin slug.
     *
     * @since    0.1.0
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Return an instance of this class.
     *
     * @since     0.1.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since     0.1.0
     */
    public function load_plugin_textdomain() {
        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');
    }

    public static function is_woocommerce_active()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * Insert chat script to WP footer
     *
     * @since     0.1.0
     */
    public function add_chat_script()
    {
        global $wpdb;

        $smartsupp = get_option('smartsupp');

        if ($smartsupp['active'] != '1') {
            return;
        }

        $chat_id = esc_attr($smartsupp['chat-id']);

        if ($chat_id == '' || !is_string($chat_id)) {
            return;
        }

        // set key
        $code = new \Smartsupp\ChatGenerator($chat_id);

        // set cookie domain
        $code->setCookieDomain(parse_url(get_site_url(), PHP_URL_HOST));

        $dashboard_name = '';
        $user_email = '';

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $dashboard_name = $user->display_name . ' (' . $user->ID . ')';

            if ($smartsupp['active-vars'] == '1') {
                foreach ($smartsupp['wp-vars'] as $key => $value) {
                    if ($value == '1') {
                        switch ($key) {
                            case 'username':
                                $code->setVariable('userName', __('Username'), $user->user_login);
                                break;
                            case 'email':
                                $code->setVariable('email', __('Email'), $user->user_email);
                                $user_email = $user->user_email;
                                break;
                            case 'role':
                                $code->setVariable('role', __('Role'), implode(' ,', $user->roles));
                                break;
                            case 'name':
                                $code->setVariable('name', __('Name'), $user->first_name  . ' ' . $user->last_name);
                                break;
                            default:
                                # code...
                                break;
                        }
                    }
                }

                if (Smartsupp::is_woocommerce_active() && !empty($smartsupp['woocommerce-vars'])) {
                    foreach ($smartsupp['woocommerce-vars'] as $key => $value) {
                        if ($value == '1') {
                            switch ($key) {
                                case 'location':
                                    $country_code = get_user_meta( $user->ID, 'billing_country', true);
                                    $city = get_user_meta($user->ID, 'billing_city', true);
                                    if ($city || $country_code) {
                                        $location = $city . ', ' . $country_code;
                                    } else {
                                        $location = '-';
                                    }

                                    $code->setVariable('billingLocation', __('Billing Location'), $location);
                                    break;
                                case 'spent':
                                    if (!$spent = get_user_meta($user->ID, '_money_spent', true)) {
                                        $spent = $wpdb->get_var("SELECT SUM(meta2.meta_value)
											FROM $wpdb->posts as posts

											LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
											LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id

											WHERE 	meta.meta_key 		= '_customer_user'
											AND 	meta.meta_value 	= $user->ID
											AND 	posts.post_type 	= 'shop_order'
											AND 	posts.post_status 	= 'wc-completed'
											AND     meta2.meta_key 		= '_order_total'
										");

                                        update_user_meta($user->ID, '_money_spent', $spent);
                                    }

                                    if (!$spent) {
                                        $spent = 0;
                                    }

                                    $formatted_spent = sprintf(get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $spent);

                                    $code->setVariable('spent', __('Spent'), $formatted_spent);
                                    break;
                                case 'orders':
                                    if (!$count = get_user_meta($user->ID, '_order_count', true)) {
                                        $count = $wpdb->get_var( "SELECT COUNT(*)
											FROM $wpdb->posts as posts

											LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id

											WHERE 	meta.meta_key 		= '_customer_user'
											AND 	posts.post_type 	= 'shop_order'
											AND 	posts.post_status 	= 'wc-completed'
											AND 	meta_value 			= $user->ID
										" );

                                        update_user_meta($user->ID, '_order_count', $count);
                                    }

                                    $count = absint($count);

                                    $code->setVariable('order', __('Order'), $count);
                                    break;
                                default:
                                    # code...
                                    break;
                            }
                        }
                    }
                }
            }
        }

        $code->setName($dashboard_name);
        $code->setEmail($user_email);

        $code->render(true);

        if(!empty($smartsupp['optional-code'])) {
            echo "<script>{$smartsupp['optional-code']}</script>";
        }
    }
}

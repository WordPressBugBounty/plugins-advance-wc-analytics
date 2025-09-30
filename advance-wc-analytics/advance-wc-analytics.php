<?php

/*
Plugin Name: AWCA - The Great Statistics Plugin for Your eStore
Description: Provides Google Analytics Integration for your WooCommerce eStore. It Provides detailed insights & powerful independent reports for WooCommerce Website.
Author: Passionate Brains
Version: 3.18.0
WC requires at least: 3.7.0
WC tested up to: 10.2.2
Requires at least: 5.0
Requires PHP: 7.0
Author URI: https://advancedwcanalytics.com/
License: GPLv3
Requires Plugins: woocommerce
*/
/* initiating plugin */
if (!defined('ABSPATH')) {
    exit;
}
if (function_exists('awca_fs')) {
    awca_fs()->set_basename(false, __FILE__);
} else {
    if (!function_exists('awca_fs')) {
        // Create a helper function for easy SDK access.
        function awca_fs()
        {
            global $awca_fs;
            if (!isset($awca_fs)) {
                // Include Freemius SDK.
                require_once dirname(__FILE__) . '/vendor/freemius/start.php';
                $awca_fs = fs_dynamic_init(array(
                    'id'             => '6094',
                    'slug'           => 'advance-wc-analytics',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_0ef8a57f999622b14409cbafab4b4',
                    'is_premium'     => false,
                    'premium_suffix' => 'Pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                        'days'               => 3,
                        'is_require_payment' => true,
                    ),
                    'menu'           => array(
                        'slug'       => 'awca_pro_plugin_options',
                        'first-path' => 'admin.php?page=awca_pro_plugin_options',
                        'support'    => false,
                    ),
                    'is_live'        => true,
                    'is_org_compliant' => true,
                ));
            }
            return $awca_fs;
        }

        // Init Freemius.
        awca_fs();
        // Signal that SDK was initiated.
        do_action('awca_fs_loaded');
    }
    /* Defining some of constant which will be helpful throughout */
    if (!defined('AWCA_BASENAME')) {
        define('AWCA_BASENAME', plugin_basename(__FILE__));
    }
    if (!defined('AWCA_DIR')) {
        define('AWCA_DIR', plugin_dir_path(__FILE__));
    }
    if (!defined('AWCA_URL')) {
        define('AWCA_URL', plugin_dir_url(__FILE__));
    }
    if (!defined('AWCA_SITE_URL')) {
        define('AWCA_SITE_URL', site_url());
    }
    if (!defined('AWCA_SITE_DOMAIN')) {
        define('AWCA_SITE_DOMAIN', trim(str_ireplace(array('http://', 'https://'), '', trim(AWCA_SITE_URL, '/'))));
    }
    if (!defined('AWCA_PREFIX')) {
        define('AWCA_PREFIX', 'AWCA_');
    }
    if (!defined('AWCA_VERSION')) {
        define('AWCA_VERSION', '3.18.0');
    }
    add_action('before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });
    /* Definining main class */
    if (!class_exists('Woo_Ana')) {
        class Woo_Ana
        {
            private static $instance = null;

            private $settings;

            private $main;

            private $admin;

            private $auth;

            public static function get_instance()
            {
                if (!self::$instance) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            private function __construct()
            {
                if ($this->awca_compat_checker()) {
                    $this->includes();
                    $this->init();
                }
            }

            /*loads other support classes*/
            private function includes()
            {
                /* Settings class. */
                require_once AWCA_DIR . 'main/class-awca-settings.php';
                /* Include core class. */
                require_once AWCA_DIR . 'main/class-awca-main.php';
                /* Include admin class. */
                require_once AWCA_DIR . 'main/class-awca-admin.php';
                /* Include auth class. */
                require_once AWCA_DIR . 'main/class-awca-auth.php';
            }

            /* init support classes*/
            private function init()
            {
                $this->settings = new AWCA_Settings();
                $this->main = new AWCA_Main();
                $this->admin = new AWCA_Admin();
                $this->auth = new AWCA_Auth();
            }

            /* returning setting class object */
            public function settings()
            {
                return $this->settings;
            }

            /* returning main class object */
            public function main()
            {
                return $this->main;
            }

            /* returning admin class object */
            public function admin()
            {
                return $this->admin;
            }

            /* returning auth class object */
            public function auth()
            {
                return $this->auth;
            }

            /* checking compatibility for plugin to get activated and working */
            public function awca_compat_checker()
            {
                global $wp_version;
                $error = '';
                $nwpv = implode('.', array_slice(explode('.', $wp_version), 0, 2));
                #getiing wp version upto 2 decimal points
                # php version requirements
                if (version_compare(PHP_VERSION, '7.0', '<')) {
                    $error = 'AWCA: Google Analytics for Wordpress requires PHP 7.0 or higher. You’re still on ' . PHP_VERSION;
                }
                # wp version requirements
                if ($nwpv < '5.0') {
                    $error = 'AWCA: Google Analytics for Wordpress requires WP 5.0 or higher. You’re still on ' . $wp_version;
                }
                if (is_plugin_active(plugin_basename(__FILE__)) && !empty($error) || !empty($error)) {
                    if (isset($_GET['activate'])) {
                        unset($_GET['activate']);
                    }
                    add_action('admin_notices', function () use ($error) {
                        echo '<div class="notice notice-error is-dismissible"><p><strong>' . $error . '</strong></p></div>';
                    });
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
    add_action('plugins_loaded', array('Woo_Ana', 'get_instance'));
}

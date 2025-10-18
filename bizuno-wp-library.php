<?php
/**
 * Plugin Name: Bizuno WP Library
 * Plugin URI:  https://www.phreesoft.com
 * Description: Bizuno library adapted for WordPress.
 * Version:     1.0
 * Author:      PhreeSoft, Inc.
 * Author URI:  http://www.PhreeSoft.com
 * Text Domain: bizuno
 * License:     Affero GPL 3.0
 * License URI: https://www.gnu.org/licenses/agpl-3.0.txt
 * Domain Path: /locale
 */

defined( 'ABSPATH' ) || exit;

class bizuno_wp_library
{
    public function __construct()
    {
        // Actions
        add_action ( 'init',                      [ $this, 'init_bizuno'] );
        add_action ( 'phpmailer_init',            [ $this, 'bizuno_phpmailer_init' ], 10, 1 );
        add_action ( 'template_redirect',         [ $this, 'bizunoPageRedirect' ] );
        add_action ( 'wp_ajax_bizuno_ajax',       [ $this, 'bizunoAjax' ] );
        add_action ( 'wp_ajax_nopriv_bizuno_ajax',[ $this, 'bizunoAjax' ] );
        add_action ( 'wp_logout',                 [ $this, 'bizuno_user_logout' ] );
        add_action ( 'bizuno_daily_event',        [ $this, 'daily_cron' ] );
        // Filters
        add_filter ( 'xmlrpc_methods', function($methods) { unset( $methods['pingback.ping'] ); return $methods; } );
        // Install/Uninstall hooks
        register_activation_hook ( __FILE__ ,     [ $this, 'activate'] );
        register_deactivation_hook ( __FILE__ ,   [ $this, 'deactivate'] );
        register_uninstall_hook ( __FILE__,       'bizunoUninstall' ); // do not put inside of class
    }

    public function init_bizuno()
    {
        // Initialize Bizuno environment by loading cfg file and setting constants
    }

    public function bizuno_phpmailer_init( $phpmailer )
    {
        if ( get_post_field( 'post_name' ) == 'bizuno' ) { $phpmailer->IsHTML( true ); } // set email format to HTML
    }

    public function bizunoPageRedirect() {
        global $post;
        if ( is_user_logged_in() && !empty($post->post_name) && 'bizuno'==$post->post_name) {
            new portalCtl();
            exit();
        }
    }

    public function bizunoAjax()
    {
        if ( is_user_logged_in() ) {
            new portalCtl();
            exit();
        }
    }

    public function bizuno_user_logout()
    {
        $redirect_url = site_url();
        wp_safe_redirect( $redirect_url );
        exit;
    }

    public function daily_cron()
    {
        \bizuno\periodAutoUpdate(false); // since function has been loaded
    }

    public static function activate()
    {
        if (!wp_next_scheduled('bizuno_daily_event')) { wp_schedule_event(time(), 'daily', 'bizuno_daily_event'); }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('bizuno_daily_event');
    }
}
new bizuno_accounting();

/**
 * Uninstall should remove all documents and settings, essentially a clean wipe.
 * WARNING: Needs to be outside of class or WordPress errors
 */
function bizunoUninstall() {
    global $wpdb;
    $upload_dir = wp_upload_dir();
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}bizuno_%'", ARRAY_A);
    foreach ($tables as $row) { $table = array_shift($row); $wpdb->query("DROP TABLE IF EXISTS $table"); } // drop the Bizuno tables
    bizunoRmdir($upload_dir['basedir'].'/bizuno');
}

//Recursive support function to remove Bizuno files from the uploads folder
function bizunoRmdir($dir) {
    if (!is_dir($dir)) { return; }
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object == "." || $object == "..") { continue; }
        if (is_dir($dir."/".$object)) { bizunoRmdir($dir."/".$object); } else { unlink($dir."/".$object);  }
    }
    rmdir($dir);
}

<?php
/*
Plugin Name: Integrate Instamojo with Gravity Forms
Plugin URI: https://wordpress.org/plugins/integrate-instamojo-with-gravity-forms
Description: With the Gravity Forms Instamojo Add-On, you can easily accept payments from over different payment methods, making it a great fit for any business wanting to sell products or services to their customers.
Version: 1.0.0
Stable tag: 1.0.0
Author: Scrippter
Author URI: https://scrippter.com
Text Domain: integrate-instamojo-with-gravity-forms
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==
With the Gravity Forms Instamojo Add-On, you can easily accept payments from over different payment methods, making it a great fit for any business wanting to sell products or services to their customers.
*/

define('GF_INSTAMOJO_VERSION', '1.0.0');

add_action('gform_loaded', array('GF_Instamojo_Bootstrap', 'load'), 5);
add_action('admin_post_gf_instamojo_init', 'gf_instamojo', 10);

/**
 * Load bootstrap class for instamojo
 */
class GF_Instamojo_Bootstrap
{
    /**
     * Load payment method for gravity form
     */
    public static function load()
    {
        if (method_exists('GFForms', 'include_payment_addon_framework') === false) {
            return;
        }
        
        require_once('class-gf-instamojo.php');
        GFAddOn::register('GF_Instamojo');

        add_filter('gform_currencies', function (array $currencies) {
            $currencies['INR'] = array(
                'name' => __('Indian Rupee', 'gravityforms'),
                'symbol_left' => '&#8377;',
                'symbol_right' => '',
                'symbol_padding' => ' ',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'decimals' => 2,
                'code' => 'INR',
            );
            return $currencies;
        });
    }
}

/**
 * @return GF_Instamojo|null
 */
function gf_instamojo()
{
    return GF_Instamojo::get_instance();
}
?>
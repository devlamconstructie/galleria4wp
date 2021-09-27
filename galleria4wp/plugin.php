<?php
/*
Plugin Name: Galleria4WP: Galleria For Wordpress
Plugin URI: https://www.devlaminteractie.nl
Description: Fork of AMW Galleria by Andy Whalen. Displays a beautiful Galleria slideshow in place of the built-in WordPress image grid. Overrides the default functionality of the [gallery] shortcode.
Version: 1.1.0
Author: Willem de Vlam / Andy Whalen
Author URI: https://www.devlaminteractie.nl
License: The MIT License
*/

$plugin_url = plugins_url(basename(dirname(__FILE__)));
$settingfields = array(
    'theme' => 'galleria4wp_themename',
    'css'   => 'galleria4wp_css'
);

if (! is_admin(  )){
    require_once dirname(__FILE__) . '/includes/galleria4wp.php';
    $galleria4wp = new Galleria4wp($plugin_url, $settingfields);
}

if (is_admin() )  {
    require_once dirname(__FILE__) . '/includes/galleria4wp_admin.php';
    $galleria4wp_admin_page = new Galleria4wpAdminPage($plugin_url, $settingfields);
}  


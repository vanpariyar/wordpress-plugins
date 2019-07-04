<?php
/**
 * Plugin Name: Creole Plugin demo
 * Plugin URI: https://github.com/vanpariyar/wordpress-plugins/creole-demo
 * Description: Display content using a shortcode to insert in a page or post
 * Version: 0.1
 * Text Domain: creole-wordpress-plugin-demo
 * Author: Ronak Vanpariya
 * Author URI: https://vanpariyar.github.io
 */

function creole_wordpress_plugin_demo($atts) {
	$Content = "<style>\r\n";
	$Content .= "h3.demoClass {\r\n";
	$Content .= "color: #26b158;\r\n";
	$Content .= "}\r\n";
	$Content .= "</style>\r\n";
	$Content .= '<h3 class="demoClass">Check it out!</h3>';
	 
    return $Content;
}
add_shortcode('creole-plugin-demo', 'creole_wordpress_plugin_demo');
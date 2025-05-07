<?php
/*
Plugin Name: Gallery Custom Links
Plugin URI: https://meowapps.com
Description: Gallery Custom Links allows you to link images from galleries to a specified URL. Tested with WordPress Gallery, Gutenberg, the Meow Gallery and others.
Version: 2.2.3
Author: Jordy Meow
Author URI: https://meowapps.com
Text Domain: gallery-custom-links
Domain Path: /languages

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

if ( !defined( 'MGCL_VERSION' ) ) {
  define( 'MGCL_VERSION', '2.2.3' );
  define( 'MGCL_PREFIX', 'mgcl' );
  define( 'MGCL_DOMAIN', 'gallery-custom-links' );
  define( 'MGCL_ENTRY', __FILE__ );
  define( 'MGCL_PATH', dirname( __FILE__ ) );
  define( 'MGCL_URL', plugin_dir_url( __FILE__ ) );
  define( 'MGCL_BASENAME', plugin_basename( __FILE__ ) );
}

require_once( 'classes/init.php' );

?>

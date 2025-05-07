<?php

if ( class_exists( 'Meow_MGCL_Core' ) ) {
	function mfrh_admin_notices() {
		echo '<div class="error"><p>Thanks for installing Gallery Custom Links :) However, another version is still enabled. Please disable or uninstall it.</p></div>';
	}
	add_action( 'admin_notices', 'mfrh_admin_notices' );
	return;
}

spl_autoload_register(function ( $class ) {
  $necessary = true;
  $file = null;
  if ( strpos( $class, 'Meow_MGCL' ) !== false ) {
    $file = MGCL_PATH . '/classes/' . str_replace( 'meow_mgcl_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommon_' ) !== false ) {
    $file = MGCL_PATH . '/common/' . str_replace( 'meowcommon_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommonPro_' ) !== false ) {
    $necessary = false;
    $file = MGCL_PATH . '/common/premium/' . str_replace( 'meowcommonpro_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowPro_MGCL' ) !== false ) {
    $necessary = false;
    $file = MGCL_PATH . '/premium/' . str_replace( 'meowpro_mgcl_', '', strtolower( $class ) ) . '.php';
  }
  if ( $file ) {
    if ( !$necessary && !file_exists( $file ) ) {
      return;
    }
    require( $file );
  }
});

new Meow_MGCL_Core();

?>
<?php
class Meow_MGCL_Admin extends MeowCommon_Admin {

	public $core;

	public function __construct() {
		parent::__construct( MGCL_PREFIX, MGCL_ENTRY, MGCL_DOMAIN, class_exists( 'MeowPro_MGCL_Core' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );
			add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 10, 2 );
			add_filter( 'attachment_fields_to_save', array( $this, 'apply_filter_attachment_fields_to_save' ), 10 , 2 );

			// Load the scripts only if they are needed by the current screen
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}
	}

	function admin_enqueue_scripts() {

		// Load the scripts
		$physical_file = MGCL_PATH . '/app/index.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : MGCL_VERSION;
		wp_register_script( 'mgcl_gallery_custom_links-vendor', MGCL_URL . 'app/vendor.js',
			['wp-element', 'wp-i18n'], $cache_buster
		);
		wp_register_script( 'mgcl_gallery_custom_links', MGCL_URL . 'app/index.js',
			['mgcl_gallery_custom_links-vendor', 'wp-i18n'], $cache_buster
		);
		wp_set_script_translations( 'mgcl_gallery_custom_links', 'gallery-custom-links' );
		wp_enqueue_script('mgcl_gallery_custom_links' );

		// Load the fonts
		wp_register_style( 'meow-neko-ui-lato-font', '//fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap');
		wp_enqueue_style( 'meow-neko-ui-lato-font' );

		// Localize and options
		wp_localize_script( 'mgcl_gallery_custom_links', 'mgcl_gallery_custom_links', array_merge( [
			'api_url' => rest_url( 'gallery-custom-links/v1' ),
			'rest_url' => rest_url(),
			'plugin_url' => MGCL_URL,
			'prefix' => MGCL_PREFIX,
			'domain' => MGCL_DOMAIN,
			'is_pro' => class_exists( 'MeowPro_MGCL_Core' ),
			'is_registered' => !!$this->is_registered(),
			'rest_nonce' => wp_create_nonce( 'wp_rest' )
		], $this->get_all_options() ) );
	}

	function is_registered() {
		return apply_filters( $this->prefix . '_meowapps_is_registered', false, $this->prefix  );
	}

	function attachment_fields_to_edit( $fields, $post ) {
		$fields['gallery_link_url'] = array(
			'label' => __( 'Link URL', 'gallery-custom-links' ),
			'input' => 'text',
			'value' => get_post_meta( $post->ID, '_gallery_link_url', true )
		);
		$target_value = get_post_meta( $post->ID, '_gallery_link_target', true );
		$fields['gallery_link_target'] = array(
			'label' => __( 'Link Target', 'gallery-custom-links' ),
			'input' => 'html',
			'html'  => '
				<select class="widefat" name="attachments[' . $post->ID . '][gallery_link_target]" id="attachments[' . $post->ID . '][gallery_link_target]">
					<option value="_self"' . ( $target_value == '_self' ? ' selected="selected"' : '' ) . '>' .
						__( 'Same page', 'gallery-custom-links' ) .
					'</option>
					<option value="_blank"' . ( $target_value == '_blank' ? ' selected="selected"' : '' ) . '>' .
						__( 'New page', 'gallery-custom-links' ) .
					'</option>
				</select>'
			);
		// XXXX: Custom modification to add "noopener noreferrer" als REL-option, Christoph Letmaier, 14.01.2020
		$rel_value = get_post_meta( $post->ID, '_gallery_link_rel', true );
		$fields['gallery_link_rel'] = array(
			'label' => __( 'Link Rel', 'gallery-custom-links' ),
			'input' => 'text',
			'value' => get_post_meta( $post->ID, '_gallery_link_rel', true )
		);
		// XXXX: Custom code for new aria-label field, Christoph Letmaier, 14.01.2020
		$fields['gallery_link_aria'] = array(
			'label' => __( 'Arial Label', 'gallery-custom-links' ),
			'input' => 'text',
			'value' => get_post_meta( $post->ID, '_gallery_link_aria', true )
		);
		
		return $fields;
	}

	function apply_filter_attachment_fields_to_save( $post, $attachment ) {
		if ( isset( $attachment['gallery_link_url'] ) )
			update_post_meta( $post['ID'], '_gallery_link_url', $attachment['gallery_link_url'] );
		if ( isset( $attachment['gallery_link_target'] ) )
			update_post_meta( $post['ID'], '_gallery_link_target', $attachment['gallery_link_target'] );
		if ( isset( $attachment['gallery_link_rel'] ) )
			update_post_meta( $post['ID'], '_gallery_link_rel', $attachment['gallery_link_rel'] );
		// XXXX: Custom code for saving _gallery_link_aria, Christoph Letmaier, 14.01.2020
		if ( isset( $attachment['gallery_link_aria'] ) )
			update_post_meta( $post['ID'], '_gallery_link_aria', $attachment['gallery_link_aria'] );
		return $post;
	}

	function app_menu() {
		add_submenu_page( 'meowapps-main-menu', 'Gallery Custom Links', 'Custom Links', 'manage_options',
			'mgcl_settings', array( $this, 'admin_settings' ) );
	}

	function admin_settings() {
		echo '<div id="mgcl-admin-settings"></div>';
	}

	function list_options() {
		return array(
			'mgcl_obmode' => true,
			'mgcl_parsing_engine' => 'HtmlDomParser',
			'mgcl_log' => false,
			'mgcl_button_enabled' => false,
			'mgcl_button_label' => "Click here",
		);
	}

	function meta_options() {
		return array(
			'_gallery_link_url' => '',
			'_gallery_link_target' => '',
			'_gallery_link_rel' => '',
			'_gallery_link_aria' => '',
		);
	}

	function get_all_options() {
		$options = $this->list_options();
		$current_options = array();
		foreach ( $options as $option => $default ) {
			$current_options[$option] = get_option( $option, $default );
		}
		return $current_options;
	}
}

?>
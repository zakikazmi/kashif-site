<?php

require trailingslashit( MGCL_PATH ) . 'vendor/autoload.php';

class Meow_MGCL_Core
{
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $isEnabled = true;
	// use OB on the whole page, or only go through the the_content ($renderingMode will be ignored)
	public $isObMode = true;
	// 'HtmlDomParser' (less prone to break badly formatted HTML), 'DiDom' (faster) or 'Javascript'
	public $parsingEngine = 'HtmlDomParser';
	public $enableLogs = false;
	public $site_url = null;
	private $is_visitor = false;
	private $namespace = 'gallery-custom-links/v1';

	public function __construct() {
		$this->site_url = get_site_url();
		$this->isObMode = get_option( 'mgcl_obmode', $this->isObMode );
		$this->parsingEngine = get_option( 'mgcl_parsing_engine', $this->parsingEngine );
		$this->enableLogs = get_option( 'mgcl_log', $this->enableLogs );
		$this->is_rest = MeowCommon_Helpers::is_rest();
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
		$this->is_visitor = !$this->is_cli && !$this->is_rest && !is_admin();
		class_exists( 'MeowPro_MGCL_Core' ) && new MeowPro_MGCL_Core( $this );
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// For visitors (client-side)
		if ( $this->is_visitor ) {

			if ( $this->parsingEngine === 'None' ) {
				return;
			}

			if ( $this->parsingEngine === 'Javascript' ) {
				add_action( 'wp_footer', array( $this, 'linkify_script' ), 10 ) ;
				return;
			}

			if ( $this->isObMode ) {
				add_action( 'template_redirect', array( $this, 'start' ) );
				add_action( 'shutdown', array( $this, 'shutdown' ) );
				add_action( 'wp_footer', array( $this, 'unlink_lightboxes_script' ) ) ;
			}
			else {
				add_filter( 'the_content', array( $this, 'linkify' ), 100 );
				add_action( 'wp_footer', array( $this, 'unlink_lightboxes_script' ) ) ;
			}

			add_filter( 'mgl_link_attributes', array( $this, 'meow_gallery_link_attributes' ), 10, 3 );

			$button_enabled = get_option( 'mgcl_button_enabled', false );
			if ( $button_enabled ) {
				require_once( 'button/gutenberg.php' );
				new Meow_MGCL_Core_Button_Gutenberg( $this );
				require_once( 'button/native_gallery.php' );
				new Meow_MGCL_Core_Button_Native_Gallery( $this );
				require_once( 'button/meow_gallery.php' );
				new Meow_MGCL_Core_Button_Meow_Gallery( $this );
			}
		}
	}

	function init() {
		// Part of the core, settings and stuff
		$this->admin = new Meow_MGCL_Admin();

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_MGCL_Rest( $this, $this->admin );
		}
		//add_action( 'init', array( $this, 'init' ) );
		// We don't need this now, we go through all the images.
		new Meow_MGCL_Extra();

		// Add the rest endpoint when the parsing engine is Javascript
		if ( $this->parsingEngine === 'Javascript' ) {
			add_action( 'rest_api_init', array( $this, 'add_rest_api' ) );
		}
	}

	function add_rest_api() {
		try {
			register_rest_route( $this->namespace, '/link_settings', array(
				'methods' => 'POST',
				'callback' => array( $this, 'rest_link_settings' ),
			) );
		}
		catch (Exception $e) {
			var_dump($e);
		}
	}

	function start() {
		$this->isEnabled = apply_filters( 'gallery_custom_links_enabled', true );
		if ( $this->isEnabled && $this->isObMode )
			ob_start( array( $this, "linkify" ) );
	}

	// Clean the path from the domain and common folders
	// Originally written for the WP Retina 2x plugin
	function get_pathinfo_from_image_src( $image_src ) {
		$uploads = wp_upload_dir();
		$uploads_url = trailingslashit( $uploads['baseurl'] );
		if ( strpos( $image_src, $uploads_url ) === 0 )
			return ltrim( substr( $image_src, strlen( $uploads_url ) ), '/');
		else if ( strpos( $image_src, wp_make_link_relative( $uploads_url ) ) === 0 )
			return ltrim( substr( $image_src, strlen( wp_make_link_relative( $uploads_url ) ) ), '/');
		$img_info = parse_url( $image_src );
		return ltrim( $img_info['path'], '/' );
	}

	function resolve_image_id( $url ) {
		global $wpdb;
		global $galleryCustomLinksCache;
		if ( !is_array( $galleryCustomLinksCache ) ) {
		  $galleryCustomLinksCache = [];
		}
		$pattern = '/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/';
		$url = preg_replace( $pattern, '', $url );
		$url = $this->get_pathinfo_from_image_src( $url );
		$urlLike = '%' . $url . '%';
		if ( array_key_exists( $urlLike, $galleryCustomLinksCache ) ) {
		  $attachment = $galleryCustomLinksCache[$urlLike];   
		}
		else {
			$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%s' LIMIT 1", $urlLike );
			$attachment = $wpdb->get_col( $query );
			// Code proposed by @wizardcoder
			// https://wordpress.org/support/topic/issue-with-page-links-on-images/
			if ( empty( $attachment ) ) {
				$query = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta 
					WHERE meta_value LIKE '%s' 
					AND meta_key = '_wp_attached_file' 
					LIMIT 1", $urlLike 
				);
				$attachment = $wpdb->get_col( $query );
			}
			$galleryCustomLinksCache[$urlLike] = $attachment;
		}
		return empty( $attachment ) ? null : $attachment[0];
	}

	function linkify_element( $element ) {
		$mediaId = null;
		$url = null;

		// 1. If there is an Attachment ID
		$mediaId = $this->parsingEngine === 'HtmlDomParser' ? $element->{'data-attachment-id'} : $element->attr('data-attachment-id');

		// 2. Check if the wp-image-xxx class exists
		if ( empty( $mediaId ) ) {
			$classes = $this->parsingEngine === 'HtmlDomParser' ? $element->class : $element->attr('class');
			if ( preg_match( '/wp-image-([0-9]{1,10})/i', $classes, $matches ) )
				$mediaId = $matches[1];
		}

		// 3. Otherwise, resolve the ID from the URL
		if ( empty( $mediaId ) ) {
			$url = $this->parsingEngine === 'HtmlDomParser' ? $element->src : $element->attr('src');
			$mediaId = $this->resolve_image_id( $url );
		}

		// if ( $this->enableLogs ) {
		// 	error_log( 'Linker: Found img tag with classes: ' . $classes );
		// }

		if ( $mediaId ) {
			$url = get_post_meta( $mediaId, '_gallery_link_url', true );
			if ( !empty( $url ) ) {
				$target = get_post_meta( $mediaId, '_gallery_link_target', true );
				$rel = get_post_meta( $mediaId, '_gallery_link_rel', true );
				// XXXX: Custom code for fetching _gallery_link_aria, Christoph Letmaier, 14.01.2020
				$aria = get_post_meta( $mediaId, '_gallery_link_aria', true );
				if ( empty( $target ) )
					$target = '_self';
				$parent = $element->parent();
				if ( $this->enableLogs ) {
					error_log( 'Linker: Found Media ' . $mediaId . ' (URL: ' . $url . ')' );
				}
				// XXXX: Custom code with $aria Christoph Letmaier, 14.01.2020
				$handled = apply_filters( 'mgcl_linkers', false, $element, $parent, $mediaId, $url, $rel, $aria, $target );
				if ( !$handled ) {
					$linker = new Meow_MGCL_Linker( $this );
					// XXXX: Custom code with $aria Christoph Letmaier, 14.01.2020
					$linker->linker( $element, $parent, $mediaId, $url, $rel, $aria, $target );
				}
				return true;
			}
		}
		return false;
	}

	function linkify( $buffer ) {
		$this->isEnabled = apply_filters( 'gallery_custom_links_enabled', true );
		if ( !$this->isEnabled || !isset( $buffer ) || trim( $buffer ) === '' )
			return $buffer;
		if ( $this->parsingEngine === 'HtmlDomParser' ) {
			$html = new KubAT\PhpSimple\HtmlDomParser();
			$html = $html->str_get_html( $buffer, true, true, DEFAULT_TARGET_CHARSET, false );
		}
		else {
			$html = new DiDom\Document();
			$html->preserveWhiteSpace();
			if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) )
				$html->loadHtml( (string)$buffer, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			else
				$html->loadHtml( (string)$buffer, 0 );
		}

		if ( empty( $html ) || is_bool( $html ) ) {
			return $buffer;
		}

		$hasChanges = false;
		// array( '.entry-content', '.gallery', '.wp-block-gallery' )
		$classes = apply_filters( 'gallery_custom_links_classes', array( '' ) );
		foreach ( $classes as $class ) {
			foreach ( $html->find( $class . ' img' ) as $element ) {
				$hasChanges = $this->linkify_element( $element ) || $hasChanges;
			}
		}
		$finalHtml = $this->parsingEngine === 'HtmlDomParser' ? $html : $html->html();
		return $hasChanges ? $finalHtml : $buffer;
	}

	function sanitize_url( $url ) {
		$has_http_https = preg_match( '/^https?:\/\//', $url );
		$escaped = esc_url( $url );
		
		// Remove the http:// or https:// if it was not there, so we can keep the URL relative
		if ( !$has_http_https ) {
			$escaped = str_replace( 'http://', '', $escaped );
			$escaped = str_replace( 'https://', '', $escaped );
		}

		return $escaped;
	}

	function meow_gallery_link_attributes( $link_attributes, $mediaId, $data ) {
		$link = get_post_meta( $mediaId, '_gallery_link_url', true );
		$link = filter_var( $link, FILTER_SANITIZE_URL );
		if ( !empty( $link ) ) {
			$target = get_post_meta( $mediaId, '_gallery_link_target', true );
			$rel = get_post_meta( $mediaId, '_gallery_link_rel', true );
			$link_attributes['href'] = empty( $link ) ? '' : $link;
			$link_attributes['type'] = 'link';
			$link_attributes['target'] = empty( $target ) ? '_self' : $target;
			$link_attributes['rel'] = empty( $rel ) ? '' : $rel;
		}
		return $link_attributes;
	}

	function shutdown() {
		if ( !$this->isEnabled ){
			return;
		}
			
		$ob_levels = ob_get_level();
        $max_ob_levels = 1000;
		
        for ( $i = 0; $i < $ob_levels && $i < $max_ob_levels; $i++ ) {
            ob_end_flush();
        }

        return;
	}

	function unlink_lightboxes_script() {
		?>
			<script>
				// Used by Gallery Custom Links to handle tenacious Lightboxes
				//jQuery(document).ready(function () {

					function mgclInit() {
						
						// In jQuery:
						// if (jQuery.fn.off) {
						// 	jQuery('.no-lightbox, .no-lightbox img').off('click'); // jQuery 1.7+
						// }
						// else {
						// 	jQuery('.no-lightbox, .no-lightbox img').unbind('click'); // < jQuery 1.7
						// }

						// 2022/10/24: In Vanilla JS
						var elements = document.querySelectorAll('.no-lightbox, .no-lightbox img');
						for (var i = 0; i < elements.length; i++) {
						 	elements[i].onclick = null;
						}


						// In jQuery:
						//jQuery('a.no-lightbox').click(mgclOnClick);

						// 2022/10/24: In Vanilla JS:
						var elements = document.querySelectorAll('a.no-lightbox');
						for (var i = 0; i < elements.length; i++) {
						 	elements[i].onclick = mgclOnClick;
						}

						// in jQuery:
						// if (jQuery.fn.off) {
						// 	jQuery('a.set-target').off('click'); // jQuery 1.7+
						// }
						// else {
						// 	jQuery('a.set-target').unbind('click'); // < jQuery 1.7
						// }
						// jQuery('a.set-target').click(mgclOnClick);

						// 2022/10/24: In Vanilla JS:
						var elements = document.querySelectorAll('a.set-target');
						for (var i = 0; i < elements.length; i++) {
						 	elements[i].onclick = mgclOnClick;
						}
					}

					function mgclOnClick() {
						if (!this.target || this.target == '' || this.target == '_self')
							window.location = this.href;
						else
							window.open(this.href,this.target);
						return false;
					}

					// From WP Gallery Custom Links
					// Reduce the number of  conflicting lightboxes
					function mgclAddLoadEvent(func) {
						var oldOnload = window.onload;
						if (typeof window.onload != 'function') {
							window.onload = func;
						} else {
							window.onload = function() {
								oldOnload();
								func();
							}
						}
					}

					mgclAddLoadEvent(mgclInit);
					mgclInit();

				//});
			</script>
		<?php
	}

	function linkify_script() {
		?>
			<script>
				async function linkify() {
					const idWithElements = [];
					const elements = document.querySelectorAll('[class^="wp-image-"]');
					const ids = Array.from(elements).map((element, i) => {
						const classes = element.className.split(' ');
						const wp_image_id = classes.find((c) => c.startsWith('wp-image-'));
						const id = wp_image_id.replace('wp-image-', '');
						idWithElements.push({ key: i, id, element });
						return id;
					});

					const response = await fetch('<?php echo rest_url( 'gallery-custom-links/v1' ); ?>/link_settings', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
						},
						body: JSON.stringify({
							ids,
							ancestors: idWithElements.map((v) => ({
								key: v.key,
								id: v.id,
								classNames: v.element.parentElement.className + ' ' + v.element.parentElement.parentElement.className
							}))
						})
					});
					const result = await response.json();
					if ( !result.success ) {
						return;
					}
					const { linkSettings, buttons } = result.data;
					idWithElements.forEach((v) => {
						const { key, id, element } = v;
						const { link_url, link_target, link_rel, link_aria } = linkSettings[id];
						if ( link_url ) {
							const button_html = buttons.find((b) => b.key === key)?.html;
							if (button_html) {
								element.insertAdjacentHTML('afterend', button_html);
							} else {
								element.addEventListener('click', (e) => {
									if (link_target === '_blank') {
										window.open(link_url, '_blank');
									} else {
										window.location.href = link_url;
									}
								});
								element.style.cursor = 'pointer';
								element.setAttribute('aria-label', link_aria);
								element.setAttribute('rel', link_rel ?? '');
								element.setAttribute('role', 'link');
							}
						}
					});
				}
				linkify();
			</script>
		<?php
	}

	function rest_link_settings( $request ) {
		$ids = (array)$request->get_param( 'ids' );
		$ids = array_unique( $ids );
		if ( !$ids || count( $ids ) === 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Missing ids parameter.' ], 400 );
		}
		$link_settings = [];
		foreach ( $ids as $media_id ) {
			$link_url = get_post_meta( $media_id, '_gallery_link_url', true );
			$link_target = get_post_meta( $media_id, '_gallery_link_target', true );
			$link_rel = get_post_meta( $media_id, '_gallery_link_rel', true );
			$link_aria = get_post_meta( $media_id, '_gallery_link_aria', true );
			$link_settings[$media_id] = [
				'link_url' => $link_url,
				'link_target' => $link_target,
				'link_rel' => $link_rel,
				'link_aria' => $link_aria,
			];
		}
		$ancestors = (array)$request->get_param( 'ancestors' );
		$buttons = [];
		if ( get_option( 'mgcl_button_enabled', false ) ) {
			require_once( 'button/gutenberg.php' );
			new Meow_MGCL_Core_Button_Gutenberg( $this );
			require_once( 'button/native_gallery.php' );
			new Meow_MGCL_Core_Button_Native_Gallery( $this );
			require_once( 'button/meow_gallery.php' );
			new Meow_MGCL_Core_Button_Meow_Gallery( $this );

			$label = get_option( 'mgcl_button_label', "Click here" );
			foreach ( $ancestors as $value ) {
				$link_setting = $link_settings[$value['id']];
				$buttons[] = [
					'key' => $value['key'],
					'html' => $link_setting['link_url']
						? apply_filters( 'mgcl_button_linker', '', $value['classNames'], $link_setting['link_url'], $label, $link_setting['link_rel'], $link_setting['link_target'] )
						: '',
				];
			}
		}
		return new WP_REST_Response( [
			'success' => true,
			'data' => [
				'linkSettings' => $link_settings,
				'buttons' => $buttons,
			]
		], 200 );
	}

	/**
	 * Roles & Access Rights
	 */
	
	public function can_access_settings() {
		return apply_filters( 'mgcl_allow_setup', current_user_can( 'manage_options' ) );
	}

	public function can_access_features() {
		return apply_filters( 'mgcl_allow_usage', current_user_can( 'administrator' ) );
	}
}

?>

<?php

class Meow_MGCL_Core_Button_Gutenberg {

	public function __construct( $core ) {
    $this->core = $core;
    add_filter( 'mgcl_linkers', array( $this, 'linker' ), 100, 7 );
    add_filter( 'mgcl_button_linker', array( $this, 'button_linker' ), 100, 6 );
	}

  function get_style( $id ) {
    return "<style>
      #mgcl-${id} {
        width: 100%;
        height: 100%;
        position: absolute;
        display: flex;
        justify-content: end;
        padding: 5px;
      }
      #mgcl-${id} a {
        height: auto !important;
        flex: none !important;
      }
      #mgcl-${id} a {
        font-size: 15px;
        text-decoration: none;
        padding: 2px 10px;
        box-shadow: 0px 0px 1px 0px rgba(0, 0, 0, 0.5);
        border-radius: 10px;
        text-align: center;
        background: rgba(15, 115, 239, 0.80);
        color: white;
      }
      #mgcl-${id} a:hover { 
        background: rgba(15, 115, 239, 0.9);
      }
    </style>";
  }

  function get_button_style( $id ) {
    return "<style>
      #mgcl-${id} {
        position: relative;
      }
      #mgcl-${id} a {
        position: absolute; 
        bottom: 8px; left: 8px;
        height: auto !important;
        width: calc(100% - 16px);
        flex: none !important;
        box-sizing: border-box;
        font-size: 15px;
        text-decoration: none;
        padding: 2px 10px;
        box-shadow: 0px 0px 1px 0px rgba(0, 0, 0, 0.5);
        border-radius: 10px;
        text-align: center;
        background: rgba(15, 115, 239, 0.80);
        color: white;
        z-index: 100;
      }
      #mgcl-${id} a:hover { 
        background: rgba(15, 115, 239, 0.9);
      }
    </style>";
  }

	function linker( $handled, $element, $parent, $mediaId, $url, $rel, $target ) {
    // Let's look for the closest link tag enclosing the image

    $elemBlocksGalleryItem = $parent->parent();
    $elemBlocksGalleryItem = $parent;

    $url = $this->core->sanitize_url( $url );

    $classes = explode( ' ', $elemBlocksGalleryItem->class );
    if ( $handled || !in_array( 'wp-block-image', $classes ) ) {
      return $handled;
    }

    $id = uniqid();
    $style = $this->get_style( $id );

    if ( $this->core->enableLogs ) {
      error_log( 'Linker: Will embed the IMG tag.' );
    }
    $label = $value = get_option( 'mgcl_button_label', "Click here" );
    if ( $this->core->parsingEngine === 'HtmlDomParser' ) {
      $elemBlocksGalleryItem->innertext = $elemBlocksGalleryItem->innertext . $style . '<div id="mgcl-' . $id . '"><a href="' . $url . 
        '" class="custom-link-button no-lightbox" onclick="event.stopPropagation()" target="' . $target . '" rel="' . $rel . '">
        ' . $label . '</a></div>';
    }
    else {
      return false;
    }
    return true;
	}

  function button_linker( $html, $class_names, $url, $label, $rel, $target ) {
    $classes = explode( ' ', $class_names );
    if ( !in_array( 'wp-block-image', $classes ) ) {
      return $html;
    }

    $id = uniqid();
    $style = $this->get_button_style( $id );
    return $style . '<div id="mgcl-' . $id . '"><a href="' . $url . '" class="custom-link-button no-lightbox" onclick="event.stopPropagation()" target="' . $target . '" rel="' . $rel . '">
    ' . $label . '</a></div>';
  }
}

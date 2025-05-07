<?php

class Meow_MGCL_Linker {

  private $core;

	public function __construct( $core ) {
    $this->core = $core;
    //add_filter( 'mgcl_linkers', array( $this, 'linker' ), 100, 6 );
	}

	// XXXX: Custom code with $aria, Christoph Letmaier, 14.01.2020
	function linker( $element, $parent, $mediaId, $url, $rel, $aria, $target ) {
    // Let's look for the closest link tag enclosing the image
    $url = $this->core->sanitize_url( $url );
    $media = get_post( $mediaId );
    $title = the_title_attribute(  array( 'echo' => false, 'post' => $media )  );
    $potentialLinkNode = $parent;
    $maxDepth = 5;
    do {
      if ( !empty( $potentialLinkNode ) && !empty( $potentialLinkNode->tag ) && $potentialLinkNode->tag === 'a' ) {

        if ( $this->core->enableLogs ) {
          error_log( 'Linker: The current link (' . $potentialLinkNode->{'href'} . ') will be replaced.' );
        }

        if ( $this->core->parsingEngine === 'HtmlDomParser' ) {
          $potentialLinkNode->{'href'} = $url;
          $class = $potentialLinkNode->{'class'};
          $class = empty( $class ) ? 'custom-link no-lightbox' : ( $class . ' custom-link no-lightbox' );
          $potentialLinkNode->{'class'} = $class;
          $potentialLinkNode->{'title'} = $title;
          $potentialLinkNode->{'onclick'} = 'event.stopPropagation()';
          if ( !empty( $target ) )
            $potentialLinkNode->{'target'} = $target;
          if ( !empty( $rel ) )
            $potentialLinkNode->{'rel'} = $rel;
          if ( !empty( $aria ) )
            $potentialLinkNode->{'aria-label'} = $aria;
        }
        else {
          $potentialLinkNode->attr( 'href', $url );
          $class = $potentialLinkNode->attr( 'class' );
          $class = empty( $class ) ? 'custom-link no-lightbox' : ( $class . ' custom-link no-lightbox' );
          $potentialLinkNode->attr( 'class', $class );
          $potentialLinkNode->attr( 'title', $title );
          $potentialLinkNode->attr( 'onclick', 'event.stopPropagation()' );
          if ( !empty( $target ) )
            $potentialLinkNode->attr( 'target', $target );
          if ( !empty( $rel ) )
            $potentialLinkNode->attr( 'rel', $rel );
          if ( !empty( $aria ) )
            $potentialLinkNode->attr['aria-label'] = $aria;
        }
        return true;
      }
      if ( method_exists( $potentialLinkNode, 'parent' ) )
        $potentialLinkNode = $potentialLinkNode->parent();
      else
        break;
    }
    while ( $potentialLinkNode && $maxDepth-- >= 0 );

    // There is no link tag, so we add one and move the image under it
    if ( $this->core->enableLogs ) {
      error_log( 'Linker: Will embed the IMG tag.' );
    }
    if ( $this->core->parsingEngine === 'HtmlDomParser' ) {
	// XXXX: Custom code with $aria, Christoph Letmaier, 22.01.2020
	$element->outertext = '<a href="' . $url . '" class="custom-link no-lightbox" title="' . $title . '" aria-label="' . $aria . '" onclick="event.stopPropagation()" target="' . $target . '" rel="' . $rel . '">' . $element . '</a>';
    }
    else {
      if ( $parent->tag === 'figure' )
      $parent = $parent->parent();
      $a = new DiDom\Element('a');
      $a->attr( 'href', $url );
      $a->attr( 'class', 'custom-link no-lightbox' );
      $a->attr( 'onclick', 'event.stopPropagation()' );
      $a->attr( 'target', $target );
      $a->attr( 'rel', $rel );
	  // XXXX: Custom code with $aria, Christoph Letmaier, 22.01.2020
      $a->attr( 'aria-label', $aria );
      $a->appendChild( $parent->children() );
	  
      foreach( $parent->children() as $img ) {
        $img->remove();
      }
      $parent->appendChild( $a );
    }
    return true;
	}
}

?>
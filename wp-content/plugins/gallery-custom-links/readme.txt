=== Gallery Custom Links ===
Contributors: TigrouMeow
Tags: custom, links, gallery, gutenberg
Donate link: https://www.patreon.com/meowapps
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.2.3

Gallery Custom Links allows you to link images to a specified URL. Tested with WordPress Gallery, Gutenberg, the Meow Gallery and others.

== Description ==

Gallery Custom Links allows you to link images from galleries to a specified URL. Tested with WordPress Gallery, Gutenberg, the [Meow Gallery](https://wordpress.org/plugins/meow-gallery/) and others. The official page is here: [Gallery Custom Links](https://meowapps.com/gallery-custom-links/).

=== Usage ===

Two fields are added to your images, in your Media Library: Link URL and Link Target (but also, Link Rel and Arial Label). If, at least, the Link URL is set up, this image will link to that URL every time it is used within a gallery. Lightbox will be automatically disabled for those images.

To do this, the Gallery Custom Links needs to analyze/rewrite your content. Depending on your WordPress, you can pick the most appropriate method (known as engine).

- HtmlDomParser: Very reliable. It will rewrite your HTML so that the links are hardcoded.
- DiDom: Same as HtmlDomParser but faster. However, your HTML needs to be perfectly valid.
- Javascript: This is the fastest, but the links won't be hardcoded. Only the visitor will experience the links.

=== Compatibility ===

It currently works with the native WP Gallery, the Gutenberg Gallery, and the [Meow Gallery](https://wordpress.org/plugins/meow-gallery/). It should actually work with any gallery plugin using the 'gallery' class and Responsive Images (src-set). Let me know if you would like more galleries to be supported, it should be easy.

=== Filters ===

You can optimize (run the plugin only on the pages where you need it) and support more galleries (through CSS classes) easily by using filters. To know more about this, visit the official page, here: [Gallery Custom Links](https://meowapps.com/gallery-custom-links/).

=== Thanks ===

The motivation to build this plugin came from my users who had issues trying to use WP Gallery Custom Links. I realized that this plugin was working extremely well with the standard gallery, but would require too much rewriting for Gutenberg and other galleries, hence the creation of this plugin. I hope it will help.

Languages: English.

== Installation ==

1. Upload `gallery-custom-links` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Upgrade Notice ==

Replace all the files. Nothing else to do.

== Changelog ==

= 2.2.3 (2025/02/02) =
* Fix: New URL sanitization was causing issues with some URLs.

= 2.2.2 (2025/01/04) =
* Fix: Sanitize URLs to avoid JS injections.
* Info: If you like the plugin, your reviews are welcome [here](https://wordpress.org/support/plugin/gallery-custom-links/reviews/?rate=5#new-post). Thank you :)

= 2.2.1 (2024/11/04) =
* Fix: Fixed common libs.
* Fix: Missing properties (PHP 8.1), non-dynamic properties.
* Update: Remove the dependency to WordPress scripts.

= 2.2.0 (2024/06/03) =
* Add: Updated to the latest libs.

= 2.1.9 (2024/04/06) =
* Add: Javascript method to rewrite the links. It is the fastest method, but the links won't be hardcoded. Only the visitor will experience the links.

= 2.1.8 (2023/10/23) =
* Fix: Addressed and prevented timeouts caused by infinite loops associated with ob levels.
* Fix: Repaired malfunctioning NekoUI components for improved user experience.

= 2.1.7 (2023/09/10) =
* Add: Support for the new version of the Meow Gallery 5 (it will require the version 5.0.1 of the Meow Gallery, which is not released yet but coming soon).

= 2.1.6 (2023/06/11) =
* Update: Lighter plugin.
* Update: Fresh packages.
* Update: Removed useless dependencies.

= 2.1.5 (2022/10/24) =
* Update: No need for jQuery anymore.

= 2.1.4 (2022/10/05) =
* Add: Some files were missing for the libraries autoload.

= 2.1.3 (2022/09/06) =
* Add: Added column in the Media Library (to edit faster).

= 2.1.1 (2022/07/27) =
* Fix: Faster UI.
* Fix: Increased compatibility.

= 2.1.0 (2022/05/17) =
* Fix: Avoid useless logs.

= 2.0.9 (2022/03/18) =
* Fix: Compatibility with the latest version of Gutenberg Gallery.

= 2.0.8 (2022/03/18) =
* Update: Latest versions of DiDom and HTML Dom Parser (might be better and faster).
* Update: Latest version of Neko UI.

= 2.0.6 (2021/09/30) =
* Update: Fix for PHP 7.4+.

= 2.0.5 (2021/08/31) =
* Update: Enhanced security and updated common librairies.

= 2.0.4 (2021/07/05) =
* Update: Refresh the UI libraries and common librairies to ensure compatibility with other plugins.

= 2.0.3 (2021/03/01) =
* Update: New Meow Common (which is needed for compatibility with other plugins).

= 2.0.2 =
* Fix: Autoload was missing.

= 2.0.1 =
* Fix: There were a few issues in this new version.
* Update: New modernized admin.

= 1.2.7 =
* Add: Remove warnings for PHP 7.4.
* Update: New versions of DiDom and the Simple HTML DOM Parser.

= 1.2.6 =
* Add: Because we love the W3C, the title has been added to the link.

= 1.2.5 =
* Fix: Avoid errors in the admin.
* Fix: Add another way to resolve the image ID if none found (https://wordpress.org/support/topic/issue-with-page-links-on-images/), let's see how it goes.

= 1.2.3 =
* Add: Label for CTA buttons.
* Fix: Moved the position of the CTA buttons in the DOM.

= 1.2.2 =
* Add: CTA Buttons for Meow Gallery, Native Galleries and Gutenberg Galleries.
* Add: Filter to... filter which images are actually managed by the plugin :) 
* Fix: Hopefully, the Reusable Blocks aren't broken anymore in the editor (I couldn't replicate the bug on this new version).

= 1.2.0 =
* Add: This was asked to me a lot, so I have adding the settings in order to change the parameters of the plugin easily. It will be much easier to make it faster now.

= 1.1.5 =
* Fix: Simpler and probably better REST detection.

= 1.1.4 =
* Fix: Attempt to fix the way autoload is working.

= 1.1.3 =
* Fix: Avoid analyzing the html content if the parser returned a boolean or an empty string.

= 1.1.2 =
* Add: Rel can now be set to nofollow.

= 1.1.1 =
* Update: Defaults set to Output Buffering + HtmlDomParser. Those settings work for most.

= 1.1.0 =
* Update: Using HtmlDomParser fully (which should avoid broken HTML). It is possible to override the plugin hidden options to switch to different mode, but I am trying to find a mode that works for 99% of the users first.

= 1.0.9 =
* Update: Avoid interfering at all with all Ajax/Rest requests.
* Info: Sorry for the last bunch of updates, some way of modifying HTML works for some, and not for others, and I am still trying to find a solution which works for everyone.

= 1.0.8 =
* Update: Back to OB, maybe there should be an option for that.
* Update: Get all the images of the page/post content instead of within specific containers previously.

= 1.0.7 =
* Update: Not using OB anymore; going through the content filter (this behavior can be changed internally), better and faster this way.
* Fix: Avoid issues with static variables which are not registered on older PHP versions.

= 1.0.6 =
* Fix: Now works with the most tenacious lightboxes.
* Update: The way the HTML was modified to make sure it is compliant.

= 1.0.5 =
* Add: Filter to let the user enables/disables the plugin depending on conditions. Check the official page to know more about this: [Gallery Custom Links](https://meowapps.com/gallery-custom-links/).

= 1.0.4 =
* Fix: Support images embedded in a few layer of tags before the link tag.
* Add: Added a class on the a-tag, for the ones who would like to add some styling to linked images. The Meow Lightbox is already handling this, by avoiding showing a zoom cursor when hovering images.
* Add: Compatibility with extra galleries is made through a filter (which anybody can use) and the file mgcl_extra.php.
* Info: If you like the plugin, your reviews are welcome [here](https://wordpress.org/support/plugin/gallery-custom-links/reviews/?rate=5#new-post. ) :) Thank you!

= 1.0.2 =
* Fix: Now works with thumbnails in src.
* Update: Optimization (does not regenerate pages which aren't impacted by changes).
* Update: DiDom from 1.13 to 1.14.1.

= 1.0.0 =
* Update: If the ID of the Media is not found in the HTML, it will resolve it through the DB from the filename.

= 0.0.1 =
* First release.

== Screenshots ==

1. The fields.

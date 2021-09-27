<?php

/**
 * The Galleria4wp Plugin class
 */
class Galleria4wp {

	protected $url;
	protected $theme;
	protected $custom_css;
	protected $version = '1.1.0';
	protected $galleriaVersion = '1.6.1'; // '1.3.6';

	/**
	 * Constructor
	 *
	 * @param string $pluginUrl The full URL to this plugin's directory.
	 */
		
	public function __construct($pluginUrl, $settingfields) {
		$this->url   = $pluginUrl;
		$this->theme = get_option($settingfields['theme'], 'azur');
		$this->custom_css = get_option($settingfields['css']);
		$this->initialize();
	}
	
	/**
	 * Initializes this plugin
	 */
	public function initialize() {

		// rather than replace the default [gallery] shortcode with: 
		//add_shortcode('gallery', array(&$this, 'galleryShortcode'));
		
		// hook into the filter 'the wordpress way':
		add_filter('post_gallery', array(&$this, 'shortcode_galleria'),10,2);
		
		//enqueue scripts
		add_action( 'wp_enqueue_scripts' , array(&$this, 'enqueue_required_scripts'));



	}

	public function enqueue_required_scripts(){
		// add required scripts and styles to head

		wp_enqueue_script('jquery');

		// use the appropriate files on the cdn.
		$cdn = "https://cdnjs.cloudflare.com/ajax/libs/galleria/" . $this->galleriaVersion . "/";
		$script_location =  $cdn . "themes/" . $this->theme . "/galleria." . $this->theme . ".min";
		wp_enqueue_script('galleria',			$cdn . "galleria.min.js",	'jquery',	$this->galleriaVersion);
		wp_enqueue_script('galleria4wp-theme',	$script_location .  ".js",	'galleria',	$this->galleriaVersion);
		wp_enqueue_style( 'galleria4wp-style',	$script_location .  ".css",	 array(),	$this->galleriaVersion);
	}

	/**
	 * Displays a Galleria slideshow using images attached to the specified post/page.
	 * Hooks into gallery_shortcode($attr) in media.php .
	 *
	 * @param array $attr Attributes of the shortcode.
	 * @return string HTML content to display gallery.
	 */
	public function shortcode_galleria($attr, $instance) {
		
		global $post, $contentwidth; 

		// global content width set for this theme? (see theme functions.php)
		if (!isset($content_width)) $content_width = 'auto';
		
		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if (isset($attr['orderby'])) {
			$attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
			if (!$attr['orderby']) {
				unset($attr['orderby']);
			}
		}

		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( ! empty( $attr['ids'] ) )
			$attr['orderby'] ??= 'post__in';
		
		// the id of the current post, or a different post if specified in the shortcode
		$id =  $post ? $post->ID : 0;

		// 3:2 display ratio of the stage, account for 60px thumbnail strip at the bottom
		$width  = 'auto';
		$height = '0.76'; // a fraction of the width
		$autoplay = false;
		$captions = 'on_expand'; // off, on_hidden, on_expand
		$hideControls = false;
		$html5 = current_theme_supports( 'html5', 'gallery' );
		// merge shortcode attributes with defaults.
		$atts = shortcode_atts(
			array(
				// standard WP [gallery] shortcode options
				'order'        => 'ASC',
				'orderby'      => 'menu_order ID',
				'id'           => $id,
				'itemtag'      => $html5 ? 'figure' : 'dl',
				'icontag'      => $html5 ? 'div' : 'dt',
				'captiontag'   => $html5 ? 'figcaption' : 'dd',
				'columns'      => 3,
				'size'         => 'thumbnail',
				'include'      => '',
				'exclude'      => '',
				'link'         => '',
				'ids'          => '',
				// galleria options
				'width'        => $width,
				'height'       => $height,
				'autoplay'     => $autoplay,
				'captions'     => $captions,
				'hideControls' => $hideControls,
			), 
			$attr, 
			'gallery'
		);
		//prioritize submitted image id's over include
		$include = (empty( $atts['ids'] )) ? $atts['include'] : $atts['ids']; 

		// fetch the images
		if (!empty($include)) {

			$_attachments = get_posts(
				array(
					'include' => $include, 
					'post_status' => 'inherit', 
					'post_type' => 'attachment', 
					'post_mime_type' => 'image', 
					'order' => $atts['order'], 
					'orderby' => $atts['orderby']
				) 
			);
			
			$attachments = array();
			foreach ($_attachments as $key => $val) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif (!empty($atts['exclude'])) {
			$attachments = get_children( 
				array(
					'post_parent' 	 => $id, 
					'exclude' 		 => $atts['exclude'], 
					'post_status' 	 => 'inherit',
					'post_type' 	 => 'attachment', 
					'post_mime_type' => 'image', 
					'order' 		 => $atts['order'], 
					'orderby' 		 => $atts['orderby']
				) 
			);
		} else {
			// default: all images attached to this post/page
			$attachments = get_children(
				array(
					'post_parent' 	 => $id,
					'post_status' 	 => 'inherit',
					'post_type' 	 => 'attachment',
					'post_mime_type' => 'image',
					'order' 		 => $atts['order'],
					'orderby' 	 	 => $atts['orderby']
				) 
			);
		}

		// output nothing if we didn't find any images
		if (empty($attachments)) {
			return '';
		}

		// output the individual images when displaying as a news feed
        if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment ) {
					if ( ! empty( $atts['link'] ) ) {
							if ( 'none' === $atts['link'] ) {
									$output .= wp_get_attachment_image( $att_id, $atts['size'], false, $attr );
							} else {
									$output .= wp_get_attachment_link( $att_id, $atts['size'], false );
							}
					} else {
							$output .= wp_get_attachment_link( $att_id, $atts['size'], true );
					}
					$output .= "\n";
			}
			return $output;
		}

		/** 
		*  galleria4WP
		* @author Andy Whalen
		*************/

		// make an array of images with the proper data for Galleria
		$images = array();
		foreach ($attachments as $attachmentId => $attachment) {
			$thumb = wp_get_attachment_image_src($attachmentId, 'thumbnail');
			$big   = wp_get_attachment_image_src($attachmentId, 'large');
			$image = array(
				'image'       => $big[0],
				'big'         => $big[0],
				'thumb'       => $thumb[0],
				'title'       => $attachment->post_title,
				//'link'        => $attachment->guid,
				'description' => wptexturize($attachment->post_excerpt),
			);
			$images[] = $image;
		}

		// encode the Galleria options as JSON
		$options = json_encode(array(
			'dataSource'        =>           $images,
			'width'             => (is_numeric($width)) ? (int) $width  : (string) $width,
			'height'            => (is_int($height))    ? (int) $height : (float)  $height,
			'imageCrop' 		=> 'height',
			'autoplay'          => (boolean) $autoplay,
			'transition'        =>           'slide',
			'initialTransition' =>           'fade',
			'transitionSpeed'   => (int)     0.5 * 1000, // milliseconds
			'_delayTime'        => (int)     4 * 1000, // milliseconds
			'_hideControls'     =>           $hideControls,
			'_thumbnailMode'    =>           'grid',
			'_captionMode'      =>           $captions,
		));

		// unique ID for this gallery
		$domId = "galleria4wp_gallery_" . $instance;

		// the DOM is built in JavaScript so we just need a placeholder div
		$output .= "<div id=\"" . $domId . "\" class=\"galleria4wp-gallery\"></div>\n";

		// galleria JavaScript output
		// NOTE: WordPress disables the use of the dollar-sign function ($) for compatibility
		$output .= '<script type="text/javascript">jQuery(document).ready(function(){ jQuery("#' . $domId . '").galleria(' . $options . '); });</script>';
		return $output;

	}

	private function return_custom_style_overrides(){


	}

}
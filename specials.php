<?php
/*
Plugin Name: Specials
Version: 1.0
Plugin URI: http://www.halgatewood.com/product-specials
Description: Easily create a product special listing page. Items include a block of text and an image.
Author: Hal Gatewood
Author URI: http://www.halgatewood.com

----

Copyright 2010-2013 Hal Gatewood

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


// NO SPECIALS MESSAGE
function specials_no_specials()
{
	return "Filter available to change this message";
}
add_filter("no_specials_messge", 'specials_no_specials' );


---
*/

define("SPECIALS_POST_TYPE_NAME", "specials");

$no_specials_message = "Currently we are not offering any specials. Please check back soon.";

/* SETUP */
add_action( 'plugins_loaded', 'specials_setup' );
function specials_setup() 
{
	$post_type = apply_filters( "specials_post_type", SPECIALS_POST_TYPE_NAME);

	add_action( 'init', 'create_specials_type' );
	add_action( 'admin_head', 'specials_css' );
	add_action( 'add_meta_boxes', 'specials_create_metaboxes' );


	add_action( 'save_post', 'specials_save_specials_meta', 1, 2 );

}



// CUSTOM POST TYPE
function create_specials_type() 
{
	$post_type = apply_filters( "specials_post_type", SPECIALS_POST_TYPE_NAME);

  	$labels = apply_filters( "specials_post_type_labels", array(
				    'name' 					=> __('Specials', 'specials'),
				    'singular_name' 		=> __('Special', 'specials'),
				    'add_new' 				=> __('Add New', 'specials'),
				    'add_new_item' 			=> __('Add New Special', 'specials'),
				    'edit_item' 			=> __('Edit Special', 'specials'),
				    'new_item' 				=> __('New Special', 'specials'),
				    'all_items' 			=> __('All Specials', 'specials'),
				    'view_item' 			=> __('View Special', 'specials'),
				    'search_items' 			=> __('Search Specials', 'specials'),
				    'not_found' 			=> __('No specials found', 'specials'),
				    'not_found_in_trash' 	=> __('No specials found in Trash', 'specials'), 
				    'parent_item_colon' 	=> '',
				    'menu_name' 			=> __('Specials')
  					));
						
	$args = apply_filters( "specials_post_type_args", array(
					'labels' 				=> $labels,
					'public' 				=> true,
					'publicly_queryable' 	=> true,
					'show_ui' 				=> true, 
					'show_in_menu' 			=> true, 
					'query_var' 			=> true,
					'rewrite' 				=> array ('with_front' => false, 'slug' => apply_filters( 'specials_slug', 'specials')),
					'capability_type' 		=> 'post',
					'has_archive' 			=> true,
					'hierarchical' 			=> false,
					'menu_position' 		=> 26.6,
					'exclude_from_search' 	=> true,
					'supports' 				=> array( 'title', 'excerpt', 'thumbnail', 'page-attributes' )
					));
					
	register_post_type( $post_type, $args );
	
	// REGISTER SHORTCODE
	add_shortcode( 'specials', 'specials_shortcode' );
	
	// POST THUMBNAILS (pippin)
	if(!current_theme_supports('post-thumbnails')) { add_theme_support('post-thumbnails'); }
}

 
// ADMIN: WIDGET ICONS
function specials_css()
{
	$post_type = apply_filters( "specials_post_type", SPECIALS_POST_TYPE_NAME);

	$icon 		= plugins_url( 'specials' ) . "/icons/menu-icon.png";
	$icon_32 	= plugins_url( 'specials' ) . "/icons/icon-32.png";
	
	echo "
		<style> 
			#menu-posts-{$post_type} .wp-menu-image { background: url({$icon}) no-repeat 6px -26px !important; }
			#menu-posts-{$post_type}.wp-has-current-submenu .wp-menu-image { background-position:6px 6px!important; }
			.icon32-posts-{$post_type} { background: url({$icon_32}) no-repeat 0 0 !important; }
		</style>
	";	
}


// META BOXES
function specials_create_metaboxes()
{
	$post_type = apply_filters( "specials_post_type", SPECIALS_POST_TYPE_NAME);

	// SPECIAL OPTIONS
	add_meta_box( 'specials_options', __('Special Options', 'specials'), 'specials_options', $post_type, 'normal', 'default' );
	

}


/* SPECIALS OPTIONS */
function specials_options() 
{
	global $post;	
	
	$specials_custom_css	= get_post_meta( $post->ID, '_specials_custom_css', true );
	
	$specials_layout		= get_post_meta( $post->ID, '_specials_layout', true ); 
	if(!$specials_layout) { $specials_layout = get_transient('specials_layout'); }
	
	$specials_image_size	= get_post_meta( $post->ID, '_specials_image_size', true );
	if(!$specials_image_size) { $specials_image_size = get_transient('specials_image_size'); } 
	
	
	$specials_text_position = get_post_meta( $post->ID, '_specials_text_position', true );
	if(!$specials_text_position) { $specials_text_position = get_transient('specials_text_position'); }	
	
	
	// GET ALL IMAGES SIZES
	$image_sizes = get_intermediate_image_sizes();
	
	// LAYOUTS
	$layouts = array( 'title-only', 'text-only', 'title-text', 'picture-only', 'picture-title', 'picture-text', 'picture-title-text' );
	
	// ALIGN POSITIONS
	$aligns = array( 'top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'  );

	echo "<p>\n";
		echo "Size: <select name='specials_image_size'>\n";
		foreach($image_sizes as $image_size)
		{
			$chk = ($specials_image_size == $image_size) ? " SELECTED" : "";
		
			echo "	<option value='{$image_size}'{$chk}>{$image_size}</options>\n";
		}
		echo "</select>";
	echo "</p>";
	
	echo "<hr style='background: #ccc; height: 1px; font-size: 1px; border: 0;' />";
	
?>
		<input type="hidden" name="specials_layout" id="specials_layout_input" value="<?php echo $specials_layout ?>" />
		
		<div class="specials_layout_options">
			<div style="margin:5px 0;">Layout:</div>
		<?php foreach($layouts as $layout) { ?>
			<img <?php if($specials_layout == $layout) echo "class='selected'"; ?> src="<?php echo plugins_url( 'specials' ); ?>/icons/<?php echo $layout ?>.png" data-val="<?php echo $layout ?>" alt="<?php echo str_replace("-", " ", $layout) ?>" title="<?php echo str_replace("-", " ", $layout) ?>" />
		<?php } ?>
		</div>
		
		<input type="hidden" name="specials_text_position" id="specials_text_position_input" value="<?php echo $specials_text_position ?>" />
		
		<div class="specials_text_positions">
			<div style="margin:5px 0;">Text Position:</div>
		<?php foreach($aligns as $align) { ?>
			<img <?php if($specials_text_position == $align) echo "class='selected'"; ?> src="<?php echo plugins_url( 'specials' ); ?>/icons/<?php echo $align ?>.png" data-val="<?php echo $align ?>" alt="<?php echo str_replace("-", " ", $align) ?>" title="<?php echo str_replace("-", " ", $align) ?>" />
		<?php } ?>
		</div>		
		
		
	<script>
		jQuery(document).ready(function($) {
		
			$(".specials_layout_options img").click(function()
			{ 
				jQuery("#specials_layout_input").val( jQuery(this).data('val') ); 
				
				$(".specials_layout_options img").removeClass("selected");
				jQuery(this).addClass("selected");
			});
			
			$(".specials_text_positions img").click(function()
			{ 
				jQuery("#specials_text_position_input").val( jQuery(this).data('val') ); 
				
				$(".specials_text_positions img").removeClass("selected");
				jQuery(this).addClass("selected");
			});
		});
	</script>	
	<style>
		.specials_layout_options img, .specials_text_positions img { border: solid 3px #ccc; border-radius: 2px; cursor: hand; }
		.specials_layout_options img.selected, .specials_text_positions img.selected { border: solid 3px #457f28; }
	</style>
	
	<hr style='clear: both; background: #ccc; height: 1px; font-size: 1px; border: 0;' />
	
	<div style="margin-top: 15px;">
		Custom CSS:<br />
		<textarea name="specials_custom_css" style="width: 50%; height: 100px;"><?php echo $specials_custom_css ?></textarea>
	</div>
	
<?php
	
}

// META BOXES
function specials_save_specials_meta( $post_id, $post )
{
	global $post;  
	if( isset( $_POST ) && isset( $post->ID ) && get_post_type( $post->ID ) == SPECIALS_POST_TYPE_NAME )  
    {  
		// SAVE
		if ( isset( $_POST['specials_layout'] ) ) 
		{ 
			update_post_meta( $post_id, '_specials_layout', strip_tags( $_POST['specials_layout'] ) );
			set_transient( 'specials_layout', strip_tags( $_POST['specials_layout'] ), 31556925 ); 
		}
		if ( isset( $_POST['specials_image_size'] ) ) 
		{ 
			update_post_meta( $post_id, '_specials_image_size', strip_tags( $_POST['specials_image_size'] ) );
			set_transient( 'specials_image_size', strip_tags( $_POST['specials_image_size'] ), 31556925 );
		}
		if ( isset( $_POST['specials_text_position'] ) ) 
		{ 
			update_post_meta( $post_id, '_specials_text_position', strip_tags( $_POST['specials_text_position'] ) );
			set_transient( 'specials_text_position', strip_tags( $_POST['specials_text_position'] ), 31556925 );
		}
		if ( isset( $_POST['specials_custom_css'] ) ) 
		{ 
			update_post_meta( $post_id, '_specials_custom_css', strip_tags( $_POST['specials_custom_css'] ) );
		}
	}
}



// SPECIALS SHORTCODE
function specials_shortcode($atts)
{
	$id = (int) isset($atts['id']) ? $atts['id'] : 0;
	$orderby = isset($atts['orderby']) ? $atts['orderby'] : "menu_order";
	$order = (isset($atts['order']) AND $atts['order'] == "DESC") ? "DESC" : "ASC";
	$limit = isset($atts['limit']) ? $atts['limit'] : -1;
	
	if(isset($atts['no']))
	{
		global $no_specials_message;
		$no_specials_message = $atts['no'];
	}
	
	return show_specials($id, $orderby, $order, $limit);
}



// BRAINS
function show_specials($id = false, $orderby = "menu_order", $order = "ASC", $limit = -1)
{
	// INCLUDE STYLES
 	if(specials_has_shortcode('specials')) 
 	{  
    	wp_enqueue_style('specials', plugins_url( 'specials' ) . '/specials.css');  
	}
	

	$post_type = apply_filters( "specials_post_type", SPECIALS_POST_TYPE_NAME);

	$specials = array();
	
	if($id) { $specials[] = get_post($id); }
	else
	{
		$specials = get_posts( array( 'numberposts' => $limit, 'orderby' => $orderby, 'order' => $order, 'post_type' => $post_type ));
	}
	
	global $no_specials_message;
	if(!$specials) return "<div class='no_specials'>" . apply_filters("no_specials_messge", $no_specials_message) . "</div>";
	
	$rtn = "<div class='specials_wrapper'>\n";
	foreach($specials as $special)
	{
		$extra_styles = "";
		$width = "100%";
		$height = "auto";
		
		
		$specials_layout 			= get_post_meta( $special->ID, '_specials_layout', true );
		$specials_image_size 		= get_post_meta( $special->ID, '_specials_image_size', true );
		$specials_text_position 	= get_post_meta( $special->ID, '_specials_text_position', true );
		$specials_custom_css 		= get_post_meta( $special->ID, '_specials_custom_css', true );
	
		// FEATURED IMAGE
		$featured_image = wp_get_attachment_image_src( get_post_thumbnail_id( $special->ID ), $specials_image_size );
		if($featured_image) 
		{
			$width = $featured_image[1] ."px";
			$height = $featured_image[2] ."px";
		
			$featured_image = $featured_image[0];  
		}
		
		if($specials_layout == "picture-only" OR $specials_layout == "picture-text" OR $specials_layout == "picture-title-text" OR $specials_layout == "picture-title")
		{
			$extra_styles .= " background-image: url($featured_image); ";
		}
		
		$inlcude_title = false;
		if($specials_layout == "title-only" OR $specials_layout == "title-text" OR $specials_layout == "picture-title-text" OR $specials_layout == "picture-title")
		{
			$inlcude_title = true;
		}
		
		$include_text = false;
		if($specials_layout == "text-only" OR $specials_layout == "title-text" OR $specials_layout == "picture-text" OR $specials_layout == "picture-title-text")
		{
			$include_text = true;
		}		
		
		$extra_styles .= "width: $width; height: $height;";
		
	
		$rtn .= "	<div id='specials_{$special->ID}' class='special {$specials_layout} {$specials_text_position}' style='{$extra_styles}'>\n";
		
			$rtn .= "<div class='specials_text'>\n";
			
			if($inlcude_title) { 	$rtn .= "	<h2>{$special->post_title}</h2>\n"; }
			if($include_text) {		$rtn .= "	<p>{$special->post_excerpt}</p>\n"; }
			
			$rtn .= "</div>\n";	
		
		$rtn .= "	</div>\n";
		
		$rtn .= '	<style> #specials_' . $special->ID . ' { ' . $specials_custom_css . ' } </style>';
		
	}
	$rtn .= "	</div>\n";
	
	
	return $rtn;

}



// check the current post for the existence of a short code. from pippin
function specials_has_shortcode($shortcode = '') 
{
	$post_to_check = get_post(get_the_ID());
	// false because we have to search through the post content first
	$found = false;
	// if no short code was provided, return false
	if (!$shortcode) {
		return $found;
	}
	// check the post content for the short code
	if ( stripos($post_to_check->post_content, '[' . $shortcode) !== false ) {
		// we have found the short code
		$found = true;
	}
	// return our final results
	return $found;
}



?>
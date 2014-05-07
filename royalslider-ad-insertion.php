<?php
/*
Plugin Name: Ad Insertion Plugin for RoyalSlider
Plugin URI:
Description: The plugin inserts an ad slide after a number of slides, as configured in the admin area.
Version: 1.0
Author: PressLabs
Author URI: http://presslabs.com
License: GPLv3
*/


function add_admin_menu_entry() {
	add_submenu_page( 'new_royalslider', 'Ad Insertion', 'Ad Insertion', 'manage_options', 'rs-ad-insertion', 'rs_ad_insertion_page_callback' );
}

add_action( 'admin_menu', 'add_admin_menu_entry', 11 );

function register_settings() {
	register_setting( 'rs-ad-insertion-options', 'gallery_ad_slide_options' );
}

add_action( 'admin_init', 'register_settings' );


function rs_ad_insertion_page_callback() {
?>
	<div class="wrap">
		<h2>Royal Slider Ad Insertion</h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'rs-ad-insertion-options' ); ?>
			<?php $options = get_option( 'gallery_ad_slide_options' );  ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Gallery Ad Slide Code</th>
					<td><textarea name="gallery_ad_slide_options[gallery_ad_slide_code]" cols="50" rows="12"><?php echo $options['gallery_ad_slide_code']; ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">Number of images after which an ad slide must be inserted</th>
					<td><input type="text" name="gallery_ad_slide_options[gallery_ad_slide_index]" size="4" value="<?php echo $options['gallery_ad_slide_index']; ?>" /></td>
				</tr>
				<tr valign="top">
                                        <th scope="row">URL for ad slide thumb</th>
                                        <td><input type="text" name="gallery_ad_slide_options[gallery_ad_slide_thumb]" size="50" value="<?php echo $options['gallery_ad_slide_thumb']; ?>" /></td>
                                </tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}


function insert_ad_slides( $slides_html, $id, $type ) {
	$options = get_option( 'gallery_ad_slide_options' );
	$ad_slide_index = $options['gallery_ad_slide_index'] ? $options['gallery_ad_slide_index'] : 0;
	$ad_slide_thumb = $options['gallery_ad_slide_thumb'] ? $options['gallery_ad_slide_thumb'] : plugin_dir_url( __FILE__ ) . 'ad_slide_tmb.png';
	$ad_slide_code = '<div class="rsSlideRoot"><img syle="height:1px" src="' . plugin_dir_url( __FILE__ ) . 'ad_slide_bk.png" alt="Advertisement" /><div class="rsTmb"><img src="' . $ad_slide_thumb . '" alt="" /></div></div>';
	$pos = -1;
	$slide_count = 0;
	while ( $pos = strpos( $slides_html, '<div class="rsSlideRoot">', $pos + 1 ) ) {
		if ( ( $slide_count != 0 ) && ( $slide_count % $ad_slide_index == 0 ) ) {
			$slides_html = substr_replace( $slides_html, $ad_slide_code, $pos, 0 );
			$pos += strlen( $ad_slide_code );
		}
		$slide_count ++;
	}

	return $slides_html;
}

add_filter( 'new_rs_slides_output_before_end', 'insert_ad_slides', 10, 3 );


function insert_ad_scripts() {
	$options = get_option( 'gallery_ad_slide_options' );
?>
var slider = $(".royalSlider").data('royalSlider');
var ad_slide_index = <?php echo $options['gallery_ad_slide_index'] ? $options['gallery_ad_slide_index'] : 0; ?>;
var ad_slide_code = '<?php echo $options['gallery_ad_slide_code'] ? preg_replace('/\s\s+/', ' ', str_replace( '/', '\/', addslashes( $options['gallery_ad_slide_code'] ) ) ) : ''; ?>';
var loaded_ad_slide = null;
var ad_slide_initial_html = '<?php echo '<img style="height:1px" src="' . plugin_dir_url( __FILE__ ) . 'ad_slide_bk.png" alt="Advertisement" />'; ?>';
slider.ev.on('rsAfterSlideChange', function( event, type, userAction ) {
	if ( slider.currSlideId != loaded_ad_slide ) {
		if ( ( slider.currSlideId + 1 ) % ( ad_slide_index + 1 ) == 0 ) {
			loaded_ad_slide = slider.currSlideId;
			var current_html = slider.slidesJQ[ slider.currSlideId ].html();
			slider.slidesJQ[ slider.currSlideId ].html( "<div style='width:300px;height:300px;margin:0 auto;text-align:center;vertical-align:middle;line-height:300px;'>" + ad_slide_code + "</div>" );
		} else {
			if ( loaded_ad_slide != null ) {
				slider.slidesJQ[ loaded_ad_slide ].html( ad_slide_initial_html );
				loaded_ad_slide = null;
			}
		}
	}
});
<?php
}

add_action('new_rs_after_js_init_code', 'insert_ad_scripts');


function plugin_activation_check() { 
	if ( ! is_plugin_active( 'new-royalslider/newroyalslider.php' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		die( 'Cannot activate plugin. Royal Slider plugin is not active.' );
	}
}

register_activation_hook( __FILE__, 'plugin_activation_check' ); 

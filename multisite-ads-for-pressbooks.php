<?php
/**
* Plugin Name: Multisite Ads for PressBooks
* Plugin URI: https://----.com/
* Version: 0.1
* Author: Peter Shaw
* Author URI: https://-----.com
* Network: true
* Description: Allows you to insert ads after paragraphs of your post content throughout your multisite network. Base on LH Multisite Ads 1.26
* License: GPL2
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* LH Multisite Ads Class
*/


if (!class_exists('LH_multisite_ads_plugin')) {

class LH_multisite_ads_plugin {

var $opt_name = 'lh_multisite_ads-options';
var $posttype = 'lh-multisite-ads';
var $namespace = 'lh_multisite_ads';
var $whitelisted_sites_field_name = 'lh_multisite_ads-whitelisted_sites_field_name';
var $ads_on_indexes_field_name = 'lh_multisite_ads-ads_on_indexes_field_name';

var $options;
var $filename;

private static $instance;

private function array_fix( $array )    {
        return array_filter(array_map( 'trim', $array ));

}

private function is_this_plugin_network_activated(){

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_plugin_active_for_network( $this->path ) ) {
    // Plugin is activated

return true;

} else  {


return false;


}

}

/**
	* Insert something after a specific paragraph in some content.
	*
	* @param  string $insertion    Likely HTML markup, ad script code etc.
	* @param  int    $paragraph_id After which paragraph should the insertion be added. Starts at 1.
	* @param  string $content      Likely HTML markup.
	*
	* @return string               Likely HTML markup.
	*/
private function insertAdAfterParagraph( $insertion, $paragraph_id, $content ) {
		$closing_p = '</div>
</div>';
		$paragraphs = explode( $closing_p, $content );


		foreach ($paragraphs as $index => $paragraph) {
			// Only add closing tag to non-empty paragraphs
			if ( trim( $paragraph ) ) {
				// Adding closing markup now, rather than at implode, means insertion
				// is outside of the paragraph markup, and not just inside of it.
				$paragraphs[$index] .= $closing_p;
			}

			// + 1 allows for considering the first paragraph as #1, not #0.
			if ( $paragraph_id == $index + 1 ) {
				$paragraphs[$index] .= '<div class="'.$this->namespace.'-ads_div">'. $insertion .'</div>';
			}
		}
		return implode( '', $paragraphs );
	}





	/**
	* Register Custom Post Type
	*/

public function register_post_types() {

if (is_main_site()){
		register_post_type($this->posttype, array(
            'labels' => array(
                'name' => _x('Post Adverts', 'post type general name'),
                'singular_name' => _x('Post Advert', 'post type singular name'),
                'add_new' => _x('Add New', 'insertpostads'),
                'add_new_item' => __('Add New Advert'),
                'edit_item' => __('Edit Advert'),
                'new_item' => __('New Advert'),
                'view_item' => __('View Adverts'),
                'search_items' => __('Search Adverts'),
                'not_found' =>  __('No adverts found'),
                'not_found_in_trash' => __('No adverts found in Trash'),
                'parent_item_colon' => ''
            ),
            'description' => 'Post Adverts',
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-migrate',
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'show_in_nav_menus' => false,
            'supports' => array('title','author'),
        ));


}

}


public function add_meta_boxes($post_type, $post) {

add_meta_box($this->namespace."-advert-code-div", "Advert Code", array($this,"ads_metabox_content"), $this->posttype, "normal", "high");

}






/**
	* Displays the meta box on the Custom Post Type
	*
	* @param object $post Post
	*/
public function ads_metabox_content($post) {
		// Get meta
		$adCode = get_post_meta($post->ID, '_ad_code', true);
		$adPosition = get_post_meta($post->ID, '_ad_position', true);
		$paragraphNumber = get_post_meta($post->ID, '_paragraph_number', true);

		// Nonce field
		wp_nonce_field($this->namespace, $this->namespace.'-nonce');
		?>
		<p>
			<textarea name="ad_code" id="ad_code" style="width: 100%; height: 100px; font-family: Courier; font-size: 12px;"><?php echo $adCode; ?></textarea>
		</p>
		<p>
			<label for="ad_position"><?php _e('Display the advert:', $this->plugin->name); ?></label>
			<select name="ad_position" size="1">
				<option value="top"<?php echo (($adPosition == 'top') ? ' selected' : ''); ?>><?php _e('Before Content', $this->namespace); ?></option>
				<option value=""<?php echo (($adPosition == '') ? ' selected' : ''); ?>><?php _e('After Paragraph Number', $this->namespace); ?></option>
				<option value="bottom"<?php echo (($adPosition == 'bottom') ? ' selected' : ''); ?>><?php _e('After Content', $this->namespace); ?></option>
			</select>
			<input type="number" name="paragraph_number" value="<?php echo $paragraphNumber; ?>" min="1" max="999" step="1" id="paragraph_number" />
		</p>
		<?php
	}


/**
	* Saves the meta box field data
	*
	* @param int $post_id Post ID
	*/
public function save_post($post_id) {
		// Check if our nonce is set.
		if (!isset($_POST[$this->namespace.'-nonce'])) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if (!wp_verify_nonce($_POST[$this->namespace.'-nonce'], $this->namespace)) {
			return $post_id;
		}

		// Check the logged in user has permission to edit this post
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}

		// OK to save meta data
		if (isset($_POST['ipa_disable_ads'])) {
			update_post_meta($post_id, '_ipa_disable_ads', sanitize_text_field($_POST['ipa_disable_ads']));
		} else {
			delete_post_meta($post_id, '_ipa_disable_ads');
		}

		if (isset($_POST['ad_code'])) {
			update_post_meta($post_id, '_ad_code', $_POST['ad_code']);
		}
		if (isset($_POST['ad_position'])) {
			update_post_meta($post_id, '_ad_position', sanitize_text_field($_POST['ad_position']));
		}
		if (isset($_POST['paragraph_number']) and is_numeric($_POST['paragraph_number'])) {
			update_post_meta($post_id, '_paragraph_number', sanitize_text_field($_POST['paragraph_number']));
		}
$this->update_query();
	}


public function plugin_menu() {

if (is_main_site()){

add_submenu_page('edit.php?post_type='.$this->posttype, __('LH Multisite Ads', $this->namespace), __('Settings', $this->namespace), 'manage_options', $this->filename, array($this, 'plugin_options'));

}


}

public function plugin_options() {


if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}


// See if the user has posted us some information
    // If they did, the nonce will be set

	if( isset($_POST[ $this->namespace."-backend_nonce" ]) && wp_verify_nonce($_POST[ $this->namespace."-backend_nonce" ], $this->namespace."-backend_nonce" )) {





$whitelisted_sites_pieces = explode(",", sanitize_text_field($_POST[ $this->whitelisted_sites_field_name ]));



if (is_array($whitelisted_sites_pieces )){



$options[ $this->whitelisted_sites_field_name ] = $this->array_fix($whitelisted_sites_pieces);

}

if (isset($_POST[$this->ads_on_indexes_field_name]) and (($_POST[$this->ads_on_indexes_field_name] == "0") || ($_POST[$this->ads_on_indexes_field_name] == "1"))){
$options[$this->ads_on_indexes_field_name] = $_POST[ $this->ads_on_indexes_field_name ];
}



if (update_site_option( $this->opt_name, $options )){

$this->options = get_site_option($this->opt_name);


?>
<div class="updated"><p><strong><?php _e('LH Post Ads settings saved', $this->namespace ); ?></strong></p></div>
<?php

}


}

// Now display the settings editing screen

include ('partials/option-settings.php');



}


public  function update_query(){

    $blog_id = get_current_blog_id();

if (($this->is_this_plugin_network_activated()) && !is_main_site($blog_id)){

switch_to_blog(BLOG_ID_CURRENT_SITE);

}

$query = new WP_Query(array(
			'post_type' => $this->posttype,
			'post_status' => 'publish',
			'posts_per_page' => -1,
		));
$i = 0;

$ads = $query->get_posts();

foreach($ads as $ad) {

$return[$i]['adCode'] = get_post_meta($ad->ID, '_ad_code', true);
$return[$i]['adPosition'] = get_post_meta($ad->ID, '_ad_position', true);
$return[$i]['paragraphNumber'] = get_post_meta($ad->ID, '_paragraph_number', true);


$i++;

}

set_site_transient( $this->namespace.'-query_results', $return, 0 );

if (($this->is_this_plugin_network_activated()) && !is_main_site($blog_id)){
restore_current_blog();
}



return $return;



}



public function return_query(){

$transient_content = get_site_transient( $this->namespace.'-query_results' );


if (isset($transient_content[0]['adCode']) and isset($transient_content[0]['adPosition']) and isset($transient_content[0]['paragraphNumber'])){


$ads = $transient_content;

} else {

$ads = $this->update_query();


}

return $ads;

}



/**
	* Inserts advert(s) into content
	*
	* @param string $content Content
	* @return string Content
	*/
public function insert_ads($content) {

if  (is_singular()){

$ad_ads = true;

$ad_ads = apply_filters('lh_multisite_ads_filter_ad_ads', $ad_ads, $content);

if (isset($ad_ads) and ($ad_ads === TRUE)){

$ads = $this->return_query();

$ads = apply_filters('lh_multisite_ads_filter_returned_ads', $ads);

			foreach($ads as $ad) {


				$adCode = $ad['adCode'];
				$adPosition = $ad['adPosition'];
				$paragraphNumber = $ad['paragraphNumber'];

				switch ($adPosition) {
					case 'top':
						$content = $adCode.$content;
						break;
					case 'bottom':
						$content = $content.$adCode;
						break;
					default:
						$content = $this->insertAdAfterParagraph($adCode, $paragraphNumber , $content);
						break;
				}
			}




}

} else {

    if ($this->options[$this->ads_on_indexes_field_name] == 1){

        if (!isset($GLOBALS['lh_multisite_ads-all_ads'])){

       $GLOBALS['lh_multisite_ads-all_ads'] = $this->return_query();


       $ads =  $GLOBALS['lh_multisite_ads-all_ads'];

        }

        if (isset($ads) && !empty($ads)){

$i = 0;
while ($i < count($ads)) {

unset($GLOBALS['lh_multisite_ads-all_ads'][$i]);
$GLOBALS['lh_multisite_ads-all_ads'] = array_values($GLOBALS['lh_multisite_ads-all_ads']);


   $i++;
}

       //$content = "foobar".$content;


    }

    }


}
		return $content;
}


public function check_adverts_required($content) {

global $post;

$siteid = get_current_blog_id();

if (!in_array($siteid , $this->options[ $this->whitelisted_sites_field_name ] ) and !isset($_REQUEST['cid'])){

// Check the post hasn't disabled adverts
$disable = get_post_meta($post->ID, '_'.$this->namespace.'-disable_ads', true);
if (!$disable) {
return $this->insert_ads($content);
}


}

		return $content;
	}


	/**
	* Updates the saved, deleted, updated messages when saving an Ad Custom Post Type
	*
	* @param array $messages Messages
	* @return array Messages
	*/
	public function changeUpdatedMessages($messages) {
		$messages[$this->posttype] = array(
			1 =>  	__('Advert updated.', $this->namespace),
		    2 => 	__('Advert updated.', $this->namespace),
		    3 => 	__('Advert deleted.', $this->namespace),
		    4 => 	__('Advert updated.', $this->namespace),
			6 => 	__('Advert published.', $this->namespace),
		);

		return $messages;
	}

	/**
	* Changes the 'Enter title here' placeholder on the Ad Custom Post Type
	*
	* @param string $title Title
	* @return string Title
	*/
	public function changeTitlePlaceholder($title) {
		global $post;
		if (isset($post->post_type) and ($post->post_type == $this->posttype)) {
			$title = __('Advert Title', $this->namespace);
		}

		return $title;
	}

// add a settings link next to deactive / edit
public function add_settings_link( $links, $file ) {

if (is_main_site()){

	if( $file == $this->filename ){
		$links[] = '<a href="'. admin_url( 'edit.php?post_type=' ).$this->posttype.'&page='.$this->filename.'">Settings</a>';
	}

}
	return $links;
}


    public function plugins_loaded(){

        // Hooks
add_action('init', array( $this, 'register_post_types'));
add_action('add_meta_boxes', array($this,"add_meta_boxes"),10,2);
add_action('save_post', array($this, 'save_post'));
add_action('admin_menu', array($this,"plugin_menu"));
add_filter('the_content', array($this, 'check_adverts_required'),10000000, 1);
add_filter('post_updated_messages', array($this, 'changeUpdatedMessages')); // Appropriate messages for the post type
add_filter('enter_title_here', array($this, 'changeTitlePlaceholder')); // Change title placeholder
add_filter('plugin_action_links', array($this,"add_settings_link"), 10, 2);


    }

  /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }



	/**
	* Constructor
	*/
	public function __construct() {

$this->options = get_site_option($this->opt_name);
$this->filename = plugin_basename( __FILE__ );


//run our hooks on plugins loaded to as we may need checks
add_action( 'plugins_loaded', array($this,'plugins_loaded'));



}



}

$lh_multisite_ads_instance = LH_multisite_ads_plugin::get_instance();

}

?>

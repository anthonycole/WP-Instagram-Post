<?php
/*
Plugin Name: WP Instagram Post
Version: 0.1
Plugin URI: http://anthonycole.me/wp-instagram-post/
Description: if you post a photo to instagram, this plugin throws it into a new blog post.
Author: Anthony Cole
Author URI: 

Copyright 2011  (email: anthony@radiopicture.com.au )

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
*/

require_once('vendor/instagram.class.php');


class WP_Instagram_Post {
	/**
	 * Our firing action
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function forge() {
		add_action( 'admin_init', get_class() . '::settings_init' );
		add_action( 'admin_menu', get_class() . '::register_options_page' );
		add_action( 'init', get_class() . '::listen' );	
			
	}
	
	/**
	 * Registers our options page
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function register_options_page() {
		add_options_page( 'Instagram Settings', 'Instagram Settings', 'manage_options', 'wpinstac', get_class() . '::options_page' );
	}
	
	/**
	 * Settings Registration
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function settings_init() {
		register_setting( 'wpinstac_options', 'wpinstac_options' );
		add_settings_section( 'wpinstac_main', 'Instagram API Settings', get_class() . '::plugin_text', 'wpinstac' );
		add_settings_field( 'client_id', 'Client ID',  get_class() . '::settings_client_id', 'wpinstac', 'wpinstac_main' );
		add_settings_field( 'client_secret', 'Client Secret', get_class() . '::settings_client_secret', 'wpinstac', 'wpinstac_main' );
	}
	
	/**
	 * The text that shows up on our settings page.
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function plugin_text() {
		
		
		if( !self::api_done() ) : 
			echo "<p>In order to get this plugin working, you're going to need to create an application with instagram. See <a href='http://instagr.am/developer/'>here</a> for instructions. </p>";
		else : 
			$option = get_option('wpinstac_oauth');
			echo "<p>You are logged Into Instagram as " .  $option->user->username . "</p>";
		endif;
		
	}
	
	/**
	 * Check if the user has inputted settings.
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function app_setup() {
		$option = get_option( 'wpinstac_options' );
		
		if( isset( $option['client_id'] ) && isset($option['client_secret']) ) 
			return true;
	}
	
	/**
	 * Instantiate the Instagram class
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function setup_api() {
		$option = get_option( 'wpinstac_options' );
		$options = array(
		   'apiKey'      => $option['client_id'],
		   'apiSecret'   => $option['client_secret'],
		   'apiCallback' => admin_url('options-general.php?page=wpinstac')
		);
	
		$instagram = new Instagram( $options );
		
		$oauth_option = get_option( 'wpinstac_oauth' );
		
		// set our access token if it is active
		if( isset( $oauth_option->access_token ) )
			$instagram->setAccessToken($oauth_option->access_token );
		
		return $instagram;
	}
	
	/**
	 * Client ID Field
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function settings_client_id() {
		$option = get_option('wpinstac_options');
		echo "<input id='wpinstac_client_id' name='wpinstac_options[client_id]' size='40' type='text' value='{$option['client_id']}' />";
	}
	
	/**
	 * Client Secret Field.
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function settings_client_secret() {
		$option = get_option('wpinstac_options');
		echo "<input id='wpinstac_client_id' name='wpinstac_options[client_secret]' size='40' type='text' value='{$option['client_secret']}' />";
	}
	
	/**
	 * Saves the oAuth token.
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function oauth_save() {
		$instagram = self::setup_api();
		$userData = $instagram->getOAuthToken($_GET['code']);	
		update_option('wpinstac_oauth', $userData );
	}
	
	/**
	 * Checks if the oAuth.
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function api_done() {
		$option = get_option( 'wpinstac_oauth' );
		
		if( isset($option->access_token) )
			return true;
		else
			return false;
	}
	
	/**
	 * Options Page
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function options_page() {
		if( isset($_GET['code'] ) ) : 
			self::oauth_save();
		endif;
		?>
		<div class="wrap">
			<h2>Instagram Settings</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'wpinstac_options' ); ?>
				<?php do_settings_sections( 'wpinstac' ); ?>
				<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
			</form>
			
			<?php if( self::app_setup() && !self::api_done() ) :
			$instagram = self::setup_api(); 
			?>
			<a href="<?php echo $instagram->getLoginUrl(); ?>">Log Into Instagram</a>
			<?php endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Subscriptions Listener
	 *
	 * @return void
	 * @author Anthony Cole
	 **/
	public static function listen() {
		if( !strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) ) 
			return false;
			
		$instagram = self::setup_api();
		$instagram->SubscriptionListener();
	}
}

// Fire!
WP_Instagram_Post::forge();
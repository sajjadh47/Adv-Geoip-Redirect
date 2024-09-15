<?php
/*
Plugin Name: Advanced GeoIP Redirect
Plugin URI : https://wordpress.org/plugins/wp-geoip-redirect/
Description: Redirect your visitors according to their geographical (country) location. Using the Maxmind GeoIP (Lite) Database (DB Last Updated : 2024-09-14).
Version: 1.0.7
Author: Sajjad Hossain Sagor
Author URI: https://sajjadhsagor.com/
Text Domain: adv-geoip-redirect

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// ---------------------------------------------------------
// Define Plugin Folders Path
// ---------------------------------------------------------
define( 'GEOIPR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'GEOIPR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'GEOIPR_PLUGIN_VERSION', '1.0.7' ); // Plugin current version

require_once GEOIPR_PLUGIN_PATH . 'includes/class.geoipr.util.php';

require_once GEOIPR_PLUGIN_PATH . 'includes/class.geoipr.redirect.php';

// check if not class exists to avoid php error
if ( ! class_exists( 'GEOIPR_ADMIN_SETTINGS' ) ) :
	/**
	 * Add Plugin Settings To The Backend
	 */
	class GEOIPR_ADMIN_SETTINGS
	{
		// stores plugin settings
		public $redirect_settings;

		// notice messages for form submission
		public $notices = array();
    	
    	/**
		 * Load scripts, textdomain, register menu page
		 *
		 */
	    public function __construct()
	    {	
	    	// this plugin needs to be loaded first to avoid any overwrite redirect rule...
	    	$this->always_load_this_plugin_first();

			// reload plugin settings
			$this->reload_settings();

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
	        
	        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

	        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );

	        // Save/Upate settings form
			add_action( 'wp_ajax_geoipr_form_submit', array( $this, 'save_settings_fields' ) );

			// reset all redirect rules or generate json file for export... also if uploaded import settings too
			add_action( 'admin_init', array( $this, 'form_submit_actions' ) );

			add_action( 'admin_footer_text', array( $this, 'admin_footer_text' ) );
	    }

		/**
		 * We need to load this plugin first to work properely
		 *
		 * @access public
		 * @return array
		 */
		public function always_load_this_plugin_first()
		{
			$geoipr_plugin 				= plugin_basename( trim( __FILE__ ) );
			
			$active_plugins 			= get_option( 'active_plugins' );
			
			if ( $geoipr_plugin_name 	= array_search( $geoipr_plugin, $active_plugins ) )
			{	
				// remove the plugin from any order it is now
				array_splice( $active_plugins, $geoipr_plugin_name, 1 );
				
				// add this plugin to the beginning to load first
				array_unshift( $active_plugins, $geoipr_plugin );
				
				// update now the option
				update_option( 'active_plugins', $active_plugins );
			}
		}

		/**
		 * Add Plugin Settings Page Link To Plugin List Table..
		 *
		 * @access public
		 * @return array
		 */
		public function add_plugin_action_links( $links )
		{
			$links[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=geoip-redirect.php' ), __( 'Settings', 'adv-geoip-redirect' ) );
					
			return $links;
		}

		/**
		 * Refresh plugin settings value..
		 *
		 * @access public
		 * @return null
		 */
		public function reload_settings()
		{
			// check if settings is available or set empty values...
			if ( ! $this->redirect_settings = GEOIPR_UTIL::get_plugin_settings() )
			{	
				GEOIPR_UTIL::reset_plugin_settings();

				$this->redirect_settings 	= GEOIPR_UTIL::get_plugin_settings();
			}
		}

		/**
		 * load language translations
		 *
		 * @access public
		 * @return null
		 */
		public function load_plugin_textdomain()
		{	
			load_plugin_textdomain( 'adv-geoip-redirect', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Load Plugin Scripts & Styles
		 *
		 * @access public
		 * @return null
		 */
		public function enqueue_scripts()
		{
			$current_scrn = get_current_screen();

			if ( $current_scrn->id !== 'settings_page_geoip-redirect' ) return;

			// reload plugin settings
			$this->reload_settings();

			wp_enqueue_style ( 'geoipr_bootstrap_css', GEOIPR_PLUGIN_URL . 'assets/css/bootstrap.css', array(), GEOIPR_PLUGIN_VERSION, 'all' );
			
			wp_enqueue_style ( 'geoipr_select2_css', GEOIPR_PLUGIN_URL . 'assets/css/select2.min.css', array(), GEOIPR_PLUGIN_VERSION, 'all' );

			wp_enqueue_style ( 'geoipr_plugin_css', GEOIPR_PLUGIN_URL . 'assets/css/style.css', array(), GEOIPR_PLUGIN_VERSION, 'all' );
			
			wp_enqueue_script( 'geoipr_select2_script', GEOIPR_PLUGIN_URL . 'assets/js/select2.min.js', array(), GEOIPR_PLUGIN_VERSION, true );
			
			wp_enqueue_script( 'geoipr_plugin_script', GEOIPR_PLUGIN_URL . 'assets/js/script.js', array( 'geoipr_select2_script', 'jquery', 'wp-util', 'jquery-ui-sortable' ), GEOIPR_PLUGIN_VERSION, true );

			wp_localize_script( 'geoipr_plugin_script', 'geoipr', array(
				'btnSavingText' 	=> __( 'Saving... Please Wait', 'adv-geoip-redirect' ),
				'confirnDeleteMsg' 	=> __( 'Do You Really Want To Delete This Redirect Rule?', 'adv-geoip-redirect' ),
				'confirnResetMsg' 	=> __( 'Do You Really Want To Reset All Redirect Rules? Please Make a backup using the Export Tool below to restore again!', 'adv-geoip-redirect' ),
				'redirectRules' 	=> json_encode( $this->redirect_settings['redirect_rules'] )
			) );
		}

		/**
		 * Add Plugin Settings page to dashboard
		 *
		 * @access public
		 * @return null
		 */
		public function add_menu_page()
		{
			add_options_page( 'GeoIP Redirect', 'GeoIP Redirect', 'manage_options' , 'geoip-redirect.php', array( $this, 'render_menu_page' ) );
		}

		/**
		 * Add Plugin Settings page to dashboard
		 *
		 * @access public
		 * @return null
		 */
		public function save_settings_fields()
		{
			if ( ! isset( $_POST['_wpnonce_geoipr_settings_form'] ) || ! wp_verify_nonce( $_POST['_wpnonce_geoipr_settings_form'], 'geoipr_settings_form' ) )
			{
				$response 		= array( 'status' => 'error', 'message' => __( 'Sorry, your nonce did not verify.', 'adv-geoip-redirect') );
			}
			else // nonce is fine so process form fields
			{
				// delete unnecessary post fields
				unset( $_POST['_wpnonce_geoipr_settings_form'] );
				
				unset( $_POST['action'] );
				
				// sanitize post fields recursively
				$form_values 	= GEOIPR_UTIL::sanitize_array_recursively( $_POST );

				if ( ! isset( $form_values['redirect_rules'] ) ) $form_values['redirect_rules'] = array();
				
				update_option( "geoipr_redirect_options", $form_values );

				$response 		= array( 'status' => 'success', 'message' => __( 'Settings Updated Successfully!', 'adv-geoip-redirect') );
			}
			
			wp_send_json( $response ); exit();
		}

		/**
		 * reset all redirect rules or generate json file for export... also if uploaded import settings too
		 *
		 * @access public
		 * @return null
		 */
		public function form_submit_actions()
		{
			$nonce_error 				= array(
				'class' 	=> 'notice notice-warning',
				'message' 	=> __( 'Sorry, your nonce did not verify.', 'adv-geoip-redirect' )
			);
			
			if ( isset( $_POST['geoipr_export_action'] ) && current_user_can( 'manage_options' ) )
			{
				if ( ! isset( $_POST['_wpnonce_geoipr_settings_export_form'] ) || ! wp_verify_nonce( $_POST['_wpnonce_geoipr_settings_export_form'], 'geoipr_settings_export_form' ) )
				{
					$this->notices[] 	= $nonce_error;
				}
				else
				{
					GEOIPR_UTIL::export_settings();
				}
			}

			if ( isset( $_POST['geoipr_import_action'] ) && current_user_can( 'manage_options' ) )
			{
				if ( ! isset( $_POST['_wpnonce_geoipr_settings_import_form'] ) || ! wp_verify_nonce( $_POST['_wpnonce_geoipr_settings_import_form'], 'geoipr_settings_import_form' ) )
				{
					$this->notices[] 		= $nonce_error;
				}
				else
				{
					$import_file 			= $_FILES['import_file']['tmp_name'];

					if( empty( $import_file ) )
					{
						$this->notices[] 	= array(
							'class' 	=> 'notice notice-warning',
							'message' 	=> __( 'Please upload a file to import.', 'adv-geoip-redirect' )
						);

						return;
					}
					
					$file_name 				= $_FILES['import_file']['name'];
					
					// get file extension
					$ext 					= explode( '.', $file_name );
					
					$extension 				= end( $ext );

					if( $extension != 'json' )
					{
						$this->notices[] 	= array(
							'class' 	=> 'notice notice-warning',
							'message'	=> __( 'Please upload a valid .json file.', 'adv-geoip-redirect' )
						);

						return;
					}

					if ( GEOIPR_UTIL::import_settings( $import_file ) )
					{
						$this->notices[] 	= array(
							'class' 	=> 'notice notice-success',
							'message' 	=> __( 'Settings Imported Successfully', 'adv-geoip-redirect' )
						);
					}
					else
					{
						$this->notices[] 	= array(
							'class' 	=> 'notice notice-warning',
							'message' 	=> __( 'Something Went Wrong! Please Try Again!', 'adv-geoip-redirect' )
						);
					}
				}
			}
			
			if ( isset( $_POST['geoipr_reset_btn'] ) && $_POST['geoipr_reset_btn'] == '1' )
			{
				if ( ! isset( $_POST['_wpnonce_geoipr_settings_form'] ) || ! wp_verify_nonce( $_POST['_wpnonce_geoipr_settings_form'], 'geoipr_settings_form' ) )
				{
					$this->notices 			= $nonce_error;
				}
				else
				{
					if( current_user_can( 'administrator' ) )
					{
						GEOIPR_UTIL::reset_plugin_settings();

						$this->notices[] 	= array(
							'class' 	=> 'notice notice-success',
							'message' 	=> __( 'Filters Reset Successfully', 'adv-geoip-redirect' )
						);
					}
				}
			}

			$this->show_admin_notices();
		}

		/**
		 * show form submit status messages
		 *
		 * @access public
		 * @return null
		 */
		public function show_admin_notices()
		{
			if ( ! empty( $this->notices ) )
			{	
				foreach ( $this->notices as $notice )
				{	
					add_action( 'admin_notices', function() use ( $notice )
					{	
						printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $notice['class'] ), esc_html( $notice['message'] ) );
					} );
				}
			}
		}

		/**
		 * Add Footer Text to reference to Maxmind DB..
		 *
		 * @access public
		 * @return null
		 */
		public function admin_footer_text( $text )
		{
			$current_scrn = get_current_screen();

			if ( $current_scrn->id !== 'settings_page_geoip-redirect' ) return $text;

			echo __( "The GeoIP Redirect Plugin is using GeoLite2 db by MaxMind. Please visit https://www.maxmind.com for more information.\n", 'adv-geoip-redirect' );
		}

		/**
		 * Plugin Settings Page Content
		 *
		 * @access public
		 * @return null
		 */
		public function render_menu_page()
		{
			// reload plugin settings
			$this->reload_settings();
		?>

			<div class="wrap">

				<h2><?php _e( 'GeoIP Redirect Settings', 'adv-geoip-redirect' ); ?>

					<button type="submit" class="button button-secondary" id="geoipr_add_new_btn"><?php _e( 'Add New Redirect Rule', 'adv-geoip-redirect' ); ?></button>
				</h2>

				<div class="notice geoipr_message"><p></p></div><br>

				<form action="" method="post" id="geoipr_settings_form">
					
					<div id="geoipr_settings_fields">
					
					<?php

						foreach ( GEOIPR_UTIL::get_plugin_settings_chk_fields() as $index => $checkbox_field ) :

							if ( $checkbox_field !== 'false' ) :

								$chk_id = GEOIPR_UTIL::$option_fields[$index];

								GEOIPR_UTIL::display_checkbox_template( $chk_id, $checkbox_field, $this->redirect_settings[$chk_id] );

							endif;

						endforeach;
					?>

						<div class="form-group row">
						    <div class="col-sm-3" style="line-height: 35px;">
						    	<?php _e( 'Redirection Type', 'adv-geoip-redirect' ); ?>
						    </div>
						    <div class="col-sm-9">
						    	<div class="form-check">
								    <select class="form-control" id="redirection_type">
										<option value="301" <?php selected( $this->redirect_settings['redirection_type'], '301' ); ?>>301 Moved Permanently</option>
										<option value="302" <?php selected( $this->redirect_settings['redirection_type'], '302' ); ?>>302 Moved Temporarily</option>
									</select>
						    	</div>
						    </div>
						</div>
					
					</div>

					<!-- Here Goes Rules Set Markup -->
					<h3 class="redirect_rules_heading" <?php echo !empty( $this->redirect_settings['redirect_rules'] ) ? 'style="display:block;"' : 'style="display:none;"'; ?>>
						<?php _e( 'Redirect Rules', 'adv-geoip-redirect' ); ?>
					</h3>
					
					<div id="geoipr_rules_group"></div>

					<div class="geoipr_action_container">
						
						<button type="button" class="button button-primary" id="geoipr_submit_btn"><?php _e( 'Save Changes', 'adv-geoip-redirect' ); ?></button>

						<?php wp_nonce_field( 'geoipr_settings_form', '_wpnonce_geoipr_settings_form' ); ?>

						<button type="submit" class="button button-secondary" id="geoipr_reset_btn" name="geoipr_reset_btn" value="1"><?php _e( 'Reset Settings', 'adv-geoip-redirect' ); ?></button>

					</div>

				</form>

				<br>
				<div class="metabox-holder">
					<div class="postbox">
						<h3><span><?php _e( 'Export Settings' ); ?></span></h3>
						<div class="inside">
							<p><?php _e( 'Export the currently saved plugin settings. This allows you to easily import the configuration into another site.' ); ?></p>
							<form action="" method="post" id="geoipr_settings_export_form">
								<p>
									<?php wp_nonce_field( 'geoipr_settings_export_form', '_wpnonce_geoipr_settings_export_form' ); ?>
									<?php submit_button( __( 'Export' ), 'secondary', 'geoipr_export_action', false ); ?>
								</p>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox">
						<h3><span><?php _e( 'Import Settings' ); ?></span></h3>
						<div class="inside">
							<p><?php _e( 'Import the plugin settings. Use above form button (on another site maybe) to generate the import file to use in here' ); ?></p>
							<form action="" method="post" id="geoipr_settings_import_form" enctype="multipart/form-data">
								<p>
									<input type="file" name="import_file"/>
								</p>
								<p>
									<?php wp_nonce_field( 'geoipr_settings_import_form', '_wpnonce_geoipr_settings_import_form' ); ?>
									<?php submit_button( __( 'Import' ), 'secondary', 'geoipr_import_action', false ); ?>
								</p>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->
				</div><!-- .metabox-holder -->

				<script type="text/template" id="tmpl-redirect-rules-set">

					<div class="input-group mb-3 geoipr_rules_group_container">

						  <div class="input-group-prepend">
						    
						    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Settings</button>
						    
						    <div class="dropdown-menu">

						      	<label for="pass_url_parameter_{{data.pass_url_parameter_id}}">
						    		<input class="dropdown-item geoipr_pass_url_parameter" type="checkbox" id="pass_url_parameter_{{data.pass_url_parameter_id}}" <# if ( data.PassParameter == 'true' ) { #> checked=checked <# } #>>
								   	Pass URL Parameters Forward
								 </label>
						      	
						      	<div role="separator" class="dropdown-divider"></div>
						    	
						    	<label for="ignore_url_parameter_{{data.ignore_url_parameter_id}}">
						    		<input class="dropdown-item geoipr_ignore_url_parameter" type="checkbox" id="ignore_url_parameter_{{data.ignore_url_parameter_id}}" <# if ( data.IgnoreParameter == 'true' ) { #> checked=checked <# } #>>
								   	Ignore URL Parameters When Check Against
								 </label>
						    	
						    </div>
						  </div>

						<div class="input-group-prepend">
							<span class="input-group-text"><?php _e( 'Redirect if user is', 'adv-geoip-redirect' ); ?></span>
						</div>

						<select class="form-control geoipr_user_from_chk_condition">
							
							<option value="from" <# if ( data.FromChkCondition == 'from' ) { #> selected=selected <# } #>>From</option>
							<option value="not_from" <# if ( data.FromChkCondition == 'not_from' ) { #> selected=selected <# } #>>Not From</option>

						</select>
						
						<select class="form-control geoipr_countries_list" multiple="multiple">
							
							<?php foreach ( GEOIPR_UTIL::get_countries() as $code => $country ) : ?>

								<option <# if ( ( data.countryField.indexOf( "<?php echo $code; ?>" ) != -1 ) ) { #> selected=selected <# } #> value="<?php echo $code; ?>"><?php echo $country; ?></option>

							<?php endforeach; ?>

						</select>

						<div class="input-group-append">
						    <span class="input-group-text"><?php _e( 'To', 'adv-geoip-redirect' ); ?></span>
						</div>

						<input type="text" class="form-control geoipr_target_url" value="{{data.TargetURLField}}" placeholder="<?php _e( 'Enter Redirect URL...', 'adv-geoip-redirect' ); ?>">

						<div class="input-group-append">
						    <span class="input-group-text"><?php _e( 'When Visits', 'adv-geoip-redirect' ); ?></span>
						</div>

						<input type="text" class="form-control geoipr_visited_url" value="{{data.VisitedURLField}}" placeholder="<?php _e( 'Enter Visited URL...', 'adv-geoip-redirect' ); ?>">
						
						<span class="dashicons dashicons-trash geoipr_delete"></span>

					</div>

				</script>
			
			</div>

			<style type="text/css">
				.geoipr_chk-slider-inner:before {
					content: '<?php _e( "ENABLED", "geoip-redirect" ); ?>';
				}
				.geoipr_chk-slider-inner:after {
					content: '<?php _e( "DISABLED", "geoip-redirect" ); ?>';
				}
			</style>

		<?php }
	}

endif;

$GEOIPR_ADMIN_SETTINGS = new GEOIPR_ADMIN_SETTINGS();

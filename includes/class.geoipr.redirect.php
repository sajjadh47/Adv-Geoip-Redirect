<?php

use GeoIp2\Database\Reader;

// check if not class exists to avoid php error
if ( ! class_exists( 'GEOIPR_REDIRECT' ) ) :
	
	/**
	* Redirect User According To Rules Set In Backend
	*/
	class GEOIPR_REDIRECT
	{
		// stores debug logs message
		public $debug_logs = '';

		// stores plugin settings
		public $redirect_settings;

		// stores current visitor ip address
		public $VisitorIP;

		// stores current visitor country iso code
		public $VisitorCountry;

		public $RedirectType;

		/**
		 * hook to template_redirect & redirect user based on rule if applied
		 *
		 */
	    public function __construct()
	    {
	    	// check if settings is available or set empty values...
			while ( ! $this->redirect_settings = GEOIPR_UTIL::get_plugin_settings() )
			{	
				GEOIPR_UTIL::reset_plugin_settings();
			}

			$this->VisitorIP = GEOIPR_UTIL::get_visitor_ip();

			// if development mode enabled then only reirect logged in admin users				
			if ( isset( $this->redirect_settings['dev_mode'] ) && $this->redirect_settings['dev_mode'] == 'true' )
			{
				if ( ! is_user_logged_in() && ! current_user_can( 'administrator' ) ) return;

				$this->VisitorIP = '103.204.85.27';
			}

			// visitor ip is not valid so early return
			if ( $this->VisitorIP == '' )
			{	
				$this->debug_logs = implode( "\t", array(
					gmdate( 'Y-m-d H:i:s' ),
					'Visitor IP ' . $this->VisitorIP,
					__( ' Redirection terminated. Invalid Visitor IP Found!' ),
				));

				return;
			} 

			// now check if redirect is enabled
			if ( isset( $this->redirect_settings['redirect_switch'] ) && $this->redirect_settings['redirect_switch'] !== 'true' ) return;

			// and last now check if any rules is set
			if ( isset( $this->redirect_settings['redirect_rules'] ) && empty( $this->redirect_settings['redirect_rules'] ) ) return;

			// don't continue if ?skipredirect is in the url
			if ( $this->redirect_settings['skip_if_skipredirect_provided'] == 'true' && isset( $_GET['skipredirect'] ) )
			{
				$this->debug_logs = implode( "\t", array(
					gmdate( 'Y-m-d H:i:s' ),
					'Visitor IP ' . $this->VisitorIP,
					__( ' Redirection terminated. ?skipredirect URL Parameter Found!', 'adv-geoip-redirect' ),
				));

				return;
			}

			// don't continue if request is from a bot/web crawler & option to skip is enabled..
			if ( $this->redirect_settings['skip_if_bot'] == 'true' && GEOIPR_UTIL::is_bot() )
			{
				$this->debug_logs = implode( "\t", array(
					gmdate( 'Y-m-d H:i:s' ),
					'Visitor IP ' . $this->VisitorIP,
					__( ' Redirection terminated. Bot/Web Crawler Detected!', 'adv-geoip-redirect' ),
				));

				return;
			}

			// set redirect type
			// if none provided use 302 permanently moved
			$this->RedirectType = 302;
			
			if ( isset( $this->redirect_settings['redirection_type'] ) )
			{
				switch ( $this->redirect_settings['redirection_type'] )
				{		
					case '301':
						
						$this->RedirectType = 301;
					break;

					case '302':
						
						$this->RedirectType = 302;
					break;
				}
			}

			require_once GEOIPR_PLUGIN_PATH . 'includes/vendor/autoload.php';

			// This creates the Reader object, 
			$reader = new Reader( GEOIPR_PLUGIN_PATH . 'assets/geoip-db/GeoLite2-Country.mmdb' );

			// if result is not found then return
			try
			{
				$VisitorGeo = $reader->country( $this->VisitorIP );

				$this->VisitorCountry = $VisitorGeo->country->isoCode;

				// hook it & if true then redirect...
    			add_action( 'template_redirect', array( $this, 'redirect' ) );
			}
			catch ( Exception $e )
			{
				$this->debug_logs = implode( "\t", array(
					gmdate( 'Y-m-d H:i:s' ),
					'Visitor IP ' . $this->VisitorIP,
					__( ' Redirection terminated. Unable to detect visitor country!', 'adv-geoip-redirect' ),
				));
			}

			GEOIPR_UTIL::write_down_debug_log( $this->debug_logs );

			$this->debug_logs = '';
	    }

	    /**
		 * Redirect Visitor According to Country
		 *
		 * @access static
		 * @return null
		 */
		public function redirect()
		{
			// go over through all rules
			foreach ( $this->redirect_settings['redirect_rules'] as $rule_set )
			{
				// convert object to array
				$rule_set = (array) $rule_set;
				
				// don't continue if redirect to & visited url is same
				if ( $rule_set['TargetURLField'] == $rule_set['VisitedURLField'] )
				{
					$this->debug_logs = implode( "\t", array(
						gmdate( 'Y-m-d H:i:s' ),
						'Visitor IP ' . $this->VisitorIP,
						__( ' Redirection terminated. Same page redirection! Aborted Redirection to avoid infinite redirect loop!', 'adv-geoip-redirect' ),
					));

					continue;
				}

				// if url is relative add get_option( 'siteurl' )
				if ( GEOIPR_UTIL::is_url_relative( $rule_set['TargetURLField'] ) )
				{
					$rule_set['TargetURLField'] = get_option( 'siteurl' ) . $rule_set['TargetURLField'];
				}

				if ( GEOIPR_UTIL::is_url_relative( $rule_set['VisitedURLField'] ) )
				{
					$rule_set['VisitedURLField'] = get_option( 'siteurl' ) . $rule_set['VisitedURLField'];
				}

				// default check if visitor from country
				$FromChkCondition = in_array( $this->VisitorCountry, $rule_set['countryField'] );
				
				// check for visitor country condition for the following rule
				if ( $rule_set['FromChkCondition'] == 'not_from' )
				{
					$FromChkCondition = ! in_array( $this->VisitorCountry, $rule_set['countryField'] );
				}
				
				// check if user is from following country
				if ( $FromChkCondition )
				{
					// get current visited url
					$current_url = GEOIPR_UTIL::get_current_url( $rule_set['IgnoreParameter'] );

					// check if redirect first visit only enabled
					if ( isset( $this->redirect_settings['redirect_for_first_time_visit_only'] ) && $this->redirect_settings['redirect_for_first_time_visit_only'] == 'true' )
					{
						if ( isset( $_COOKIE[$current_url] ) )
						{		
							$this->debug_logs = implode( "\t", array(
								gmdate( 'Y-m-d H:i:s' ),
								'Visitor IP ' . $this->VisitorIP,
								__( ' Redirection terminated. Already Visited The Page!', 'adv-geoip-redirect' ),
							));

							continue;
						}
					}

					$VisitedURLField = str_replace( '?' , '\?', $rule_set['VisitedURLField'] );

					// check if VisitedURLField has any url parameter
					$URLParams = explode( '?', $rule_set['VisitedURLField'] );

					// if it has Params remove them to go forward
					if ( count( $URLParams ) > 1 )
					{
						$param = $URLParams[1];

						$VisitedURLField = $URLParams[0] . '[\?|&].*' . $param;
						
						$_SERVER['QUERY_STRING'] = preg_replace( "#$param&?#i", '', $_SERVER['QUERY_STRING'] );
					}

					// don't continue if it's a WooCommerce ajax or Job Manager ajax request
					if ( preg_match( '/jm-ajax/', $current_url ) || preg_match( '/wp-content/', $current_url ) || preg_match( '/wc-ajax/', $current_url ) ) continue;

					// now check if user is visiting the set url
					if ( preg_match( "#$VisitedURLField#i", $current_url, $matches ) )
					{
						// remove the first value which is basically not needed
						array_shift( $matches );
						
						$redirect_to = esc_url( $rule_set['TargetURLField'] );

						if ( strpos( $redirect_to, '(.*)' ) !== false && count( $matches ) )
						{
							$regex_count = 0;

							$redirect_to = preg_replace_callback( "#\(\.\*\)#", function( $match ) use ( &$regex_count, $matches )
							{
								return $matches[$regex_count++];

							}, $redirect_to );
						}

						// if pass query enabled then add it to url
						if ( $rule_set['PassParameter'] == 'true' && ! empty( $_SERVER['QUERY_STRING'] ) )
						{
							$QueryStringDivider = ( strpos( $redirect_to, '?' ) === false )  ? '?' : '&';
							
							$redirect_to = $redirect_to . $QueryStringDivider . $_SERVER['QUERY_STRING'];
						}

						$this->debug_logs = implode( "\t", array(
							gmdate( 'Y-m-d H:i:s' ),
							'Visitor IP ' . $this->VisitorIP,
							__( ' Redirection succeeded! To ', 'adv-geoip-redirect' ) . $redirect_to . ' From ' . $current_url
						));

						setcookie( $current_url, time(), strtotime( '+24 hours' ) );

						// don't continue if redirect to & visited url is same
						if ( $redirect_to == $current_url )
						{
							$this->debug_logs = implode( "\t", array(
								gmdate( 'Y-m-d H:i:s' ),
								'Visitor IP ' . $this->VisitorIP,
								__( ' Redirection terminated. Same page redirection! Aborted Redirection to avoid infinite redirect loop!', 'adv-geoip-redirect' ),
							));

							continue;
						}
						
						// if everything is fine then redirect user to destined url
						if ( wp_redirect( $redirect_to, $this->RedirectType ) )
						{
							GEOIPR_UTIL::write_down_debug_log( $this->debug_logs );

							$this->debug_logs = '';
							
							exit();
						}
					
					} //url matching end
				
				} //country matching end
			
			} //endforeach
		}
	}

endif;

<?php

/**
* Utility Class With Some Helpful Functions
*/
class GEOIPR_UTIL
{	
	static public $option_name 				= 'geoipr_redirect_options';

	static public $option_fields 			= array
	(
		'redirect_switch', 'dev_mode', 'dubug_log', 'skip_if_bot',
		
		'skip_if_skipredirect_provided', 'redirect_for_first_time_visit_only',
		
		'redirection_type', 'redirect_rules'
	);

	static public $default_option_values 	= array
	(
		'false', 'false', 'false', 'false', 'false', 'false', '302', array()
	);
	
	/**
	 * Set Plugin default option value
	 *
	 * @access static
	 * @return null
	 */
	static function reset_plugin_settings()
	{
		// add empty option for not showing error
		update_option( self::$option_name, array_combine( self::$option_fields, self::$default_option_values ) );
	}

	/**
	 * Get Plugin option from {$prefix}_options table using options api
	 *
	 * @access static
	 * @return array
	 */
	static function get_plugin_settings()
	{
		return get_option( self::$option_name, false );
	}

	/**
	 * Get checkbox fields label
	 *
	 * @access static
	 * @return array
	 */
	static function get_plugin_settings_chk_fields()
	{
		return array (

			__( 'Enable Redirection', 'adv-geoip-redirect' ), __( 'Enable Development Mode', 'adv-geoip-redirect' ),

			__( 'Write Down Debug Log', 'adv-geoip-redirect' ), __( 'Skip Redirect For Bot & Crawlers', 'adv-geoip-redirect' ),
			
			__( 'Skip Redirect If <code>?skipredirect</code> Parameter Found', 'adv-geoip-redirect' ), __( 'Only Redirect If First Time Visit (reset after 24hrs)', 'adv-geoip-redirect' ),
			
			'false', 'false'
		);
	}

	/**
	 * Generate HTML for checkbox field
	 *
	 * @access static
	 * @return null
	 */
	static function display_checkbox_template( $id, $label, $checked )
	{ ?>
		<div class="form-group row">
				<div class="col-sm-3" style="line-height: 35px;">
					<?= $label; ?>
				</div>
				<div class="col-sm-9">
			    	<div class="form-check">
			        	<div class="geoipr_chk-slider">
						    <input type="checkbox" class="geoipr_chk-slider-checkbox" id="<?= $id; ?>" <?php checked( $checked, 'true' ); ?>>
						    <label class="geoipr_chk-slider-label" for="<?= $id; ?>">
						    	<span class="geoipr_chk-slider-inner"></span><span class="geoipr_chk-slider-circle"></span>
						    </label>
						</div>
			    	</div>
			    </div>
			</div>
	<?php
	}

	/**
	 * Recursively sanitize each array fields
	 *
	 * @access static
	 * @return array
	 */
	static function sanitize_array_recursively( array &$array )
	{
		array_walk_recursive( $array, function ( &$value )
		{
			$value = sanitize_text_field( $value );
	    } );

	    return $array;
	}

	/**
	 * Check if url is relative to wp home url...
	 *
	 * @access static
	 * @return boolean
	 */
	static function is_url_relative( $uri )
	{
		//leading '/': absolute to domain name (half relative)
		return ( strpos( $uri, '://' ) === false && substr( $uri, 0, 1 ) == '/' );
	}

	/**
	 * generate plugin setttings as json data & export it to browser as json file
	 *
	 * @access static
	 */
	static function export_settings()
	{
		$settings = self::get_plugin_settings();

		ignore_user_abort( true );

		nocache_headers();
		
		header( 'Content-Type: application/json; charset=utf-8' );
		
		header( 'Content-Disposition: attachment; filename=geoipr-settings-export-' . date( 'm-d-Y' ) . '.json' );
		
		header( "Expires: 0" );

		echo json_encode( $settings );
		
		exit;
	}

	/**
	 * import plugin settings from json file
	 *
	 * @access static
	 */
	static function import_settings( $import_file )
	{
		try
		{		
			// Retrieve the settings from the file and convert the json object to an array.
			$settings = (array) json_decode( file_get_contents( $import_file ) );

			return update_option( self::$option_name, $settings );
		}
		catch ( Exception $e )
		{
			
			return false;
		}
	}

	/**
	 * get full url of current visited page
	 * @return string
	 *
	 * @access static
	 */
	static function get_current_url( $ignore_url_parameter = 'false' )
	{
	    $s 					= &$_SERVER;
	    
	    $ssl_enabled 		= ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' ) ? true : false;
	    
	    $server_protocol 	= strtolower( $s['SERVER_PROTOCOL'] );
	    
	    $protocol 			= substr( $server_protocol, 0, strpos( $server_protocol, '/' ) ) . ( ( $ssl_enabled ) ? 's' : '' );
	    
	    $port 				= $s['SERVER_PORT'];
	    
	    $port 				= ( ( ! $ssl_enabled && $port == '80' ) || ( $ssl_enabled && $port == '443' ) ) ? '' : ':' . $port;
	    
	    $host 				= isset( $s['HTTP_X_FORWARDED_HOST'] ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
	    
	    $host 				= isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
	    
	    $uri 				= $protocol . '://' . $host . $s['REQUEST_URI'];
	        	
	    if ( $ignore_url_parameter == 'true' )
	    {
	    	$segments 		= explode( '?', $uri, 2 );
	    	
	    	$uri 			= $segments[0];
	    }
	    
	    return rtrim( $uri, "/" ) . '/';
	}

	/**
	 * get the client IP address
	 * @return string
	 *
	 * @access static
	 */
	static function get_visitor_ip()
	{
	    $ipaddress 		= '';
    
	    // If website is hosted behind CloudFlare protection.
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) )
			
			$ipaddress 	= $_SERVER['HTTP_CF_CONNECTING_IP'];

		else if ( isset( $_SERVER['X-Real-IP'] ) )
			
			$ipaddress 	= $_SERVER['X-Real-IP'];

	    else if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) )
	        
	        $ipaddress 	= $_SERVER['HTTP_CLIENT_IP'];
	    
	    else if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	        
	        $ipaddress 	= $_SERVER['HTTP_X_FORWARDED_FOR'];
	    
	    else if( isset( $_SERVER['HTTP_X_FORWARDED'] ) )
	    
	        $ipaddress 	= $_SERVER['HTTP_X_FORWARDED'];
	    
	    else if( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) )
	    
	        $ipaddress 	= $_SERVER['HTTP_FORWARDED_FOR'];
	    
	    else if( isset( $_SERVER['HTTP_FORWARDED'] ) )
	    
	        $ipaddress 	= $_SERVER['HTTP_FORWARDED'];
	    
	    else if( isset( $_SERVER['REMOTE_ADDR'] ) )
	    
	        $ipaddress 	= $_SERVER['REMOTE_ADDR'];
	    
	    // validate ip address
	    if ( filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) )
	    {
	    	return $ipaddress;
	    }

	    return $ipaddress;
	}

	/**
	 * check if current request is from a bot or search engine crawler
	 *
	 * @access static
	 * @return boolean
	 */
	static public function is_bot()
	{	
		if ( preg_match('/baidu|bingbot|facebookexternalhit|googlebot|-google|ia_archiver|msnbot|naverbot|pingdom|seznambot|slurp|teoma|twitter|yandex|yeti|linkedinbot|pinterest/i', self::get_user_agent() ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * get the visitor browser agent
	 *
	 * @access static
	 * @return boolean
	 */
	static public function get_user_agent()
	{
		return ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	/**
	 * keep a debug log in debug.log file for references
	 *
	 * @access static
	 * @return null
	 */
	static public function write_down_debug_log( $debug_logs )
	{
		if ( (array) $redirect_settings = self::get_plugin_settings() )
		{
			if ( isset( $redirect_settings['dubug_log'] ) && $redirect_settings['dubug_log'] == 'true' )
			{
				// check if we have any debug logs
				if ( ! empty( $debug_logs ) )
				{
					file_put_contents( GEOIPR_PLUGIN_PATH . "debug.log", 'Debug Log : ' . $debug_logs . "\n", FILE_APPEND );
				}
			}
		}
	}

	/**
	 * All Country List with ISO Code Name
	 *
	 * @access static
	 * @return array
	 */
	static function get_countries()
	{
		return ['AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia, Plurinational State of', 'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'CV' => 'Cabo Verde', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, The Democratic Republic of The', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CW' => 'Curacao', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island and Mcdonald Islands', 'VA' => 'Holy See', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia, The Former Yugoslav Republic of', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States of', 'MD' => 'Moldova, Republic of', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestine, State of', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena, Ascension and Tristan Da Cunha', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin (French Part)', 'PM' => 'Saint Pierre and Miquelon', 'VC' => 'Saint Vincent and The Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SX' => 'Sint Maarten (Dutch Part)', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and The South Sandwich Islands', 'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard and Jan Mayen', 'SZ' => 'Eswatini', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan, Province of China', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania, United Republic of', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UM' => 'United States Minor Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela, Bolivarian Republic of', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis and Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'];
	}
}

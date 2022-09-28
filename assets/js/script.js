jQuery( document ).ready( function( $ )
{
	// Get the template function corresponding to our template markup.
	var template = wp.template( 'redirect-rules-set' );

	// on click dropdown toggle show settings
    $( document ).on( 'click', '.dropdown-toggle', function( event )
    {
		$( this ).next( '.dropdown-menu' ).toggleClass( 'show' );
	});

	// add a new redirect rule when clicked plus buttton
	$( document ).on( 'click', '#geoipr_add_new_btn', function( event )
	{
		$( '.redirect_rules_heading' ).css( 'display', 'block' );
		
		// Let's make fake empty data
        var tmplData =
        {
            countryField: [],
            TargetURLField: '',
            VisitedURLField: '',
            // generate random number for checkbox id
			pass_url_parameter_id : _.random( 1, Number.MAX_SAFE_INTEGER ),
			ignore_url_parameter_id : _.random( 1, Number.MAX_SAFE_INTEGER )
        };

        // Send the data to our new template function, get the HTML markup back.
        var html = template( tmplData );

		// add new rules set box to last
		$( '#geoipr_rules_group' ).append( html );
		
		// reinitiate select2
		$( '.geoipr_countries_list' ).select2();
	});
	
	// delete redirect rules set
	$( document ).on( 'click', '.geoipr_delete', function( event )
	{
		if ( confirm( geoipr.confirnDeleteMsg ) )
		{
			$( this ).closest( '.geoipr_rules_group_container' ).remove();
		}
	});

	// reset redirect rules set
	$( document ).on( 'submit', '#geoipr_settings_form', function( event )
	{
		return confirm( geoipr.confirnResetMsg );
	});

	// submit the form
	$( "#geoipr_submit_btn" ).click( function( event )
	{	
		event.preventDefault();

		var $_thisBtn = $( this );
		
		var stateSaving = geoipr.btnSavingText;

		var btnText = $_thisBtn.text();

		// change btn text & make it disabled to not submit again untill this req is finished
		$_thisBtn.text( stateSaving ).attr( 'disabled', 'disabled' );

		var data =
		{
			action  : 'geoipr_form_submit',
			redirect_switch : $( '#redirect_switch' ).is( ':checked' ),
			dev_mode : $( '#dev_mode' ).is( ':checked' ),
			dubug_log : $( '#dubug_log' ).is( ':checked' ),
			skip_if_bot : $( '#skip_if_bot' ).is( ':checked' ),
			skip_if_skipredirect_provided : $( '#skip_if_skipredirect_provided' ).is( ':checked' ),
			redirect_for_first_time_visit_only : $( '#redirect_for_first_time_visit_only' ).is( ':checked' ),
			redirection_type : $( '#redirection_type' ).val(),
			_wpnonce_geoipr_settings_form : $( '#_wpnonce_geoipr_settings_form' ).val(),
			redirect_rules : []
		};

		var $rulesList = $( '.geoipr_rules_group_container' );

		// get all fields
		if ( $rulesList.length )
		{
			$rulesList.each( function( index, el )
			{
				var $CountryField = $( el ).find( 'select.geoipr_countries_list' ).val();

				var $TargetURLField = $( el ).find( 'input.geoipr_target_url' ).val();

				var $VisitedURLField = $( el ).find( 'input.geoipr_visited_url' ).val();
				
				var $FromChkCondition = $( el ).find( 'select.geoipr_user_from_chk_condition' ).val();
	
				var $PassParameter = $( el ).find( 'input.geoipr_pass_url_parameter' ).is(':checked');

				var $IgnoreParameter = $( el ).find( 'input.geoipr_ignore_url_parameter' ).is(':checked');

				data.redirect_rules.push(
					{
						countryField: $CountryField,
						TargetURLField: $TargetURLField,
						VisitedURLField: $VisitedURLField,
						FromChkCondition: $FromChkCondition,
						PassParameter: $PassParameter,
						IgnoreParameter: $IgnoreParameter
					}
				);
			});
		}

        // ajaxurl is always set in backend
        $.post( ajaxurl, data, function( response )
        {
			$_thisBtn.text( btnText ).removeAttr( 'disabled' );

			$( ".geoipr_message p" ).text( response.message ).end().find( ".geoipr_message" ).removeClass( 'notice-warning' ).addClass( 'notice-success' ).show( 'slow' );
		});
    });

    $( JSON.parse( geoipr.redirectRules ) ).each( function( index, el )
    {
		// generate random number for checkbox id
		el.pass_url_parameter_id = _.random( 1, Number.MAX_SAFE_INTEGER );
		
		el.ignore_url_parameter_id = _.random( 1, Number.MAX_SAFE_INTEGER );

		// Send the data to our new template function, get the HTML markup back.
		var html = template( el );

		// add new rules set box to last
		$( '#geoipr_rules_group' ).append( html );
		
		// reinitiate select2
		$( '.geoipr_countries_list' ).select2();
    });

	jQuery( "#geoipr_rules_group" ).sortable();
});
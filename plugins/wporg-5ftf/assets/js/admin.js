/* global ajaxurl, FiveForTheFuture_ManageNonce, jQuery */
/* eslint no-alert: "off" */
jQuery( document ).ready( function( $ ) {
	function sendAjaxRequest( data, callback ) {
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'manage_contributors',
				pledge_id: data.pledgePost || 0,
				contributor_id: data.contributorPost || 0,
				manage_action: data.action || '',
				_ajax_nonce: FiveForTheFuture_ManageNonce,
			},
			success: callback,
			dataType: 'json',
		} );
	}

	$( '.contributor-list [data-action="resend-contributor-confirmation"]' ).click( function( event ) {
		event.preventDefault();
		sendAjaxRequest( event.currentTarget.dataset, function( response ) {
			if ( response.message ) {
				alert( response.message );
			}
		} );
	} );
} );

/* global ajaxurl, FiveForTheFuture, jQuery */
/* eslint no-alert: "off" */
jQuery( document ).ready( function( $ ) {
	function sendAjaxRequest( data, callback ) {
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: Object.assign( {
				action: 'manage_contributors',
				_ajax_nonce: FiveForTheFuture.manageNonce,
			}, data ),
			success: callback,
			dataType: 'json',
		} );
	}

	const container = document.getElementById( '5ftf-contributors' );

	// Remove Contributor button action.
	$( container ).on( 'click', '[data-action="remove-contributor"]', function( event ) {
		event.preventDefault();

		const confirmMsg = event.currentTarget.dataset.confirm;
		if ( confirmMsg && confirm( confirmMsg ) ) {
			const data = event.currentTarget.dataset;

			sendAjaxRequest( {
				pledge_id: data.pledgePost || 0,
				contributor_id: data.contributorPost || 0,
				manage_action: data.action || '',
			}, function( response ) {
				if ( response.message ) {
					alert( response.message );
				}
				if ( response.success ) {
					$( event.currentTarget ).closest( 'li' ).remove();
				}
			} );
		}
	} );

	// Resend Contributor Confirmation button action.
	$( container ).on( 'click', '[data-action="resend-contributor-confirmation"]', function( event ) {
		event.preventDefault();
		const data = event.currentTarget.dataset;

		sendAjaxRequest( {
			pledge_id: data.pledgePost || 0,
			contributor_id: data.contributorPost || 0,
			manage_action: data.action || '',
		}, function( response ) {
			if ( response.message ) {
				alert( response.message );
			}
		} );
	} );
} );

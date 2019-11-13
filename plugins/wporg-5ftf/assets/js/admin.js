/* global ajaxurl, FiveForTheFuture, fftfContributors, jQuery */
/* eslint no-alert: "off" */
jQuery( document ).ready( function( $ ) {
	/**
	 * Render the contributor lists using the contributors template into the pledge-contributors container. This
	 * uses `_renderContributors` to render a list of contributors per status (published, pending).
	 *
	 * @param {Object} contributors - An object listing all contributors on a pledge.
	 * @param {Object[]} contributors.publish - The list of published/confirmed contributors.
	 * @param {Object[]} contributors.pending - The list of pending/unconfirmed contributors.
	 * @param {Object} container - The parent container for this section.
	 */
	function render( contributors, container ) {
		const listContainer = container.querySelector( '.pledge-contributors' );
		const template = wp.template( '5ftf-contributor-lists' );
		const data = {
			publish: _renderContributors( contributors.publish ),
			pending: _renderContributors( contributors.pending ),
		};
		$( listContainer ).html( template( data ) );
	}

	/**
	 * Render a given contributor list using the contributor template.
	 *
	 * @param {Object[]} contributors - An array of contributor data objects.
	 * @return {string} An HTML string of contributors.
	 */
	function _renderContributors( contributors ) {
		if ( ! contributors ) {
			return [];
		}
		const template = wp.template( '5ftf-contributor' );
		return contributors.map( template ).join( '' );
	}

	/**
	 * The default callback for AJAX actions.
	 *
	 * @param {Object} response - An array of contributor data objects.
	 * @param {string} response.message - An optional message to display to the user.
	 * @param {Object[]} response.contributors - The new list of contributors.
	 */
	function defaultCallback( response ) {
		if ( response.message ) {
			alert( response.message );
		}
		if ( response.contributors ) {
			render( response.contributors, container );
		}
	}

	/**
	 * Send an ajax request using the `manage_contributors` action. This function also automatically adds the
	 * nonce, which should be defined in the global FiveForTheFuture variable.
	 *
	 * @param {Object} data - A list of data to send to the endpoint.
	 * @param {Function} callback - A function to be called when the request completes.
	 */
	function sendAjaxRequest( data, callback ) {
		if ( ! callback ) {
			callback = defaultCallback;
		}
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

	// Initialize.
	const container = document.getElementById( '5ftf-contributors' );
	render( fftfContributors, container );

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
		} );
	} );
} );

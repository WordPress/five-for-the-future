/* global FFTF_Dialog, jQuery */
jQuery( document ).ready( function( $ ) {
	const button = document.getElementById( 'toggle-management-link-form' );
	const template = document.getElementById( 'tmpl-5ftf-send-link-dialog' ) && wp.template( '5ftf-send-link-dialog' );

	if ( !! template ) {
		$( document.body ).prepend( template() );
	}

	const modal = document.getElementById( 'send-link-dialog' );
	const modalBg = document.getElementById( 'send-link-dialog-bg' );
	const children = document.querySelectorAll( 'body > *:not([role="dialog"])' );

	/**
	 * Get the top/left position for the modal, based on the button location.
	 */
	function getModalPosition() {
		const offsetTop = ( 'number' === typeof window.scrollY ) ? window.scrollY : window.pageYOffset;
		const offsetLeft = ( 'number' === typeof window.scrollX ) ? window.scrollX : window.pageXOffset;
		const bounds = button.getBoundingClientRect();
		const modalWidth = 300; // Modal width is hardcoded, because it's not visible yet.

		return {
			top: bounds.y + offsetTop + bounds.height,
			left: bounds.x + offsetLeft + bounds.width - modalWidth,
		};
	}

	/**
	 * Open the modal.
	 */
	function openModal() {
		const position = getModalPosition();
		// Hide other content on this page while modal is open.
		for ( let i = 0; i < children.length; i++ ) {
			if ( children[ i ].hasAttribute( 'data-no-inert' ) ) {
				continue;
			}
			if ( children[ i ].getAttribute( 'inert' ) ) {
				children[ i ].setAttribute( 'data-keep-inert', '' );
			} else {
				children[ i ].setAttribute( 'inert', 'true' );
			}
		}

		modal.removeAttribute( 'hidden' );
		modalBg.removeAttribute( 'hidden' );
		modal.style.top = position.top + 'px';
		modal.style.left = position.left + 'px';
		modal.focus();
	}

	/**
	 * Close the modal.
	 */
	function closeModal() {
		// Reveal content again.
		for ( let i = 0; i < children.length; i++ ) {
			if ( ! children[ i ].hasAttribute( 'data-keep-inert' ) ) {
				children[ i ].removeAttribute( 'inert' );
			}

			children[ i ].removeAttribute( 'data-keep-inert' );
		}

		modal.hidden = true;
		modalBg.hidden = true;

		// Wait a tick before setting focus. See https://github.com/WICG/inert#performance-and-gotchas
		setTimeout( function() {
			if ( button ) {
				button.focus();
			} else {
				document.body.focus();
			}
		}, 0 );
	}

	function sendRequest( event ) {
		event.preventDefault();
		const email = $( event.target.querySelector( 'input[type="email"]' ) ).val();
		const pledgeId = $( event.target.querySelector( 'input[name="pledge_id"]' ) ).val();
		$( event.target.querySelector( '.message' ) ).html( '' );
		$.ajax( {
			type: 'POST',
			url: FFTF_Dialog.ajaxurl,
			data: {
				action: 'send-manage-email',
				pledge_id: pledgeId,
				email: email,
				_ajax_nonce: FFTF_Dialog.ajaxNonce,
			},
			success( response ) {
				if ( response.message ) {
					// Say the message for screen reader users.
					wp.a11y.speak( response.message );

					if ( response.success && !! button ) {
						closeModal();
						$( button ).after( $( '<p>' ).html( '<em>' + response.message + '<em>' ) );
					} else {
						const $message = $( '<div>' )
							.addClass( 'notice notice-alt' )
							.addClass( response.success ? 'notice-success' : 'notice-error' )
							.append( $( '<p>' ).html( response.message ) );

						$( event.target.querySelector( '.message' ) ).html( $message );
					}
				}
			},
			dataType: 'json',
		} );
	}

	// Initialize.
	$( button ).on( 'click', function( event ) {
		event.preventDefault();

		if ( !! modal.hidden ) {
			openModal( event );
		} else {
			closeModal( event );
		}
	} );

	// Make sure `modal` exists before using it.
	if ( !! modal ) {
		$( modalBg ).on( 'click', closeModal );
		$( modal ).on( 'click', '.pledge-dialog__close', closeModal );
		$( document ).on( 'keydown', function( event ) {
			if ( 27 === event.which ) { // Esc
				closeModal( event );
			}
		} );

		$( modal.querySelector( 'form' ) ).submit( sendRequest );
	}
} );

/* global ajaxurl, FiveForTheFuture, jQuery */
jQuery( document ).ready( function( $ ) {
	const button = document.getElementById( 'toggle-management-link-form' );
	const template = wp.template( '5ftf-send-link-dialog' );

	if ( ! template && ! button ) {
		// No modal on this page.
		return;
	}

	$( document.body ).prepend( template() );
	const modal = document.getElementById( 'send-link-dialog' );
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
		}
	}

	/**
	 * Open the modal.
	 */
	function openModal() {
		const position = getModalPosition();
		// Hide other content on this page while modal is open.
		for ( i = 0; i < children.length; i++ ) {
			if ( children[i].getAttribute('inert') ) {
				children[i].setAttribute('data-keep-inert', '');
			} else {
				children[i].setAttribute('inert', 'true');
			}
		}

		modal.removeAttribute( 'hidden' );
		modal.style.top = position.top + 'px';
		modal.style.left = position.left + 'px';
		modal.focus();
	}

	/**
	 * Close the modal.
	 */
	function closeModal() {
		// Reveal content again.
		for ( i = 0; i < children.length; i++ ) {
			if ( !children[i].hasAttribute('data-keep-inert') ) {
				children[i].removeAttribute('inert');
			}

			children[i].removeAttribute('data-keep-inert');
		}

		modal.hidden = true;

		// Wait a tick before setting focus. See https://github.com/WICG/inert#performance-and-gotchas
		setTimeout( function() {
			if ( button ) {
				button.focus();
			} else {
				document.body.focus();
			}
		}, 0);
	}

	function sendRequest() {
		//
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

	$( modal ).on( 'click', '.pledge-dialog__close', closeModal );
	$( document ).on( 'keydown', function( event ) {
		if ( 27 === event.which ) { // Esc
			closeModal( event );
		}
	} );

	$( modal ).on( 'submit', 'form', sendRequest );
} );




window.wp = window.wp || {};

jQuery( function( $ ) {
	'use strict';

	var allCompanies = window.fiveFutureCompanies || {},
	    sortOrder    = 'ascending';

	var app = window.wp.FiveForTheFuture = {
		// jsdoc
		init: function() {
			app.renderTemplate( allCompanies );

			$( '#5ftf-search' ).keyup( app.searchCompanies );
				// works on keyup but not change. isn't change better?
			$( '.fftf-sorting-indicator' ).click( app.orderCompanies );
		},

		//
		renderTemplate: function( companies ) {
			var $container = $( '#5ftf-companies-body' ),
			    template   = wp.template( '5ftf-companies' );

			$container.html( template( companies ) );
		},

		//
		searchCompanies: function( event ) {
			var matches = $.extend( true, [], allCompanies ),
				query = $( event.target ).val().toLowerCase();

			matches = _.filter( matches, function( company ) {
				return -1 !== company.name.toLowerCase().indexOf( query );
			} );

			app.renderTemplate( matches );
		},

		//
		orderCompanies: function( event ) {
			var $activeSortButton = $( event.target ),
			    $activeSortColumn = $activeSortButton.parent( 'th' ),
				$sortColumns      = $( '.fftf-sorting-indicator' );

			allCompanies = _.sortBy( allCompanies, $activeSortButton.data( 'field' ) );

			$sortColumns.removeClass( 'fftf-sorted-ascending' );
			$sortColumns.removeClass( 'fftf-sorted-descending' );

			if ( 'ascending' === sortOrder ) {
				sortOrder    = 'descending';
				allCompanies = allCompanies.reverse();

				$activeSortColumn.addClass( 'fftf-sorted-descending' );
			} else {
				sortOrder = 'ascending';
				$activeSortColumn.addClass( 'fftf-sorted-ascending' );
			}

			app.renderTemplate( allCompanies );
		}
	};

	app.init();
} );

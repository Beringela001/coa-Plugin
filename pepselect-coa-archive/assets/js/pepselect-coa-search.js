/**
 * COA archive typeahead search.
 *
 * Turns the archive hero search into a live combobox. As the user types,
 * results appear beneath the field: compound-and-strength entries with their
 * public status ("Latest record", "Testing underway", ...) and public batch
 * codes. Choosing a compound opens its history page; choosing a batch opens
 * that batch report. Submitting the form still performs the normal
 * server-side search, so the feature degrades gracefully.
 */
( function () {
	'use strict';

	var form = document.querySelector( '.ps-coa-archive-search' );

	if ( ! form ) {
		return;
	}

	var input = form.querySelector( 'input[type="search"]' );
	var endpoint = ( window.PepSelectCoaSearch && window.PepSelectCoaSearch.endpoint ) || '';

	if ( ! input || ! endpoint ) {
		return;
	}

	var listbox = document.createElement( 'ul' );
	listbox.className = 'ps-coa-typeahead';
	listbox.setAttribute( 'role', 'listbox' );
	listbox.hidden = true;

	var wrapper = form.querySelector( '.ps-coa-search__controls' ) || form;
	wrapper.style.position = 'relative';
	wrapper.appendChild( listbox );

	input.setAttribute( 'role', 'combobox' );
	input.setAttribute( 'aria-autocomplete', 'list' );
	input.setAttribute( 'aria-expanded', 'false' );
	input.setAttribute( 'autocomplete', 'off' );

	var items = [];
	var active = -1;
	var controller = null;
	var debounce;

	function close() {
		listbox.hidden = true;
		listbox.innerHTML = '';
		items = [];
		active = -1;
		input.setAttribute( 'aria-expanded', 'false' );
	}

	function go( url ) {
		if ( url ) {
			window.location.assign( url );
		}
	}

	function render( results ) {
		listbox.innerHTML = '';
		items = [];

		if ( ! results.length ) {
			close();
			return;
		}

		results.forEach( function ( result, index ) {
			var li = document.createElement( 'li' );
			li.className = 'ps-coa-typeahead__item';
			li.setAttribute( 'role', 'option' );
			li.id = 'ps-coa-typeahead-' + index;
			li.setAttribute( 'data-url', result.url );

			if ( 'batch' === result.kind ) {
				li.innerHTML =
					'<span class="ps-coa-typeahead__batch">' + escapeHtml( result.batch ) + '</span>' +
					'<span class="ps-coa-typeahead__meta">' + escapeHtml( result.name ) + ' &middot; batch report</span>';
			} else {
				var title = escapeHtml( result.name );
				if ( result.strength ) {
					title += ' <span class="ps-coa-typeahead__strength">' + escapeHtml( result.strength ) + '</span>';
				}
				var meta = '';
				if ( result.batch ) {
					meta += '<span class="ps-coa-typeahead__item-batch">' + escapeHtml( result.batch ) + '</span>';
				}
				if ( result.status ) {
					meta += ( meta ? '<span class="ps-coa-typeahead__sep">&middot;</span>' : '' ) +
						'<span class="ps-coa-typeahead__status">' + escapeHtml( result.status ) + '</span>';
				}
				li.innerHTML =
					'<span class="ps-coa-typeahead__name">' + title + '</span>' +
					( meta ? '<span class="ps-coa-typeahead__meta-group">' + meta + '</span>' : '' );
			}

			li.addEventListener( 'mousedown', function ( event ) {
				event.preventDefault();
				go( result.url );
			} );

			listbox.appendChild( li );
			items.push( li );
		} );

		listbox.hidden = false;
		input.setAttribute( 'aria-expanded', 'true' );
		active = -1;
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = value == null ? '' : String( value );
		return div.innerHTML;
	}

	function search( term ) {
		if ( controller ) {
			controller.abort();
		}

		controller = new AbortController();

		fetch( endpoint + '?q=' + encodeURIComponent( term ), {
			signal: controller.signal,
			headers: { Accept: 'application/json' }
		} )
			.then( function ( response ) {
				return response.ok ? response.json() : { results: [] };
			} )
			.then( function ( data ) {
				render( ( data && data.results ) || [] );
			} )
			.catch( function () {} );
	}

	input.addEventListener( 'input', function () {
		var term = input.value.trim();
		window.clearTimeout( debounce );

		if ( term.length < 2 ) {
			close();
			return;
		}

		debounce = window.setTimeout( function () {
			search( term );
		}, 220 );
	} );

	input.addEventListener( 'keydown', function ( event ) {
		if ( listbox.hidden ) {
			return;
		}

		if ( 'ArrowDown' === event.key ) {
			event.preventDefault();
			active = ( active + 1 ) % items.length;
			setActive();
		} else if ( 'ArrowUp' === event.key ) {
			event.preventDefault();
			active = active <= 0 ? items.length - 1 : active - 1;
			setActive();
		} else if ( 'Enter' === event.key ) {
			if ( active > -1 && items[ active ] ) {
				event.preventDefault();
				go( items[ active ].getAttribute( 'data-url' ) );
			}
		} else if ( 'Escape' === event.key ) {
			close();
		}
	} );

	function setActive() {
		items.forEach( function ( li, index ) {
			if ( index === active ) {
				li.classList.add( 'is-active' );
				input.setAttribute( 'aria-activedescendant', li.id );
			} else {
				li.classList.remove( 'is-active' );
			}
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! form.contains( event.target ) ) {
			close();
		}
	} );
}() );

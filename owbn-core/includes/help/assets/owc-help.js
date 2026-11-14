/**
 * Help panel for OWbN plugins' wp-admin screens: a ? next to the screen's title opens the screen's help page in a
 * side panel, read from the Markdown the plugin ships, in the reader's language, with a link to the same page on the
 * support site. Links to the plugin's other help pages open in the panel, with a Back button.
 */
( function () {
	'use strict';

	var cfg = window.owcHelp;
	if ( ! cfg || ! window.marked || ! document.querySelector ) {
		return;
	}

	var panel = null;
	var parts = {};
	var trail = [];

	function has( key ) {
		return cfg.keys.indexOf( key ) !== -1;
	}

	function supportUrl( slug, anchor ) {
		return cfg.site + ( 'pt_BR' === cfg.lang ? '/pt' : '' ) + '/docs/' + slug + '/' + ( anchor || '' );
	}

	function slugify( text ) {
		return text.toLowerCase().replace( /[^\p{L}\p{M}\p{N}\p{Pc}\- ]/gu, '' ).replace( / /g, '-' );
	}

	function build() {
		if ( panel ) {
			return;
		}
		panel = document.createElement( 'aside' );
		panel.className = 'owc-help-panel';
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', cfg.words.help );
		panel.hidden = true;
		panel.innerHTML =
			'<div class="owc-help-head">' +
			'<button type="button" class="owc-help-back" hidden></button>' +
			'<h2 class="owc-help-title"></h2>' +
			'<button type="button" class="owc-help-close"></button>' +
			'</div>' +
			'<div class="owc-help-links"><a class="owc-help-open" target="_blank" rel="noopener"></a></div>' +
			'<div class="owc-help-body" tabindex="0"></div>';
		document.body.appendChild( panel );
		parts = {
			back: panel.querySelector( '.owc-help-back' ),
			title: panel.querySelector( '.owc-help-title' ),
			close: panel.querySelector( '.owc-help-close' ),
			open: panel.querySelector( '.owc-help-open' ),
			body: panel.querySelector( '.owc-help-body' )
		};
		parts.back.textContent = '‹ ' + cfg.words.back;
		parts.close.textContent = '×';
		parts.close.setAttribute( 'aria-label', cfg.words.close );
		parts.open.textContent = cfg.words.open;

		parts.close.addEventListener( 'click', close );
		parts.back.addEventListener( 'click', function () {
			var previous = trail.pop();
			if ( previous ) {
				load( previous.key, previous.anchor, true );
			}
		} );
		parts.body.addEventListener( 'click', function ( event ) {
			var link = event.target.closest ? event.target.closest( 'a' ) : null;
			if ( ! link ) {
				return;
			}
			var href = link.getAttribute( 'href' ) || '';
			var match = href.match( /^(\.\.\/)?(?:help\/)?([a-z0-9_-]+)\.md(#.*)?$/ );
			if ( match && ! match[ 1 ] && has( match[ 2 ] ) ) {
				event.preventDefault();
				load( match[ 2 ], match[ 3 ] || '' );
				return;
			}
			if ( match && match[ 1 ] && cfg.guides[ match[ 2 ] ] ) {
				event.preventDefault();
				window.open( supportUrl( cfg.guides[ match[ 2 ] ], match[ 3 ] || '' ), '_blank', 'noopener' );
				return;
			}
			if ( /^#/.test( href ) ) {
				event.preventDefault();
				scrollTo( href );
			}
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && panel && ! panel.hidden ) {
				close();
			}
		} );
	}

	function scrollTo( anchor ) {
		var target = null;
		try {
			target = parts.body.querySelector( decodeURIComponent( anchor ) );
		} catch ( error ) {
			target = null;
		}
		if ( target ) {
			target.scrollIntoView( { block: 'start' } );
		}
	}

	function load( key, anchor, goingBack ) {
		if ( ! goingBack && parts.title.getAttribute( 'data-key' ) ) {
			trail.push( { key: parts.title.getAttribute( 'data-key' ), anchor: '' } );
		}
		parts.back.hidden = ! trail.length;
		parts.title.setAttribute( 'data-key', key );
		parts.title.textContent = cfg.words.help;
		parts.body.innerHTML = '<p>' + cfg.words.loading + '</p>';
		parts.open.href = supportUrl( cfg.prefix + key, anchor );

		var url = cfg.ajax + '?action=owc_help_page&id=' + encodeURIComponent( cfg.id ) + '&key=' + encodeURIComponent( key ) + '&lang=' + encodeURIComponent( cfg.lang );
		window.fetch( url, { credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( parts.title.getAttribute( 'data-key' ) !== key ) {
					return;
				}
				if ( ! result || ! result.success ) {
					parts.body.innerHTML = '<p>' + cfg.words.missing + '</p>';
					return;
				}
				parts.body.innerHTML = window.marked.parse( result.data.markdown );
				var heading = parts.body.querySelector( 'h1' );
				parts.title.textContent = heading ? heading.textContent : cfg.words.help;
				if ( heading ) {
					heading.parentNode.removeChild( heading );
				}
				parts.body.querySelectorAll( 'h2, h3, h4' ).forEach( function ( node ) {
					node.id = slugify( node.textContent );
				} );
				if ( anchor ) {
					scrollTo( anchor );
				} else {
					parts.body.scrollTop = 0;
				}
			} )
			.catch( function () {
				parts.body.innerHTML = '<p>' + cfg.words.missing + '</p>';
			} );
	}

	function open() {
		build();
		trail = [];
		parts.title.removeAttribute( 'data-key' );
		panel.hidden = false;
		load( cfg.key, '' );
		parts.close.focus();
	}

	function close() {
		if ( panel ) {
			panel.hidden = true;
		}
	}

	function button() {
		var node = document.createElement( 'button' );
		node.type = 'button';
		node.className = 'owc-help-button';
		node.textContent = '?';
		node.setAttribute( 'aria-label', cfg.words.help );
		node.title = cfg.words.help;
		node.addEventListener( 'click', open );
		return node;
	}

	// The block editor draws its own top bar, and may draw it again; the button goes first in its settings group.
	function placeInEditorBar() {
		var bar = document.querySelector( '.editor-header__settings, .edit-post-header__settings' );
		if ( bar && ! bar.querySelector( '.owc-help-button' ) ) {
			bar.insertBefore( button(), bar.firstChild );
		}
	}

	function init() {
		if ( document.body.classList.contains( 'block-editor-page' ) ) {
			placeInEditorBar();
			new MutationObserver( placeInEditorBar ).observe( document.body, { childList: true, subtree: true } );
			return;
		}
		var heading = document.querySelector( '#wpbody-content .wrap h1, #wpbody-content h1' );
		if ( ! heading || heading.querySelector( '.owc-help-button' ) ) {
			return;
		}
		heading.appendChild( button() );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

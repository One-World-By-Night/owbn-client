/**
 * The OAT help panel: a ? button on each OAT screen that opens the screen's help page in a side panel, read from the
 * Markdown the plugin ships, in the reader's language, with a link to the same page on the support site. On Submit
 * and New Submission the panel follows the form picked; on a request's page it links to that request's form.
 */
( function () {
	'use strict';

	var cfg = window.owcOatHelp;
	if ( ! cfg || ! window.marked || ! document.querySelector ) {
		return;
	}

	var WIDGETS = {
		owc_oat_dashboard: 'dashboard',
		owc_oat_inbox: 'inbox',
		owc_oat_submit: 'submit',
		owc_oat_entry: 'entry',
		owc_oat_registry: 'registry',
		owc_oat_registry_detail: 'registry-detail',
		owc_oat_fame_registry: 'fame-registry',
		owc_cchub_browse: 'cchub',
		owc_cchub_categories: 'cchub'
	};
	var FORM_SELECTS = '#oat-form-select, #oat_form_slug';
	var DOMAIN_SELECTS = '#oat-domain-select, #oat_domain';

	var panel = null;
	var parts = {};
	var trail = [];
	var screenKey = '';

	function has( key ) {
		return cfg.keys.indexOf( key ) !== -1;
	}

	function supportUrl( slug, anchor ) {
		return cfg.site + ( 'pt_BR' === cfg.lang ? '/pt' : '' ) + '/docs/' + slug + '/' + ( anchor || '' );
	}

	function slugify( text ) {
		return text.toLowerCase().replace( /[^\p{L}\p{M}\p{N}\p{Pc}\- ]/gu, '' ).replace( / /g, '-' );
	}

	// The page a Submit screen's help should show: the form picked, else the domain when it is also a form, else Submit.
	function submitKey() {
		var form = document.querySelector( FORM_SELECTS );
		var domain = document.querySelector( DOMAIN_SELECTS );
		if ( form && form.value && has( 'form-' + form.value ) ) {
			return 'form-' + form.value;
		}
		if ( domain && domain.value && has( 'form-' + domain.value ) && ( ! form || ! form.value ) ) {
			return 'form-' + domain.value;
		}
		return 'submit';
	}

	function build() {
		if ( panel ) {
			return;
		}
		panel = document.createElement( 'aside' );
		panel.className = 'oat-help-panel';
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', cfg.words.help );
		panel.hidden = true;
		panel.innerHTML =
			'<div class="oat-help-head">' +
			'<button type="button" class="oat-help-back" hidden></button>' +
			'<h2 class="oat-help-title"></h2>' +
			'<button type="button" class="oat-help-close"></button>' +
			'</div>' +
			'<div class="oat-help-links"><a class="oat-help-open" target="_blank" rel="noopener"></a>' +
			'<a class="oat-help-form" href="#" hidden></a></div>' +
			'<div class="oat-help-body" tabindex="0"></div>';
		document.body.appendChild( panel );
		parts = {
			back: panel.querySelector( '.oat-help-back' ),
			title: panel.querySelector( '.oat-help-title' ),
			close: panel.querySelector( '.oat-help-close' ),
			open: panel.querySelector( '.oat-help-open' ),
			form: panel.querySelector( '.oat-help-form' ),
			body: panel.querySelector( '.oat-help-body' )
		};
		parts.back.textContent = '‹ ' + cfg.words.back;
		parts.close.textContent = '×';
		parts.close.setAttribute( 'aria-label', cfg.words.close );
		parts.open.textContent = cfg.words.open;
		parts.form.textContent = cfg.words.form;

		parts.close.addEventListener( 'click', close );
		parts.back.addEventListener( 'click', function () {
			var previous = trail.pop();
			if ( previous ) {
				load( previous.key, previous.anchor, true );
			}
		} );
		parts.form.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var slug = parts.form.getAttribute( 'data-form' );
			if ( slug && has( 'form-' + slug ) ) {
				load( 'form-' + slug );
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

		var formSlug = '';
		var host = document.querySelector( '[data-oat-help-form]' );
		if ( 'entry' === key && host ) {
			formSlug = host.getAttribute( 'data-oat-help-form' ) || '';
		}
		parts.form.hidden = ! ( formSlug && has( 'form-' + formSlug ) );
		parts.form.setAttribute( 'data-form', formSlug );

		var url = cfg.ajax + '?action=owc_oat_help_page&key=' + encodeURIComponent( key ) + '&lang=' + encodeURIComponent( cfg.lang );
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

	function open( key ) {
		build();
		trail = [];
		parts.title.removeAttribute( 'data-key' );
		panel.hidden = false;
		load( key, '' );
		parts.close.focus();
	}

	function close() {
		if ( panel ) {
			panel.hidden = true;
		}
	}

	function button( keyOf, inline ) {
		var node = document.createElement( 'button' );
		node.type = 'button';
		node.className = 'oat-help-button' + ( inline ? ' oat-help-inline' : '' );
		node.textContent = '?';
		node.setAttribute( 'aria-label', cfg.words.help );
		node.title = cfg.words.help;
		node.addEventListener( 'click', function () {
			open( keyOf() );
		} );
		return node;
	}

	// Follows the form picked on a Submit screen: an open panel switches to the new form's page.
	function followForms() {
		document.addEventListener( 'change', function ( event ) {
			if ( ! event.target.matches || ! event.target.matches( FORM_SELECTS + ', ' + DOMAIN_SELECTS ) ) {
				return;
			}
			window.setTimeout( function () {
				if ( panel && ! panel.hidden && /^(submit|form-)/.test( screenKey ) ) {
					screenKey = submitKey();
					trail = [];
					parts.title.removeAttribute( 'data-key' );
					load( screenKey, '' );
				}
			}, 50 );
		} );
	}

	function init() {
		if ( cfg.key ) {
			var heading = document.querySelector( '#wpbody-content .wrap h1, #wpbody-content h1' );
			if ( ! heading ) {
				return;
			}
			var adminKey = function () {
				screenKey = /^(submit|form-)/.test( cfg.key ) ? submitKey() : cfg.key;
				return screenKey;
			};
			heading.appendChild( button( adminKey, true ) );
			followForms();
			return;
		}
		Object.keys( WIDGETS ).forEach( function ( name ) {
			document.querySelectorAll( '.elementor-widget-' + name ).forEach( function ( widget ) {
				var host = widget.querySelector( '.elementor-widget-container' ) || widget;
				if ( host.querySelector( ':scope > .oat-help-button' ) ) {
					return;
				}
				host.classList.add( 'oat-help-host' );
				host.insertBefore(
					button( function () {
						screenKey = 'submit' === WIDGETS[ name ] ? submitKey() : WIDGETS[ name ];
						return screenKey;
					}, false ),
					host.firstChild
				);
			} );
		} );
		followForms();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

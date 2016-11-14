/**
 * File customize-preview.js.
 *
 * Instantly live-update customizer settings in the preview for improved user experience.
 */

( function( $ ) {

	// Collect information from customize-controls.js about which panels are opening
	wp.customize.bind( 'preview-ready', function() {
		wp.customize.preview.bind( 'section-highlight', function( data ) {

			// Only on the front page.
			if ( ! $( 'body' ).hasClass( 'twentyseventeen-front-page' ) ) {
				return;
			}

			// When the section is expanded, show and scroll to the content placeholders, exposing the edit links.
			if ( true === data.expanded ) {
				$( 'body' ).addClass( 'highlight-front-sections' );
				$( '.panel-placeholder' ).slideDown( 200, function() {
					$.scrollTo( $( '#panel1' ), {
						duration: 600,
						offset: { 'top': -70 } // Account for sticky menu.
					} );
				});

			// If we've left the panel, hide the placeholders and scroll back to the top
			} else {
				$( 'body' ).removeClass( 'highlight-front-sections' );
				$( '.panel-placeholder' ).slideUp( 200 ); // Don't change scroll when leaving - it's likely to have unintended consequences.
			}
		} );
	} );

	// Site title and description.
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			$( '.site-title a' ).text( to );
		} );
	} );
	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			$( '.site-description' ).text( to );
		} );
	} );

	// Header text color.
	wp.customize( 'header_textcolor', function( value ) {
		value.bind( function( to ) {
			if ( 'blank' === to ) {
				$( '.site-title, .site-description' ).css( {
					'clip': 'rect(1px, 1px, 1px, 1px)',
					'position': 'absolute'
				} );
				$( 'body' ).addClass( 'title-tagline-hidden' );
			} else {
				$( '.site-title, .site-description' ).css( {
					'clip': 'auto',
					'position': 'relative'
				} );
				$( '.site-branding, .site-branding a, .site-description, .site-description a' ).css( {
					'color': to
				} );
				$( 'body' ).removeClass( 'title-tagline-hidden' );
			}
		} );
	} );

	// Header image/video.
	wp.customize( 'header_video', 'external_header_video', 'header_image', function( headerVideo, externalHeaderVideo, headerImage ) {
		var body = $( 'body' ), toggleClass;
		toggleClass = function() {
			var hasHeaderImage, hasHeaderVideo;
			hasHeaderImage = '' !== headerImage.get() && 'remove-header' !== headerImage.get();
			hasHeaderVideo = Boolean( headerVideo.get() || externalHeaderVideo.get() );
			body.toggleClass( 'has-header-image', hasHeaderImage || body.hasClass( 'home' ) && hasHeaderVideo );
		};
		_.each( [ headerVideo, externalHeaderVideo, headerImage ], function( setting ) {
			setting.bind( toggleClass );
		} );
	} );

	// Color scheme.
	wp.customize( 'colorscheme', function( value ) {
		value.bind( function( to ) {

			// Update color body class.
			$( 'body' ).removeClass( 'colors-light colors-dark colors-custom' )
			           .addClass( 'colors-' + to );
		} );
	} );

	// Custom color hue.
	wp.customize( 'colorscheme_hue', function( value ) {
		value.bind( function( to ) {

			// Update custom color CSS
			var style = $( '#custom-theme-colors' ),
			    hue = style.data( 'hue' ),
			    css = style.html();

			css = css.split( hue + ',' ).join( to + ',' ); // Equivalent to css.replaceAll, with hue followed by comma to prevent values with units from being changed.
			style.html( css )
			     .data( 'hue', to );
		} );
	} );

	// Page layouts.
	wp.customize( 'page_layout', function( value ) {
		value.bind( function( to ) {
			if ( 'one-column' === to ) {
				$( 'body' ).addClass( 'page-one-column' ).removeClass( 'page-two-column' );
			} else {
				$( 'body' ).removeClass( 'page-one-column' ).addClass( 'page-two-column' );
			}
		} );
	} );
} )( jQuery );

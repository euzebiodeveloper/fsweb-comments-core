/**
 * FSWWP Reviews — Minimal Slider
 *
 * Zero-dependency carousel. Attach to any element with [data-fswwp-slider].
 */
( function() {
	'use strict';

	function initSlider( el ) {
		var track    = el.querySelector( '.fswwp-reviews-track' );
		var slides   = el.querySelectorAll( '.fswwp-review-slide' );
		var prev     = el.querySelector( '.fswwp-slider-prev' );
		var next     = el.querySelector( '.fswwp-slider-next' );
		var dotsWrap = el.querySelector( '.fswwp-slider-dots' );
		var index    = 0;
		var total    = slides.length;

		if ( total < 2 ) {
			if ( prev ) { prev.style.display = 'none'; }
			if ( next ) { next.style.display = 'none'; }
			if ( dotsWrap ) { dotsWrap.style.display = 'none'; }
			return;
		}

		// Build dots.
		if ( dotsWrap ) {
			for ( var i = 0; i < total; i++ ) {
				var dot = document.createElement( 'span' );
				dot.className = 'fswwp-dot' + ( i === 0 ? ' active' : '' );
				dot.setAttribute( 'data-index', i );
				dot.addEventListener( 'click', function() {
					goTo( parseInt( this.getAttribute( 'data-index' ), 10 ) );
				} );
				dotsWrap.appendChild( dot );
			}
		}

		function goTo( newIndex ) {
			index = newIndex;
			track.style.transform = 'translateX(-' + ( index * 100 ) + '%)';

			if ( dotsWrap ) {
				var dots = dotsWrap.querySelectorAll( '.fswwp-dot' );
				dots.forEach( function( d, i ) {
					d.classList.toggle( 'active', i === index );
				} );
			}
		}

		prev.addEventListener( 'click', function() {
			goTo( index > 0 ? index - 1 : total - 1 );
		} );

		next.addEventListener( 'click', function() {
			goTo( index < total - 1 ? index + 1 : 0 );
		} );

		// Auto-advance every 4s, pause on hover.
		var autoplay = setInterval( function() {
			goTo( index < total - 1 ? index + 1 : 0 );
		}, 4000 );

		el.addEventListener( 'mouseenter', function() { clearInterval( autoplay ); } );
		el.addEventListener( 'mouseleave', function() {
			autoplay = setInterval( function() {
				goTo( index < total - 1 ? index + 1 : 0 );
			}, 4000 );
		} );
	}

	// Init all sliders on page.
	document.querySelectorAll( '[data-fswwp-slider]' ).forEach( initSlider );
} )();

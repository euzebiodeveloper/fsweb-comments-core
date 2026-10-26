/**
 * FSWEB Reviews — Slider.
 *
 * Attach to any element with [data-fsweb-slider]. Cards per view come from CSS
 * (--fsweb-slides, capped on tablet and phone), so the script measures the
 * real layout instead of trusting the attribute, and moves the track by the
 * pixel offset of the target slide.
 *
 * The movement is animated with the Web Animations API instead of a CSS
 * transition: themes reset transitions and prefers-reduced-motion disables
 * them, which made the carousel snap from one page to the next.
 *
 * The ends are real ends: the previous arrow stops on the first page and the
 * next arrow stops on the last one, so nothing rewinds across the track.
 */
( function() {
	'use strict';

	var DURATION = 520;
	var EASING   = 'cubic-bezier(0.33, 1, 0.68, 1)';

	function initSlider( el ) {
		if ( el.dataset.fswebSliderReady ) {
			return;
		}

		var viewport = el.querySelector( '.fsweb-slider-viewport' );
		var track    = el.querySelector( '.fsweb-reviews-track' );
		var prev     = el.querySelector( '.fsweb-slider-prev' );
		var next     = el.querySelector( '.fsweb-slider-next' );
		var dotsWrap = el.querySelector( '.fsweb-slider-dots' );
		var slides   = Array.prototype.slice.call( track ? track.querySelectorAll( '.fsweb-review-slide' ) : [] );
		var total    = slides.length;
		var perView  = 1;
		var index    = 0;
		var anim     = null;

		if ( ! viewport || ! track || total < 1 ) {
			if ( prev ) { prev.hidden = true; }
			if ( next ) { next.hidden = true; }
			if ( dotsWrap ) { dotsWrap.hidden = true; }
			return;
		}

		el.dataset.fswebSliderReady = '1';

		function measurePerView() {
			var width = slides[ 0 ].getBoundingClientRect().width;

			if ( ! width ) {
				return 1;
			}

			return Math.max( 1, Math.min( total, Math.round( viewport.clientWidth / width ) ) );
		}

		function lastIndex() {
			return Math.max( 0, total - perView );
		}

		function offsetOf( position ) {
			var slide = slides[ position ];

			if ( ! slide || ! slides[ 0 ] ) {
				return 0;
			}

			return Math.round( slide.getBoundingClientRect().left - slides[ 0 ].getBoundingClientRect().left );
		}

		function place( position ) {
			track.style.transform = 'translate3d(-' + offsetOf( position ) + 'px, 0, 0)';
		}

		function animateTo( position, onDone ) {
			var target = 'translate3d(-' + offsetOf( position ) + 'px, 0, 0)';
			var from;

			if ( anim ) {
				anim.cancel();
				anim = null;
			}

			if ( ! track.animate ) {
				place( position );
				if ( onDone ) { onDone(); }
				return;
			}

			from = window.getComputedStyle( track ).transform;
			place( position );

			if ( ! from || 'none' === from || from === target ) {
				if ( onDone ) { onDone(); }
				return;
			}

			anim = track.animate(
				[ { transform: from }, { transform: target } ],
				{ duration: DURATION, easing: EASING, fill: 'none' }
			);

			anim.onfinish = function() {
				if ( onDone ) { onDone(); }
				anim = null;
			};
		}

		function paintControls() {
			var last = lastIndex();

			if ( prev ) {
				prev.disabled = 0 === index;
				prev.setAttribute( 'aria-disabled', 0 === index ? 'true' : 'false' );
			}

			if ( next ) {
				next.disabled = index >= last;
				next.setAttribute( 'aria-disabled', index >= last ? 'true' : 'false' );
			}

			if ( ! dotsWrap ) {
				return;
			}

			Array.prototype.forEach.call( dotsWrap.children, function( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
				dot.setAttribute( 'aria-current', i === index ? 'true' : 'false' );
			} );
		}

		function buildDots() {
			if ( ! dotsWrap ) {
				return;
			}

			dotsWrap.innerHTML = '';

			for ( var i = 0; i <= lastIndex(); i++ ) {
				var dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'fsweb-dot';
				dot.setAttribute( 'data-index', i.toString() );
				dot.setAttribute( 'aria-label', ( i + 1 ).toString() );
				dot.addEventListener( 'click', function() {
					goTo( parseInt( this.getAttribute( 'data-index' ), 10 ) );
				} );
				dotsWrap.appendChild( dot );
			}

			paintControls();
		}

		function goTo( target ) {
			index = Math.max( 0, Math.min( target, lastIndex() ) );
			animateTo( index, paintControls );
			paintControls();
		}

		function step( direction ) {
			goTo( index + direction );
		}

		if ( prev ) {
			prev.addEventListener( 'click', function() {
				step( -1 );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function() {
				step( 1 );
			} );
		}

		// Keyboard: the arrows alone do not help someone tabbing through cards.
		function onKeydown( event ) {
			if ( 'ArrowLeft' === event.key ) {
				step( -1 );
			} else if ( 'ArrowRight' === event.key ) {
				step( 1 );
			} else {
				return;
			}

			event.preventDefault();
		}

		el.addEventListener( 'keydown', onKeydown );

		function layout() {
			perView = measurePerView();
			buildDots();
			index = Math.min( index, lastIndex() );
			place( index );
			paintControls();

			if ( perView >= total ) {
				if ( prev ) { prev.hidden = true; }
				if ( next ) { next.hidden = true; }
				if ( dotsWrap ) { dotsWrap.hidden = true; }
			} else {
				if ( prev ) { prev.hidden = false; }
				if ( next ) { next.hidden = false; }
				if ( dotsWrap ) { dotsWrap.hidden = false; }
			}
		}

		var resizeTimer = null;
		window.addEventListener( 'resize', function() {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( layout, 150 );
		} );

		// Images and web fonts change the card widths after first paint.
		window.addEventListener( 'load', layout );

		layout();

		// Exposed for the test suite (asserts the paging maths).
		el.fswebSlider = {
			next: function() { return step( 1 ); },
			prev: function() { return step( -1 ); },
			goTo: goTo,
			state: function() {
				return { index: index, perView: perView, total: total, last: lastIndex() };
			}
		};
	}

	document.querySelectorAll( '[data-fsweb-slider]' ).forEach( initSlider );
} )();

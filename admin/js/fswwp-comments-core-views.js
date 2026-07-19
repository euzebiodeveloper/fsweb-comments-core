/**
 * FSWWP Comments Core — Views Builder JS
 *
 * Grid-based row/column layout builder with drag-and-drop components.
 * Components are draggable; columns are droppable.  Drop position is
 * calculated from cursor coordinates, giving true mouse-enter behaviour
 * on every grid cell (real component or empty slot).
 */
( function( $ ) {
	'use strict';

	var $rows    = $( '#fswwp-cc-rows' );
	var $empty   = $( '#fswwp-cc-view-empty' );
	var $msg     = $( '#fswwp-cc-view-message' );
	var $codeOut = $( '#fswwp-cc-view-shortcode-output' );
	var $codeTxt = $( '#fswwp-cc-view-shortcode-text' );
	var nonce    = $( '.fswwp-cc-builder' ).data( 'nonce' );

	// ---- Row reordering (unchanged — sortable) ---------------------------
	$rows.sortable( {
		handle: '.fswwp-cc-row-handle',
		placeholder: 'fswwp-cc-row-placeholder',
		forcePlaceholderSize: true,
		opacity: 0.8,
		tolerance: 'pointer'
	} );

	// ---- Helpers ---------------------------------------------------------

	function addExtraSlots( $row ) {
		$row.find( '.fswwp-cc-view-col' ).each( function() {
			var $inner = $( this ).find( '.fswwp-cc-col-inner' );
			if ( ! $inner.find( '.fswwp-cc-view-comp--extra' ).length ) {
				$inner.append(
					'<div class="fswwp-cc-view-comp fswwp-cc-view-comp--empty fswwp-cc-view-comp--extra" data-type="empty">' +
					'<span class="fswwp-cc-extra-label">+ Drop here</span></div>'
				);
			}
		} );
	}

	function balanceColumns( extra ) {
		extra = extra || 0;
		$rows.find( '.fswwp-cc-view-row' ).each( function() {
			var $cols = $( this ).find( '.fswwp-cc-view-col' );
			var max   = 0;
			var counts = [];

			// Count real components per column (ignore empties + extras).
			$cols.each( function() {
				var real = $( this ).find( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).length;
				counts.push( real );
				if ( real > max ) { max = real; }
			} );

			max += extra;
			if ( max < 1 ) { max = 1; }

			$cols.each( function( ci ) {
				var $inner     = $( this ).find( '.fswwp-cc-col-inner' );
				var real       = counts[ ci ];
				var $empties   = $inner.find( '.fswwp-cc-view-comp--empty' );
				var emptyCount = $empties.length;
				var total      = real + emptyCount;

				if ( total < max ) {
					// Not enough slots — add empties at the end.
					for ( var i = total; i < max; i++ ) {
						$inner.append(
							'<div class="fswwp-cc-view-comp fswwp-cc-view-comp--empty" data-type="empty">Drop here</div>'
						);
					}
				} else if ( total > max ) {
					// Too many slots — remove empties from the END only,
					// preserving empties that sit between real components.
					var toRemove = total - max;
					$empties.slice( -toRemove ).remove();
				}
			} );
		} );
		toggleEmpty();
	}

	function toggleEmpty() {
		var has = false;
		$rows.find( '.fswwp-cc-col-inner' ).each( function() {
			if ( $( this ).find( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).length ) {
				has = true;
			}
		} );
		$empty.toggle( ! has );
	}

	function showMsg( text, type ) {
		$msg.removeClass( 'notice-success notice-error' )
			.addClass( 'notice notice-' + type + ' notice-alt' )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show().delay( 3000 ).fadeOut( 400 );
	}

	// ---- Find child at cursor Y inside a column --------------------------
	// Returns { el, position } — el is the child under the cursor,
	// position is 'top' (cursor in upper half) or 'bottom' (lower half).

	function childAtY( $col, clientY ) {
		var $children = $col.children( ':visible' );
		var result    = null;

		// Above first child → prepend.
		var first = $children.first()[0];
		if ( first ) {
			var r0 = first.getBoundingClientRect();
			if ( clientY < r0.top ) {
				return { el: first, position: 'top' };
			}
		}

		$children.each( function() {
			var rect = this.getBoundingClientRect();
			if ( clientY >= rect.top && clientY <= rect.bottom ) {
				result = {
					el:       this,
					position: ( clientY < rect.top + rect.height / 2 ) ? 'top' : 'bottom'
				};
				return false;
			}
		} );

		// Below last child → append.
		if ( ! result ) {
			var last = $children.last()[0];
			if ( last ) {
				var r = last.getBoundingClientRect();
				if ( clientY > r.bottom ) {
					result = { el: last, position: 'below' };
				}
			}
		}

		return result;
	}

	// ---- Make a real component draggable ---------------------------------
	function makeCompDraggable( $comp ) {
		if ( $comp.hasClass( 'ui-draggable' ) ) { return; }
		$comp.draggable( {
			helper: 'original',
			revert: 'invalid',
			revertDuration: 150,
			appendTo: 'body',
			zIndex: 1000,
			scroll: true,
			scrollSensitivity: 40,

			start: function() {
				$( this ).addClass( 'fswwp-cc-comp--dragging' );
				var $row = $( this ).closest( '.fswwp-cc-view-row' );
				addExtraSlots( $row );
				$row.addClass( 'fswwp-cc-row--dragging' );
			},

			stop: function() {
				$( this ).removeClass( 'fswwp-cc-comp--dragging ui-state-hover ui-state-active' );
				$rows.find( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );
				$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active ui-state-hover ui-state-active' );
				$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$( '.fswwp-cc-view-comp--extra' ).remove();
				balanceColumns( 0 );
			}
		} );
	}

	// ---- Palette items draggable -----------------------------------------
	$( '.fswwp-cc-palette-item' ).draggable( {
		helper: 'clone',
		revert: 'invalid',
		revertDuration: 150,
		appendTo: 'body',
		zIndex: 1000,
		scroll: true,
		scrollSensitivity: 40,

		start: function() {
			$rows.find( '.fswwp-cc-view-row' ).each( function() {
				addExtraSlots( $( this ) );
				$( this ).addClass( 'fswwp-cc-row--dragging' );
			} );
		},

		stop: function() {
			$rows.find( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );
			$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active ui-state-hover ui-state-active' );
			$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
			$( '.fswwp-cc-view-comp--extra' ).remove();
			balanceColumns( 0 );
		}
	} );

	// ---- Columns as droppable targets ------------------------------------
	var dropTrackNS = 'mousemove.fswwpDropTrack';
	var lastHit     = null;  // cached from tracker, used in drop

	function initColumnDroppable() {
		$( '.fswwp-cc-col-inner' ).droppable( {
			accept: '.fswwp-cc-view-comp, .fswwp-cc-palette-item',
			tolerance: 'pointer',

			over: function( e, ui ) {
				var $col = $( this );
				$col.addClass( 'fswwp-cc-col--drop-active' );

				// Track cursor continuously while over this column.
				$( document ).off( dropTrackNS ).on( dropTrackNS, function( ev ) {
					lastHit = childAtY( $col, ev.clientY );
					$col.find( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
					if ( lastHit && $( lastHit.el ).hasClass( 'fswwp-cc-view-comp--empty' ) ) {
						$( lastHit.el ).addClass( 'fswwp-cc-empty--hover' );
					}
				} );
			},

			out: function() {
				lastHit = null;
				var $col = $( this );
				$col.removeClass( 'fswwp-cc-col--drop-active ui-state-hover ui-state-active' );
				$col.find( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$col.find( '.ui-state-hover, .ui-state-active' ).removeClass( 'ui-state-hover ui-state-active' );
				$( document ).off( dropTrackNS );
			},

			drop: function( e, ui ) {
				var $col = $( this );
				$( document ).off( dropTrackNS );

				$col.removeClass( 'fswwp-cc-col--drop-active ui-state-hover ui-state-active' );
				$col.find( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$col.find( '.ui-state-hover, .ui-state-active' ).removeClass( 'ui-state-hover ui-state-active' );

				var hit = lastHit;
				lastHit = null;
				var $comp;

				if ( ui.draggable.hasClass( 'fswwp-cc-palette-item' ) ) {
					var type   = ui.draggable.data( 'type' );
					var icons  = { image:'dashicons-format-image', name:'dashicons-admin-users', content:'dashicons-editor-paragraph', rating:'dashicons-star-filled', date:'dashicons-calendar', category:'dashicons-category' };
					var labels = { image:'Image', name:'Name', content:'Content', rating:'Rating', date:'Date', category:'Category' };
					$comp = $( '<div class="fswwp-cc-view-comp" data-type="' + type + '">' +
						'<span class="dashicons ' + icons[type] + '"></span>' +
						'<span>' + labels[type] + '</span>' +
						'<button type="button" class="fswwp-cc-comp-delete dashicons dashicons-no-alt"></button>' +
						'</div>' );
					makeCompDraggable( $comp );
				} else {
					$comp = ui.draggable.detach();
				}

				if ( hit ) {
					var $target = $( hit.el );

					if ( $target.hasClass( 'fswwp-cc-view-comp--extra' ) ) {
						var $prev = $target.prev( ':not(.fswwp-cc-view-comp--extra)' );
						if ( $prev.length ) {
							$comp.insertAfter( $prev );
						} else {
							$col.prepend( $comp );
						}
					} else if ( $target.hasClass( 'fswwp-cc-view-comp--empty' ) || hit.position !== 'top' ) {
						$comp.insertAfter( hit.el );
					} else {
						$comp.insertBefore( hit.el );
					}
				} else {
					$col.append( $comp );
				}

				$comp.removeClass( 'ui-state-hover ui-state-active ui-draggable-dragging' );

				$( '.fswwp-cc-view-comp--extra' ).remove();
				balanceColumns( 0 );
			}
		} );
	}
	initColumnDroppable();

	// Make existing real components draggable.
	$( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).each( function() {
		makeCompDraggable( $( this ) );
	} );

	// ---- Component deletion ----------------------------------------------
	$rows.on( 'click', '.fswwp-cc-comp-delete', function() {
		$( this ).closest( '.fswwp-cc-view-comp' ).remove();
		balanceColumns( 0 );
	} );

	// ---- Row management --------------------------------------------------
	$( '#fswwp-cc-add-row' ).on( 'click', function() {
		var idx = $rows.find( '.fswwp-cc-view-row' ).length;
		var html = '<div class="fswwp-cc-view-row" data-row="' + idx + '">' +
			'<div class="fswwp-cc-view-row-toolbar">' +
			'<span class="dashicons dashicons-move fswwp-cc-row-handle"></span>' +
			'<strong>Row ' + ( idx + 1 ) + '</strong>' +
			'<button type="button" class="fswwp-cc-col-btn" data-cols="1">1 col</button>' +
			'<button type="button" class="fswwp-cc-col-btn active" data-cols="2">2 cols</button>' +
			'<button type="button" class="fswwp-cc-col-btn" data-cols="3">3 cols</button>' +
			'<button type="button" class="fswwp-cc-delete-row dashicons dashicons-trash"></button>' +
			'</div>' +
			'<div class="fswwp-cc-view-cols">' +
			'<div class="fswwp-cc-view-col" data-col="0" style="flex:50;"><div class="fswwp-cc-col-inner fswwp-cc-col-sortable"></div></div>' +
			'<div class="fswwp-cc-view-col" data-col="1" style="flex:50;"><div class="fswwp-cc-col-inner fswwp-cc-col-sortable"></div></div>' +
			'</div></div>';
		$rows.append( html );
		initColumnDroppable();
		balanceColumns( 0 );
		$empty.hide();
	} );

	// Change column count.
	$rows.on( 'click', '.fswwp-cc-col-btn', function() {
		var $row   = $( this ).closest( '.fswwp-cc-view-row' );
		var cols   = parseInt( $( this ).data( 'cols' ), 10 );
		var $cols  = $row.find( '.fswwp-cc-view-cols' );
		var $exist = $cols.find( '.fswwp-cc-view-col' );
		var count  = $exist.length;

		$( this ).siblings( '.fswwp-cc-col-btn' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		if ( cols === count ) { return; }

		if ( cols > count ) {
			for ( var i = count; i < cols; i++ ) {
				$cols.append( '<div class="fswwp-cc-view-col" data-col="' + i + '" style="flex:' + Math.floor(100/cols) + ';">' +
					'<div class="fswwp-cc-col-inner fswwp-cc-col-sortable"></div></div>' );
			}
		} else {
			for ( var j = cols; j < count; j++ ) {
				var $col = $cols.find( '.fswwp-cc-view-col[data-col="' + j + '"]' );
				$col.find( '.fswwp-cc-view-comp' ).appendTo( $cols.find( '.fswwp-cc-view-col[data-col="0"] .fswwp-cc-col-sortable' ) );
				$col.remove();
			}
		}

		var w = Math.floor( 100 / cols );
		$cols.find( '.fswwp-cc-view-col' ).css( 'flex', w );
		$cols.find( '.fswwp-cc-view-col' ).each( function( idx ) { $( this ).attr( 'data-col', idx ); } );
		initColumnDroppable();
		balanceColumns( 0 );
	} );

	// Delete row.
	$rows.on( 'click', '.fswwp-cc-delete-row', function() {
		$( this ).closest( '.fswwp-cc-view-row' ).remove();
		if ( ! $rows.find( '.fswwp-cc-view-row' ).length ) {
			$empty.show();
		}
	} );

	// ---- Save ------------------------------------------------------------
	$( '#fswwp-cc-save-view' ).on( 'click', function() {
		var $btn = $( this );
		var name = $( '#fswwp-cc-view-name' ).val().trim();
		var data = [];

		$rows.find( '.fswwp-cc-view-row' ).each( function() {
			var row = { columns: [] };
			$( this ).find( '.fswwp-cc-view-col' ).each( function() {
				var col = { components: [] };
				$( this ).find( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).each( function() {
					col.components.push( { type: $( this ).data( 'type' ) } );
				} );
				row.columns.push( col );
			} );
			if ( row.columns.length ) { data.push( row ); }
		} );

		$btn.prop( 'disabled', true ).text( 'Saving…' );
		$.post( window.ajaxurl, {
			action:    'fswwp_cc_save_view',
			nonce:     nonce,
			view_name: name,
			view_id:   $( '#fswwp-cc-view-id' ).val(),
			layout:    $( '#fswwp-cc-view-layout' ).val(),
			count:     $( '#fswwp-cc-view-count' ).val(),
			order:     $( '#fswwp-cc-view-order' ).val(),
			category:  '',
			rows:      JSON.stringify( data )
		}, function( r ) {
			if ( r.success ) {
				$( '#fswwp-cc-view-id' ).val( r.data.view_id );
				$codeTxt.text( r.data.shortcode );
				$codeOut.show();
				showMsg( r.data.message, 'success' );
				setTimeout( function() {
					window.location.search = '?page=fswwp-comments-core-views&post_type=fswwp_review&view_id=' + r.data.view_id;
				}, 1000 );
			} else {
				showMsg( r.data.message, 'error' );
			}
			$btn.prop( 'disabled', false ).text( 'Save View' );
		}, 'json' ).fail( function() {
			showMsg( 'Network error.', 'error' );
			$btn.prop( 'disabled', false ).text( 'Save View' );
		} );
	} );

	// ---- Toolbar actions -------------------------------------------------
	$( '#fswwp-cc-new-view' ).on( 'click', function() {
		$( '#fswwp-cc-view-id' ).val( '' );
		$( '#fswwp-cc-view-name' ).val( '' ).focus();
		$codeOut.hide();
	} );

	$( '.fswwp-cc-delete-view' ).on( 'click', function() {
		if ( ! confirm( 'Delete?' ) ) { return; }
		$.post( window.ajaxurl, { action:'fswwp_cc_delete_view', nonce:nonce, view_id:$( this ).data( 'id' ) }, function() {
			window.location.reload();
		} );
	} );

	$( '.fswwp-cc-shortcode-copy' ).on( 'click', function() {
		navigator.clipboard.writeText( $( this ).text() );
	} );

	// ---- Initial balance -------------------------------------------------
	balanceColumns( 0 );

} )( jQuery );

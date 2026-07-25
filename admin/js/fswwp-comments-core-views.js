/**
 * FSWWP Comments Core — Views Builder JS
 *
 * Grid-based row/column layout builder with drag-and-drop components.
 * Components are draggable; columns are droppable.
 *
 * Connected Form: changing the form selector reloads the palette
 * via AJAX so available components match the form fields.
 */
( function( $ ) {
	'use strict';

	var $rows       = $( '#fswwp-cc-rows' );
	var $empty      = $( '#fswwp-cc-view-empty' );
	var $msg        = $( '#fswwp-cc-view-message' );
	var $formSelect = $( '#fswwp-cc-view-form-id' );
	var $palette    = $( '#fswwp-cc-views-palette' );
	var nonce       = $( '.fswwp-cc-builder' ).data( 'nonce' );

	// ---- Row reordering ---------------------------------------------------
	$rows.sortable( {
		handle: '.fswwp-cc-row-handle',
		placeholder: 'fswwp-cc-row-placeholder',
		forcePlaceholderSize: true,
		opacity: 0.8,
		tolerance: 'pointer'
	} );

	// ---- Connected Form change → reload palette ----------------------------
	$formSelect.on( 'change', function() {
		var formId = $( this ).val();

		$.post( window.ajaxurl, {
			action:  'fswwp_cc_get_form_components',
			nonce:   nonce,
			form_id: formId
		}, function( r ) {
			if ( r.success && r.data.components ) {
				$palette.empty();
				$.each( r.data.components, function( i, comp ) {
					var $item = $( '<div class="fswwp-cc-palette-item" data-type="' + comp.type + '" data-field-id="' + comp.id + '">' +
						'<span class="dashicons ' + comp.icon + '"></span>' +
						comp.label +
						'</div>' );
					$palette.append( $item );
				} );
				// Re-init palette draggable.
				initPaletteDraggable();
			}
		}, 'json' );
	} );

	// ---- Helpers -----------------------------------------------------------

	function addExtraSlots( $row ) {
		$row.find( '.fswwp-cc-view-col' ).each( function() {
			var $inner = $( this ).find( '.fswwp-cc-col-inner' );
			if ( ! $inner.find( '.fswwp-cc-view-comp--extra' ).length ) {
				$inner.prepend(
					'<div class="fswwp-cc-view-comp fswwp-cc-view-comp--empty fswwp-cc-view-comp--extra" data-type="empty">' +
					'<span class="fswwp-cc-extra-label">+ Drop here</span></div>'
				);
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
					for ( var i = total; i < max; i++ ) {
						$inner.append(
							'<div class="fswwp-cc-view-comp fswwp-cc-view-comp--empty" data-type="empty">Drop here</div>'
						);
					}
				} else if ( total > max ) {
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

	function childAtY( $col, clientY ) {
		// Exclude the element currently being dragged.
		var $children = $col.children( ':visible' ).not( '.ui-draggable-dragging' );
		var result    = null;

		if ( ! $children.length ) {
			return null;
		}

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

		if ( ! result ) {
			var last = $children.last()[0];
			if ( last ) {
				var r = last.getBoundingClientRect();
				if ( clientY > r.bottom ) {
					result = { el: last, position: 'below' };
				}
			}
		}

		// Fallback: find the closest child when between gaps.
		if ( ! result && $children.length ) {
			var closest = null;
			var minDist = Infinity;
			$children.each( function() {
				var rect = this.getBoundingClientRect();
				var mid  = rect.top + rect.height / 2;
				var dist = Math.abs( clientY - mid );
				if ( dist < minDist ) {
					minDist = dist;
					closest = this;
				}
			} );
			if ( closest ) {
				var cr = closest.getBoundingClientRect();
				result = {
					el:       closest,
					position: clientY < cr.top + cr.height / 2 ? 'top' : 'bottom'
				};
			}
		}

		return result;
	}

	// ---- Make a real component draggable -----------------------------------
	function makeCompDraggable( $comp ) {
		if ( $comp.hasClass( 'ui-draggable' ) ) { return; }
		$comp.draggable( {
			handle: '.fswwp-cc-field-handle',
			helper: 'original',
			revert: 'invalid',
			revertDuration: 150,
			appendTo: 'body',
			zIndex: 1000,
			scroll: true,
			scrollSensitivity: 40,

			start: function() {
				var $self = $( this );
				$self.addClass( 'fswwp-cc-comp--dragging' );
				var $row = $self.closest( '.fswwp-cc-view-row' );
				addExtraSlots( $row );
				$row.addClass( 'fswwp-cc-row--dragging' );
			},

			stop: function() {
				var $self = $( this );

				// If the element was rejected (still on body from appendTo:'body'),
				// return it to the palette instead of letting it disappear.
				if ( $self.parent().is( 'body' ) ) {
					var type    = $self.data( 'type' );
					var fieldId = $self.data( 'field-id' ) || '';
					var label   = $self.find( 'span:not(.dashicons)' ).text().trim();
					var typeIcons = { image:'dashicons-format-image', name:'dashicons-admin-users', content:'dashicons-editor-paragraph', text:'dashicons-editor-textcolor', textarea:'dashicons-editor-paragraph', email:'dashicons-email', url:'dashicons-admin-links', date:'dashicons-calendar', select:'dashicons-menu-alt', rating:'dashicons-star-filled', category:'dashicons-category' };
					var compIcon = typeIcons[ type ] || 'dashicons-edit';

					var $paletteItems = $( '#fswwp-cc-views-palette' );
					var $palette      = $( '.fswwp-cc-builder-palette' );
					$palette.find( '.description' ).hide();

					if ( ! $paletteItems.length ) {
						$palette.find( 'h3' ).after( '<div class="fswwp-cc-palette-items" id="fswwp-cc-views-palette" style="margin-bottom:16px;"></div>' );
						$paletteItems = $( '#fswwp-cc-views-palette' );
					}
					$paletteItems.show();

					// Check if already exists in palette.
					var exists = false;
					$paletteItems.find( '.fswwp-cc-palette-item' ).each( function() {
						if ( $( this ).data( 'type' ) === type && $( this ).data( 'field-id' ) === fieldId ) {
							exists = true;
							return false;
						}
					} );

					if ( ! exists ) {
						var $palItem = $( '<div class="fswwp-cc-palette-item" data-type="' + type + '" data-field-id="' + fieldId + '">' +
							'<span class="dashicons ' + compIcon + '"></span>' +
							label +
							'</div>' );
						$paletteItems.append( $palItem );
						initPaletteDraggable();
					}

					$self.remove(); // Remove stranded element from body.
				}

				$self.removeClass( 'fswwp-cc-comp--dragging ui-state-hover ui-state-active' );
				// Clean up jQuery UI leftover inline offsets (keep position for draggable).
				$self.css( { left: '', top: '', zIndex: '' } );
				$rows.find( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );
				$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active' );
				$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$( '.fswwp-cc-view-comp--extra' ).remove();
				balanceColumns( 0 );
			}
		} );
	}

	// ---- Palette items draggable -------------------------------------------
	function initPaletteDraggable() {
		// Only destroy if already initialized.
		$( '.fswwp-cc-palette-item' ).each( function() {
			if ( $( this ).hasClass( 'ui-draggable' ) ) {
				$( this ).draggable( 'destroy' );
			}
		} );
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
				$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active' );
				$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$( '.fswwp-cc-view-comp--extra' ).remove();
				balanceColumns( 0 );
			}
		} );
	}
	initPaletteDraggable();

	// ---- Columns as droppable targets --------------------------------------
	var dropTrackNS = 'mousemove.fswwpDropTrack';
	var lastHit     = null;

	function initColumnDroppable() {
		// Only destroy if already initialized.
		$( '.fswwp-cc-col-inner' ).each( function() {
			if ( $( this ).hasClass( 'ui-droppable' ) ) {
				$( this ).droppable( 'destroy' );
			}
		} );
		$( '.fswwp-cc-col-inner' ).droppable( {
			accept: '.fswwp-cc-view-comp, .fswwp-cc-palette-item',
			tolerance: 'pointer',
			activeClass: false,   // Don't let jQuery UI add ui-droppable-active to all columns.
			hoverClass: false,    // We use fswwp-cc-col--drop-active instead.

			over: function( e, ui ) {
				var $col = $( this );

				// Clear ALL columns and rows before highlighting this one.
				$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active' );
				$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );

				$col.addClass( 'fswwp-cc-col--drop-active' );

				$( document ).off( dropTrackNS ).on( dropTrackNS, function( ev ) {
					// If the mouse isn't over any column, clear everything.
					var el = document.elementFromPoint( ev.clientX, ev.clientY );
					if ( ! $( el ).closest( '.fswwp-cc-col-inner' ).length ) {
						$( '.fswwp-cc-col-inner' ).removeClass( 'fswwp-cc-col--drop-active' );
						$( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
						$( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );
						lastHit = null;
						return;
					}

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
				$col.removeClass( 'fswwp-cc-col--drop-active' );
				$col.find( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );
				$col.closest( '.fswwp-cc-view-row' ).removeClass( 'fswwp-cc-row--dragging' );
				// Don't unbind the mousemove tracker here — when out fires
				// after the next column's over, it would kill the new tracker.
			},

			drop: function( e, ui ) {
				var $col = $( this );
				$( document ).off( dropTrackNS );

				$col.removeClass( 'fswwp-cc-col--drop-active' );
				$col.find( '.fswwp-cc-view-comp--empty' ).removeClass( 'fswwp-cc-empty--hover' );

				// Recalculate hit using THIS column and the drop event's actual
				// mouse position — avoids stale lastHit from a different column.
				var hit = childAtY( $col, e.clientY );
				lastHit = null;
				var $comp;

				if ( ui.draggable.hasClass( 'fswwp-cc-palette-item' ) ) {
					var type     = ui.draggable.data( 'type' );
					var fieldId  = ui.draggable.data( 'field-id' ) || '';
					var icons    = { image:'dashicons-format-image', name:'dashicons-admin-users', content:'dashicons-editor-paragraph', text:'dashicons-editor-textcolor', textarea:'dashicons-editor-paragraph', email:'dashicons-email', url:'dashicons-admin-links', date:'dashicons-calendar', select:'dashicons-menu-alt', rating:'dashicons-star-filled', category:'dashicons-category' };
					var labels   = { image:'Image', name:'Name', content:'Content', text:'Text', textarea:'Textarea', email:'Email', url:'URL', date:'Date', select:'Select', rating:'Rating', category:'Category' };
					var compIcon = icons[ type ] || 'dashicons-edit';
					var compLabel = labels[ type ] || type;
					$comp = $( '<div class="fswwp-cc-view-comp fswwp-cc-field-card" data-type="' + type + '" data-field-id="' + fieldId + '" data-styles="{}">' +
						'<div class="fswwp-cc-field-header">' +
						'<span class="dashicons dashicons-move fswwp-cc-field-handle"></span>' +
						'<span class="dashicons ' + compIcon + ' fswwp-cc-field-icon"></span>' +
						'<strong class="fswwp-cc-field-label-text">' + compLabel + '</strong>' +
						'<span class="fswwp-cc-field-type-badge">' + compLabel + '</span>' +
						'<button type="button" class="fswwp-cc-field-edit dashicons dashicons-edit" title="Edit styles"></button>' +
						'<button type="button" class="fswwp-cc-field-delete dashicons dashicons-trash" title="Remove"></button>' +
						'</div>' +
						'<div class="fswwp-cc-field-body" style="display:none;"></div>' +
						'</div>' );
					makeCompDraggable( $comp );

					// Remove the palette item — consumed.
					ui.draggable.remove();

					// Hide palette items if now empty, show description again.
					var $palItems = $( '#fswwp-cc-views-palette' );
					if ( $palItems.length && ! $palItems.find( '.fswwp-cc-palette-item' ).length ) {
						$palItems.hide();
						$( '.fswwp-cc-builder-palette .description' ).show();
					}
				} else {
					$comp = ui.draggable.detach();
				}

				if ( hit ) {
					var $target = $( hit.el );

					if ( $target.hasClass( 'fswwp-cc-view-comp--empty' ) ) {
						// Empty/extra slots are placeholders — always insert before them.
						$comp.insertBefore( hit.el );
					} else if ( hit.position === 'top' ) {
						$comp.insertBefore( hit.el );
					} else {
						$comp.insertAfter( hit.el );
					}
				} else {
					$col.append( $comp );
				}

				// Clean up jQuery UI leftover inline styles from drag (keep position for draggable).
				$comp.css( {
					left:   '',
					top:    '',
					zIndex: ''
				} );
				$comp.removeClass( 'ui-state-hover ui-state-active ui-draggable-dragging' );

				$( '.fswwp-cc-view-comp--extra' ).remove();
				balanceColumns( 0 );
			}
		} );
	}
	initColumnDroppable();

	// Make existing real components draggable.
	$( '.fswwp-cc-view-comp' ).each( function() {
		makeCompDraggable( $( this ) );
	} );

	// ---- Component deletion → return to palette ---------------------------
	$rows.on( 'click', '.fswwp-cc-field-delete', function( e ) {
		e.stopPropagation();
		var $comp    = $( this ).closest( '.fswwp-cc-view-comp' );
		var type     = $comp.data( 'type' );
		var fieldId  = $comp.data( 'field-id' ) || '';
		var label    = $comp.find( '.fswwp-cc-field-label-text' ).text().trim();
		var typeIcons = { image:'dashicons-format-image', name:'dashicons-admin-users', content:'dashicons-editor-paragraph', text:'dashicons-editor-textcolor', textarea:'dashicons-editor-paragraph', email:'dashicons-email', url:'dashicons-admin-links', date:'dashicons-calendar', select:'dashicons-menu-alt', rating:'dashicons-star-filled', category:'dashicons-category' };
		var compIcon  = typeIcons[ type ] || 'dashicons-edit';

		// Check if this component is already in the palette.
		var $paletteItems = $( '#fswwp-cc-views-palette' );
		var exists = false;
		$paletteItems.find( '.fswwp-cc-palette-item' ).each( function() {
			if ( $( this ).data( 'type' ) === type && $( this ).data( 'field-id' ) === fieldId ) {
				exists = true;
				return false;
			}
		} );

		if ( ! exists ) {
			// Ensure the palette items container is visible.
			var $palette = $( '.fswwp-cc-builder-palette' );
			$palette.find( '.description' ).hide();
			if ( ! $paletteItems.length ) {
				// Palette items div doesn't exist (connected form mode) — create it.
				$palette.find( 'h3' ).after( '<div class="fswwp-cc-palette-items" id="fswwp-cc-views-palette" style="margin-bottom:16px;"></div>' );
				$paletteItems = $( '#fswwp-cc-views-palette' );
			}
			$paletteItems.show();

			// Add component back to palette.
			var $palItem = $( '<div class="fswwp-cc-palette-item" data-type="' + type + '" data-field-id="' + fieldId + '">' +
				'<span class="dashicons ' + compIcon + '"></span>' +
				label +
				'</div>' );
			$paletteItems.append( $palItem );
			initPaletteDraggable();
		}

		$comp.remove();
		balanceColumns( 0 );
	} );

	// ---- Row management ----------------------------------------------------
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
			'<div class="fswwp-cc-view-col" data-col="0" style="flex:50;"><div class="fswwp-cc-col-inner"></div></div>' +
			'<div class="fswwp-cc-view-col" data-col="1" style="flex:50;"><div class="fswwp-cc-col-inner"></div></div>' +
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
					'<div class="fswwp-cc-col-inner"></div></div>' );
			}
		} else {
			for ( var j = cols; j < count; j++ ) {
				var $col = $cols.find( '.fswwp-cc-view-col[data-col="' + j + '"]' );
				$col.find( '.fswwp-cc-view-comp' ).appendTo( $cols.find( '.fswwp-cc-view-col[data-col="0"] .fswwp-cc-col-inner' ) );
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

	// ---- Save --------------------------------------------------------------
	$( '#fswwp-cc-save-view' ).on( 'click', function() {
		var $btn = $( this );
		var name = $( '#fswwp-cc-view-name' ).val().trim();
		var data = [];

		$rows.find( '.fswwp-cc-view-row' ).each( function() {
			var row = { columns: [] };
			$( this ).find( '.fswwp-cc-view-col' ).each( function() {
				var col = { components: [] };
				$( this ).find( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).each( function() {
					col.components.push( {
						type: $( this ).data( 'type' ),
						field_id: $( this ).data( 'field-id' ) || ''
					} );
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
			form_id:   $formSelect.val(),
			rows:      JSON.stringify( data )
		}, function( r ) {
			if ( r.success ) {
				$( '#fswwp-cc-view-id' ).val( r.data.view_id );
				showMsg( r.data.message, 'success' );
				setTimeout( function() {
					window.location.search = '?page=fswwp-comments-core-views&post_type=fswwp_review&view_id=' + r.data.view_id;
				}, 800 );
			} else {
				showMsg( r.data.message, 'error' );
			}
			$btn.prop( 'disabled', false ).text( 'Save View' );
		}, 'json' ).fail( function() {
			showMsg( 'Network error.', 'error' );
			$btn.prop( 'disabled', false ).text( 'Save View' );
		} );
	} );

	// ---- Toolbar actions ---------------------------------------------------
	$( '#fswwp-cc-new-view' ).on( 'click', function() {
		window.location.search = '?page=fswwp-comments-core-views&post_type=fswwp_review&new=1';
	} );

	// Delete view (on list page).
	$( '.fswwp-cc-delete-view' ).on( 'click', function() {
		if ( ! confirm( 'Delete this view?' ) ) { return; }
		$.post( window.ajaxurl, { action:'fswwp_cc_delete_view', nonce:nonce, view_id:$( this ).data( 'id' ) }, function() {
			window.location.reload();
		} );
	} );

	// ---- Copy shortcode ----------------------------------------------------
	// List page copy buttons.
	$( '.fswwp-cc-copy-btn' ).on( 'click', function() {
		var code = $( this ).data( 'code' );
		copyToClipboard( code, $( this ) );
	} );

	// Click-to-copy on shortcode text.
	$( '.fswwp-cc-shortcode-copy' ).on( 'click', function() {
		var code = $( this ).text();
		copyToClipboard( code, $( this ) );
	} );

	function copyToClipboard( text, $btn ) {
		navigator.clipboard.writeText( text ).then( function() {
			if ( $btn.length && $btn.find( '.dashicons' ).length ) {
				$btn.find( '.dashicons' ).removeClass( 'dashicons-clipboard' ).addClass( 'dashicons-yes' );
				setTimeout( function() {
					$btn.find( '.dashicons' ).removeClass( 'dashicons-yes' ).addClass( 'dashicons-clipboard' );
				}, 1500 );
			}
		} ).catch( function() {
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
		} );
	}

	// ---- Initial balance ---------------------------------------------------
	// Normalize column widths on load so they match the active column button.
	$rows.find( '.fswwp-cc-view-row' ).each( function() {
		var $row     = $( this );
		var $active  = $row.find( '.fswwp-cc-col-btn.active' );
		var cols     = $active.length ? parseInt( $active.data( 'cols' ), 10 ) : $row.find( '.fswwp-cc-view-col' ).length;
		var w        = Math.floor( 100 / cols );
		$row.find( '.fswwp-cc-view-col' ).css( 'flex', w );
	} );

	balanceColumns( 0 );

	// ====================================================================
	//  Component Style Modal
	// ====================================================================

	var $compModal    = $( '#fswwp-cc-comp-modal' );
	var $compBody     = $( '#fswwp-cc-comp-modal-body' );
	var $compApply    = $( '#fswwp-cc-comp-apply' );
	var $compCancel   = $( '#fswwp-cc-comp-cancel' );
	var currentComp   = null;  // The component being edited.

	// Style fields per component type.
	var textFields = [
		{ key: 'text_align',  label: 'Text Align',  type: 'select',  opts: ['', 'left', 'center', 'right', 'justify'] },
		{ key: 'font_weight', label: 'Font Weight', type: 'select',  opts: ['', 'normal', 'bold', '300', '600', '700'] },
		{ key: 'font_style',  label: 'Font Style',  type: 'select',  opts: ['', 'normal', 'italic'] },
		{ key: 'font_size',   label: 'Font Size',   type: 'text',    placeholder: 'e.g. 14px' },
		{ key: 'color',       label: 'Text Color',  type: 'color' },
		{ key: 'background',  label: 'Background',  type: 'color_text', placeholder: 'e.g. #f0f0f1' },
		{ key: 'margin',      label: 'Margin',      type: 'text',    placeholder: 'e.g. 0 0 8px 0' },
		{ key: 'padding',     label: 'Padding',     type: 'text',    placeholder: 'e.g. 4px 8px' }
	];

	var imageFields = [
		{ key: 'width',         label: 'Width',        type: 'text', placeholder: 'e.g. 150px' },
		{ key: 'height',        label: 'Height',       type: 'text', placeholder: 'e.g. 150px' },
		{ key: 'border_radius', label: 'Border Radius',type: 'text', placeholder: 'e.g. 8px' },
		{ key: 'object_fit',    label: 'Object Fit',   type: 'select', opts: ['', 'cover', 'contain', 'fill', 'none'] },
		{ key: 'box_shadow',    label: 'Box Shadow',   type: 'text', placeholder: 'e.g. 0 2px 8px rgba(0,0,0,0.1)' }
	];

	var ratingFields = [
		{ key: 'star_size',  label: 'Star Size',  type: 'text',  placeholder: 'e.g. 20px' },
		{ key: 'star_color', label: 'Star Color', type: 'color' },
		{ key: 'gap',        label: 'Gap',        type: 'text',  placeholder: 'e.g. 2px' },
		{ key: 'text_align', label: 'Align',      type: 'select', opts: ['', 'left', 'center', 'right'] }
	];

	var badgeFields = [
		{ key: 'text_align', label: 'Align',      type: 'select', opts: ['', 'left', 'center', 'right'] },
		{ key: 'font_size',  label: 'Font Size',  type: 'text',   placeholder: 'e.g. 13px' },
		{ key: 'font_style', label: 'Font Style', type: 'select', opts: ['', 'normal', 'italic'] },
		{ key: 'color',      label: 'Text Color', type: 'color' }
	];

	function getFieldsForType( type ) {
		switch ( type ) {
			case 'image':    return imageFields;
			case 'rating':   return ratingFields;
			case 'date':
			case 'category': return badgeFields;
			default:         return textFields;
		}
	}

	function openCompModal( $comp ) {
		currentComp = $comp;
		var type    = $comp.data( 'type' );
		var styles  = $comp.data( 'styles' ) || {};
		var fields  = getFieldsForType( type );

		var html = '<div class="fswwp-cc-comp-modal-info">' +
			'<span class="fswwp-cc-comp-type-label">' + ( type.charAt(0).toUpperCase() + type.slice(1) ) + '</span>' +
			'</div>';

		$.each( fields, function( i, f ) {
			var val = styles[ f.key ] || '';
			html += '<div class="fswwp-cc-field-row">' +
				'<label>' + f.label + '</label>';

			if ( f.type === 'select' ) {
				html += '<select class="fswwp-cc-comp-style" data-key="' + f.key + '">';
				$.each( f.opts, function( j, o ) {
					html += '<option value="' + o + '"' + ( val === o ? ' selected' : '' ) + '>' + ( o || 'Default' ) + '</option>';
				} );
				html += '</select>';
			} else if ( f.type === 'color' ) {
				html += '<input type="color" class="fswwp-cc-comp-style" data-key="' + f.key + '" value="' + ( val || '#000000' ) + '">';
			} else if ( f.type === 'color_text' ) {
				html += '<div style="display:flex;gap:6px;align-items:center;">' +
					'<input type="color" class="fswwp-cc-comp-style-color" data-key="' + f.key + '" value="' + ( val && val !== 'transparent' ? val : '#ffffff' ) + '" style="width:36px;height:30px;padding:0;border:none;cursor:pointer;">' +
					'<input type="text" class="fswwp-cc-comp-style widefat" data-key="' + f.key + '" value="' + val + '" placeholder="' + ( f.placeholder || '' ) + '">' +
					'</div>';
			} else {
				html += '<input type="text" class="fswwp-cc-comp-style widefat" data-key="' + f.key + '" value="' + val + '" placeholder="' + ( f.placeholder || '' ) + '">';
			}
			html += '</div>';
		} );

		$compBody.html( html );

		// Sync color_text inputs: text ↔ color picker.
		$compBody.find( '.fswwp-cc-comp-style-color' ).on( 'input', function() {
			var $txt = $( this ).closest( '.fswwp-cc-field-row' ).find( 'input[type="text"].fswwp-cc-comp-style' );
			$txt.val( this.value );
		} );

		$compModal.show();
	}

	function closeCompModal() {
		$compModal.hide();
		currentComp = null;
	}

	function applyCompStyles() {
		if ( ! currentComp ) { return; }
		var styles = {};
		$compBody.find( '.fswwp-cc-comp-style' ).each( function() {
			var key = $( this ).data( 'key' );
			var val = $( this ).val().trim();
			if ( val ) { styles[ key ] = val; }
		} );
		// Also pick up color_text text inputs.
		$compBody.find( '.fswwp-cc-comp-style-color' ).each( function() {
			var key  = $( this ).data( 'key' );
			var $txt = $( this ).closest( '.fswwp-cc-field-row' ).find( 'input[type="text"].fswwp-cc-comp-style' );
			var val  = $txt.val().trim();
			if ( val ) { styles[ key ] = val; }
		} );

		currentComp.data( 'styles', styles );
		currentComp.attr( 'data-styles', JSON.stringify( styles ) );

		// Update preview on canvas.
		applyPreviewStyles( currentComp, styles );

		closeCompModal();
	}

	function applyPreviewStyles( $comp, styles ) {
		var css = {};
		$.each( styles, function( key, val ) {
			var prop = key.replace( /_/g, '-' );
			css[ prop ] = val;
		} );

		// For rating, apply star-size to font-size and star-color to color.
		if ( $comp.data( 'type' ) === 'rating' && styles.star_size ) {
			css[ 'font-size' ] = styles.star_size;
		}
		if ( $comp.data( 'type' ) === 'rating' && styles.star_color ) {
			css[ 'color' ] = styles.star_color;
		}

		$comp.css( css );
	}

	// ---- Modal events ------------------------------------------------------
	$compApply.on( 'click', applyCompStyles );
	$compCancel.on( 'click', closeCompModal );
	$compModal.find( '.fswwp-cc-modal-close' ).on( 'click', closeCompModal );
	$compModal.find( '.fswwp-cc-modal-backdrop' ).on( 'click', closeCompModal );

	// Open modal on edit button click OR header click.
	$rows.on( 'click', '.fswwp-cc-field-edit', function( e ) {
		e.stopPropagation();
		openCompModal( $( this ).closest( '.fswwp-cc-view-comp' ) );
	} );

	$rows.on( 'click', '.fswwp-cc-field-header', function( e ) {
		if ( $( e.target ).closest( '.fswwp-cc-field-handle, .fswwp-cc-field-edit, .fswwp-cc-field-delete' ).length ) {
			return;
		}
		openCompModal( $( this ).closest( '.fswwp-cc-view-comp' ) );
	} );

	// ---- Save with styles -----------------------------------------------
	$( '#fswwp-cc-save-view' ).off( 'click' ).on( 'click', function() {
		var $btn = $( this );
		var name = $( '#fswwp-cc-view-name' ).val().trim();
		var data = [];

		$rows.find( '.fswwp-cc-view-row' ).each( function() {
			var row = { columns: [] };
			$( this ).find( '.fswwp-cc-view-col' ).each( function() {
				var col = { components: [] };
				$( this ).find( '.fswwp-cc-view-comp:not(.fswwp-cc-view-comp--empty)' ).each( function() {
					var $c   = $( this );
					var comp = {
						type:     $c.data( 'type' ),
						field_id: $c.data( 'field-id' ) || ''
					};
					var styles = $c.data( 'styles' );
					if ( styles && ! $.isEmptyObject( styles ) ) {
						comp.styles = styles;
					}
					col.components.push( comp );
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
			form_id:   $formSelect.val(),
			rows:      JSON.stringify( data )
		}, function( r ) {
			if ( r.success ) {
				$( '#fswwp-cc-view-id' ).val( r.data.view_id );
				showMsg( r.data.message, 'success' );
				setTimeout( function() {
					window.location.search = '?page=fswwp-comments-core-views&post_type=fswwp_review&view_id=' + r.data.view_id;
				}, 800 );
			} else {
				showMsg( r.data.message, 'error' );
			}
			$btn.prop( 'disabled', false ).text( 'Save View' );
		}, 'json' ).fail( function() {
			showMsg( 'Network error.', 'error' );
			$btn.prop( 'disabled', false ).text( 'Save View' );
		} );
	} );

} )( jQuery );

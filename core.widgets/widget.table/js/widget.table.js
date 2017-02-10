//	#required {
//		core.widget
//		core.actions
//	}


//		-- core:jquery.scrollto


(function() {


	// https://jsfiddle.net/kzheuaau
	

/*	var a = reactState(0);

	a.set();
	a.value;
	
	a.depends(
		[c, d],
		function(c, d) {
			// this -> a
			return new_val;
		}
	);

	a.changed( function(new_val) {
		some_action();
	});
	
	a.become( new_val, function() {
		;
	});
	
*/

	

	$('.table-wrap').each( function() {
		var cont = $(this);
		var toolbar = $('> .toolbar',cont);
		// var table = $('> .table',cont);
		var tables = $('table.table',cont);
		var table_blocks = $('table tbody.speed-up',cont);

		if (table_blocks.length > 0) {
			tables.css('opacity',0.6);
			setTimeout( function() {
				table_blocks.filter(':first').removeClass('speed-up').show();
				setTimeout( function() {
					table_blocks.filter('.speed-up').removeClass('speed-up').show();
					tables.removeAttr('style');
				}, 200);
			}, 200);
		}

		function filterCheckbox() {
			var name = $(this).attr('name');
			return (name.substr(0,10) != 'check_all_') && (name.substr(0,11) != 'check_group');
		}



		var enabled_buttons = [];
		var disableUpdate = false;
		function updateButtonsState() {
			if (disableUpdate) {
				return;
			}
			if (enabled_buttons.length > 0) {
				$.each(enabled_buttons, function(index, btn) {
					var button = btn.button;
					var cond = btn.cond;
					var filter = btn.filter;
					var has_filter = (filter !== '');
					
					var button_enabled = false;
					if (cond === 'no-selected') {
						button_enabled = true;
					}
					var selected_count = 0;

					var active_table = tables.filter(':visible:first');
					
					$('> tbody > tr :checkbox:not(:hidden)',active_table).filter( /* function() {
						return (!$(this).parent().is('.check-all'));
					} */
						filterCheckbox
						
					).each( function() {
						var tr = $(this).closest('tr');
						var checked = $(this).is(':checked');
						
						var filtered = true;
						if (has_filter) {
							filtered = tr.is(filter);
						}
						
						if (cond === 'no-selected') {
						
							if ((checked === true) && filtered) {
								button_enabled = false;
								return false; // break;
							}
							
						} else if (cond === 'selected') {
						
							if (checked === true) {
								if (has_filter && !filtered) {
									button_enabled = false;
									return false; // break;
								}
								button_enabled = true;
								if (!has_filter) {
									return false; // break;
								}
							}
							
						} else if (cond === 'selected-single') {
						
							if (checked === true) {
								if (has_filter && !filtered) {
									button_enabled = false;
									return false; // break;
								}
								selected_count++;
								button_enabled = (selected_count === 1);
								if (!button_enabled) {
									return false; // break;
								}
							}
							
						} else if (cond === 'selected-multiple') {
						
							if (checked === true) {
								if (has_filter && !filtered) {
									button_enabled = false;
									return false; // break;
								}
								selected_count++;
								button_enabled = (selected_count > 1);
								if (button_enabled && !has_filter) {
									return false; // break;
								}
							}
							
						}
						
					});

					var is_disabled = button.hasClass('disabled');
					if (button_enabled) {
						if (is_disabled) {
							button.removeClass('disabled');
						}
					} else {
						if (!is_disabled) {
							button.addClass('disabled');
						}
					}

				});
			}
		}

		// $('table.table .check-all :checkbox',cont).click( function() {
		tables.click( function(e) {
			var el = $(e.target);
			if (!el.is(':checkbox') || !$(el.parent()).is('.check-all')) {
				return;
			}
			var table_cont = el.closest('table.table');
			var check_id_field = el.attr('name').replace(/^check_all_/,'');
			var checked = el.is(':checked');
			disableUpdate = true;
			$('.field-checkbox-'+check_id_field+':not(.check-all):not(.check-group):not(.check-group-item) :checkbox',table_cont).prop('checked',checked);
			disableUpdate = false;
			updateButtonsState();
		});

		$('table.table .check-group :checkbox',cont).click( function() {
			var table_cont = $(this).closest('table.table');
			var check_group_field = $(this).attr('name').replace(/^check_group\[/,'').replace(/\]$/,'');
			var checked = $(this).is(':checked');
			disableUpdate = true;
			// $('.field-checkbox-'+check_id_field+':not(.check-all):not(.check-group) :checkbox',table_cont).prop('checked',checked);
			$(':checkbox[data-check-group="'+check_group_field+'"]',table_cont).prop('checked',checked);
			disableUpdate = false;
			updateButtonsState();
		});


		$('table.table :checkbox',cont).filter( filterCheckbox ).change(updateButtonsState);
		
		function isRowVisible(row, filter_name, row_hide) {
			var filtered = row.attr('data-filtered') || '';
			if (row_hide) {
				if (filtered.search(' '+filter_name) === -1) {
					filtered += ' '+filter_name;
				}
			} else {
				filtered = filtered.replace(' '+filter_name,'');
			}
			return (filtered === '');
		}
		
		function setRowFiltered(row, filter_name, row_hide, row_group_has_filter) {
			var filtered = '';
			if (arguments.length < 4) {
				row_group_has_filter = true;
			}
			if (row_group_has_filter) {
				filtered = row.attr('data-filtered') || '';
				var _filtered = filtered;
				if (row_hide) {
					if (filtered.search(' '+filter_name) === -1) {
						filtered += ' '+filter_name;
						row.hide(); // css('opacity',0.5); // .hide();
					}
				} else {
					filtered = filtered.replace(' '+filter_name,'');
					if (filtered !== _filtered) {
						if (filtered === '') {
							row.show(); // css('opacity',1); // .show();
						}
					}
				}
				if (filtered !== _filtered) {
					row.attr('data-filtered',filtered);
				}
			} else {
				if (row_hide) {
					filtered = ' '+filter_name;
					row.hide();
				} else {
					filtered = '';
					row.show();
				}
			}
			return filtered;
		}

		function rowHasFilters(row, row_filters) {
			var filters = row_filters.trim().split(' ');
			var row_has_filter = false;
			$.each(filters, function(index, filter) {
				var row_val = row.attr('data-filter_'+filter);
				if (typeof row_val !== "undefined") {
					row_has_filter = true;
					return false;
				}
			});
			return row_has_filter;
		}


		var checkboxes = $('> .toolbar-checkbox',toolbar);

		/* function updateChecked(item, checked) {
			if (checked) {
				item.addClass('checked');
			} else {
				item.removeClass('checked');
			}
		} */

		checkboxes.each( function() {
			var checkbox = reactWidget.Checkbox($(this));
			/* var item = $(this);
			var ch = $(':checkbox',item);
			var checked = ch.is(':checked');
			ch.on('change', function() {
				var checked_ = ch.is(':checked');
				if (checked_ !== checked) {
					checked = checked_;
					updateChecked(item,checked);
				}
			});
			updateChecked(item,checked); */
		});
		

		var filters = $('> .filter',toolbar);

		var static_tables = tables;
		if (filters.length > 0) {
			static_tables = tables.filter('.no-filter');
		}

		static_tables.each( function() {
			var table = $(this);
			var empty_msg = $('> tbody > tr.empty-msg',table);
			if ($('> tbody > tr:not(.empty-msg)',table).filter( function() {
				return ($('th',this).length == 0);
			}).length == 0) {
				empty_msg.show();
			}
		});
		
		var filtering_tables = tables.filter(':not(.no-filter)');

		function defaultRowFilter(val, row_val) {
			if ((val === null) || (val === '')) {
				return true;
			}
			var expr = new RegExp("(^|\\|)" + val + "(\\||$)");
			return expr.test(row_val);
		}

		cont.on('filter_rows.table', function(e, filter_name, val, filter_func) {

			// console.log('filter_rows.'+filter_name+': '+val);

			if (typeof filter_func === 'undefined') {
				filter_func = defaultRowFilter;
			}
		
			var sliders = $('.table-slider',cont);
			var has_slider = false;
			
			filtering_tables.each( function() {
				var table = $(this);
				
				if (!has_slider) {
					var slider = table.closest('.table-slider');
					if (sliders.index(slider) !== -1) {
						has_slider = true;
					}
				}
				
				var last_row_val = ''; //null;

				var row_group_counter = false;
				var row_group_visible_rows_count = 0;
				var row_group = [];
				var group_head_rows = false;

				var row_group_filter = '';
				var row_group_has_filter = false;
				
				var all_filtered = true;
				
				$('> tbody > tr:not(.no-filter):not(.empty-msg)',table).each( function() {
					var row = $(this);

					if (row.is('.reset-filters')) {
						last_row_val = '';
					}
					var row_val = row.attr('data-filter_'+filter_name);

					// console.log('data-filter_'+filter_name);
					
					var row_has_filter = (typeof row_val !== "undefined");
					if (!row_has_filter) {
						row_val = last_row_val;
					}
					last_row_val = row_val;

					var row_filter_hide = false;
					if ( (val === '') || (row_val === '') ) {
						row_filter_hide = false
					} else {
						// var expr = new RegExp("(^|\\|)" + val + "(\\||$)");
						// row_filter_hide = !expr.test(row_val);
						row_filter_hide = !filter_func(val, row_val);
					}


					var skip_filter = false;
					
					var group_filter = row.attr('data-group_filter');
					if (group_filter) {
						group_filter = ' '+group_filter;

						// seems it works now!
						if ( (group_filter.search(' '+filter_name) !== -1) || (row_has_filter && isRowVisible(row,filter_name,row_filter_hide)) ) { // << !!!

							if (row_group_counter && (row_group.length > 0) ) {
								var row_hide = (row_group_visible_rows_count == 0);
								$.each( row_group, function(index, item) {
									row_filtered = setRowFiltered(item,filter_name, row_hide, row_group_has_filter);
									if (all_filtered && (row_filtered === '')) {
										all_filtered = false;
									}
								});
							}
							
							row_group_has_filter = false;
							if (row_has_filter) {
								row_group_has_filter = true;
							}

							row_group_counter = true;
							row_group_visible_rows_count = 0;
							
							row_group = [];
							skip_filter = true;
							last_row_val = '';//null; 

							row_group_filter = group_filter;
							row_group.push(row);
							group_head_rows = true;
						}
					} else {
						if (row_group_counter) {
							if (rowHasFilters(row,row_group_filter)) {
								group_head_rows = false;
							} else if (group_head_rows) {
								skip_filter = true;
								row_group.push(row);
							}
						}
					}

					var row_visible = true;
					if (!skip_filter) {
						var filtered = setRowFiltered(row,filter_name,row_filter_hide);
						if (filtered === '') {
							row_visible = true;
						} else {
							row_visible = false;
						}

						if (row_group_counter && row_visible && rowHasFilters(row,row_group_filter)) {
							row_group_visible_rows_count++;
						}
					}

				});

				if (row_group_counter && (row_group.length > 0) ) {
					var row_hide = (row_group_visible_rows_count == 0);
					$.each( row_group, function(index, item) {
						row_filtered = setRowFiltered(item,filter_name, row_hide, row_group_has_filter);
						if (all_filtered && (row_filtered === '')) {
							all_filtered = false;
						}
					});
				}

				var empty_msg = $('> tbody > tr.empty-msg',table);
				if (all_filtered) {
					empty_msg.show();
				} else {
					empty_msg.hide();
				}
				
			});

			if (has_slider) {
				sliders.trigger('updateHeight.slider');
			}
			
		});



		/* cont.trigger('filter_rows.table', ['nosology', 0, function(val, row_val) {
			;
		}]); */

		var filter_els = [];

		filters.each( function() {
			var filter = reactWidget.Filter($(this));
			filter_els.push(filter);
			var filter_name = $('input[type="hidden"]:first',this).attr('name').replace(/^filter_/,'');

			filter.val.changed( function(val) {
				// console.log('changed');
				// console.log(filter_name);
				console.log('filter.'+filter_name+': '+val);
				cont.trigger('filter_rows.table', [filter_name, val]);
			}); // , '', false);
			
			if (filter.val.value !== null) {
				// console.log('refresh');
				// console.log(filter_name);
				filter.val.refresh(true);
			}
			// return false;
		});

		$('> .button-clear_filters a',toolbar).click( function(e) {
			e.preventDefault();
			$(this).blur();
			$.each(filter_els, function(i, filter) {
				filter.selectItem(null);
			});
			return false;
		});
	
		$('> .toolbar-button',toolbar).each( function() {
			var btn = $(this);
			var button = $('.button',btn);
			if (btn.is('[data-enabled]')) {
				var enabled = btn.attr('data-enabled');
				
				var arr = enabled.split('[');
				var enabled_cond = arr[0];
				var enabled_filter = '';
				if (arr.length > 1) {
					enabled_filter = arr[1].replace(/\]$/,'');
				}

				if (enabled_cond !== 'no-selected') {
					button.addClass('disabled');
				}
				enabled_buttons.push({
					button: button,
					cond: enabled_cond,
					filter: enabled_filter
				});
				
				// console.log(enabled_cond);
				// console.log(enabled_filter);
				
				// var enabled_filter = '';

				// enabled_cond >> 'no-selected', 'selected', 'selected-single', 'selected-multiple'
			}
			if (button.is('[data-button_action]')) {
				button.click( function(e) {
					e.preventDefault();

					var btn = $(this);
					var action = btn.data('button_action');

					var res = btn.trigger('click.button_action');
					if ( (typeof res !== 'undefined') && (res === false) ) {
						return false;
					}

					/* if (btn.is('[data-action_params]')) {
						var action_params = btn.attr('data-action_params');
						if (action_params) {
							var arr;
							$.each( action_params.split('&'), function(index, item) {
								arr = item.split('=');
								fields[arr[0]] = arr[1];
							});
						}
					} */

					var action_allowed = $.wait().bind({result: true});
					btn.trigger('action',[action_allowed]);
					
					action_allowed.then(function(status) {
						if (status.result === true) {
							var fields = {};

							$(':input',cont).each( function() {
								var inp = $(this);
								var name = inp.attr('name');
								if (inp.is(':checkbox')) {
									if ( inp.is(filterCheckbox) ) { // (name.substr(0,10) != 'check_all_') && (name.substr(0,11) != 'check_group') ) {
										fields[name] = inp.is(':not(:hidden):checked') ? inp.val() : '';
									}
								} else {
									fields[name] = inp.val();
								}
							});

							actionForm({
								action: action,
								params: fields
							});
						}
					});
					
					return false;
				});
			}
		});
		
	});
	
//	var dropdown = $('.dropdown');
	
	
//	var button = $('.btn');
	
	
	// .waitfor();
	
	
	
	
/*	var Dropdown = function(el) {
		var opened_dropdown = null;
		
		var dropdown_open = function() {
			if (!this.opened) {
				var dropdown = this.el;
				if (opened_dropdown !== null) { // close opened dropdown
					// opened_dropdown.removeClass('open');
					opened_dropdown.close();
				}
				dropdown.addClass('open'); // dropdown open
				this.opened = true;
				opened_dropdown = this;
			}
		};

		var dropdown_close = function() {
			if (this.opened) {
				var dropdown = this.el;
				dropdown.removeClass('open'); // dropdown close
				this.opened = false;
				opened_dropdown = null;
			}
		};
		
		$(document).click( function(e) {
			if (opened_dropdown === null) return;
			
			var el = $(e.target);
			var d = el.closest('.dropdown');
			if (!d.hasClass('dropdown')) {
				opened_dropdown.close();
//				var b = el.closest('.button-dropdown');
//				if (!b.hasClass('button-dropdown')) {
					// opened_dropdown.removeClass('open'); // close opened dropdown
//					opened_dropdown.close();
//				}
			}
		});
		
//		window.Dropdown = function(el) {
			// var self = {};
		var dropdown = {
			el: el,
			open: dropdown_open,
			close: dropdown_close,
			opened: false
		}
		return dropdown;
//		}
	};
	
	$('.table-wrap .toolbar').each( function() {
		var cont = $(this);
		$('> .filter',cont).each( function() {
			var filter = $(this);
			var inp = $(':text',filter);
			var val_inp = $(':hidden',filter);

			var dropdown = Dropdown($('.dropdown',filter));
			
			var update_delay = 200;
			var current_text = inp.val();

			var update_timer = null;

			$('.button-dropdown',filter).click( function(e) {
				e.preventDefault();
				if (!dropdown.opened) {
					// if (opened_dropdown !== null) { // close opened dropdown
					//	opened_dropdown.removeClass('open');
					// }
					// dropdown.addClass('open'); // dropdown open
					// opened_dropdown = dropdown;
					$('> li',dropdown.el).removeClass('hide');
					if (update_timer !== null) {
						clearTimeout(update_timer);
					}
					dropdown.open();
				} else {
					// dropdown.removeClass('open'); // dropdown close
					// opened_dropdown = null;
					dropdown.close();
				}
				e.stopImmediatePropagation();
				return false;
			});

			inp.on('keydown', function(e) {
				switch (e.which) {
					case 40: // Down
						// if (!dropdown.hasClass('open')) {
						if (!dropdown.opened) {
							// open_dropdown(dropdown);
							dropdown.open();
							// dropdown.addClass('open');
							// opened_dropdown = dropdown;
							return;
						}
						break;
					case 38: // Up
						break;
					case 39: // Right
						break;
					case 27: // Esc
						break;
					case 9: // Tab
						break;
					case 13: // Return
						break;
					default:
						return;
				}
				
				// e.stopImmediatePropagation();
				// e.preventDefault();
			});
			
			
			function escapeRegexp(str) {
				return str.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
			};
			
			var updateFilter = function() {
				if (current_text !== '') {
					var filtered = $();
					var match_text = current_text.toLowerCase();
					var list = $('> li',dropdown.el);
					
					filtered = list.filter( function() {
						// return ( $(this).text().search(new RegExp('\(\^\|\\s\)'+escapeRegexp(match_text),'i')) !== -1 );
						return ( $(this).text().search(new RegExp('\(\^\|\[\^\\wа-я\]\)'+escapeRegexp(match_text),'i')) !== -1 );
						// return ( new RegExp('\\b'+match_text,'i').test($(this).text()) );
						// return ($(this).text().toLowerCase().indexOf(match_text) !== -1);
					});
					if (filtered.length > 0) {
						list.addClass('hide');
						filtered.removeClass('hide');
						autocomplete_opened = true;
						dropdown.open();
						return;
					}
				}
				dropdown.close();
			};
			
			inp.on('keyup change input', function(e) {
				if (e.which == 38 || e.which == 40) return;
				
				if ( (current_text !== inp.val()) || !dropdown.opened ) {
					current_text = inp.val();
					if (update_timer !== null) {
						clearTimeout(update_timer);
					}
					update_timer = setTimeout(updateFilter, update_delay);
				}
			});
			
			dropdown.el.click( function(e) {
				var el = $(e.target);
				var text = '';
				if (el.is('[data-text]')) {
					text = el.attr('data-text');
				} else {
					text = el.text();
				}
				inp.val(text);
				val_inp.val(el.attr('data-val'));
				$('> li',dropdown.el).removeClass('selected');
				if (!el.hasClass('default')) {
					el.addClass('selected');
				}
				// trigger change
				dropdown.close();
				// dropdown.removeClass('open'); // dropdown close
				// opened_dropdown = null;
			});
		});
	}); */


})();

(function() {
	// Array forEach
	Array.prototype.forEach = function(callback) {
		var obj = this;
		var length = obj.length;
		var i = -1;
		
		if (typeof callback !== "function") {
			throw new TypeError('Array.prototype.forEach: callback must be a function');
		}
		
		while (++i < length) {
			if (i in obj) {
				if ( callback( obj[i], i, obj ) === false ) {
					break;
				}
			}
		}
		// return obj;
	};

	// Array.isArray
	if (!Array.isArray) {
		Array.isArray = function(arg) {
			return Object.prototype.toString.call(arg) === '[object Array]';
		};
	}

	// Number.isInteger
	if (!Number.isInteger) {
		Number.isInteger = function(val) {
			return ( (typeof val === "number") && isFinite(val) && (Math.floor(val) === val) );
		};
	}

})();


// $.periodic
(function($) {

	$.periodic = function(interval, callback) {
		var timer = null;
		
		var periodic = {
//			paused: false,
			stopped: false,
			
//			pause: function() {
//				timerStop();
//				stopped = true;
//				paused = true;
//			},
			stop: function() {
				timerStop();
				// this.paused = false;
				this.stopped = true;
			},
			reset: function() {
				timerStop();
				timerStart();
				// this.paused = false;
				this.stopped = false;
			},
			resume: function() {
				if (this.stopped) {
					// if (this.paused) {
					//	;
					// } else {
						timerStart();
						this.stopped = false;
					// }
				}
				// this.paused = false;
			},
			callback: callback
		};
		
		function callbackFunc() {
			periodic.callback();
		}
		
		function timerStop() {
			if (timer === null) {
				return;
			}
			clearInterval(timer);
			timer = null;
		}
		
		function timerStart() {
			timer = setInterval(callbackFunc, interval);
		}
		timerStart();
		
		return periodic;
	}
	
})(jQuery);


// $.refine
(function($) {

	$.refine = function(obj, callback) {
		if (typeof callback !== 'function') {
			throw new TypeError('$.refine: callback must be a function');
			return null;
		}
		return callback.call(obj,obj);
	}

})(jQuery);


// reactEvent
(function($) {
	
	var sequences = []; // by id
	var sequences_by_source_id = []; // grouped by source
	var current_sequence_id = 0;
	var next_source = 0;
	
	function sequenceBindSources(seq, source_ids) {
		var source_id;
		var s_index = -1;
		var length = source_ids.length;
		while (++s_index < length) {
			source_id = source_ids[s_index];
			if (typeof sequences_by_source_id[source_id] === "undefined") {
				sequences_by_source_id[source_id] = [];
			}
			sequences_by_source_id[source_id].push(seq);
		}
	}
	
	function wrapSequence(sequence_id) {
		return {
			sequence_id: sequence_id,
			filter: sequenceFilter,
			merge: sequenceMerge,
			callback: sequenceCallback,
			log: sequenceLog,
			debounce: sequenceDebounce,
			dump: sequenceDump
		};
	}

	function sequenceDump() {
		var seq_ = sequences[this.sequence_id];

		var filters = [];
		seq_.filters.forEach( function(el, index) {
			if (typeof el !== "undefined") {
				filters[index] = el.slice(0);
			}
		});
		
		var seq = {
			sequence_id: this.sequence_id,
			filters: filters,
			callbacks: seq_.callbacks.slice(0),
			source_ids: seq_.source_ids.slice(0)
		};
		console.log(seq);
	}
	
	function sequenceCallback(func) {
		sequences[this.sequence_id].callbacks.push(func);
		return this;
	}

	function sequenceLog() {
		sequences[this.sequence_id].callbacks.push( function(e) {
			console.log(e);
		});
		return this;
	}

	function sequenceDebounce(time) {
		next_source++;
		var seq = {
			filters: [],
			callbacks: [],
			source_ids: [next_source]
		};
		seq.filters[next_source] = [];
		var sequence_id = newSequence(seq);

		var source_id = seq.source_ids[0];

		var timer = null;

		this.callback( function(e) {
			var el = this;
			if (timer !== null) {
				clearTimeout(timer);
			}
			timer = setTimeout( function() {
				runSequence(source_id, el, e);
				timer = null;
			}, time);
		});
		
		return wrapSequence(sequence_id);
	}
	
	function sequenceFilter(func) {
		var parent_seq = sequences[this.sequence_id];
		
		// var filters = parent_seq.filters.slice(0);
		var filters = [];

		parent_seq.filters.forEach( function(el, index) {
			if (typeof el !== "undefined") {
				filters[index] = el.slice(0);
			}
		});
		
		var len = parent_seq.source_ids.length;
		var source_id;

		var i = -1;
		while (++i < len) {
			source_id = parent_seq.source_ids[i];
			if (typeof filters[source_id] === "undefined") {
				filters[source_id] = [];
			}
			filters[source_id].push(func);
		}

		var sequence_id = newSequence({
			filters: filters,
			callbacks: [],
			source_ids: parent_seq.source_ids
		});
		return wrapSequence(sequence_id);
	}
	
	function mergeSources(sources1, sources2) {
		var merged = sources1.slice(0);
		var s2_len = sources2.length;
		var source_id;

		var i = -1;
		while (++i < s2_len) {
			source_id = sources2[i];
			if (!(source_id in merged)) {
				merged.push(source_id);
			}
		}
		return merged;
	}
	
	function sequenceMerge(s) {
		var m_seq = sequences[s.sequence_id];
		var parent_seq = sequences[this.sequence_id];
		
		// var filters = parent_seq.filters.slice(0);

		var filters = [];

		parent_seq.filters.forEach( function(el, index) {
			if (typeof el !== "undefined") {
				filters[index] = el.slice(0);
			}
		});
		
		var len = m_seq.source_ids.length;
		var source_id;

		var i = -1;
		var j;
		var func;
		var m_filters;
		
		while (++i < len) {
			source_id = m_seq.source_ids[i];
			if (typeof filters[source_id] === "undefined") {
				filters[source_id] = [];
			}
			
			m_filters = m_seq.filters[source_id];
			var filters_len = m_filters.length;
			j = -1;
			while (++j < filters_len) {
				func = m_filters[j];
				if (!(func in filters[source_id])) {
					filters[source_id].push(func);
				}
			}
		}
		
		var sequence_id = newSequence({
			filters: filters,
			callbacks: [],
			source_ids: mergeSources(parent_seq.source_ids, m_seq.source_ids)
		});
		return wrapSequence(sequence_id);
	}
	
	function newSequence(seq) {
		sequenceBindSources(seq, seq.source_ids);
		
		current_sequence_id++;
		sequences[current_sequence_id] = seq;
		
		return current_sequence_id;
	}

	function runSequence(source_id, el, e) {
		// var el = elements[source_id];
		var cancelled = false;
		sequences_by_source_id[source_id].forEach( function(seq, id) {

			var callbacks_len = seq.callbacks.length;
			if (callbacks_len > 0) {
				var filters = seq.filters[source_id];
				var filters_len = filters.length;
				var i;
				var filtered = true;
				var res;
				
				if (filters_len > 0) {

					i = -1;
					while (++i < filters_len) {
						if (filters[i].call(el,e) === false) {
							filtered = false;
							break;
						}
					}
				}
				
				if (!filtered) {
					return true; // next iteration of forEach
				}

				i = -1;
				while (++i < callbacks_len) {
					if (seq.callbacks[i].call(el,e) === false) {
						cancelled = true;
					}
				}
				
			}
		});
		return cancelled;
	}
	
	$.fn.reactEvent = function(events, filter_func) {
		var el = this; // element or elements
		
		next_source++;
		var seq = {
			filters: [],
			callbacks: [],
			source_ids: [next_source]
		};
		seq.filters[next_source] = [];
		var sequence_id = newSequence(seq);

		var source_id = seq.source_ids[0];
		
		el.on(events, function(e) {
			var cancelled = runSequence(source_id, el, e);
			
			if (cancelled) {
				return false;
			}
		});

		sequence = wrapSequence(sequence_id);

		if (arguments.length > 1) {
			return sequence.filter(filter_func);
		}

		return sequence;
	};

})(jQuery);



// reactState
(function() {

	var last_state_id = 0;

	window.reactState = function(initValue) {

		var value = initValue;

		var changedFuncs = [];
		var funcKeys = [];
		// var dependsFuncs = [];

		var id = last_state_id;
		last_state_id++;

		var dependStates = [];
		var locked = false;

		var updateFunc = null;

		function updateChanged() {
			for (var i = 0; i < changedFuncs.length; i++) {
				changedFuncs[i].call(this,this.value);
			}
		}

		var state = {
			_id: id,
			value: initValue,
			set: function(val) {
				if (value === val) {
					return this;
				}
				if (locked) {
					console.log('Warning: cyclic dependencies occured. Make sure you have no cycles in dependency links');
					return this;
				}

				locked = true;
				
				value = val;
				this.value = val;

				updateChanged.call(this);

				locked = false;
				
				return this;
			},
			/* reset: function() {
				;
			}, */
			depends: function(states, fn) {
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.depends( states, fn ) 2nd parameter must be a function');
					return this;
				}
				var self = this;

				// remove old relations
				if (dependStates.length > 0) {
					for (var j = 0; j < dependStates.length; j++) {
						dependStates[j].removeChanged(id);
					}
				}
				
				dependStates = states;

				updateFunc = function() {
					var values = [];
					for (var i = 0; i < states.length; i++) {
						values.push(states[i].value);
					}
					self.set( fn.apply(self,values) );
				};
				updateFunc();

				for (var i = 0; i < states.length; i++) {
					states[i].changed(updateFunc,id);
					// dependsFuncs.push(fn);
				}
				return this;
			},
			removeChanged: function(key) {
				var funcs = [], keys = [];
				for (var i = 0; i < funcKeys.length; i++) {
					if (funcKeys[i] !== key) {
						funcs.push(changedFuncs[i]);
						keys.push(funcKeys[i]);
					}
				}
				if (changedFuncs.length !== funcs.length) {
					changedFuncs = funcs;
					funcKeys = keys;
				}
			},
			changed: function(fn, key, triggerChanged) {
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.changed(fn) 1st parameter must be a function');
					return this;
				}
				if (typeof key === 'undefined') {
					key = '';
				}
				if (typeof triggerChanged === 'undefined') {
					triggerChanged = false;
				}
				
				changedFuncs.push(fn);
				funcKeys.push(key);

				if (triggerChanged) {
					updateChanged.call(this);
				}
					
				return this;
			},
			refresh: function(triggerChanged) {
				// this.value = value;
				if (typeof updateFunc === 'function') {
					updateFunc();
				}
				if (triggerChanged) {
					updateChanged.call(this);
				}
				return this;
			},
			become: function(val, fn) {
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.become(fn) 2nd parameter must be a function');
					return this;
				}
				var self = this;
				changedFuncs.push( function(v) {
					if (v === val) {
						fn.call(self);
					}
				});
				return this;
			}
		};


		return state;
	};

	/* window.reactWidget = {};

	reactWidget.getWidget = function(el) {
		return el.data('widget');
	}; */

})();


// reactState (old)
(function() {

	var states = [];
	var current_state_id = 0;

	function stateReset() {
		var state_id = this.state_id;

		var state = states[state_id];
		if (state.locked === true) {
			// warning: you can't reset value of reactState inside of another function call that changes it's value
			console.log('warning: you can\'t reset value of reactState inside of another function call that changes it\'s value');
			return this;
		}

		state.value = state.initValue;
		this.value = state.value;
		return this;
	}

	function stateSet(new_val) {
		var state_id = this.state_id;
		
		var state = states[state_id];
		if (state.locked === true) {
			// warning: you can't modify value of reactState inside of another function call that changes it's value
			console.log('warning: you can\'t modify value of reactState inside of another function call that changes it\'s value');
			return this;
		}
		
		if (state.value === new_val) { // value not changed
			this.value = state.value; // на всякий случай
			return this;
		}
		
		stateSetVal(state, new_val, state_id);
		
		return this;
	}
	
	function stateRefresh() {
		var state_id = this.state_id;
		
		var state = states[state_id];
		if (state.locked === true) {
			// warning: you can't refresh value of reactState inside of another function call that changes it's value
			console.log('warning: you can\'t refresh value of reactState inside of another function call that changes it\'s value');
			return this;
		}
		
		stateSetVal(state, state.value, state_id);
		
		return this;
	}
	
	function stateSetVal(state, new_val, initial_state_id) {
		state.value = new_val;
		state.ref.value = new_val;

		var len = state.notify_states.length;
		var notify_id;
		var i;

		state.locked = true;
		
		if (len > 0) {
			i = -1;
			while (++i < len) {
				notify_id = state.notify_states[i];
				if (notify_id === initial_state_id) { // recursion
					throw new Error('reactState.set recursion detected, ensure your reactState dependecies have no cycles (state.id = '+initial_state_id+')');
					return;
				}
				stateRefreshDepends(states[notify_id], initial_state_id);
			}
		}
		
		if (state.changed_funcs.length > 0) {
			len = state.changed_funcs.length;
			
			i = -1;
			while (++i < len) {
				state.changed_funcs[i].call( state.ref, new_val );
			}
		}
		
		if (state.become_funcs.length > 0) {
			len = state.become_funcs.length;
			var become_val;
			
			i = -1;
			while (++i < len) {
				become_val = state.become_funcs[i];
				// become_val[0]; // val
				// become_val[1]; // func
				if (become_val[0] === new_val) {
					become_val[1].call( state.ref );
				}
			}
		}

		state.locked = false;
	}
	
	function stateRefreshDepends(state, initial_state_id) {
		var len = state.depend.length;
		var depend_id;
		var values = [];
		
		var i = -1;
		while (++i < len) {
			depend_id = state.depend[i];
			values.push(states[depend_id].value);
		}
		
		var new_val = state.calculation_func.apply( state.ref, values );
		if (new_val === state.value) { // value not changed
			state.ref.value = state.value; // на всякий случай, опять таки
			return;
		}
		
		stateSetVal(state, new_val, initial_state_id);
	}
	
	function stateDepends(depend_states, callback) {
		if (typeof callback !== "function") {
			throw new TypeError('reactState.depends( [state1, state2, ...], calc_function ) 2nd parameter must be a function');
			return this;
		}

		var state_id = this.state_id;
		var state = states[state_id];
		
		state.depend = [];
		depend_states.forEach( function(d) {
			if (typeof d.state_id !== "undefined") {
				state.depend.push(d.state_id);
				states[d.state_id].notify_states.push(state_id);
			}
		});
		
		state.calculation_func = callback;
		
		stateRefreshDepends(state, state_id);
		
		return this;
	}
	
	function stateChanged(callback) {
		if (typeof callback !== "function") {
			throw new TypeError('reactState.changed( callback ) 1st parameter must be a function');
			return this;
		}
		states[this.state_id].changed_funcs.push( callback );

		return this;
	}
	
	function stateBecome(val, callback) {
		if (typeof callback !== "function") {
			throw new TypeError('reactState.become( value, callback ) 2nd parameter must be a function');
			return this;
		}
		states[this.state_id].become_funcs.push( [val, callback] );
		
		return this;
	}
	
	function wrapState(state_id, value) {
		return {
			state_id: state_id,
			value: value,
			set: stateSet,
			reset: stateReset,
			depends: stateDepends,
			changed: stateChanged,
			refresh: stateRefresh,
			become: stateBecome
		};
	}

	function newReactState(state) {
		current_state_id++;
		state_id = current_state_id;
		states[state_id] = state;
		state.ref = wrapState(state_id, state.value);
		
		return state_id;
	}

	window.reactState_old = function(value) {
		
		if (arguments.length == 0) {
			value = null;
		}
		
		var state = {
			value: value,
			initValue: value,
			notify_states: [],
			depend: [],
			changed_funcs: [],
			become_funcs: [],
			locked: false,
			calculation_func: null
		};
		
		newReactState(state);
		
		return state.ref;
	};
	
	window.reactWidget = {};

	reactWidget.getWidget = function(el) {
		return el.data('widget');
	};

})();








// reactWidget.Dropdown
(function($) {

	var current_dropdown = null;

	function notInDropdown(e) {
		return ($(e.target).closest('.dropdown').length === 0);
	}

	$(document).reactEvent('click').filter(notInDropdown).callback( function(e) {
		if (current_dropdown !== null) {
			var allowBlur = true;
			if (typeof current_dropdown.onBlur === "function") {
				allowBlur = current_dropdown.onBlur.call(current_dropdown, e);
			}
			if (allowBlur) {
				current_dropdown.opened.set(false);
				current_dropdown = null;
			}
		}
	});
	
	
	reactWidget.Dropdown = function(options) { // el) {
		var el = (typeof options.el === 'undefined') ? options : options.el;
		
		options = $.extend({
			onBlur: null,
			onClick: null
		}, options);



		var opened = reactState(false);

		var self = {
			type: 'dropdown',
			el: el,
			opened: opened,
			
			onBlur: options.onBlur,
			onClick: options.onClick,

			setItems: setItems,
			getItems: getItems,

			filterItems: filterItems
		};
		el.data('widget',self);


		var items_cache = null; // cache for restore when calling filterItems

		// items = [ {text: '', insert_text: '', val: '', attribs: {}}, ... ]
		function setItems(items, update_cache) {
			if (typeof update_cache === 'undefined') {
				update_cache = true;
			}
			var items_html = '';
			$.each( items, function(i, item) {
				/* item.val;
				item.text;
				item.insert_text;
				item.attribs; */
				
				item = $.extend({
					insert_text: item.text,
					attribs: {}
				}, item);

				var attribs = '';
				if ( (typeof item.is_default !== 'undefined') && item.is_default) {
					attribs = ' class="default"';
				} else {
					$.each( item.attribs, function(attr, attr_val) {
						attribs += ' data-'+attr+'="'+attr_val+'"';
					});
				}
				var item_html = '<li data-val="'+item.val+'" data-text="'+item.insert_text+'"'+attribs+'>'+item.text+'</li>';
				/* if (item.val === '') {
					items_html = item_html + items_html;
				} else { */
					items_html += item_html;
				// }
			});

			opened.set(false);
			el.html(items_html);
			if (update_cache) {
				items_cache = null;
			}
		}
		
		function getItems(attribs, val_keys) {
			if (typeof val_keys === 'undefined') {
				val_keys = true;
			}
			
			if (typeof attribs !== 'undefined') {
				attribs = attribs.split(' ');
			}
			var items = [];
			if (val_keys) {
				items = {};
			}
			$('li[data-val]',el).each( function() {
				var item = $(this);

				var val = item.attr('data-val');
				var item_params = {
					text: item.text(),
					val: val
				};
				if (item.is('.default')) {
					item_params.is_default = true;
				}
				if (item.is('[data-text]')) {
					item_params.insert_text = item.attr('data-text');
				}
				
				if (typeof attribs !== 'undefined') {
					item_params.attribs = {};
					$.each(attribs, function(i, attr) {
						var attr_val = item.attr('data-'+attr);
						if (typeof attr_val !== 'undefined') {
							item_params.attribs[attr] = attr_val;
						}
					});
				}
				if (val_keys) {
					items[val] = item_params;
				} else {
					items.push(item_params);
				}
			});
			return items;
		}

		function filterItems(attrib, val, filter_func) {
			if (items_cache === null) {
				items_cache = getItems(attrib, false);
			}
			var options = [];
			if (val === '') {
				options = items_cache;
			} else {
				if (typeof filter_func !== 'function') {
					filter_func = function(option, val, attrib) {
						return (option.val === '') || (option.attribs[attrib] == val);
					}
				}
				$.each(items_cache, function(id, option) {
					if (filter_func(option, val, attrib)) {
						options.push(option);
					}
				}); 
			}
			setItems(options, false);
		}

		opened.changed( function(opened) {
			if (opened) {
				if (current_dropdown !== null) {
					current_dropdown.opened.set(false);
				}
				current_dropdown = self;
			} else {
				current_dropdown = null;
			}
		});

		// onClick
		el.click( function(e) {
			if (typeof self.onClick === "function") {
				if (self.onClick.call(self, e)) {
					opened.set(false);
				}
			}
		});
		
		opened.changed( function(opened) {
			if (opened) {
				el.addClass('open');
			} else {
				el.removeClass('open');
			}
		});
		
		return self;
	};
	
})(jQuery);




// reactWidget.Checkbox
(function($) {

	reactWidget.Checkbox = function(options) { // el) {
		var el = (typeof options.el === 'undefined') ? options : options.el;


		var is_checked = reactState(false);
		
		var is_disabled = reactState(false);
		
		var self = {
			type: 'checkbox',
			el: el,
			checked: is_checked,
			disabled: is_disabled,
			
			setChecked: setChecked
		};
		el.data('widget',self);

		is_checked.changed( function(checked) {
			if (checked) {
				el.addClass('checked');
			} else {
				el.removeClass('checked');
			}
		});

		var ch = $(':checkbox:visible:first',el);
//			var wrap = $('.filter-wrap',el);
		if (el.is('.disabled')) {
			is_disabled.set(true);
		}
		if (ch.is(':checked')) {
			is_checked.set(true);
		}

		is_disabled.changed( function(disabled) {
			if (disabled) {
				el.addClass('disabled');
			} else {
				el.removeClass('disabled');
			}
		});
		
		function setChecked(checked) {
			if (is_checked.value !== checked) {
				is_checked.set(checked);
				ch.prop('checked',checked);
			}
		}

		ch.on('change', function() {
			is_checked.set($(this).is(':checked'));
		});
		
		return self;
	};
	
})(jQuery);




// reactWidget.Filter
(function($) {

	function escapeRegexp(str) {
		return str.replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
	}

	function filterSuggestions(match_text, dropdown, highlight, full_match) {
		var list = $('> li',dropdown);
		if (match_text === '') {
			return $();
		}
		if (arguments.length < 3) {
			highlight = true;
		}
		if (arguments.length < 4) {
			full_match = false;
		}
		var res = list.filter( function() {
			var item = $(this);
			var matched = false;
			var itemText = item.text();
			var highlighted = itemText;
			if (full_match) {
				if (itemText === match_text) {
					matched = true;
					highlighted = '<span>'+itemText+'</span>';
				}
			} else {
				highlighted = itemText.replace( new RegExp('\(\^\|\[\^\\wа-я\]\)\('+escapeRegexp(match_text)+'\)','ig'), function() {
					matched = true;
					return arguments[1]+'<span>'+arguments[2]+'</span>';
				});
			}
			if (!matched) {
				if (highlight) {
					item.text(itemText);
				}
				return false;
			}
			if (highlight) {
				item.html(highlighted);
			}
			return true;
		});
		return res;
	}

	function findSuggestions(match_text, dropdown) {
		var list = $('> li',dropdown);
		var filtered = filterSuggestions(match_text,dropdown);
		list.addClass('hide');
		filtered.removeClass('hide');
		return true;
	}

	function clearSuggestions(dropdown) {
		$('> li',dropdown).removeClass('hide'); // selected');
		$('span',dropdown).contents().unwrap();
	}

	function getOptionText(item) {
		if (item === null) {
			return '';
		}
		
		var text_val = item.text();
		if (item.is('[data-text]')) {
			text_val = item.attr('data-text');
		}
		return text_val;
	}

	function scrollIntoView(cont, el) {
		/* if (rel.top < 0) {
			scrollTop = dim.s.scroll.top + rel.top;
		} else if (rel.top > 0 && rel.bottom < 0) {
			scrollTop = dim.s.scroll.top + Math.min(rel.top, -rel.bottom);
		} */

		/* var scrollHeight = cont[0].scrollHeight;
		var height = el.height();
		var cont_pos = cont.position();
		var el_pos = el.position(); */
		// cont_pos.top;
		// el_pos.top;

		;
	}

	// var eClosed = false;
	var ignoreOnChange = false;

	reactWidget.Filter = function(options) { // el) {
		var el = (typeof options.el === 'undefined') ? options : options.el;


		var val_index = reactState(null);

		var is_disabled = reactState(false);
		
		var self = {
			type: 'filter',
			hidden_attribs: {},
			el: el,
			disabled: is_disabled,
			val: val_index,
			
			setItems: setItems,
			selectItem: selectItem,
			getItems: getItems,

			dropdownOpen: dropdownOpen,
			dropdownClose: dropdownClose,

			filterItems: filterItems
		};
		el.data('widget',self);


		var autocompleteOpened = false;
		
		// var inp = $(':text',el);
		var inp;
		if (el.is(':visible')) {
			inp = $('input:visible:first',el);
		} else {
			inp = $('input:first',el);
		}
		var wrap = $('.filter-wrap',el);
		if (wrap.is('.disabled')) {
			is_disabled.set(true);
		}

		var val_inp = $('input[type="hidden"]:first',el);
		var button = $('.button-dropdown',el);

//		console.log('val_inp:');
//		console.log(val_inp.val());

		if (val_inp.val() === '') {
			inp.val(''); //
		}
		
		var last_val = inp.val();
		

		var text_val = '';


		var attrs = inp.attr('data-hidden_attribs');
		if (attrs) {
			var inp_name = val_inp.attr('name')+'_';
			$.each( attrs.split(' '), function(i, attr) {
				self.hidden_attribs[attr] = $('input[name="'+inp_name+attr+'"]',el);
			});
		}

		/* var dropdown = reactWidget.Dropdown($('.dropdown',el)); */

		var dropdown = reactWidget.Dropdown({
			el: $('.dropdown',el),
			onBlur: function(e) {
				if (e.target === inp[0]) {
					return false;
				}
				clearSuggestions(dropdown.el);
				autocompleteOpened = false;
				return true;
			},
			onClick: function(e) {
				var item = $(e.target).closest('li');

				selectListItem(item);
				
				inp.focus();
				
				return true;
			}
		});

		self.dropdown = dropdown;
		
		// init value if specified
		var val_ = val_inp.val();
		if (val_ !== '') {
			var item = $('li[data-val="'+val_+'"]:first',dropdown.el);
			if (item.length !== 0) {
				// val_index.set(val_);
				selectListItem(item);
			}
		}



		function setItems(items) {
			dropdown.setItems(items);
		}
		
		function getItems(attrs) {
			return dropdown.getItems(attrs);
		}

		function filterItems(attrs, val, filter_func) {
			dropdown.filterItems(attrs, val, filter_func);
			item = $('li[data-val="'+val_index.value+'"]:first',dropdown.el);
			if (item.length === 0) {
				selectListItem(null);
			}
		}

		function updateHiddenAttribs(item) {
			$.each( self.hidden_attribs, function(attr, inp) {
				var attr_val = '';
				if (item !== null) {
					attr_val = item.attr('data-'+attr);
				}
				inp.val(attr_val);
			});
		}

		function selectListItem(item) {
			text_val = getOptionText(item);
			ignoreOnChange = true;
			inp.val(text_val);
			ignoreOnChange = false;

			updateHiddenAttribs(item);

			if (item) {
				var val = item.attr('data-val');
				val_index.set(val);
			} else {
				val_index.set(null);
				// val_index.reset();
			}
		}

		function selectItem(val) {
			var item = null;
			if (val) {
				item = $('li[data-val="'+val+'"]:first',dropdown.el);
				if (item.length === 0) {
					item = null;
				}
			}
			
			selectListItem(item);
		}
		
		
		/* dropdown.onBlur = function(e) {
			if (e.target === inp[0]) {
				return false;
			}
			clearSuggestions(dropdown.el);
			autocompleteOpened = false;
			return true;
		};

		dropdown.onClick = function(e) {
			var item = $(e.target).closest('li');

			text_val = getOptionText(item);
			// console.log('text: '+text_val);
			ignoreOnChange = true;
			// inp.get(0).offsetHeight; // reflow
			inp.val(text_val);
			inp.focus();
			
//				inp.get(0).offsetHeight; // reflow
			ignoreOnChange = false;

			val_index.set(item.attr('data-val'));
			// selected_item();
			
			return true;
		}; */
		
		val_index.changed( function(val) {
			val_inp.val(val);
		});

		is_disabled.changed( function(disabled) {
			if (disabled) {
				wrap.addClass('disabled');
			} else {
				wrap.removeClass('disabled');
			}
		});
		

		dropdown.opened.become( true, function() {
			if ((val_index.value !== null) && (val_index.value !== '')) {
				// console.log(val_index.value);
				$('> li[data-val="'+val_index.value+'"]',dropdown.el).addClass('selected');
			}
		}).become( false, function() {
			$('> li',dropdown.el).removeClass('selected');
			if (autocompleteOpened) {
				clearSuggestions(dropdown.el);
				autocompleteOpened = false;
				// eClosed = true;
			}
		});

		
		var inputFocused = false;
		
		inp.on('focus', function() {
			inputFocused = true;
		});
		
		inp.on('blur', function() {
			inputFocused = false;
		});

		var keyCodes = {
			Up: 38,
			Down: 40,
			Right: 39,
			End: 35,
			Esc: 27,
			Tab: 9,
			Return: 13
		};

		var isKeyArrows = function(e) {
			return (e.type === 'keyup') && (e.which === keyCodes.Up || e.which === keyCodes.Down);
		};
		

		// нажатие Enter-а
		var inpReturn = inp.reactEvent('keydown', function(e) {
			return (e.which === keyCodes.Return);
		});
		
		var inpReturn_dropdown = inpReturn.filter( function(e) {
			if (dropdown.opened.value) {
				var selected_item = $('li.selected:visible',dropdown.el);
				// console.log(1);
				// console.log(selected_item);
				if (selected_item.length > 0) {
					autocompleteOpened = false;
					dropdown.opened.set(false);
					clearSuggestions(dropdown.el);
					selectListItem(selected_item);
					return false;
				}
			}

			var match_text = this.val();
			if (match_text !== '') {
				var list = filterSuggestions(match_text, dropdown.el, false, true);
				if (list.length === 1) { // одно совпадение
					if (dropdown.opened.value) {
						autocompleteOpened = false;
						dropdown.opened.set(false);
						clearSuggestions(dropdown.el);
					}
					selectListItem(list.filter(':first'));
					return false;
				}
			}

			// если inp.val === '' или не нашлось 100%-го совпадения
			return true;
		});


		var inpKeyup = inp.reactEvent('keyup');

		inpKeyup.filter( function(e) { // перемещение курсора стрелками вверх и вниз по списку автокомплита если он открыт
			// console.log(autocompleteOpened);
			return isKeyArrows(e) && dropdown.opened.value; // autocompleteOpened;
		}).callback( function(e) {
			var selected = $('> li.selected:visible',dropdown.el);
			var selected_el = null;
			if (selected.length > 0) {
				var current;
				if (e.which === keyCodes.Down) {
					current = selected.nextAll(':not(.hide):first');
				} else if (e.which === keyCodes.Up) {
					current = selected.prevAll(':not(.hide):not(.default):first');
				}
				if (current.length > 0) {
					selected.removeClass('selected');
					var selected_el = current.addClass('selected');
				}
			} else {
				selected_el = $('> li:not(.hide):not(.default):first',dropdown.el).addClass('selected');
				// console.log(selected_el);
			}
			if (selected_el !== null) {
				scrollIntoView(dropdown.el,selected_el);
				// console.log(selected_el);
				
				// dropdown.el.scrollTo(selected_el,0,{axis: 'y'});
				// selected_el[0].;
				// selected_el[0].scrollIntoView();
			}
		});

		inpKeyup.filter(function(e) {
			return isKeyArrows(e);
		}).merge(inpReturn).filter( function(e) { // выпадение всех вариантов при нажатии на стрелку вверх или вниз в пустом инпуте
			return (this.val() === '');
		}).callback( function() {
			autocompleteOpened = true;
			dropdown.opened.set(true);
			clearSuggestions(dropdown.el);
		});



		inpKeyup.filter( function(e) {
			return autocompleteOpened && (e.which === keyCodes.Right || e.which === keyCodes.End);
		}).callback( function(e) {
			var selected = $('> li.selected:visible',dropdown.el);
			if (selected.length > 0) {
				inp.val(getOptionText(selected));
			}
		});

		
		
		/* var inpChanged = inpKeyup.merge(
			inp.reactEvent('change input').filter( function(e) {
				return inputFocused;
			})
		); */

		inpKeyup.filter( function(e) { // убирание вариантов автокомплита при стирании строки в инпуте
			return (this.val() === '') && autocompleteOpened && !isKeyArrows(e) && (e.which !== keyCodes.Return);
		}).callback( function() {
			autocompleteOpened = false;
			dropdown.opened.set(false);
			clearSuggestions(dropdown.el);
		});


		var inpChanged = inpKeyup.merge(
			inp.reactEvent('change input').filter( function(e) {
				var _last_val = last_val;
				last_val = this.val();
				return !ignoreOnChange && ( (this.val() !== _last_val) || isKeyArrows(e) );
			})
		).filter( function(e) {
			return (e.which !== keyCodes.Return);
		});

		
		var inpChanges = inpChanged.filter( function(e) {
			return (this.val() !== '') && !( (this.val() === text_val) ); // && isKeyArrows(e) );
		}).debounce(200);


		inpChanged.filter( function(e) { // выпадение всех вариантов автокомплита если в cписке автокомплита подходит только один вариант со 100% совпадением
			return (this.val() !== '') && (this.val() === text_val) && (isKeyArrows(e) || !autocompleteOpened); // && !autocompleteOpened; //  && isKeyArrows(e)
		}).callback( function() {
			autocompleteOpened = true;
			dropdown.opened.set(true);
			clearSuggestions(dropdown.el);
		});
		


		inpChanges.merge(inpReturn_dropdown).filter( function(e) { // поиск совпадений по вписанному тексту и выпадение вариантов если есть хотя бы один
			return inputFocused && (this.val() !== ''); // && (!isKeyArrows(e) || autocompleteOpened);
		}).callback( function(e) {
			var match_text = this.val();
			if (findSuggestions(match_text, dropdown.el)) {
				autocompleteOpened = true;
				dropdown.opened.set(true);
			} else {
				autocompleteOpened = false;
				dropdown.opened.set(false);
				clearSuggestions(dropdown.el);
			}
		});
		

		function dropdownOpen() {
			dropdown.opened.set(true);
			inp.focus();
		}

		function dropdownClose() {
			if (autocompleteOpened) {
				autocompleteOpened = false;
				clearSuggestions(dropdown.el);
				opened = false;
			}
			
			dropdown.opened.set(false);
		}
		
		button.click( function(e) {

			var opened = dropdown.opened.value;

			if (opened) {
				dropdownClose();
			} else {
				dropdownOpen();
			}
		
			/* var opened = dropdown.opened.value;
		
			if (autocompleteOpened) {
				autocompleteOpened = false;
				clearSuggestions(dropdown.el);
				opened = false;
			}
			
			if (opened) {
				dropdown.opened.set(false);
			} else {
				dropdown.opened.set(true);
				inp.focus();
			} */
		
			e.stopPropagation();
		});
		
		return self;
	};

})(jQuery);

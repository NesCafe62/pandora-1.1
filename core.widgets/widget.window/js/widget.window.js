//	#required {
//	}

(function($) {

	var wnd_stack = [];

	$(document).on('keydown', function(e) {
		if (e.keyCode == 27) {
			var i = wnd_stack.length-1;
			while ((i >= 0) && (wnd_stack[i] === null || !wnd_stack[i].closeEsc())) {
				wnd_stack.pop();
				i--;
			}
		}
	});

	var wnd = function(el) {
		var self = this;
		self.opened = false;
		// var opened = false;

		function extend(arr) {
			$.each(arr, function(key,val) {
				self[key] = val;
			});
		}

		var cont = $('.window',el);
		self.cont = cont;
		var methods = {
			init: function() {
				var w = $(el);
				cont.css('visibility','hidden');
				
				var wnd_w = cont.data('width');
				var wnd_h = cont.data('height');
				if (wnd_w) {
					cont.css('width',wnd_w+'px');
				}
				if (wnd_w) {
					$('.cont-wrap',cont).css('height',wnd_h+'px');
				}
				if (self.options['centered']) {
					var margin_top = Math.round( cont.height() / 2);
					cont.css({
						'top': '40%',
						'margin-top': '-' + margin_top + 'px'
					});
				}

				var effect = self.options['effect'];
				if (effect != 'none') {
					cont.addClass('effect-'+effect);
				}

				$('.window-overlay',w).click(function() {
					var res = trigger('OverlayClick');
					if (res === null || res === true) {
						self.close();
					}
				});
				$('.close, .cancel',w).click(function(e) {
					self.close();
					e.preventDefault();
					return false;
				});
				$('.save',w).click(function(e) {
					self.save();
					e.preventDefault();
					return false;
				});
			},

			open: function() {
				var w = $(el);
				var res = trigger('Open');
				if (res === null || res) {
					w.css('visibility','visible')
					 .addClass('show');

					wnd_stack.push(self);

					cont.css('visibility','visible');

					self.opened = true;
					trigger('Show');
				}
			},

			close: function() {
				var res = trigger('Close'); // null - if onClose event function is null
				if (res === null || res) {
					var w = $(el);

					var delay = self.options['effect_delay'];
					// var fade_delay = self.options['fade_delay'];
					/* if (self.options['effect'] != 'none') {
						cont.delay(delay).queue( function () {
							if (!self.opened) cont.css('visibility','hidden');
							cont.dequeue();
						});
					} else {
						cont.css('visibility','hidden');
					} */

					var i;
					for (i = 0; i < wnd_stack.length; i++) {
						if (wnd_stack[i] === self) {
							wnd_stack[i] = null;
							break;
						}
					}
					
					self.opened = false;

					w.removeClass('show')
					 .delay(delay).queue( function () {
						if (!self.opened) {
							// if (self.options['effect'] != 'none') {
								cont.css('visibility','hidden');
							// }
							w.css('visibility','hidden');
							trigger('AfterClose');
						}
						w.dequeue();
					 });

					/* w.removeClass('show')
					 .delay(fade_delay).queue( function () {
						if (!self.opened) w.css('visibility','hidden');
						w.dequeue();
					 }); */
				}
			},
			
			closeEsc: function() {
				if (!self.options['closeOnEscape']) {
					return true;
				}
				if (self.opened) {
					self.close();
					if (!self.opened) {
						if (wnd_stack[wnd_stack.length-1] === null) {
							wnd_stack.pop();
						}
					}
					return true;
				}
				return false;
			},
			
			save: function() {
				if (trigger('Save')) {
					self.close();
				}
			}

		};

		extend(methods);

		var setOptions = function(options) {
			options = $.extend({
				centered: true,
				effect: 'none',
				effect_delay: 450,
				closeOnEscape: true,
				onOpen: null,
				onShow: null,
				onClose: null,
				onAfterClose: null,
				onOverlayClick: null,
				onSave: null
			},this);

/*			switch(options['effect']) {
				case 'fade_scale':
				case 'slide':
					break;
				default:
					options['effect'] = 'none';
			} */

			extend({options: options});
		};

		extend({
			setOptions: setOptions
		});

		function trigger(event) {
			event = 'on' + event;
			if (typeof self.options[event] !== 'function') return null;

			var args = Array.prototype.slice.call(arguments,1);
			return self.options[event].apply(self,args);
		}
	};

	$.fn.Window = function( method_ ) {
		var w = null,
			err = false, created = false,
			method = method_,
			$this = $(this),
			data = $this.data('window');

		if (data) {
			w = data.wnd;
		} else {
			created = true;
			w = new wnd(this);
		}

		var args;
		if ( w[method] ) {
			args = Array.prototype.slice.call(arguments,1);
		} else if (typeof method === 'object' || !method) {
			args = arguments[0];
			method = 'init';
		} else {
			$.error( 'Метод с именем ' + method + ' не существует для jQuery.Window' );
			err = true;
		}
		
		if (!err) {
			if (created) {
				w.setOptions.apply(args);
				if (method !== 'init') {
					w.init.apply(this);
				}
			}
			w[method].apply(this,args);
			
			if (!data) {
				data = {
					target: $this,
					wnd: w
				}
				$this.data('window',data);
			}

			return this;
		}
	};
	
})(jQuery);

//	#required {
//		core:jquery.min
//	}

// $.wait
(function($) {

	function D(obj) {
		var status = 'pending';
		var doneFuncs = [];
		if (arguments.length == 0) {
			obj = {};
		}

		var deferred = {
			then: function(callback) {
				if (status === 'resolved') {
					callback.call(callback,obj);
				} else {
					doneFuncs.push(callback);
				}
				return this;
			},
			
			resolve: function() {
				if (status === 'pending') {
					status = 'resolved';
					for (var i = 0; i < doneFuncs.length; i++) {
						doneFuncs[i].call(doneFuncs[i],obj);
					}
				}
				return this;
			},
			
			bind: function(_obj) {
				obj = _obj;
				return this;
			},

			reject: function() {
				if (status === 'pending') {
					status = 'rejected';
					doneFuncs = [];
				}
				return this;
			}
		};
		
		return deferred;
	}

	$.D = D;
	
	$.wait = function() {
		
		// var obj = {};

		var seq = {
		
			_deferred: D(this.obj).resolve(),

			obj: {},

			wait: function(waits, callback) {
				if (!Array.isArray(waits)) {
					waits = [waits];
				}
				
				var d = D(this.obj);
				
				if (typeof callback === 'function') {
					d.then(callback);
				}

				var self = this;
				
				this._deferred.then( function() {
					var counter = waits.length;
					
					function checkResolve() {
						counter--;
						if (counter <= 0) {
							d.resolve();
						}
					}
					
					for (var i = 0; i < waits.length; i++) {
						var w = waits[i];
						if (typeof w._deferred !== 'undefined') {
							w._deferred.then( function() {
								checkResolve();
							});
						} else if (typeof w === 'function') {
							w({
								resolve: checkResolve
							}, self.obj);
						} else if (Number.isInteger(w)) {
							setTimeout( checkResolve, w);
						} else {
							counter--;
						}
					}
					
					if (counter === 0) {
						d.resolve();
					}
				});
				
				this._deferred = d;
				
				return this;
			},
			
			reset: function() {
				this._deferred.reject();
				this._deferred = D(this.obj).resolve();
				
				return this;
			},
			
			then: function(callback) {
				this._deferred.then(callback,this.obj);
				
				return this;
			},

			bind: function(_obj) {
				this.obj = _obj;
				this._deferred.bind(_obj);

				return this;
			}
		};
		
		if (arguments.length > 0) {
			seq.wait.apply(seq, arguments);
		}
		
		return seq;
	}

})(jQuery);


(function() {

/*	actionForm({
		action: '',
		fields: {
			jhu: '',
		}
	}); */
	
	var actionForm = $('body form[name="actionForm"]');
	if (actionForm.length == 0) {
		var submitUrl = window.location.pathname + window.location.search;
		$('body').append('<form name="actionForm" action="' + submitUrl + '" method="post"><input type="hidden" name="action" value=""/></form>');
		actionForm = $('body > form[name="actionForm"]');
	}
	var fldAction = $(':input[name="action"]',actionForm);
	
	$('a[data-action]').click(function(e) {
		e.preventDefault();
		var link = $(e.target).closest('a[data-action]');
		var action = link.data('action');

		var action_params = link.data('action_params');
		
		var submitUrl = link.attr('href');
		if (!submitUrl || (submitUrl === '#')) {
			submitUrl = '';
		}

		/* if (action_params) {
			;
		}
		fldAction.val(action);
		actionForm.submit(); */

		var action_allowed = $.wait().bind({result: true});
		link.trigger('action',[action_allowed]);

		action_allowed.then(function(status) {
			if (status.result === true) {
				var params = {};
				if (action_params) {
					var arr;
					$.each( action_params.split('&'), function(index, item) {
						arr = item.split('=');
						params[arr[0]] = arr[1];
					});
				}

				window.actionForm({
					action: action,
					url: submitUrl,
					params: params
				});
			}
		});
			
		return false;
	});

	$('form a.submit').click(function(e) {
		var link = $(e.target).closest('a.submit');

		var form = link.closest('form');
		form.submit();

		e.preventDefault();
		return false;
	});
	
	window.actionForm = function(options) {
		var fields = '';
		if (typeof options.action === 'undefined') {
			throw new TypeError('actionForm: options.action must be defined');
			return false;
		}
		if ( (typeof options.params === 'undefined') && (typeof options.fields !== 'undefined') ) {
			console.log('actionForm: options.fields is deprecated use options.params instead');
			options.params = options.fields;
		}
		$.each(options.params, function(key, val) {
			if (key !== 'action') {
				fields += '<input type="hidden" name="'+key+'" value="'+val+'"/>';
			}
		});
		if ((typeof options.submitUrl !== 'undefined') && options.submitUrl) {
			actionForm.attr('action',options.submitUrl);
		}
		actionForm.append(fields);

		fldAction.val(options.action);
		actionForm.submit();
	};
	
})();

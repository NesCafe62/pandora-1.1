//	#required {
//		core:jquery.min
//	}

(function() {
	// Array.isArray
	if (!Array.isArray) {
		Array.isArray = function(arg) {
			return Object.prototype.toString.call(arg) === '[object Array]';
		};
	}

})();

(function() {

	debug_state = false;
	if ( (typeof global_params.debug_mode !== 'undefined') && global_params.debug_mode) {
		debug_state = true;
	}
	window.core = {
		debug: debug_state
	};

	window.core.param = function(param_name, default_val) {
		if (typeof global_params[param_name] !== 'undefined') {
			return global_params[param_name];
		}
		return default_val;
	};

	var callbacks = [];

	window.core.action = function(args) {
		if ( (arguments.length >= 2) && !Array.isArray(arguments[0]) ) {
			args = {
				action: arguments[0],
				data: arguments[1]
			};
		}

		if (!args.action) {
			throw new TypeError('core.action: args.action must be defined');
			return false;
		}

		if (typeof args.params !== 'undefined') {
			args.data = args.params;
		}

		var params_data = $.extend({
			ajax: true,
			action: args.action
		}, args.data);
		
		var request = $.ajax({
			url: global_params.app_base, // << app url needs to go here
			method: 'post',
			dataType: 'json',
			data: params_data
		});

		var deferred = {done: function(func) {
			if (typeof func === "function") {
				args.done = func;
			}
		}};
		
		request.done( function(data) {
			var err = null;
			if ( (data === 'empty') || ( !data.result && (data.msg !== '') ) ) {
				err = {
					msg: data.msg
				};
				data.result = false;
			}
			if (callbacks.length > 0) {
				for (var i = 0; i < callbacks.length; i++) {
					callbacks[i](data);
				}
			}
			if (typeof args.done === "function") {
				args.done(data.result, err);
			}
		});

		return deferred;
	}

	window.core.afterAction = function(func) {
		if (typeof func !== "function") {
			throw new TypeError('core.afterAction: argument type must be function');
			return false;
		}
		callbacks.push(func);
	}

})();

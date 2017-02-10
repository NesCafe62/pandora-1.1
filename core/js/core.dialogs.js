//	#required {
//		core.widget
//	}

(function($) {

	$.dialogs = {
		theme: 'flat',

		confirmDelete: {
			title: 'Подтверждение',
			result: 'confirm',
			buttons: {
				confirm: 'Удалить',
				cancel: 'Отмена'
			}
		}

	};
	
	var dialog_stack = null;
	
	function initDialogEsc() {
		dialog_stack = [];
		$(document).on('keydown.dialog', function(e) {
			if (e.which == 27) {
				var i = dialog_stack.length;
				if (i > 0) {
					dialog_stack[i-1].trigger('close.dialog');
				}
			}
		});
	}

	$.dialogs.modal = function(dialog_params, message, dialog) {
		if (typeof dialog_params === 'string') {
			if ((dialog_params === 'modal') || (typeof $.dialogs[dialog_params] === 'undefined')) {
				// throw new Error('dialog type not exists'); <<
				return false; // <<
			}
			dialog_params = $.dialogs[dialog_params];
		}

		if ((typeof dialog === 'undefined') || (typeof dialog.result === 'undefined')) {
			dialog = {};
		}
		dialog.result = false;
		dialog.status = '';

		var D = $.D(dialog);

		html = '<div class="dialog theme-'+$.dialogs.theme+'">';
			html += '<div class="overlay"></div>';
			html += '<div class="dialog-window">';
				html += '<div class="caption">';
					html += '<span class="title">'+dialog_params.title+'</span>';
					html += '<a href="#" class="close"></a>';
				html += '</div>';
				html += '<div class="dialog-content">';
					html += '<div class="message">'+message+'</div>';
				html += '</div>';
			html += '</div>';
		html += '</div>';
		
		var dialog_wnd = $(html);
		
		/* $('.overlay:first',dialog_wnd).on('click.dialog', function() {
			dialogResult('cancel');
		}); */
		
		var elements = $();
		
		var btn_close = $('.close:first',dialog_wnd);
		elements.add(btn_close);
		btn_close.on('click.dialog', function(e) {
			e.preventDefault();
			dialogResult('cancel');
			return false;
		});

		dialog_wnd.on('close.dialog', function() {
			dialogResult('cancel');
		});
		
		if (dialog_stack === null) {
			initDialogEsc();
		}
		dialog_stack.push(dialog_wnd);
		
		var dialog_cont = $('.dialog-content',dialog_wnd);
		
		$.each(dialog_params.buttons, function(name, title) {
			var btn = $('<a href="#" class="button button-'+name+'">'+title+'</a>');
			elements.add(btn);
			btn.on('click.dialog', function(e) {
				e.preventDefault();
				dialogResult(name);
				return false;
			});
			dialog_cont.append(btn);
		});
		
		$(document.body).append(dialog_wnd);
		// dialog_wnd[0].offsetHeight; // reflow

		var wnd = dialog_cont.parent();
		var margin_top = Math.round( wnd.height() / 2);
		// first_slide.css('transition','0s margin ease-out')
		wnd.css({
			'transition': '0s margin ease-out',
			'margin-top': '-' + margin_top + 'px'
		});
		wnd[0].offsetHeight; // reflow
		wnd.css('transition','');
		
//		$.wait(100, function() {
			dialog_wnd.addClass('show');
//		});
		
		function dialogResult(status) {
			if (dialog.status != '') {
				return;
			}
			dialog_stack.pop();
			dialog_wnd.removeClass('show');
			elements.off('.dialog');
			$.wait(450, function() {
				dialog_wnd.remove();
			});
		
			if (status === dialog_params.result) {
				dialog.result = true;
			}
			dialog.status = status;
			D.resolve();
		}

		return {
			_deferred: {
				then: function(callback) {
					D.then( function(dialog) {
						callback();
					});
				}
			},
			result: function(res, dlg_result) {
				return function(d) {
					D.then( function(dialog) {
						if (typeof dlg_result === 'undefined') {
							if (dialog.status === res) {
								d.resolve();
							}
						} else {
							dlg_result.status = dialog.status;
							if (dialog.status === res) {
								dlg_result.result = true;
							}
							d.resolve();
						}
					});
				};
			},
			then: function(res, callback) {
				if (typeof callback === 'undefined') {
					callback = res;
					res = null;
				}
				D.then( function(dialog) {
					if (res === null) {
						callback(dialog);
					} else {
						if (dialog.status === res) {
							callback(dialog);
						}
					}
				});
				return this;
			}
		};
	};
	
})(jQuery);

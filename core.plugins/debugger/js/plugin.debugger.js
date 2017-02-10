//	#required {
//		core:jquery.cookie
//		core.action
//	}

(function() {

	var defaultHeight = 170;

	var cont = $('.debugger-console');

	var resizing = $('.resizing',cont);
	
	var debuggerHeight = $('.debugger-height');
	isDebuggerHeight = !debuggerHeight.is(':hidden'); // распорка
	
	var consoleHeight = cont;
	if (isDebuggerHeight) {
		consoleHeight = consoleHeight.add(debuggerHeight);
	}

	var page_wrap = $('.page-wrap');
	var heightOption = 'height';
	if (page_wrap.length == 0) {
		page_wrap = $(document.body);
		heightOption = 'padding';
	}

	function update_toolbar_height(h, instant) {
		// $('.page').css('padding-bottom',h);
		
		if (instant == true) {
			cont.attr('style','transition: '+heightOption+' 0s ease-out 0s !important');

			consoleHeight.css('height',h);
			if (!isDebuggerHeight) {
				page_wrap.css('padding-bottom',h);
			}
			cont[0].offsetHeight; // reflow

			cont.css('transition','');

			consoleHeight.css('transition','height 0.2s ease-out 0s');
			if (!isDebuggerHeight) {
				page_wrap.css('transition','padding 0.2s ease-out 0s');
			}
		} else {
			consoleHeight.css('height',h);
			if (!isDebuggerHeight) {
				page_wrap.css('padding-bottom',h);
			}
		}
	}

	var messgaes = $('.messages:first',cont);

	core.afterAction( function(data) {
		if ( (typeof data.debug_messages !== 'undefined') && data.debug_messages) {
			$('> .messages-item:last',messgaes).css('margin-bottom','10px');
			messgaes.append(data.debug_messages);
		}
	});

	var lastH;

//	function deleteAllCookies() {
//		document.cookie.split(";").forEach(function(c) { document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); });
//	}
	
	// deleteAllCookies();
	
	lastH = $.Cookies.get('debugger.console_height');
	if (typeof lastH === "undefined") {
		lastH = defaultHeight;
	}
	
	var lastY, lastH, newH;
	var isResizing = false;
	// var resizingLeaved = false;

	var minH = 23;

	function start_resizing(e) {
		isResizing = true;
		lastY = e.pageY;
		lastH = cont.height();
		newH = lastH;
		resizing.show().css('height',lastH-1);

		if (typeof document.body.setCapture !== 'undefined') {
			document.body.setCapture();
		}
		
		e.preventDefault();
		return false;
	}

	function stop_resizing(e) {
		if (isResizing) {
			isResizing = false;
			resizing.hide();
			var h = newH;
			$.Cookies.set('debugger.console_height', h, { path: '/' });

			if (typeof document.body.releaseCapture !== 'undefined') {
				document.body.releaseCapture();
			}

			update_toolbar_height(h);
		}
	}

	function update_resizing(e) {
		if (isResizing) {
			/* if (resizingLeaved) {
				resizingLeaved = false;
				if ((e.buttons & 1) == 0) {
					cancel_resizing;
				}
				// console.log(e.buttons);
			} */
			var d = (e.pageY - lastY);
			newH = lastH - d;
			var maxH = document.body.clientHeight - 100;
			if (newH < minH) newH = minH;
			if (newH > maxH) newH = maxH;
			resizing.css('height',newH-1);
		}
	}
	
/*	function mouse_leaved(e) {
		if (isResizing) {
			resizingLeaved = true;
		}
	} */


	$('.resize',cont).on('mousedown',start_resizing);
	$(document.body)
		.on('mousemove',update_resizing)
		.on('mouseup',stop_resizing);
		// .on('mouseleave',mouse_leaved);

	update_toolbar_height(lastH,true);
	
	$('a.close',cont).click( function(e) {
		e.preventDefault();
		core.action({
			action: 'debugger/console.close',
			data: {}
			/* ,done: function(data) {
				console.log(data);
			} */
		});
		cont.hide();
		debuggerHeight.hide();
		return false;
	});

})();

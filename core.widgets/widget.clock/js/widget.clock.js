//	#required {
//	}

(function() {

	function string_to_time(s) {
		var arr = s.split(':');

		var hours = parseInt(arr[0]);
		var minutes = parseInt(arr[1]);

		var d = new Date();
		d.setHours(hours, minutes, 0, 0);
		
		return d;
	}

	function time_to_string(t) {
		var hours = t.getHours();
		var minutes = t.getMinutes();

		hours = (hours < 10) ? '0' + hours : hours;
		minutes = (minutes < 10) ? '0' + minutes : minutes;

		return hours + ':' + minutes;
	}



	var clocks = $('.widget-clock');
	var start = new Date();
	var offset_seconds = 60 - start.getSeconds();

	start.setSeconds(0, 0);


	var timer = null;
	var time = string_to_time(clocks.text());
	
	var start_time_offset = time.getTime() - start.getTime();

	// console.log('start: '+start.toTimeString());
	// console.log('start_time_offset: '+start_time_offset);

	function updateTime() {
		clocks.text(time_to_string(time));
	}

	function updateTimeout() {
		time.setMinutes(time.getMinutes() + 1);

		// console.log('updateTime');
		// console.log('time: '+time.toTimeString());

		if (timer === null) {
			// console.log('setInterval(60)');
			timer = setInterval(updateTimeout, 60000);
		} else {
			var time_offset = new Date().getSeconds();
			// console.log('seconds: ' + time_offset);

			if (time_offset > 2) {
				// console.log('time_offset > 2');
				
				clearInterval(timer);
				timer = null;
				time.setTime(new Date().getTime() + start_time_offset);

				// console.log('time: '+time.toTimeString());
				
				offset_seconds = 60 - time_offset;
				// console.log('setTimeout('+offset_seconds+')');
				setTimeout(updateTimeout, offset_seconds * 1000);
			}
		}

		// console.log('');

		updateTime();
	}

	// console.log('setTimeout('+offset_seconds+')');
	// console.log('');
	setTimeout(updateTimeout, offset_seconds * 1000);

})();

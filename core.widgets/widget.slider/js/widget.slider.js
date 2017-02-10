//	#required {
//	}

(function() {

	$('.slider.background').each(function() {
		
		var slider = $(this);
		
		var cont = slider;
		
		// var cont = $('.slides',slider);
		// var cont_wrap = $('.slides-wrap',slider);
		var image0 = $('.slide-bg',cont);
		var images = $('.slide',cont);
		
		var slideTimeout = 40000;
		var slideFxDelay = 1500;
		
		var imagesCount = images.length + 1;
		
		var active = imagesCount;
		
		images.each(function(i,item) {
			if ($(this).hasClass('active')) {
				active = i+1;
			}
		});
		
		
		var eventIndex = 0;
		
		var slideFunc;
		var slideEvent = null;
		
		
		// var first_item = image0;
		
		function doSlide(target_id) { // , right) {
			
			
			var right = true;
			
			// console.log(target_id);
			
		/*	if (locked) {
				next_id = target_id;
				next_right = right;
				return true;
			} */
			
		//	locked = true;
		//	next_id = 0;
		//	next_right = true;

			var current = $('.slide.slide-'+active,cont);
			var next = $('.slide.slide-'+target_id,cont);
			
			eventIndex++;
			var eIndex = eventIndex;
			
			
			var last_el = (target_id == imagesCount);
			active = target_id;

			// slide to right
//			if (right) {
				/*if (target_id == 1) {
					last_item.addClass('show');
				} else {
					next.addClass('show');
				} */
				;
				if (last_el) {
					current.removeClass('show');
					// return;
				} else {
					next.addClass('active');
					next.get(0).offsetHeight; // reflow page
					
					next.addClass('show');
					
					/* next.delay(0).queue(function() {
						next.addClass('show');
						$(this).dequeue();
					}); */
				}
				
				// cont_wrap.addClass('right');
//			} else {
				/* if (target_id == imagesCount) {
					last_item.addClass('show');
					next.addClass('show');
					current.removeClass('active');
				} else {
					next.addClass('show');
				}
				cont_wrap.addClass('left'); */
				
//			}
			
			
			
		//	$('.slide-button',slide_buttons).removeClass('active');
		//	$('.slide-button.slide-'+target_id,slide_buttons).addClass('active');
			
			
			// cont.clearQueue();
			
/*			var el = current;
			if (target_id == 1) {
				el = next;
			} */
			
			cont.delay(slideFxDelay).queue(function() {
				
				// current.clearQueue();
				// next.clearQueue();
				
				if (eIndex === eventIndex) {
				
//					if (right) {
						/* if (target_id == 1) {
							last_item.removeClass('show');
						} else {
							next.removeClass('show');
						} */
						
						/* next.removeClass('show');
						current.removeClass('active');
						next.addClass('active'); */
						
						if (last_el) {
							current.removeClass('active');
						} else {
							// next.removeClass('show');
							current.removeClass('active');
							current.removeClass('show');
						}
						
						// cont_wrap.removeClass('right');
//					} else {
						/* if (target_id == imagesCount) {
							last_item.removeClass('show');
						} else {
							next.removeClass('show');
							current.removeClass('active');
						}
						next.addClass('active');
						
						cont_wrap.removeClass('left'); */
//					}
					
					// locked = false;
				/*	if (next_id > 0) {
						// var nx = next_id;
						cont.clearQueue();
						cont.delay(20).queue(function() {
							// console.log(next_id);
							locked = false;
							var nx = next_id;
							var nr = next_right;
							next_id = 0;
							next_right = true;
							slider.trigger('slide.slider',[nx,nr]);
							$(this).dequeue();
						});
					} else {
						locked = false;
					} */
				}
				
				$(this).dequeue();
			});
			
		}
		
		
		slider.on('slide.slider',function() {
			
			var id = active + 1;
			if (id > imagesCount) {
				id = 1;
			}
			
			doSlide(id); // , true);
			
		});
		
		slideFunc = function() {
			slider.trigger('slide.slider');
		};
		
		slideEvent = setInterval(slideFunc,slideTimeout);
		
		
	});

/*	var _halt = false;
	
	function halt() {
		_halt = true;
		console.log('halt');
	}

	function sleep(ms) {
		ms += new Date().getTime();
		while (new Date() < ms){}
	} */

	$('.slider.slideshow').each(function() {
		
		var slider = $(this);
		

		// var slideshow = $('.slideshow');
		// slides
		var slide_buttons = $('.slide-buttons',slider);
		var cont = $('.slides',slider);
		var cont_wrap = $('.slides-wrap',slider);
		var images = $('.slide',cont);
		
		
		var texts_cont = $('.slide-texts',slider);
		var slide_texts = $('.slide-text',texts_cont);
		var texts = [];
		slide_texts.each(function() {
			texts.push($(this));
		});
		
		// var button_prev = ('.slide-prev',slider);
		// var button_next = ('.slide-next',slider);
		

		var slideTimeout = 11000;
		var slideFxDelay = 600;
		var safeDelay = 1000;

		var imagesCount = images.length;
		var active = 1;

		
		var locked = false;
		var next_id = 0;
		var next_right = true;
		
		
		var eventIndex = 0;
		
		
		var slideFunc;
		var slideEvent = null;
		
		
		var last_item = images.first().clone();
		// last_item.removeClass('active').addClass('last').removeAttr('data-id').appendTo(cont_wrap);
		last_item.removeClass('active').addClass('last').removeClass('slide-1').appendTo(cont_wrap);
		
		// halt();
		
		// cont_wrap.addClass('transition');
		
		function doSlide(target_id, right) {
			
			// console.log(target_id);
			
//			if (_halt) return;
			
			if (locked) {
				// if (target_id !== active) {
				next_id = target_id;
				next_right = right;
				// }
				return true;
			}
			
			locked = true;
			next_id = 0;
			next_right = true;

			// var current = $('.image[data-id="'+active+'"]',cont);
			// var next = $('.image[data-id="'+target_id+'"]',cont);
			
			var current = $('.slide.slide-'+active,cont);
			var next = $('.slide.slide-'+target_id,cont);
			
			var text_current = texts[active-1];
			var text_next = texts[target_id-1];
			
			eventIndex++;
			var eIndex = eventIndex;
			
			
			active = target_id;
			var slide_class = 'right';

			// slide to right
			if (right) {
				if (target_id == 1) {
					last_item.addClass('show');
				} else {
					next.addClass('show');
				}
				cont_wrap.addClass('right');
				
				
				// ------
				
				
/*				if (target_id == 1) {
					// last_text.addClass('show');
				} else {
					// text_next.addClass('show');
		//			text_next.addClass('active');
		//			text_next.get(0).offsetHeight; // reflow page
					
					text_next.addClass('show');
				} */

		//		text_current.removeClass('active');

				//	texts_cont.addClass('right');
				
				
				// cont_wrap.addClass('transition');
//				cont_wrap.css('transition','left 0.6s ease-in-out 0s');
//				slide_class = 'right';
			} else {
				if (target_id == imagesCount) {
					last_item.addClass('show');
					next.addClass('show');
					current.removeClass('active');
				} else {
					next.addClass('show');
				}
				cont_wrap.addClass('left');
				
				
				// ------
			//	texts_cont.addClass('left');
				
				
//				slide_class = 'left';
				
			}
			
			text_current.removeClass('show');
				
			text_next.addClass('show');
			
			
/*			if (right) {
			cont_wrap.delay(safeDelay).queue(function() {
			
				if (_halt) return;
				
//				if (eIndex === eventIndex) {
//					cont_wrap.addClass('right'); // slide_class);

//					cont_wrap.css('left', '-100%');

					// cont_wrap.get(0).className = 'slides-wrap '+slide_class;
					
					halt();
//				}
				
				$(this).dequeue();
			});
			} */
			
			
			
			$('.slide-button',slide_buttons).removeClass('active');
			// $('.slide[data-id="'+target_id+'"]',slide_buttons).addClass('active');
			$('.slide-button.slide-'+target_id,slide_buttons).addClass('active');
			
			
			// cont.clearQueue();
			
			next.delay(safeDelay+slideFxDelay).queue(function() {
				
//				if (_halt) return;
				
				// current.clearQueue();
				// next.clearQueue();
				
				if (eIndex === eventIndex) {
				
					if (right) {
						if (target_id == 1) {
							last_item.removeClass('show');
						} else {
							next.removeClass('show');
						}
						// halt();
						current.removeClass('active');
						next.addClass('active');
						
						// cont_wrap.addClass('transition');
						cont_wrap.removeClass('right');
//						cont_wrap.css('transition','');
//						cont_wrap.css('left','');
						// cont_wrap.removeClass('right transition');
						
						
						
						// ------
				//		texts_cont.removeClass('right');
						/* if (last_el) {
							current.removeClass('active');
						} else {
							current.removeClass('active');
							current.removeClass('show');
						} */
						
				//		text_current.removeClass('active');
						
						
						
					} else {
						if (target_id == imagesCount) {
							last_item.removeClass('show');
						} else {
							next.removeClass('show');
							current.removeClass('active');
						}
						next.addClass('active');
						
						cont_wrap.removeClass('left');
						
						
						
						// ------
				//		texts_cont.removeClass('left');
						
						
						
					}
					
					// locked = false;
					if (next_id > 0) {
						// var nx = next_id;
						cont.clearQueue();
						cont.delay(20).queue(function() {
							// console.log(next_id);
							locked = false;
							var nx = next_id;
							var nr = next_right;
							next_id = 0;
							next_right = true;
							slider.trigger('slide.slider',[nx,nr]);
							$(this).dequeue();
						});
					} else {
						locked = false;
					}
				}
				
				$(this).dequeue();
			});
			
		}
		
		
		slider.on('slidePrev.slider',function() {
			
			var id = active - 1;
			if (id < 1) {
				id = imagesCount;
			}
			
			doSlide(id, false);
			
		}).on('slideNext.slider',function() {
			
			var id = active + 1;
			if (id > imagesCount) {
				id = 1;
			}
			
			doSlide(id, true);
			
		}).on('slide.slider',function(e,id,nx) {
			
			if (id === active) {
				return;
			}
			if ((next_id > 0) && (id === next_id)) {
				return;
			}

			doSlide(id, nx); // || (id > active) );
			
		});

		slideFunc = function() {
			slider.trigger('slideNext.slider');
		};
		
		slideEvent = setInterval(slideFunc,slideTimeout);

		slider.hover(
			function() {
				clearInterval(slideEvent);
			},function() {
				clearInterval(slideEvent);
				slideEvent = setInterval(slideFunc,slideTimeout);
			}
		);
		
		$('.slide-button',slider).click(function(e) {
			e.preventDefault();
			var el = $(e.target).closest('.slide-button');
			if (el.hasClass('next')) {
				slider.trigger('slideNext.slider');
			} else {
				slider.trigger('slidePrev.slider');
			}
			el.blur();
			return false;
		});
		
		/* slide_buttons.click(function(e) {
			e.preventDefault();
			el = $(e.target).closest('.slide-button');
			if (el.hasClass('slide')) {
				el.blur();
				var id = el.data('id');
				slider.trigger('slide.slider',[id,(id > active)]);
			}
			return false;
		}); */		


		
	});

})();

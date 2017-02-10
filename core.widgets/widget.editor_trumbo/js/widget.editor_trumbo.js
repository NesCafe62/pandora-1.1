//	#required {
//	}

(function($) {

	$('.editor-trumbo').each( function() {
		$(this).trumbowyg({
			btns: [
				['viewHTML'],
				['formatting'],
				['bold','italic'],
				['link'],
				['insertImage'],
				'btnGrp-justify',
				'btnGrp-lists',
				['removeformat'],
				['fullscreen']
			],
			removeformatPasted: true,
			/* formatting: {
				dropdown: ['p', 'blockquote', 'h3', 'h4']
			}, */
			lang: 'ru',
			autogrow: true
		});
	});
	
})(jQuery);

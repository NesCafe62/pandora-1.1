//	#required {
//		core.actions
//	}

(function() {

	$('form[name="auth_login"]').each( function() {
		var form = $(this);
		var fld_login = $('input[name="login"]');
		var fld_pass = $('input[name="password"]');
		var inps = fld_login.add(fld_pass);
		var btn_submit = $('.button.submit',form);
		inps.on('keydown', function(e) {
			if (e.which !== 13) {
				return true;
			}
			if ( (fld_login.val() !== '') && (fld_pass.val() !== '') ) {
				btn_submit.click();
				// form.submit();
			}
		});
	});

})();

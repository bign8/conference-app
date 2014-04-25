"use strict";

// jQuery soft scroll
jQuery.fn.scroll_top = function (cb) {
	if ( this.offset() ) jQuery('html, body').animate({
		scrollTop: parseInt( this.offset().top, 10 )
	}, 500, cb);
};
jQuery('a').on('click', function (e){
	if ( e.target.hash ) {
		jQuery( e.target.hash ).scroll_top(function() {
			if (e.target.hash == '#login') $('#inputUser').focus();
			document.location.hash = e.target.hash;
		});
		e.preventDefault();
	}
});

// Profile image
jQuery('#user_image').change(function() {
	if (this.files && this.files[0]) {
		var reader = new FileReader();
		reader.onload = function (e) {
			jQuery('#actual_user_image').attr('src', e.target.result);
		};
		reader.readAsDataURL(this.files[0]);
	}
});
jQuery('#profile_reset').click(function () {
	document.profile.reset();
	$('#actual_user_image').attr('src', 'img/usr/' + $('#orig_image').val());
});

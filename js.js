"use strict";

// jQuery soft scroll
jQuery.fn.scroll_top = function (cb) {
	if ( this.offset() ) {
		jQuery('html, body').animate({
			scrollTop: parseInt( this.offset().top, 10 )
		}, 500, cb);
	}
};
jQuery('ul.nav a,a.navbar-brand').click( function (e){
	if ( e.target.hash ) {
		jQuery( e.target.hash ).scroll_top(function() {
			if (e.target.hash == '#login') $('#inputUser').focus();
			document.location.hash = e.target.hash;
		});
		e.preventDefault();
	}
});
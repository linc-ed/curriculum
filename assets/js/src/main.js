(function ($) {

	"use strict";

	$(window).on('load', function () {
		$('[data-loader="circle-side"]').fadeOut(); // will first fade out the loading animation
		$('#preloader').delay(350).fadeOut('slow'); // will fade out the white DIV that covers the website.
		$('body').delay(350);
		$(window).scroll();
	});
	
	/* Check and radio input styles */
	$('input.icheck').iCheck({
		checkboxClass: 'icheckbox_square-grey',
		radioClass: 'iradio_square-grey'
	});
	
	/* Scroll to top small screens: change the top position offset based on your content*/
	var $Scrolbt = $('button.backward,button.forward');
	var $Element = $('html, body');

		$Scrolbt.on("click", function () {
			$Element.animate({
				scrollTop: 75
			}, "slow");
			return false;
		});
	
	/* Form submit loader */
    $('form').on('submit',function() {
        var form = $("form#wrapped");
        form.validate();
        if (form.valid()) {
            $("#loader_form").fadeIn();
        }
    });
	
	/*  Image popups */
	$('.magnific-gallery').each(function () {
		$(this).magnificPopup({
			delegate: 'a',
			type: 'image',
			gallery: {
				enabled: true
			},
			removalDelay: 500, //delay removal by X to allow out-animation
			callbacks: {
				beforeOpen: function () {
					// just a hack that adds mfp-anim class to markup 
					this.st.image.markup = this.st.image.markup.replace('mfp-figure', 'mfp-figure mfp-with-anim');
					this.st.mainClass = this.st.el.attr('data-effect');
				}
			},
			closeOnContentClick: true,
			midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
		});
	});
	
	/* Price Calculator */
	$("#wrapped").PriceCalc();
	//	Adds Total price and details to hidden inputs that can be used to e-mail data	
	$("form :input, form textarea, form select").change(function () {
	    setTimeout(function () {
	        var total = $('#total_value').html();
	        $('#hidden_total').val(total);
	    }, 150);
	});

})(window.jQuery);
(function ($) {
	'use strict';

	$(function () {

		/* -------------------------------
		 * Close accordion
		 * ------------------------------- */
		$('.et_pb_toggle_title').click(function(){
			var $toggle = $(this).closest('.et_pb_toggle');
			if (!$toggle.hasClass('et_pb_accordion_toggling')) {
				var $accordion = $toggle.closest('.et_pb_accordion');
				if ($toggle.hasClass('et_pb_toggle_open')) {
					$accordion.addClass('et_pb_accordion_toggling');
					$toggle.find('.et_pb_toggle_content').slideToggle(700, function() { 
						$toggle.removeClass('et_pb_toggle_open').addClass('et_pb_toggle_close'); 
					});
				}
				setTimeout(function(){ 
					$accordion.removeClass('et_pb_accordion_toggling'); 
				}, 750);
			}
		});
		
		
		/* -------------------------------
		 * Video Popup
		 * ------------------------------- */
		const $videoLinks = $('a.mdw-video-popup, .mdw-video-popup a');
		if ($videoLinks.length && $.fn.magnificPopup) {
			$videoLinks.magnificPopup({
				type: 'iframe',
				mainClass: 'mfp-fade',
				removalDelay: 160,
				preloader: false,
				fixedContentPos: false
			});
		}
		
		
		/* -------------------------------
		 * Blog Loop
		 * ------------------------------- */
		$('.mdw-blog-loop').each(function() {
			var imgModule = $(this).find('.et_pb_image');
			if (imgModule.find('img').length === 0) {
				$(this).addClass('no-featured-image');
			}
		});
		
	});
})(jQuery);
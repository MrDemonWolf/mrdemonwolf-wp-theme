jQuery(function ($) {
	var cfg = window.mdwServiceMetabox || {};
	var frame;

	$('#mdw-service-upload-btn').on('click', function (e) {
		e.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: cfg.title || 'Select Image',
			button: { text: cfg.buttonText || 'Use this image' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#mdw-service-image').val(attachment.url);
			$('#mdw-service-image-preview').attr('src', attachment.url).show();
			$('#mdw-service-remove-btn').show();
		});

		frame.open();
	});

	$('#mdw-service-remove-btn').on('click', function () {
		$('#mdw-service-image').val('');
		$('#mdw-service-image-preview').hide();
		$(this).hide();
	});
});

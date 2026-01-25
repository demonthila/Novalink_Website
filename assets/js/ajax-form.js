$(function() {
	var form = $('#contact-form');
	var formMessages = $('.ajax-response');

	if (!form.length || !formMessages.length) return;

	form.on('submit', function(e) {
		e.preventDefault();

		var $btn = form.find('button[type="submit"]');
		var btnText = $btn.find('.text-1').text();
		$btn.prop('disabled', true).find('.text-1').text('Sending…');
		formMessages.removeClass('success error').text('');

		$.ajax({
			type: 'POST',
			url: form.attr('action'),
			data: form.serialize()
		})
		.done(function(response) {
			formMessages.removeClass('error').addClass('success').text(response || 'Thank you! Your message has been sent.');
			form.find('input, textarea').val('');
		})
		.fail(function(xhr) {
			formMessages.removeClass('success').addClass('error');
			var msg = (xhr.responseText && xhr.responseText.trim()) ? xhr.responseText.trim() : 'Oops! An error occurred and your message could not be sent. Please try again or email us at info@novalinkinnovations.com.';
			formMessages.text(msg);
		})
		.always(function() {
			$btn.prop('disabled', false).find('.text-1').text(btnText);
		});
	});
});

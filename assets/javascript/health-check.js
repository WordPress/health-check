jQuery(document).ready(function ($) {
	$(".health-check-toc").click(function (e) {
		e.preventDefault();

		// Remove the height of the admin bar, and an extra 10px for better positioning.
		var offset = $( $(this).attr('href') ).offset().top - $("#wpadminbar").height() - 10;

		$('html, body').animate({
			scrollTop: offset
		}, 1200);
	});
});
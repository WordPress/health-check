jQuery(document).ready(function ($) {
	function health_check_failure_modal( markup, action, parent ) {
		$("#dynamic-content").html( markup );
		$(".health-check-modal").data( 'modal-action', action ).data( 'parent-field', parent ).show();
	}

	function health_check_failure_modal_close( modal ) {
		modal.hide();
	}

	$(".modal-close").click(function (e) {
		e.preventDefault();
		health_check_failure_modal_close( $(this).closest('.health-check-modal') );
	});

	$(".health-check-toc").click(function (e) {
		e.preventDefault();

		// Remove the height of the admin bar, and an extra 10px for better positioning.
		var offset = $( $(this).attr('href') ).offset().top - $("#wpadminbar").height() - 10;

		$('html, body').animate({
			scrollTop: offset
		}, 1200);
	});

	$("#loopback-no-plugins").click(function (e) {
		var $trigger = $(this),
			$parent = $(this).closest('td');

		$(this).html( '<span class="spinner" style="visibility: visible;"></span> ' + health_check.string.please_wait );

		e.preventDefault();

		var data = {
			action: 'health-check-loopback-no-plugins'
		};

		$.post(
			ajaxurl,
			data,
			function (response) {
				$trigger.remove();
				if ( true === response.success ) {
					$parent.append(response.data.message);
				} else {
					health_check_failure_modal( response.data, data.action, $parent );
				}
			},
			'json'
		)
	});

	$(".dashboard_page_health-check").on('click', '#loopback-individual-plugins', function (e) {
		var $trigger = $(this),
			$parent = $(this).closest('td');

		$(this).html( '<span class="spinner" style="visibility: visible;"></span> ' + health_check.string.please_wait );

		e.preventDefault();

		var data = {
			action: 'health-check-loopback-individual-plugins'
		};

		$.post(
			ajaxurl,
			data,
			function (response) {
				$trigger.remove();
				if ( true === response.success ) {
					$parent.append(response.data.message);
				} else {
					health_check_failure_modal( response.data, data.action, $parent );
				}
			},
			'json'
		)
	});

	$(".health-check-modal").on('submit', 'form', function (e) {
		var data = $(this).serializeArray(),
			modal = $(this).closest('.health-check-modal');

		e.preventDefault();

		$.post(
			ajaxurl,
			data,
			function (response) {
				if ( true === response.success ) {
					$( modal.data('parent-field') ).append(response.data.message);
				} else {
					health_check_failure_modal( response.data.message, data.action, modal.data('parent-field') );
				}
			}
		);

		health_check_failure_modal_close( modal );
	});
});
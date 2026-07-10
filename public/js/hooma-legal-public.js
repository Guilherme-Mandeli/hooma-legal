(function( $ ) {
	'use strict';

	$(function() {
		// Position the inline document containers above their form fields dynamically to avoid breaking paragraph elements
		$('.hooma-inline-doc-container').each(function() {
			var $container = $(this);
			var targetIdentifier = $container.attr('id').replace('hooma-inline-doc-', '');
			var $wrap = $('.wpcf7-form-control-wrap[data-name="hooma_legal_accept-' + targetIdentifier + '"], .wpcf7-form-control-wrap[data-name="hooma_legal_consent-' + targetIdentifier + '"]');
			
			if ($wrap.length) {
				var $targetElement = $wrap.closest('p').length ? $wrap.closest('p') : $wrap;
				$container.insertBefore($targetElement);
			}
		});

		// Intercept clicks on legal document links with inline viewer targets
		$(document).on('click', 'a[data-hooma-target]', function(e) {
			e.preventDefault();
			var targetIdentifier = $(this).attr('data-hooma-target');
			var $container = $('#hooma-inline-doc-' + targetIdentifier);
			
			if ($container.length) {
				// Toggle the CSS transition open state class
				$container.toggleClass('hooma-is-open');
			}
		});

		// Handle close button inside the inline document viewer
		$(document).on('click', '.hooma-inline-doc-close-btn', function(e) {
			e.preventDefault();
			var targetIdentifier = $(this).attr('data-target');
			var $container = $('#hooma-inline-doc-' + targetIdentifier);
			
			if ($container.length) {
				$container.removeClass('hooma-is-open');
			}
		});
	});

})( jQuery );

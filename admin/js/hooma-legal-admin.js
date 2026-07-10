(function( $ ) {
	'use strict';

	/**
	 * Manage tab switching inside the Hooma Legal options page
	 */
	$(function() {
		$('.hooma-legal-tabs').on('click', '.nav-tab', function(e) {
			e.preventDefault();

			var $tabLink = $(this);
			var targetTab = $tabLink.data('tab');

			// Toggle active tab header class
			$tabLink.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');

			// Toggle active tab content visibility
			$('#tab-' + targetTab).addClass('hooma-legal-active').siblings('.hooma-legal-tab-content').removeClass('hooma-legal-active');

			// Update URL hash without jumping the page
			history.pushState(null, null, '#tab-' + targetTab);
		});

		// Check URL hash on page load to restore active tab
		var hash = window.location.hash;
		if (hash) {
			var tabName = hash.replace('#tab-', '');
			var $tabToActivate = $('.hooma-legal-tabs .nav-tab[data-tab="' + tabName + '"]');
			if ($tabToActivate.length) {
				$tabToActivate.trigger('click');
			}
		}

		// Quick Edit handler for hooma_legal_doc CPT
		$(document).on('click', '.editinline', function() {
			var post_id = $(this).closest('tr').attr('id');
			if (post_id) {
				post_id = post_id.replace('post-', '');
				var version = $('#hooma_version_' + post_id).text().trim();
				
				setTimeout(function() {
					var $editRow = $('#edit-' + post_id);
					$editRow.find('input[name="hooma_version"]').val(version);
				}, 50);
			}
		});

		// Open modal and load snapshot content
		$(document).on('click', '.hooma-view-snapshot-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var snapshotId = $btn.data('id');
			
			// Show loading in modal
			$('#hooma-modal-title').text('Cargando...');
			$('#hooma-modal-meta-version').text('...');
			$('#hooma-modal-meta-date').text('...');
			$('.hooma-modal-meta-bar').show(); // Ensure the meta bar is visible for snapshots
			$('#hooma-modal-content-body').html('<div style="text-align:center; padding: 50px 0;"><span class="spinner is-active" style="float:none; margin:0 auto; display:block; margin-bottom:10px;"></span><p>Obteniendo instantánea histórica...</p></div>');
			$('#hooma-legal-snapshot-modal').fadeIn(150);

			$.ajax({
				url: hooma_legal_admin_params.ajax_url,
				type: 'POST',
				data: {
					action: 'hooma_legal_get_snapshot',
					snapshot_id: snapshotId,
					security: hooma_legal_admin_params.nonce
				},
				success: function(response) {
					if (response.success) {
						$('#hooma-modal-title').text(response.data.title);
						$('#hooma-modal-meta-version').text(response.data.version);
						$('#hooma-modal-meta-date').text(response.data.date);
						$('#hooma-modal-content-body').html(response.data.content);
					} else {
						$('#hooma-modal-title').text('Error');
						$('#hooma-modal-content-body').html('<div class="notice notice-error" style="margin: 20px;"><p>' + response.data + '</p></div>');
					}
				},
				error: function() {
					$('#hooma-modal-title').text('Error');
					$('#hooma-modal-content-body').html('<div class="notice notice-error" style="margin: 20px;"><p>No se pudo conectar con el servidor.</p></div>');
				}
			});
		});

		// Helper function to fetch consents inside the modal with filters
		function fetchModalConsents(versionId, filters = {}) {
			var $contentBody = $('#hooma-modal-content-body');
			$contentBody.css('opacity', '0.5');

			var requestData = $.extend({
				action: 'hooma_legal_get_consents',
				version_id: versionId,
				security: hooma_legal_admin_params.nonce
			}, filters);

			$.ajax({
				url: hooma_legal_admin_params.ajax_url,
				type: 'POST',
				data: requestData,
				success: function(response) {
					$contentBody.css('opacity', '1');
					if (response.success) {
						$('#hooma-modal-title').text(response.data.title);
						$contentBody.html(response.data.content);
					} else {
						$('#hooma-modal-title').text('Error');
						$contentBody.html('<div class="notice notice-error" style="margin: 20px;"><p>' + response.data + '</p></div>');
					}
				},
				error: function() {
					$contentBody.css('opacity', '1');
					$('#hooma-modal-title').text('Error');
					$contentBody.html('<div class="notice notice-error" style="margin: 20px;"><p>No se pudo conectar con el servidor.</p></div>');
				}
			});
		}

		// Open modal and load consents list
		$(document).on('click', '.hooma-view-consents-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var versionId = $btn.data('id');
			
			// Show loading in modal
			$('#hooma-modal-title').text('Cargando...');
			$('.hooma-modal-meta-bar').hide(); // Hide the document-specific version meta bar
			$('#hooma-modal-content-body').html('<div style="text-align:center; padding: 50px 0;"><span class="spinner is-active" style="float:none; margin:0 auto; display:block; margin-bottom:10px;"></span><p>Obteniendo registros de consentimiento...</p></div>');
			$('#hooma-legal-snapshot-modal').fadeIn(150);

			fetchModalConsents(versionId);
		});

		// Click on Filter buttons inside modal
		$(document).on('click', '#hooma-filter-submit-btn', function(e) {
			e.preventDefault();
			var $form = $('#hooma-consents-filter-form');
			var versionId = $form.data('version-id');
			var formData = {};
			$form.serializeArray().forEach(function(item) {
				formData[item.name] = item.value;
			});
			formData['paged'] = 1; // reset page on new filter
			fetchModalConsents(versionId, formData);
		});

		// Clear filters inside modal
		$(document).on('click', '#hooma-filter-clear-btn', function(e) {
			e.preventDefault();
			var $form = $('#hooma-consents-filter-form');
			var versionId = $form.data('version-id');
			fetchModalConsents(versionId, { paged: 1 });
		});

		// Click on pagination links inside modal
		$(document).on('click', '.hooma-modal-paged-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var page = $btn.data('page');
			var $form = $('#hooma-consents-filter-form');
			var versionId = $form.data('version-id');
			var formData = {};
			$form.serializeArray().forEach(function(item) {
				formData[item.name] = item.value;
			});
			formData['paged'] = page;
			fetchModalConsents(versionId, formData);
		});

		// Export filtered consent logs to CSV
		$(document).on('click', '#hooma-export-csv-btn', function(e) {
			e.preventDefault();
			var $form = $('#hooma-consents-filter-form');
			var versionId = $form.data('version-id');
			
			// Build query parameters matching current active filters
			var params = {
				action: 'hooma_legal_export_csv',
				version_id: versionId,
				security: hooma_legal_admin_params.nonce
			};

			$form.serializeArray().forEach(function(item) {
				if (item.value.trim() !== '') {
					params[item.name] = item.value;
				}
			});

			// Construct the download URL using admin-post.php redirect
			var downloadUrl = hooma_legal_admin_params.ajax_url.replace('admin-ajax.php', 'admin-post.php') + '?' + $.param(params);
			
			// Trigger download natively
			window.location.href = downloadUrl;
		});

		// Close modal function
		function closeHoomaModal() {
			$('#hooma-legal-snapshot-modal').fadeOut(150);
		}

		$('#hooma-modal-close-x, #hooma-modal-close-btn').on('click', function(e) {
			e.preventDefault();
			closeHoomaModal();
		});

		// Close on clicking outside modal container
		$('#hooma-legal-snapshot-modal').on('click', function(e) {
			if ($(e.target).is('#hooma-legal-snapshot-modal')) {
				closeHoomaModal();
			}
		});

		// Close on Escape key press
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#hooma-legal-snapshot-modal').is(':visible')) {
				closeHoomaModal();
			}
		});

		// Toggle dropdown menu
		$(document).on('click', '.hooma-dropdown-trigger-btn', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $trigger = $(this);
			var $menu = $trigger.siblings('.hooma-dropdown-menu');
			
			// Close other open menus
			$('.hooma-dropdown-menu').not($menu).hide();
			$('.hooma-dropdown-trigger-btn').not($trigger).attr('aria-expanded', 'false');

			var isVisible = $menu.is(':visible');
			if (isVisible) {
				$menu.hide();
				$trigger.attr('aria-expanded', 'false');
			} else {
				$menu.show();
				$trigger.attr('aria-expanded', 'true');
			}
		});

		// Close menu when clicking actions inside it
		$(document).on('click', '.hooma-dropdown-menu a', function() {
			$('.hooma-dropdown-menu').hide();
			$('.hooma-dropdown-trigger-btn').attr('aria-expanded', 'false');
		});

		// Close menu on clicking anywhere else
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.hooma-dropdown-wrapper').length) {
				$('.hooma-dropdown-menu').hide();
				$('.hooma-dropdown-trigger-btn').attr('aria-expanded', 'false');
			}
		});

		var deleteVersionId = null;

		// Open delete confirmation modal
		$(document).on('click', '.hooma-delete-version-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			deleteVersionId = $btn.data('id');
			$('#hooma-legal-delete-modal').fadeIn(150);
			$('#hooma-delete-modal-confirm-btn').focus();
		});

		// Close delete modal function
		function closeHoomaDeleteModal() {
			deleteVersionId = null;
			$('#hooma-legal-delete-modal').fadeOut(150);
		}

		$('#hooma-delete-modal-close-x, #hooma-delete-modal-cancel-btn').on('click', function(e) {
			e.preventDefault();
			closeHoomaDeleteModal();
		});

		// Close on clicking outside delete modal
		$('#hooma-legal-delete-modal').on('click', function(e) {
			if ($(e.target).is('#hooma-legal-delete-modal')) {
				closeHoomaDeleteModal();
			}
		});

		// Confirm delete AJAX request
		function executeVersionDeletion() {
			if (!deleteVersionId) return;

			var $confirmBtn = $('#hooma-delete-modal-confirm-btn');
			$confirmBtn.prop('disabled', true).text('Eliminando...');

			$.ajax({
				url: hooma_legal_admin_params.ajax_url,
				type: 'POST',
				data: {
					action: 'hooma_legal_delete_version',
					version_id: deleteVersionId,
					security: hooma_legal_admin_params.nonce
				},
				success: function(response) {
					if (response.success) {
						window.location.reload();
					} else {
						alert(response.data);
						$confirmBtn.prop('disabled', false).text('Confirmar');
						closeHoomaDeleteModal();
					}
				},
				error: function() {
					alert('No se pudo conectar con el servidor.');
					$confirmBtn.prop('disabled', false).text('Confirmar');
					closeHoomaDeleteModal();
				}
			});
		}

		$('#hooma-delete-modal-confirm-btn').on('click', function(e) {
			e.preventDefault();
			executeVersionDeletion();
		});

		// Keydown handlers for Escape and Enter on the delete modal
		$(document).on('keydown', function(e) {
			if ($('#hooma-legal-delete-modal').is(':visible')) {
				if (e.key === 'Escape') {
					closeHoomaDeleteModal();
				} else if (e.key === 'Enter') {
					e.preventDefault();
					executeVersionDeletion();
				}
			}
		});
	});

})( jQuery );

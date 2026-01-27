/**
 * Simple Fundraiser - Admin Scripts
 */
jQuery(document).ready(function ($) {

	// QRIS Image Upload
	$('.sf-upload-qris').on('click', function (e) {
		e.preventDefault();

		var button = $(this);
		var input = $('#sf_qris_image');
		var preview = $('.sf-qris-preview');
		var removeBtn = $('.sf-remove-qris');

		var frame = wp.media({
			title: 'Select QRIS Image',
			button: {
				text: 'Use this image'
			},
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			input.val(attachment.id);
			preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
			removeBtn.show();
		});

		frame.open();
	});

	// Remove QRIS Image
	$('.sf-remove-qris').on('click', function (e) {
		e.preventDefault();
		$('#sf_qris_image').val('');
		$('.sf-qris-preview').html('');
		$(this).hide();
	});

	// Dynamic Donation Types
	var campaignSelect = $('#sf_campaign_id');
	var typeSelect = $('#sf_donation_type');

	if (campaignSelect.length > 0 && typeSelect.length > 0) {
		campaignSelect.on('change', function () {
			var campaignId = $(this).val();
			var currentType = typeSelect.val(); // Preserve value if possible

			typeSelect.empty();
			typeSelect.append('<option value="">— Select Type —</option>');

			if (campaignId && sf_admin_data.campaign_types[campaignId]) {
				var types = sf_admin_data.campaign_types[campaignId];
				$.each(types, function (i, type) {
					typeSelect.append('<option value="' + type + '">' + type + '</option>');
				});

				// Restore value if it exists in new options
				if (currentType) {
					typeSelect.val(currentType);
				}
			}
		});

		// Trigger change on load if campaign is selected but type options are empty (and types exist)
		// But PHP handles initial rendering, so we might only need this if PHP didn't populate options fully.
		// Actually, our PHP implementation only populates the *selected* option.
		// So we should trigger change to populate ALL options, but select the current one.
		if (campaignSelect.val()) {
			campaignSelect.trigger('change');
		}
	}

});

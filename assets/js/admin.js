/**
 * Simple Fundraiser - Admin Scripts
 */
jQuery(document).ready(function($) {
	
	// QRIS Image Upload
	$('.sf-upload-qris').on('click', function(e) {
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
		
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			input.val(attachment.id);
			preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
			removeBtn.show();
		});
		
		frame.open();
	});
	
	// Remove QRIS Image
	$('.sf-remove-qris').on('click', function(e) {
		e.preventDefault();
		$('#sf_qris_image').val('');
		$('.sf-qris-preview').html('');
		$(this).hide();
	});
	
});

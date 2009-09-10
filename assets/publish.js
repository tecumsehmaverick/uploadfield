jQuery(document).ready(function() {
	jQuery('.field-advancedupload').each(function() {
		var field = jQuery(this);
		var upload = field.find('.upload');
		var details = field.find('.details');
		var details_clear = details.find('.clear a');
		var details_popup = details.find('.popup a');
		var preview = field.find('.preview');
		var preview_image = preview.find('img');
		
		if (details.length) {
			upload.hide();
			
			details_clear.bind('click', function() {
				var hidden = upload.find('input[type = "hidden"]');
				var file = jQuery('<input type="file" />');
				
				file.attr('name', hidden.attr('name'));
				file.appendTo(upload);
				hidden.remove();
				
				details.hide();
				preview.hide();
				upload.show();
			});
			
			if (preview.length) {
				details.hide();
				
				preview.bind('mouseenter', function() {
					details.fadeIn('fast');
				});
				details.bind('mouseleave', function() {
					details.fadeOut('fast');
				});
				
				details_popup.bind('click', function() {
					jQuery('<div class="field-advancedupload-overlay" />')
						.append(preview_image.clone())
						.appendTo('body').bind('click',
							function() {
								jQuery(this).remove();
							});
				
					return false;
				});
			}
		}
	});
});
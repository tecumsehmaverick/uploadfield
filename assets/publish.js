jQuery(document).ready(function() {
	jQuery('.field-advancedupload').each(function() {
		var field = jQuery(this);
		var details = field.find('.details');
		var details_clear = details.find('.clear');
		var upload = field.find('.upload');
		var preview = details.find('.preview');
		var preview_image = preview.find('img');
		
		if (details.length) {
			var top = ((details.height() + 24) - preview.height()) / 2;
			var left = (preview.width() - preview_image.width()) / 2;
			
			if (top > 0) preview.css('top', top + 'px');
			if (left > 0) preview.css('left', left + 'px');
			
			upload.hide();
			
			details_clear.bind('click', function() {
				var hidden = upload.find('input[type = "hidden"]');
				var file = jQuery('<input type="file" />');
				
				file.attr('name', hidden.attr('name'));
				file.appendTo(upload);
				hidden.remove();
				
				details.hide();
				upload.show();
			});
			
			preview_image.bind('click', function() {
				jQuery('<div class="field-advancedupload-overlay" />')
					.append(preview_image.clone())
					.appendTo('body').bind('click',
						function() {
							jQuery(this).remove();
						});
			});
		}
	});
});
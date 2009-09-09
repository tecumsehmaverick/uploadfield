jQuery(document).ready(function() {
	jQuery('.field-advancedupload').each(function() {
		var field = jQuery(this);
		var details = field.find('.details');
		var upload = field.find('.upload');
		
		if (details.length) {
			upload.hide();
			
			details.find('.clear').bind('click', function() {
				var hidden = upload.find('input[type = "hidden"]');
				var file = jQuery('<input type="file" />');
				
				file.attr('name', hidden.attr('name'));
				file.appendTo(upload);
				hidden.remove();
				
				details.hide();
				upload.show();
			});
			
			details.find('.preview').bind('click', function() {
				var preview = details.find('.preview img');
				var overlay = jQuery('<div class="field-advancedupload-overlay" />');
				
				overlay.append(preview.clone());
				overlay.appendTo('body');
				overlay.bind('click', function() {
					overlay.remove();
				});
			});
		}
	});
});
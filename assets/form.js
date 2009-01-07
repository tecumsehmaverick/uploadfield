$.fn.insertAtCaret = function (value) {
	return this.each(function() {
		// Trident:
		if (document.selection) {
			this.focus();
			sel = document.selection.createRange();
			sel.text = value;
			this.focus();
		}
		
		// Gecko:
		else if (this.selectionStart || this.selectionStart == '0') {
			var startPos = this.selectionStart;
			var endPos = this.selectionEnd;
			var scrollTop = this.scrollTop;
			this.value = this.value.substring(0, startPos) + value + this.value.substring(endPos,this.value.length);
			this.focus();
			this.selectionStart = startPos + value.length;
			this.selectionEnd = startPos + value.length;
			this.scrollTop = scrollTop;
				
		// Failsafe:
		} else {
			this.value += value;
			this.focus();
		}
	});
};

$(document).ready(function() {
	$('.imageuploadfield img').draggable({
		helper: function() {
			var html = '<span class="imageuploadfield-helper">%</span>';
			var text = $(this).attr('alt');
			
			text = text.replace('&', '&amp;')
				.replace('<', '&lt;')
				.replace('>', '&gt;')
				.replace('"', '&quot;');
				
			html = html.replace('%', text);
			
			return $(html).appendTo('body');
		},
		containment: 'document',
		opacity: 0.8
	});
	$('textarea, input').droppable({
		accept: '.imageuploadfield img',
		drop: function(env, ui) {
			$(this).insertAtCaret(ui.draggable.attr('alt'));
		}
	});
});
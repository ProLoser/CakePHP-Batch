$(document).ready(function(){
	$('form.batch').delegate('tr', 'click', function(event) {
	    var $this = $(this);
	    var $box = $this.find('input.batch');
	    console.log('called');

	    if ($box.is(':checked')) {
	        $box.removeAttr('checked');
	        $this.removeClass('checked');
	    } else {
	        $box.attr('checked', 'checked');
	        $this.addClass('checked');
	    }
	}).delegate('input.batch', 'click', function(event) {
	    event.stopPropagation();
	    var $box = $(this);

	    if ($box.is(':checked')) {
	        $box.closest('tr').addClass('checked');
	    } else {
	        $box.closest('tr').removeClass('checked');
	    }
	});
	$('input.batch-all').live('click', function(){
		if ($(this).is(':checked')) {
			$('form.batch input.batch').attr('checked','checked').closest('tr').addClass('checked');
		} else {
			$('form.batch input.batch').removeAttr('checked').closest('tr').removeClass('checked');
		}
	});
	$('tr.batch').delegate('input:checkbox', 'change', function() {
		$this = $(this);
		$el = $this.next('input, select');
		if ($el.is(':enabled') && !$this.is(':checked')) {
			$el.attr('disabled', 'disabled');
		} else {
			$el.removeAttr('disabled');
		}
	});
});
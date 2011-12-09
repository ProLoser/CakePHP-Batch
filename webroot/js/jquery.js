$(document).ready(function(){
    $('form.batch').delegate('tr', 'click', function(event) {
        var $this = $(this);
        var $box = $this.find('input.batch');

        if ($box.is(':checked')) {
            $box.removeAttr('checked');
            $this.removeClass('checked');
        } else {
            $box.attr('checked', 'checked');
            $this.addClass('checked');
        }
    }).delegate('input, a', 'click', function(event) {
        event.stopPropagation();
        var $el = $(this);
		if ($el.is('input.batch')) {
	        if ($el.is(':checked')) {
	            $el.closest('tr').addClass('checked');
	        } else {
	            $el.closest('tr').removeClass('checked');
	        }
		}
    }).delegate('input.batch-all', 'click', function(event){
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
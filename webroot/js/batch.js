$(document).ready(function(){
	$('form.batch tr *').live('click', function(){
		if (!$(this).is('a, input')) {
			$box = $(this).closest('tr').find('input.batch');
			if ($box.is(':checked')) {
				$box.removeAttr('checked').closest('tr').removeClass('checked');
			} else {
				$box.attr('checked','checked').closest('tr').addClass('checked');
			}
		}
	});
	$('form.batch tr input.batch').live('change', function(){
		$box = $(this);
		if ($box.is(':checked')) {
			$box.closest('tr').addClass('checked');
		} else {
			$box.closest('tr').removeClass('checked');
		}
	});
});
$(document).ready(function(){
	$('form.batch tr *').live('click', function(){
		if (!$(this).is('a, input')) {
			$box = $(this).closest('tr').find('input.batch');
			if ($box.is(':checked')) {
				$box.removeAttr('checked');
			} else {
				$box.attr('checked','checked');
			}
		}
	});
});
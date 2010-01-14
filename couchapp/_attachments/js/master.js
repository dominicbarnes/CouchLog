$(document).ready(function() {
	// jQuery UI ThemeRoller
	$('table').addClass('ui-widget');
	$('th').addClass('ui-widget-header');
	$('td').addClass('ui-widget-content');
	$('thead tr').addClass('ui-widget-header');
	$('tbody tr').hover(
		function() { $(this).addClass('ui-state-hover'); },
		function() { $(this).removeClass('ui-state-hover'); }
	);
});
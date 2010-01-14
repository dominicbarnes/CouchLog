$(document).ready(function() {
	$('#btnFilter').click(function() {
		$(this).addClass('ui-helper-hidden');
		$('#frmFilter').removeClass('ui-helper-hidden');
	});

	$('#btnSubmit').click(function() {
		var start = $('#txtDateStart').val();
		var end = $('#txtDateEnd').val();

		if (start == '' || end == '')
		{
			alert('Please enter both a Start Date and an End Date');
			return false;
		}
		else if (start > end)
		{
			alert('The Starting Date cannot come after the Ending Date');
			return false;
		}
		else if (start == end)
		{
			alert('The Starting Date and Ending Date cannot be the same date');
			return false;
		}

		var base = $('#hdnBasePath').val(),
			view = $('#hdnView').val(),
			startkey = $('#hdnStartKey').val(),
			endkey = $('#hdnEndKey').val();

		if (startkey != '')
			startkey = JSON.parse(startkey);
		else
			startkey = null;

		if (endkey != '')
			endkey = JSON.parse(endkey);
		else
			endkey = null;

		start /= 1000;
		end /= 1000;

		var new_url;
		switch (view) {
			case 'entries-by-app':
				startkey[1] = end;
				endkey[1] = start;
				new_url = makePath(base.split('/').concat(['_list', 'browse', 'entries-by-app']), {
					descending: true,
					startkey: startkey,
					endkey: endkey
				});
				break;

			case 'entries-by-app-section':
				startkey[2] = end;
				endkey[2] = start;
				new_url = getPath(base, '_list', 'browse', 'entries-by-app-section', {
					descending: true,
					startkey: startkey,
					endkey: endkey
				});
				break;

			case 'entries-by-app-level':
				startkey[2] = end;
				endkey[2] = start;
				new_url = getPath(base, '_list', 'browse', 'entries-by-app-level', {
					descending: true,
					startkey: startkey,
					endkey: endkey
				});
				break;

			case 'entries-recent':
				new_url = '?descending=true&startkey=' + end + '&endkey=' + start;
				break;
		}

		window.location = unescape(new_url);
	});

	$('.datePicker').datepicker({
		dateFormat: '@'
	});

	$('.tooltip').qtip({
		position: {
			corner: {
				target: 'bottomLeft',
				tooltip: 'topRight'
			}
		},
		style: {
			name: 'blue',
			padding: 10,
			border: {
				width: 3,
				radius: 5
			},
			width: {
				min: 250,
				max: 500
			}
		}
	});
});
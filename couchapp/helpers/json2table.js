function json2table(json, tbl_attr) {
	var tmp = '<table class="ui-widget" ' + tbl_attr + '>';
	for (var key in json)
	{
		tmp += '<tr><th class="ui-widget-header">' + key + '</th>';
		if (typeof json[key] === 'object')
			tmp += '<td class="ui-widget-content">' + json2table(json[key], tbl_attr) + '</td>';
		else
			tmp += '<td class="ui-widget-content">' + json[key] + '</td>';
		tmp += '</tr>';
	}
	tmp += '</table>';
	return tmp;
}
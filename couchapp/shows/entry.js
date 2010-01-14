function(doc, req) {
	// !json config
	// !json templates.global
	// !json templates.show
	// !code helpers/dateFormat.js
	// !code helpers/json2table.js
	// !code vendor/couchapp/path.js
	// !code vendor/couchapp/template.js

	var basePath = assetPath();
	var indexPath = listPath('browse', 'entries-recent', {descending: true});
	var homePath = listPath('home', 'applications', { group: true });

	doc.timestamp = dateFormat(new Date(doc.timestamp * 1000), 'mmm dd, yyyy HH:MM:ss.l');

	if (doc.data)
		doc.data = json2table(doc.data, 'class="data" border="1" cellpadding="5"');

	var content = template(templates.show.entry, doc);

	var masterOptions = {
		config: config,
		index: homePath,
		base: basePath,
		stylesheets: ['custom-theme/jquery-ui-1.7.2.custom.css'],
		scripts: ['jquery-1.3.2.min.js','jquery-ui-1.7.2.custom.min.js','jquery.qtip-1.0.0-rc3.min.js','browse.js'],
		content: content,
		req: req,
		debug: null
	};

	if (config.debug = req.query.debug || config.debug)
		masterOptions.debug = '<div id="debug">' + json2table(req, 'border="1" cellpadding="5"') + '</div>';

	// we only show html
	return template(templates.global.master, masterOptions);
}
function(head, req) {
	// !json config
	// !json templates.home
	// !json templates.global
	// !code helpers/json2table.js
	// !code vendor/couchapp/path.js
	// !code vendor/couchapp/template.js

	var basePath = assetPath();

	// render the html head using a template
	var content = template(templates.home.head, { base: basePath });
	var homePath = listPath('home', 'applications', { group: true });

	// loop over view rows, rendering one at a time
	var row;
	while (row = getRow()) {
		content += template(templates.home.row, {
			app: row.key,
			url: listPath('browse', 'entries-by-app', {
				descending: true,
				startkey: [row.key, {}],
				endkey: [row.key]
			})
		});
	}

	// render the html tail template
	content += template(templates.home.tail, {});

	var masterOptions = {
		config: config,
		index: homePath,
		base: basePath,
		stylesheets: null,
		scripts: null,
		content: content,
		req: req,
		debug: null
	};

	if (config.debug = req.query.debug || config.debug)
		masterOptions.debug = '<div id="debug">' + json2table(req, 'border="1" cellpadding="5"') + '</div>';

	return template(templates.global.master, masterOptions);
};
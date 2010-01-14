function(head, req) {
	// !json config
	// !json templates.browse
	// !json templates.global
	// !code helpers/dateFormat.js
	// !code helpers/json2table.js
	// !code vendor/couchapp/path.js
	// !code vendor/couchapp/template.js

	registerType("log", "text/plain; charset=utf-8", "txt");

	var basePath = assetPath();
	var homePath = listPath('home', 'applications', { group: true });
	var browsePath = listPath('browse', 'entries-recent', { descending: true });
	var currentPath = '/' + currentPath();

	// The provides function serves the format the client requests.
	// The first matching format is sent, so reordering functions changes
	// thier priority. In this case HTML is the preferred format, so it comes first.
	provides("html", function() {
		var skip = parseInt(req.query.skip || 0);
		var limit = parseInt(req.query.limit || 0);

		// render the html head using a template
		var content = template(templates.browse.head, {
			currentPath: currentPath,
			browsePath: browsePath,
			basePath: basePath,
			req: req
		});

		// loop over view rows, rendering one at a time
		var row;
		while (row = getRow()) {
			doc = row.value;
			doc.timestamp = dateFormat(new Date(doc.timestamp * 1000), 'mmm dd, yyyy HH:MM:ss.L');

			if (doc.data)
				doc.data = json2table(doc.data, 'border="1" cellpadding="5"');

			content += template(templates.browse.row, {
				data: row.value,
				paths: {
					base: basePath,
					byApp: listPath('browse', 'entries-by-app', {
						descending: true,
						startkey: [doc.application, {}],
						endkey: [doc.application]
					}),
					bySection: listPath('browse', 'entries-by-app-section', {
						descending: true,
						startkey: [doc.application, doc.section, {}],
						endkey: [doc.application, doc.section]
					}),
					byLevel: listPath('browse', 'entries-by-app-level', {
						descending: true,
						startkey: [doc.application, doc.level, {}],
						endkey: [doc.application, doc.level]
					}),
					showEntry: showPath('entry', doc._id)
				}
			});
		}

		// render the html tail template
		content += template(templates.browse.tail, {
			browsePath: browsePath
		});

		var masterOptions = {
			config: config,
			index: homePath,
			base: basePath,
			stylesheets: null,
			scripts: ['jquery.qtip-1.0.0-rc3.min.js','path.js','browse.js'],
			content: content,
			req: req,
			debug: null
		};

		if (config.debug = req.query.debug || config.debug)
			masterOptions.debug = '<div id="debug">' + json2table(req, 'border="1" cellpadding="5"') + '</div>';

		return template(templates.global.master, masterOptions);
	});

	provides("log", function() {
		start({ headers: {
			'Location': homePath,
			'Content-Disposition': 'attachment; filename="'+ dateFormat(new Date(), 'mm-dd-yyyy') +'.log"'
		} });

		var row = getRow();

		// loop over all rows
		while (row = getRow())
		{
			var entry = row.value;

			// generate the line for this entry
			var feedEntry = '[' + dateFormat(new Date(entry.timestamp * 1000), 'mmm dd, yyyy HH:MM:ss.l') + '] '
				+ '[' + entry.section + '] '
				+ '[' + entry.level + '] '
				+ entry.message + '\n';

			// send the line to client
			send(feedEntry);
		}

		// close the loop after all rows are rendered
		return '\n';
	});
};
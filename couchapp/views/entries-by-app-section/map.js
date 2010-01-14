function(doc) {
	if (doc.application && doc.section && doc.timestamp)
		emit([doc.application, doc.section, doc.timestamp], doc);
};
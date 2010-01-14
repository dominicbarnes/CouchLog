function(doc) {
	if (doc.application && doc.timestamp)
		emit([doc.application, doc.timestamp], doc);
};
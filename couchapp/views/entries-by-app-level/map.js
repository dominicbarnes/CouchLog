function(doc) {
	if (doc.application && doc.level && doc.timestamp)
		emit([doc.application, doc.level, doc.timestamp], doc);
};
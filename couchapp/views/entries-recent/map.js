function(doc) {
	if (doc.application && doc.timestamp)
		emit(doc.timestamp, doc);
};
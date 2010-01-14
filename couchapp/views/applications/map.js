function(doc) {
	if (doc.application)
		emit(doc.application, doc);
};
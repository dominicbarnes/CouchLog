// from couch.js
function encodeOptions(options, noJson) {
	var buf = []
	if (typeof(options) == "object" && options !== null) {
		for (var name in options) {
			if (!options.hasOwnProperty(name)) continue;
			var value = options[name];
			if (!noJson && (name == "key" || name == "startkey" || name == "endkey")) {
				value = JSON.stringify(value);
			}
			buf.push(encodeURIComponent(name) + "=" + encodeURIComponent(value));
		}
	}
	if (!buf.length) {
		return "";
	}
	return "?" + buf.join("&");
}

function makePath(path, options) {
	path = path.map(function(item) {return encodeURIComponent(item)}).join('/');

	if (options) {
		return path + encodeOptions(options);
	} else {
		return path;
	}
};
OC.MimeType = {

	mimeTypeIcons: {},
	
	getIconUrl: function(mimeType) {
		if (_.isUndefined(mimeType)) {
			return undefined;
		}

		if (mimeType in OC.MimeTypes.aliases) {
			mimeType = OC.MimeTypes.aliases[mimeType];
		}
		if (mimeType in OC.MimeType.mimeTypeIcons) {
			return OC.MimeType.mimeTypeIcons[mimeType];
		}

		var icon = mimeType.replace(new RegExp('/', 'g'), '-');
		//icon = icon.replace(new RegExp('\\', 'g'), '-');

		var path = OC.webroot + '/core/img/filetypes/';

		// Generate path
		if (mimeType === 'dir') {
			path += 'folder';
		} else if (mimeType === 'dir-shared') {
			path += 'folder-shared';
		} else if (mimeType === 'dir-external') {
			path += 'folder-external';
		} else if ($.inArray(icon, OC.MimeTypes.files)) {
			path += icon;
		} else if ($.inArray(icon.split('-')[0], OC.MimeTypes.files)) {
			path += icon.split('-')[0];
		} else {
			path += 'file';
		}

		// Use svg if we can
		if(OC.Util.hasSVGSupport()){
			path += '.svg';
		} else {
			path += '.png';
		}

		// Cache the result
		OC.MimeType.mimeTypeIcons[mimeType] = path;
		return path;
	}

};



if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('en_US', {
		'EventManagement.REGISTRATIONEXPIRED': 'Sorry, but your registration session'
			+ ' has expired and your tickets have been released. Please try registering again.'
	});
}

$(function() {
	if (typeof PHORK.nav != 'undefined') {
		new PHORK.nav($('#nav-errors')).append([
			{ title: "403 error page", href: '/403/' },
			{ title: "404 error page", href: '/404/', spacer: true },
			{ title: "500 error page", href: '/500/' }
		]).offset = {top: 15, left: -2};
		
		new PHORK.nav($('#nav-settings')).append([
			{ title: "account settings", href: '/account/settings/' },
			{ title: "profile settings", href: '/account/profile/' }
		]).offset = {top: 15, left: -2};
	}
});
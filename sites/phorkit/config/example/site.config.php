<?php
	//comment this line out when the site has been installed
	require('installing.config.php');
	

	//the default title for the public pages
	$arrConfig['SiteTitle'] = 'Phork/it';
	
	//the theme to use for the site; themes must have their own templates
	$arrConfig['Theme'] = 'default';
	
	//whether the site is in private beta and requires authentication and any multi use codes
	$arrConfig['PrivateBeta'] = false;
	$arrConfig['BetaPromoCodes'] = array('YOUR_SECRET_BETA_CODE');
	
	//the secret key to add to encoded action urls and the number of seconds the key is good for
	$arrConfig['ActionKey'] = 'YOUR_SECRET_ACTION_KEY';
	$arrConfig['ActionKeyTTL'] = 600;
	
	//all session tokens are joined with this to further increase security
	$arrConfig['FingerprintSessionSalt'] = 'YOUR_SESSION_SALT';
	
	
	/*******************************************/
	/**     THIRD PARTY APPS                  **/
	/*******************************************/
	
	
	//whether to use postmark to send emails (requires postmark.config.php)
	$arrConfig['PostmarkEnabled'] = false;
	
	//whether to use facebook to connect accounts (requires facebook.config.php)
	$arrConfig['FacebookConnect'] = false;
	
	//whether to use twitter to connect accounts (requires twitter.config.php)
	$arrConfig['TwitterConnect'] = false;
	
	
	/*******************************************/
	/**     URLS                              **/
	/*******************************************/
	
	
	//the site urls
	$arrConfig['SiteUrl'] = 'http://example.org';
	$arrConfig['SecureUrl'] = 'https://example.org';
	$arrConfig['ApiUrl'] = 'http://api.example.org';
	$arrConfig['WidgetUrl'] = 'http://example.org';
	$arrConfig['ImageUrl'] = 'http://example.org';
	$arrConfig['CssUrl'] = '';
	$arrConfig['JsUrl'] = '';
	
	//the url of the front controller (no trailing slash) excluding the filename if using mod rewrite
	//$arrConfig['BaseUrl'] = '';					//mod rewrite enabled
	$arrConfig['BaseUrl'] = '/index.php';			//no mod rewrite
	
	//the domain to use for cookies
	$arrConfig['CookieDomain'] = 'example.org';
	
		
	/*******************************************/
	/**     ACCOUNTS                          **/
	/*******************************************/
	
	
	//the maximum number of concurrent logins per-user
	$arrConfig['MaxConcurrentLogins'] = 5;
	
	//whether the user needs to be reverified when their email changes
	$arrConfig['EmailReverify'] = true;
	
	//the number of seconds a temporary password is good for (12 hrs)
	$arrConfig['TempPasswordTTL'] = 43200;
	
	//the names of account session and cookie vars
	$arrConfig['UserCookieName'] = 'u';
	$arrConfig['UserIdSessionName'] = 'id';
	$arrConfig['UserObjectSessionName'] = '_u';
	$arrConfig['FingerprintSessionName'] = '_f';
	$arrConfig['FacebookSessionName'] = '_fb';
	$arrConfig['TwitterSessionName'] = '_tw';
	
	//flags to fire the login/logout action if the hook has been registered
	$arrConfig['LogoutFlag'] = 'logout';
	$arrConfig['LoginFlag'] = 'login';
	
	//the form field names for the login username and password
	$arrConfig['LoginUsernameField'] = 'username';
	$arrConfig['LoginPasswordField'] = 'password';
	
	//the names of the models to use when logging in users
	$arrConfig['UserModel'] = 'UserModel';
	$arrConfig['UserLoginModel'] = 'UserLoginModel';
	
	
	/*******************************************/
	/**     REQUEST VARS                      **/
	/*******************************************/
	
	
	//the names of various session and cookie vars
	$arrConfig['DebugSessionName'] = '_d';
	$arrConfig['TokenSessionName'] = '_t';
	$arrConfig['HistorySessionName'] = '_h';
	$arrConfig['AlertSessionName'] = '_a';
	
	//the name of the form field containing the token used to verify post data
	$arrConfig['TokenField'] = '_t';
	
	
	/*******************************************/
	/**     SESSIONS                          **/
	/*******************************************/
	
	
	//whether the sessions should be enabled
	$arrConfig['SessionsEnabled'] = true;
	
	//session garbage collection settings
	$arrConfig['SessionGcProbability'] = 1/1000;
	$arrConfig['SessionGcLifetime'] = 86400;
	
	//the custom session handler, if any
	//$arrConfig['SessionHandler'] = 'SessionDatabase';
	
	
	/*******************************************/
	/**     CSS & JS CONCAT                   **/
	/*******************************************/
	
	
	//the CSS and JS versions for cache busting
	$arrConfig['CssVersion'] = 1;
	$arrConfig['JsVersion'] = 1;
	
	//the domains that are trusted for CSS and JS files
	$arrConfig['AssetUrls'] = array(
		$arrConfig['CssUrl'],
		$arrConfig['JsUrl']
	);
	
	//the paths that are trusted for CSS and JS files
	$arrConfig['AssetPaths'] = array(
		AppConfig::get('SiteDir') . 'htdocs/css/',
		AppConfig::get('SiteDir') . 'htdocs/js/',
		AppConfig::get('SiteDir') . 'htdocs/lib/'
	);
	
	//whether to display the raw CSS and JS
	$arrConfig['NoConcat'] = true;
	
	
	/*******************************************/
	/**     PAGE CACHE                        **/
	/*******************************************/
	
	
	//define the url patterns for full page caches
	$arrConfig['CacheUrls'] = array(
		'#^/concat/(.*)#'	=> array(
			'Namespace'		=> null,
			'Expire'		=> 300,
			'Compress'		=> true
		)
	);
	
	
	/*******************************************/
	/**     ROUTING                           **/
	/*******************************************/
	
	
	//route the css and javascript
	$arrConfig['Routes']['^/concat/(css|js)/([0-9]*)/([^/]*)/[a-z]+.(css|js)$'] = '/concat/$1/version=$2/files=$3/';
	
	//route the user pages
	$arrConfig['Routes']['^/user/([a-zA-Z0-9]+)/?$'] = '/user/profile/user=$1/';
	$arrConfig['Routes']['^/user/([a-zA-Z0-9]+)/(.+)/?$'] = '/user/$2/user=$1/';
	
	//route the account pages
	$arrConfig['Routes']['^/account/password/?$'] = '/account/forgotPassword/';
	$arrConfig['Routes']['^/account/recover/?$'] = '/account/resetPassword/';
	$arrConfig['Routes']['^/account/settings/([a-z]+)/?$'] = '/account/$1/';
	
	//route the miscellaneous pages
	$arrConfig['Routes']['^/(about|contact|help|terms|privacy)/?$'] = '/misc/$1/';
	
	//route the logout URL to the site controller
	$arrConfig['Routes']['^/logout/$'] = '/site/logout/';
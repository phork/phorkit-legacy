<?php
	//the facebook connect args from http://www.facebook.com/developers/apps.php?app_id=YOUR_APP_ID
	$arrConfig['FacebookApiKey'] = 'YOUR_API_KEY';
	$arrConfig['FacebookAppSecret'] = 'YOUR_APP_SECRET';
	$arrConfig['FacebookAppId'] = 'YOUR_APP_ID';
	
	//the default permissions just to access the site
	$arrConfig['FacebookBasePerms'] = '';
	
	//the permissions to share the user's activity
	$arrConfig['FacebookSharePerms'] = 'publish_stream,offline_access';
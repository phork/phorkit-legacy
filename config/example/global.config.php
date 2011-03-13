<?php
	//the language to use, if this isn't defined nothing will be translated
	//$arrConfig['Language'] = 'english';
	//$arrConfig['LangCache'] = AppConfig::get('FilesDir') . 'app/lang';
	
	//the timezone of the server (http://us.php.net/manual/en/timezones.php)
	$arrConfig['Timezone'] = 'America/Los_Angeles';
	date_default_timezone_set($arrConfig['Timezone']);

	//the PHP CLI path
	$arrConfig['PhpCli'] = '/usr/bin/php';
	
	//the email addresses for the site
	$arrConfig['EmailNoRespond'] = 'noreply@example.org';
	
	//whether to filter out certain records (eg. banned, unpublished) by default
	$arrConfig['FilterRecords'] = true;
	
	
	/*******************************************/
	/**     DATA STORAGE                      **/
	/*******************************************/
	
	
	//whether the database should be enabled
	$arrConfig['DatabaseEnabled'] = false;
	
	//whether caching should be enabled
	$arrConfig['CacheEnabled'] = false;
	$arrConfig['NodeCacheEnabled'] = false;
	
	//the storage types to use for uploaded files
	$arrConfig['FileSystem'] = 'Local';
	
	//the uploaded file paths relative to the files dir
	$arrConfig['PublicFilePath'] = 'public/';
	$arrConfig['AvatarFilePath'] = 'avatar/';
	
	
	/*******************************************/
	/**     CACHE KEYS                        **/
	/*******************************************/
	
	
	//the cache keys for the various API namespaces
	$arrConfig['UserEventNamespace'] = 'user-event-ns:%d';
	$arrConfig['UserConnectionNamespace'] = 'user-connection-ns:%d';
	$arrConfig['UserTagNamespace'] = 'user-tag-ns:%s';
	$arrConfig['DeletedItemCache'] = 'user-deleted:%s:%d';
	
	
	/*******************************************/
	/**     ROLES & USERS                     **/
	/*******************************************/
	
	
	//the developer role is a special role
	$arrConfig['DeveloperRole'] = 1;
	
	//the default user ID and username for the robot
	$arrConfig['SystemBotUserId'] = 1;
	$arrConfig['SystemBotUsername'] = 'admin';
	
	
	/*******************************************/
	/**     FILES & IMAGES                    **/
	/*******************************************/
	
	
	//the url of the public files base directory
	$arrConfig['FilesUrl'] = '/files/';
	
	//the avatar names and optional resize dimensions
	$arrConfig['Avatar']['Full']['Name'] = 'full';
	$arrConfig['Avatar']['Tiny']['Name'] = 'tiny';
	$arrConfig['Avatar']['Tiny']['Width'] = 25;
	$arrConfig['Avatar']['Tiny']['Height'] = 25;
	$arrConfig['Avatar']['Thumb']['Name'] = 'thumb';
	$arrConfig['Avatar']['Thumb']['Width'] = 60;
	$arrConfig['Avatar']['Thumb']['Height'] = 60;
	$arrConfig['Avatar']['Large']['Name'] = 'large';
	$arrConfig['Avatar']['Large']['Width'] = 100;
	$arrConfig['Avatar']['Large']['Height'] = 100;
	
	//the default user avatar
	$arrConfig['DefaultAvatar'] = '/img/avatars/user.png';
	
	
	/*******************************************/
	/**     ERRORS                            **/
	/*******************************************/
	
	
	//whether to use verbose error messages with file names and line numbers (recommended for dev only)
	$arrConfig['ErrorVerbose'] = true;
	
	//the error log file relative to the files dir (must be writable by the webserver and whatever user runs scripts)
	$arrConfig['ErrorLogFile'] = AppConfig::get('FilesDir') . 'app/logs/error.' . date('Ymd') . '.log';
	
	//whether to log specific error types
	$arrConfig['ErrorLogNotice'] = false;
	$arrConfig['ErrorLogWarning'] = false;
	$arrConfig['ErrorLogError'] = false;
	
	
	/*******************************************/
	/**     DEBUGGING                         **/
	/*******************************************/
	
	
	//whether debugging is turned on
	$arrConfig['DebugEnabled'] = false;
	
	//the debugging log file relative to the the files dir (must be writable by the webserver and whatever user runs scripts)
	$arrConfig['DebugFile'] = AppConfig::get('FilesDir') . 'app/logs/debug.' . date('Ymd') . '.log';
	

	/*******************************************/
	/**     ETC                               **/
	/*******************************************/
	
	
	//whether this is hosted in a shared hosting environment
	$arrConfig['SharedHosting'] = false;
	
	//the secret key to use when generating random md5 hashes
	$arrConfig['HashKey'] = 'YOUR_SECRET_HASH_KEY';
	
	//a substition cipher used for very basic encryption ($output = str_shuffle($input))
	$arrConfig['EncryptInput'] = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890=/+';
	$arrConfig['EncryptOutput'] = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890=/+';
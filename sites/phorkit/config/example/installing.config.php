<?php
	//set all the options here for when a site hasn't been fully installed and configured
	$arrConfig['Installing'] = true;
	$arrConfig['Routes']['^/?$'] = '/site/redirect/status=301/install/';
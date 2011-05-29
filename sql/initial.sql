SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phork`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `cacheid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tier` varchar(10) DEFAULT NULL,
  `cachekey` varchar(255) DEFAULT NULL,
  `format` enum('raw','serialized') NOT NULL,
  `data` blob,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`cacheid`),
  UNIQUE KEY `cachekey` (`tier`,`cachekey`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `countryid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(100) NOT NULL,
  `abbr2` char(2) NOT NULL,
  `abbr3` char(3) NOT NULL,
  `continent` char(2) NOT NULL,
  PRIMARY KEY (`countryid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

LOCK TABLES `countries` WRITE;
INSERT INTO `countries` VALUES (1,'Afghanistan, Islamic Republic of','AF','AFG','AS'),(2,'Åland Islands','AX','ALA','EU'),(3,'Albania, Republic of','AL','ALB','EU'),(4,'Algeria, People\'s Democratic Republic of','DZ','DZA','AF'),(5,'American Samoa','AS','ASM','OC'),(6,'Andorra, Principality of','AD','AND','EU'),(7,'Angola, Republic of','AO','AGO','AF'),(8,'Anguilla','AI','AIA','NA'),(9,'Antarctica (the territory South of 60 deg S)','AQ','ATA','AN'),(10,'Antigua and Barbuda','AG','ATG','NA'),(11,'Argentina, Argentine Republic','AR','ARG','SA'),(12,'Armenia, Republic of','AM','ARM','AS'),(13,'Aruba','AW','ABW','NA'),(14,'Australia, Commonwealth of','AU','AUS','OC'),(15,'Austria, Republic of','AT','AUT','EU'),(16,'Azerbaijan, Republic of','AZ','AZE','AS'),(17,'Bahamas, Commonwealth of the','BS','BHS','NA'),(18,'Bahrain, Kingdom of','BH','BHR','AS'),(19,'Bangladesh, People\'s Republic of','BD','BGD','AS'),(20,'Barbados','BB','BRB','NA'),(21,'Belarus, Republic of','BY','BLR','EU'),(22,'Belgium, Kingdom of','BE','BEL','EU'),(23,'Belize','BZ','BLZ','NA'),(24,'Benin, Republic of','BJ','BEN','AF'),(25,'Bermuda','BM','BMU','NA'),(26,'Bhutan, Kingdom of','BT','BTN','AS'),(27,'Bolivia, Republic of','BO','BOL','SA'),(28,'Bosnia and Herzegovina','BA','BIH','EU'),(29,'Botswana, Republic of','BW','BWA','AF'),(30,'Bouvet Island (Bouvetoya)','BV','BVT','AN'),(31,'Brazil, Federative Republic of','BR','BRA','SA'),(32,'British Indian Ocean Territory (Chagos Archipelago)','IO','IOT','AS'),(33,'British Virgin Islands','VG','VGB','NA'),(34,'Brunei Darussalam','BN','BRN','AS'),(35,'Bulgaria, Republic of','BG','BGR','EU'),(36,'Burkina Faso','BF','BFA','AF'),(37,'Burundi, Republic of','BI','BDI','AF'),(38,'Cambodia, Kingdom of','KH','KHM','AS'),(39,'Cameroon, Republic of','CM','CMR','AF'),(40,'Canada','CA','CAN','NA'),(41,'Cape Verde, Republic of','CV','CPV','AF'),(42,'Cayman Islands','KY','CYM','NA'),(43,'Central African Republic','CF','CAF','AF'),(44,'Chad, Republic of','TD','TCD','AF'),(45,'Chile, Republic of','CL','CHL','SA'),(46,'China, People\'s Republic of','CN','CHN','AS'),(47,'Christmas Island','CX','CXR','AS'),(48,'Cocos (Keeling) Islands','CC','CCK','AS'),(49,'Colombia, Republic of','CO','COL','SA'),(50,'Comoros, Union of the','KM','COM','AF'),(51,'Congo, Democratic Republic of the','CD','COD','AF'),(52,'Congo, Republic of the','CG','COG','AF'),(53,'Cook Islands','CK','COK','OC'),(54,'Costa Rica, Republic of','CR','CRI','NA'),(55,'Cote d\'Ivoire, Republic of','CI','CIV','AF'),(56,'Croatia, Republic of','HR','HRV','EU'),(57,'Cuba, Republic of','CU','CUB','NA'),(58,'Cyprus, Republic of','CY','CYP','AS'),(59,'Czech Republic','CZ','CZE','EU'),(60,'Denmark, Kingdom of','DK','DNK','EU'),(61,'Djibouti, Republic of','DJ','DJI','AF'),(62,'Dominica, Commonwealth of','DM','DMA','NA'),(63,'Dominican Republic','DO','DOM','NA'),(64,'Ecuador, Republic of','EC','ECU','SA'),(65,'Egypt, Arab Republic of','EG','EGY','AF'),(66,'El Salvador, Republic of','SV','SLV','NA'),(67,'Equatorial Guinea, Republic of','GQ','GNQ','AF'),(68,'Eritrea, State of','ER','ERI','AF'),(69,'Estonia, Republic of','EE','EST','EU'),(70,'Ethiopia, Federal Democratic Republic of','ET','ETH','AF'),(71,'Faroe Islands','FO','FRO','EU'),(72,'Falkland Islands (Malvinas)','FK','FLK','SA'),(73,'Fiji, Republic of the Fiji Islands','FJ','FJI','OC'),(74,'Finland, Republic of','FI','FIN','EU'),(75,'France, French Republic','FR','FRA','EU'),(76,'French Guiana','GF','GUF','SA'),(77,'French Polynesia','PF','PYF','OC'),(78,'French Southern Territories','TF','ATF','AN'),(79,'Gabon, Gabonese Republic','GA','GAB','AF'),(80,'Gambia, Republic of the','GM','GMB','AF'),(81,'Georgia','GE','GEO','AS'),(82,'Germany, Federal Republic of','DE','DEU','EU'),(83,'Ghana, Republic of','GH','GHA','AF'),(84,'Gibraltar','GI','GIB','EU'),(85,'Greece, Hellenic Republic','GR','GRC','EU'),(86,'Greenland','GL','GRL','NA'),(87,'Grenada','GD','GRD','NA'),(88,'Guadeloupe','GP','GLP','NA'),(89,'Guam','GU','GUM','OC'),(90,'Guatemala, Republic of','GT','GTM','NA'),(91,'Guernsey, Bailiwick of','GG','GGY','EU'),(92,'Guinea, Republic of','GN','GIN','AF'),(93,'Guinea-Bissau, Republic of','GW','GNB','AF'),(94,'Guyana, Co-operative Republic of','GY','GUY','SA'),(95,'Haiti, Republic of','HT','HTI','NA'),(96,'Heard Island and McDonald Islands','HM','HMD','AN'),(97,'Holy See (Vatican City State)','VA','VAT','EU'),(98,'Honduras, Republic of','HN','HND','NA'),(99,'Hong Kong, Special Administrative Region of China','HK','HKG','AS'),(100,'Hungary, Republic of','HU','HUN','EU'),(101,'Iceland, Republic of','IS','ISL','EU'),(102,'India, Republic of','IN','IND','AS'),(103,'Indonesia, Republic of','ID','IDN','AS'),(104,'Iran, Islamic Republic of','IR','IRN','AS'),(105,'Iraq, Republic of','IQ','IRQ','AS'),(106,'Ireland','IE','IRL','EU'),(107,'Isle of Man','IM','IMN','EU'),(108,'Israel, State of','IL','ISR','AS'),(109,'Italy, Italian Republic','IT','ITA','EU'),(110,'Jamaica','JM','JAM','NA'),(111,'Japan','JP','JPN','AS'),(112,'Jersey, Bailiwick of','JE','JEY','EU'),(113,'Jordan, Hashemite Kingdom of','JO','JOR','AS'),(114,'Kazakhstan, Republic of','KZ','KAZ','AS'),(115,'Kenya, Republic of','KE','KEN','AF'),(116,'Kiribati, Republic of','KI','KIR','OC'),(117,'Korea, Democratic People\'s Republic of','KP','PRK','AS'),(118,'Korea, Republic of','KR','KOR','AS'),(119,'Kuwait, State of','KW','KWT','AS'),(120,'Kyrgyz Republic','KG','KGZ','AS'),(121,'Lao People\'s Democratic Republic','LA','LAO','AS'),(122,'Latvia, Republic of','LV','LVA','EU'),(123,'Lebanon, Lebanese Republic','LB','LBN','AS'),(124,'Lesotho, Kingdom of','LS','LSO','AF'),(125,'Liberia, Republic of','LR','LBR','AF'),(126,'Libyan Arab Jamahiriya','LY','LBY','AF'),(127,'Liechtenstein, Principality of','LI','LIE','EU'),(128,'Lithuania, Republic of','LT','LTU','EU'),(129,'Luxembourg, Grand Duchy of','LU','LUX','EU'),(130,'Macao, Special Administrative Region of China','MO','MAC','AS'),(131,'Macedonia, the former Yugoslav Republic of','MK','MKD','EU'),(132,'Madagascar, Republic of','MG','MDG','AF'),(133,'Malawi, Republic of','MW','MWI','AF'),(134,'Malaysia','MY','MYS','AS'),(135,'Maldives, Republic of','MV','MDV','AS'),(136,'Mali, Republic of','ML','MLI','AF'),(137,'Malta, Republic of','MT','MLT','EU'),(138,'Marshall Islands, Republic of the','MH','MHL','OC'),(139,'Martinique','MQ','MTQ','NA'),(140,'Mauritania, Islamic Republic of','MR','MRT','AF'),(141,'Mauritius, Republic of','MU','MUS','AF'),(142,'Mayotte','YT','MYT','AF'),(143,'Mexico, United Mexican States','MX','MEX','NA'),(144,'Micronesia, Federated States of','FM','FSM','OC'),(145,'Moldova, Republic of','MD','MDA','EU'),(146,'Monaco, Principality of','MC','MCO','EU'),(147,'Mongolia','MN','MNG','AS'),(148,'Montenegro, Republic of','ME','MNE','EU'),(149,'Montserrat','MS','MSR','NA'),(150,'Morocco, Kingdom of','MA','MAR','AF'),(151,'Mozambique, Republic of','MZ','MOZ','AF'),(152,'Myanmar, Union of','MM','MMR','AS'),(153,'Namibia, Republic of','NA','NAM','AF'),(154,'Nauru, Republic of','NR','NRU','OC'),(155,'Nepal, State of','NP','NPL','AS'),(156,'Netherlands Antilles','AN','ANT','NA'),(157,'Netherlands, Kingdom of the','NL','NLD','EU'),(158,'New Caledonia','NC','NCL','OC'),(159,'New Zealand','NZ','NZL','OC'),(160,'Nicaragua, Republic of','NI','NIC','NA'),(161,'Niger, Republic of','NE','NER','AF'),(162,'Nigeria, Federal Republic of','NG','NGA','AF'),(163,'Niue','NU','NIU','OC'),(164,'Norfolk Island','NF','NFK','OC'),(165,'Northern Mariana Islands, Commonwealth of the','MP','MNP','OC'),(166,'Norway, Kingdom of','NO','NOR','EU'),(167,'Oman, Sultanate of','OM','OMN','AS'),(168,'Pakistan, Islamic Republic of','PK','PAK','AS'),(169,'Palau, Republic of','PW','PLW','OC'),(170,'Palestinian Territory, Occupied','PS','PSE','AS'),(171,'Panama, Republic of','PA','PAN','NA'),(172,'Papua New Guinea, Independent State of','PG','PNG','OC'),(173,'Paraguay, Republic of','PY','PRY','SA'),(174,'Peru, Republic of','PE','PER','SA'),(175,'Philippines, Republic of the','PH','PHL','AS'),(176,'Pitcairn Islands','PN','PCN','OC'),(177,'Poland, Republic of','PL','POL','EU'),(178,'Portugal, Portuguese Republic','PT','PRT','EU'),(179,'Puerto Rico, Commonwealth of','PR','PRI','NA'),(180,'Qatar, State of','QA','QAT','AS'),(181,'Reunion','RE','REU','AF'),(182,'Romania','RO','ROU','EU'),(183,'Russian Federation','RU','RUS','EU'),(184,'Rwanda, Republic of','RW','RWA','AF'),(185,'Saint Barthelemy','BL','BLM','NA'),(186,'Saint Helena','SH','SHN','AF'),(187,'Saint Kitts and Nevis, Federation of','KN','KNA','NA'),(188,'Saint Lucia','LC','LCA','NA'),(189,'Saint Martin','MF','MAF','NA'),(190,'Saint Pierre and Miquelon','PM','SPM','NA'),(191,'Saint Vincent and the Grenadines','VC','VCT','NA'),(192,'Samoa, Independent State of','WS','WSM','OC'),(193,'San Marino, Republic of','SM','SMR','EU'),(194,'Sao Tome and Principe, Democratic Republic of','ST','STP','AF'),(195,'Saudi Arabia, Kingdom of','SA','SAU','AS'),(196,'Senegal, Republic of','SN','SEN','AF'),(197,'Serbia, Republic of','RS','SRB','EU'),(198,'Seychelles, Republic of','SC','SYC','AF'),(199,'Sierra Leone, Republic of','SL','SLE','AF'),(200,'Singapore, Republic of','SG','SGP','AS'),(201,'Slovakia (Slovak Republic)','SK','SVK','EU'),(202,'Slovenia, Republic of','SI','SVN','EU'),(203,'Solomon Islands','SB','SLB','OC'),(204,'Somalia, Somali Republic','SO','SOM','AF'),(205,'South Africa, Republic of','ZA','ZAF','AF'),(206,'South Georgia and the South Sandwich Islands','GS','SGS','AN'),(207,'Spain, Kingdom of','ES','ESP','EU'),(208,'Sri Lanka, Democratic Socialist Republic of','LK','LKA','AS'),(209,'Sudan, Republic of','SD','SDN','AF'),(210,'Suriname, Republic of','SR','SUR','SA'),(211,'Svalbard & Jan Mayen Islands','SJ','SJM','EU'),(212,'Swaziland, Kingdom of','SZ','SWZ','AF'),(213,'Sweden, Kingdom of','SE','SWE','EU'),(214,'Switzerland, Swiss Confederation','CH','CHE','EU'),(215,'Syrian Arab Republic','SY','SYR','AS'),(216,'Taiwan','TW','TWN','AS'),(217,'Tajikistan, Republic of','TJ','TJK','AS'),(218,'Tanzania, United Republic of','TZ','TZA','AF'),(219,'Thailand, Kingdom of','TH','THA','AS'),(220,'Timor-Leste, Democratic Republic of','TL','TLS','AS'),(221,'Togo, Togolese Republic','TG','TGO','AF'),(222,'Tokelau','TK','TKL','OC'),(223,'Tonga, Kingdom of','TO','TON','OC'),(224,'Trinidad and Tobago, Republic of','TT','TTO','NA'),(225,'Tunisia, Tunisian Republic','TN','TUN','AF'),(226,'Turkey, Republic of','TR','TUR','AS'),(227,'Turkmenistan','TM','TKM','AS'),(228,'Turks and Caicos Islands','TC','TCA','NA'),(229,'Tuvalu','TV','TUV','OC'),(230,'Uganda, Republic of','UG','UGA','AF'),(231,'Ukraine','UA','UKR','EU'),(232,'United Arab Emirates','AE','ARE','AS'),(233,'United Kingdom of Great Britain & Northern Ireland','GB','GBR','EU'),(234,'United States of America','US','USA','NA'),(235,'United States Minor Outlying Islands','UM','UMI','OC'),(236,'United States Virgin Islands','VI','VIR','NA'),(237,'Uruguay, Eastern Republic of','UY','URY','SA'),(238,'Uzbekistan, Republic of','UZ','UZB','AS'),(239,'Vanuatu, Republic of','VU','VUT','OC'),(240,'Venezuela, Bolivarian Republic of','VE','VEN','SA'),(241,'Vietnam, Socialist Republic of','VN','VNM','AS'),(242,'Wallis and Futuna','WF','WLF','OC'),(243,'Western Sahara','EH','ESH','AF'),(244,'Yemen','YE','YEM','AS'),(245,'Zambia, Republic of','ZM','ZMB','AF'),(246,'Zimbabwe, Republic of','ZW','ZWE','AF');
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `facebook`
--

CREATE TABLE `facebook` (
  `facebookid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `externalid` bigint(20) unsigned NOT NULL,
  `sessionkey` varchar(255) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `gender` char(1) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`facebookid`),
  UNIQUE KEY `externalid` (`externalid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `promo`
--

CREATE TABLE `promo` (
  `promoid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(15) NOT NULL,
  `code` char(32) NOT NULL,
  `userid` int(10) unsigned DEFAULT NULL,
  `sent` datetime NOT NULL,
  `claimed` datetime DEFAULT NULL,
  PRIMARY KEY (`promoid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `roleid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `rank` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`roleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

LOCK TABLES `roles` WRITE;
INSERT INTO `roles` VALUES (1,'Developer',1),(2,'Super User',2);
UNLOCK TABLES;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `sessionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session` varchar(100) NOT NULL,
  `ipaddr` varchar(16) DEFAULT NULL,
  `useragent` varchar(155) DEFAULT NULL,
  `data` text,
  `expires` datetime DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sessionid`),
  UNIQUE KEY `session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tagid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(20) DEFAULT NULL,
  `abbr` varchar(20) DEFAULT NULL,
  `banned` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`tagid`),
  UNIQUE KEY `abbr` (`abbr`),
  KEY `banned` (`banned`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `twitter`
--

CREATE TABLE `twitter` (
  `twitterid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `externalid` int(10) unsigned NOT NULL,
  `secret` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `username` varchar(30) NOT NULL,
  `displayname` varchar(30) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `blurb` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`twitterid`),
  UNIQUE KEY `externalid` (`externalid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(80) NOT NULL,
  `firstname` varchar(30) NOT NULL,
  `lastname` varchar(40) NOT NULL DEFAULT '',
  `displayname` varchar(30) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `timezone` decimal(4,2) NOT NULL,
  `countryid` smallint(5) unsigned DEFAULT NULL,
  `location` varchar(30) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `blurb` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `roles` smallint(6) NOT NULL DEFAULT '0',
  `verified` tinyint(3) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`userid`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `countryid` (`countryid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_connections`
--

CREATE TABLE `user_connections` (
  `userconnectionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `connectionid` int(10) unsigned NOT NULL,
  `pending` smallint(5) unsigned NOT NULL DEFAULT '0',
  `approved` smallint(5) unsigned NOT NULL DEFAULT '0',
  `denied` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`userconnectionid`),
  UNIQUE KEY `connection` (`userid`,`connectionid`),
  KEY `connectionid` (`connectionid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_events`
--

CREATE TABLE `user_events` (
  `usereventid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `typeid` int(10) unsigned NOT NULL,
  `type` varchar(40) NOT NULL,
  `typegroup` varchar(40) DEFAULT NULL,
  `metadata` text,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`usereventid`),
  KEY `userid` (`userid`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_login`
--

CREATE TABLE `user_login` (
  `userloginid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `publickey` char(32) DEFAULT NULL,
  `privatekey` char(32) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `accessed` datetime DEFAULT NULL,
  PRIMARY KEY (`userloginid`),
  UNIQUE KEY `user` (`userid`,`privatekey`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `logid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `site` varchar(20) NOT NULL,
  `method` enum('form','cookie','facebook','twitter') NOT NULL,
  `ipaddr` varchar(16) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`logid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_passwords`
--

CREATE TABLE `user_passwords` (
  `passwordid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `password` char(32) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`passwordid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_tags`
--

CREATE TABLE `user_tags` (
  `usertagid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `tagid` int(10) unsigned NOT NULL,
  `weight` smallint(5) unsigned NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usertagid`),
  KEY `userid` (`userid`),
  KEY `tagid` (`tagid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `verify`
--

CREATE TABLE `verify` (
  `verifyid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `typeid` int(10) unsigned NOT NULL,
  `type` varchar(25) NOT NULL,
  `token` char(32) NOT NULL,
  `verified` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`verifyid`),
  KEY `combo` (`typeid`,`type`,`token`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_connections`
--
ALTER TABLE `user_connections`
  ADD CONSTRAINT `user_connections_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_connections_ibfk_2` FOREIGN KEY (`connectionid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `user_events`
--
ALTER TABLE `user_events`
  ADD CONSTRAINT `user_events_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `user_login`
--
ALTER TABLE `user_login`
  ADD CONSTRAINT `user_login_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `user_passwords`
--
ALTER TABLE `user_passwords`
  ADD CONSTRAINT `user_passwords_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `user_tags`
--
ALTER TABLE `user_tags`
  ADD CONSTRAINT `user_tags_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_tags_ibfk_2` FOREIGN KEY (`tagid`) REFERENCES `tags` (`tagid`) ON DELETE CASCADE;

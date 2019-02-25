<?php

/**
 * Config file for Unique Channel Titles
 *
 * @package			Unique_channel_titles
 * @author			Simon Andersohn
 * @copyright 		Copyright (c) 2015
 * @license 		
 * @link			
 * @see				
 */

if ( ! defined('UNIQUE_CHANNEL_TITLES_NAME'))
{
	define('UNIQUE_CHANNEL_TITLES_NAME',        'Unique Channel Titles');
	define('UNIQUE_CHANNEL_TITLES_CLASS_NAME',  'unique_channel_titles');
	define('UNIQUE_CHANNEL_TITLES_DESCRIPTION', 'Checks if title already exists within a channel while editing/updating entries');
	define('UNIQUE_CHANNEL_TITLES_VERSION',     '1.3.0');
	define('UNIQUE_CHANNEL_TITLES_DOCS_URL', 	'https://github.com/ignetic/ee-unique-channel-titles'); 
}

$config['name'] 	= UNIQUE_CHANNEL_TITLES_NAME;
$config['version'] 	= UNIQUE_CHANNEL_TITLES_VERSION;

/* End of file config.php */
/* Location: ./system/expressionengine/third_party/unique_channel_titles/config.php */

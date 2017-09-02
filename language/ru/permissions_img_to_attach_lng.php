<?php
/**
*
* @package phpBB Extension - IMG to Attachment
* @copyright (c) 2015 Sheer
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACL_U_CONVERT_IMG'		=> 'Может конвертировать изображения IMG во вложения',
));

<?php
/**
*
* @package phpBB Extension - PM Limit
* @copyright (c) 2015 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
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
	'IMG_TO_ATTACH'				=> 'Загрузить IMG',
	'IMG_TO_ATTACH_TITLE'		=> 'Загрузка изображений из BBCode IMG и HSIMG на конференцию в виде вложения в текст сообщения',
));
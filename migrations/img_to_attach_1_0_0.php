<?php
/**
*
* @package phpBB Extension - IMG to Attachment
* @copyright (c) 2015 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace sheer\img_to_attach\migrations;

class img_to_attach_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
		);
	}

	public function revert_schema()
	{
		return array(
		);
	}

	public function update_data()
	{
		return array(
			// Current version
			array('config.add', array('img_to_attach_version', '1.0.0')),
			// Add permissions
			array('permission.add', array('u_convert_img', true)),
			// Add permissions sets
			array('permission.permission_set', array('ADMINISTRATORS', 'u_convert_img', 'group', true)),
			array('permission.permission_set', array('GLOBAL_MODERATORS', 'u_convert_img', 'group', true)),
			array('permission.permission_set', array('GUESTS', 'u_convert_img', 'group', false)),
			array('permission.permission_set', array('REGISTERED', 'u_convert_img', 'group', true)),
			array('permission.permission_set', array('NEWLY_REGISTERED', 'u_convert_img', 'group', false)),
		);
	}
}
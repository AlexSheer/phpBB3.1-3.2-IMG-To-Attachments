<?php
/**
*
* @package phpBB Extension - IMG to Attachment
* @copyright (c) 2015 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace sheer\img_to_attach\migrations;

class img_to_attach_1_0_3 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['img_to_attach_version']) && version_compare($this->config['img_to_attach_version'], '1.0.3', '>=');
	}

	static public function depends_on()
	{
		return array('\sheer\img_to_attach\migrations\img_to_attach_1_0_2');
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
			array('config.update', array('img_to_attach_version', '1.0.3')),
		);
	}
}
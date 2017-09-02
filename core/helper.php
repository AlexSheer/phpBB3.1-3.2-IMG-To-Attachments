<?php
/**
*
* @package phpBB Extension - IMG to Attachment
* @copyright (c) 2015 Sheer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sheer\img_to_attach\core;

class helper
{
	/** @var \phpbb	emplate	emplate */
	protected $template;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	//** @var string phpbb_root_path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\auth\auth */
	protected $auth;

	public function __construct (
		\phpbb\template\template $template,
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->template = $template;
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->db = $db;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	public function create_attach($file, $filenane, $poster_id, $post_msg_id, $topic_id)
	{
		$size = @getimagesize($file);
		$filedata = array();
		if (!count($size) || !isset($size[0]) || !isset($size[1]))
		{
			return false;
		}
		$image_type = $size[2];
		$filetime = time();
		$physical_filename = $poster_id . '_' . md5($file.$filetime);
		$attach_file = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;
		if(copy($file, $attach_file))
		{			$file_data = explode('.', $filenane);
			$filesize = filesize($attach_file);
			$thumb = 0;
			if($this->config['img_create_thumbnail'] && $filesize >= $this->config['img_min_thumb_filesize'])
			{				// Create thumbnail
				$thumbnail_file = 'thumb_' . $physical_filename;
				$source = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;
				$thumb = create_thumbnail($source, $this->phpbb_root_path . $this->config['upload_path'] . '/' . $thumbnail_file, $size['mime']);
			}
			$sql_ary = array(
				'physical_filename'	=> $physical_filename,
				'attach_comment'	=> '',
				'real_filename'		=> $filenane,
				'extension'			=> $file_data[1],
				'mimetype'			=> $size['mime'],
				'filesize'			=> $filesize,
				'filetime'			=> $filetime,
				'thumbnail'			=> $thumb,
				'is_orphan'			=> 1,
				'in_message'		=> 0,
				'poster_id'			=> $poster_id,
				'post_msg_id'		=> $post_msg_id,
				'topic_id'			=> $topic_id,
			);
			$this->db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));

			$attachment_data = array(
				'real_filename'		=> $filenane,
				'attach_comment'	=> '',
				'filesize'			=> $filesize,
				'is_orphan'			=> 1,
				'attach_id'			=> $this->db->sql_nextid(),
			);
			return $attachment_data;
		}
	}

	public function url_exists($url)
	{		$handle = @fopen($url, 'r');
		if ($handle === false)
		{
			@fclose($handle);
			return false;
		}
		else
		{
			fclose($handle);
			return true;
		}
	}
}

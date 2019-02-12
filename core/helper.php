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
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	//** @var string phpbb_root_path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\extension\manager */
	protected $phpbb_extension_manager;

	public function __construct (
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		$phpbb_root_path,
		$php_ext,
		\phpbb\request\request_interface $request,
		\phpbb\extension\manager $phpbb_extension_manager
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->request = $request;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
	}

	public function create_attach($file, $filename, $poster_id, $post_msg_id, $topic_id)
	{
		$size = @getimagesize($file);
		$filedata = array();
		if (!count($size) || !isset($size[0]) || !isset($size[1]))
		{
			return false;
		}
		$image_type = $size[2];
		$imagetypes = array('gif' => IMAGETYPE_GIF, 'jpg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG);
		if (in_array($image_type , $imagetypes))
		{
			$file_ext = array_search ($image_type, $imagetypes);
		}
		else
		{
			return false;
		}
		$filetime = time();
		$physical_filename = $poster_id . '_' . md5($file.$filetime);
		$attach_file = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;

		if (copy($file, $attach_file))
		{
			$thumb = 0;

			// Extension "Attached Image rotator" by sheer enabled?
			// https://www.phpbbguru.net/community/viewtopic.php?f=64&t=44140
			if ($this->phpbb_extension_manager->is_enabled('sheer/image_rotator') && ($this->config['rotate_img_max_width'] || $this->config['rotate_img_max_height']))
			{
				$this->config['img_max_height'] = $this->config['rotate_img_max_height'];
				$this->config['img_max_width'] = $this->config['rotate_img_max_width'];
			}

			if ($this->config['img_max_height'] > 0 && $this->config['img_max_width'] > 0) // Enable resize?
			{
				if ($size[0] > $this->config['img_max_width'] || $size[1] > $this->config['img_max_height']) // Need resize?
				{
					if ($size[0] > $size[1])
					{
						$resize_width = $this->config['img_max_width'];
						$resize_height = ($this->config['img_max_width'] / $size[0]) * $size[1];
					}
					else
					{
						$resize_width =  ($this->config['img_max_height'] /  $size[1]) * $size[0];
						$resize_height = $this->config['img_max_height'];
					}
					$this->resize_image($physical_filename, $size, $resize_width, $resize_height);
				}
			}
			$filesize = filesize($attach_file);

			// Extension "Attached PNG Image Convert" by vlad enabled?
			// https://www.phpbbguru.net/community/viewtopic.php?f=59&t=47951#p533248
			if ($this->phpbb_extension_manager->is_enabled('vlad/image_convert') && $file_ext === 'png')
			{
				include_once($this->phpbb_root_path . 'ext/vlad/image_convert/core/pngconvert.' . $this->php_ext);
				$class = '\vlad\image_convert\core\pngconvert';
				if (class_exists($class))
				{
					$filedata = array(
						'extension'		=> $file_ext,
						'real_filename'	=> $filename,
					);

					$image_convert = new $class();
					$filedata = $image_convert->pngconvert($this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename, $filedata);
					$filename = $filedata['real_filename'];
					$size['mime'] = $filedata['mimetype'];
					$file_ext = $filedata['extension'];
				}
			}

			// Extension "Editor of attachments (resize, watermark, etc.)" by tatiana5 enabled?
			// https://www.phpbbguru.net/community/viewtopic.php?t=42380
			if ($this->phpbb_extension_manager->is_enabled('tatiana5/editor_of_attachments') && $this->config['img_create_watermark'])
			{
				include_once($this->phpbb_root_path . 'ext/tatiana5/editor_of_attachments/core/watermark.' . $this->php_ext);
				$class = '\tatiana5\editor_of_attachments\core\watermark';
				if (class_exists($class))
				{
					$watermark = new $class($this->config, $this->request);
					$watermark->watermark_images($this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename, $file_ext);
				}
			}

			if ($this->config['img_create_thumbnail'] && $filesize >= $this->config['img_min_thumb_filesize'])
			{
				// Create thumbnail
				$thumbnail_file = 'thumb_' . $physical_filename;
				$source = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;
				$thumb = create_thumbnail($source, $this->phpbb_root_path . $this->config['upload_path'] . '/' . $thumbnail_file, $size['mime']);
			}

			$sql_ary = array(
				'physical_filename'	=> $physical_filename,
				'attach_comment'	=> '',
				'real_filename'		=> $filename,
				'extension'			=> $file_ext,
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
				'real_filename'		=> $filename,
				'attach_comment'	=> '',
				'filesize'			=> $filesize,
				'is_orphan'			=> 1,
				'attach_id'			=> $this->db->sql_nextid(),
			);
			return $attachment_data;
		}
	}

	public function url_exists($url)
	{
		$handle = @fopen($url, 'r');
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

	/**
	* Resize Image
	*
	* @param string $source source file
	* @param string $destination destination file
	* @param string $ext file tyep of source file
	* @param int $src_width width of source
	* @param int $src_hieght height of source
	* @param int $resize_width required resized width
	* @param int $resize_height required resized height
	*
	*/
	private function resize_image($physical_filename, $size, $resize_width, $resize_height)
	{
		$gd_version = $this->gd_version_check();
		$src_width = $size[0];
		$src_height = $size[1];
		$ext = $size['mime'];

		switch ($ext)
		{
			case 'image/jpeg':
				$read_function = 'imagecreatefromjpeg';
				break;
			case 'image/png':
				$read_function = 'imagecreatefrompng';
				break;
			case 'image/gif':
				$read_function = 'imagecreatefromgif';
				break;
			default:
				return;
		}

		$source_file_name = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;
		$destination_file_name = $this->phpbb_root_path . $this->config['upload_path'] . '/' . $physical_filename;

		$src = @$read_function($source_file_name);

		if (!$src || !$gd_version)
		{
			return;
		}
		else
		{
			$dest = ($gd_version == 1) ? @imagecreate($resize_width, $resize_height) : @imagecreatetruecolor($resize_width, $resize_height);
			$resize_function = ($gd_version == 1) ? 'imagecopyresized' : 'imagecopyresampled';
			@$resize_function($dest, $src, 0, 0, 0, 0, $resize_width, $resize_height, $src_width, $src_height);
		}

		//Different Call Based On Image Type...
		switch ($ext)
		{
			case 'image/jpg':
				@imagejpeg($dest, $destination_file_name, 85);
				break;
			case 'image/png':
				@imagepng($dest, $destination_file_name);
				break;
			case 'image/gif':
				@imagegif($dest, $destination_file_name);
				break;
		}
		@chmod($destination_file_name, 0777);

		//We should ALWAYS clear the RAM used by this.
		imagedestroy($dest);
		imagedestroy($src);
		clearstatcache();

		return;
	}

	/**
	* Determine GD version if available else set to 0
	*
	* @param int $user_ver version to default
	*
	*/
	private function gd_version_check($user_ver = 0)
	{
		if (!extension_loaded('gd'))
		{
			return;
		}

		static $gd_ver = 0;
		//Just Accept The Specified Setting If It's 1
		if ($user_ver == 1)
		{
			$gd_ver = 1;
			return 1;
		}
		//Use The Static Variable If function Was Called Previously
		if ($user_ver !=2 && $gd_ver > 0)
		{
			return $gd_ver;
		}
		//Use The gd_info() Function If Possible
		if (function_exists('gd_info'))
		{
			$ver_info = gd_info();
			preg_match('/\d/', $ver_info['GD Version'], $match);
			$gd_ver = $match[0];
			return $match[0];
		}
		//If phpinfo() is disabled use a specified / fail-safe choice...
		if (preg_match('/phpinfo/', ini_get('disable_functions')))
		{
			$gd_ver = ($user_ver == 2) ? 2 : 1;

			return $gd_ver;
		}
		//Otherwise Use phpinfo()
		ob_start();
		phpinfo(8);
		$info = ob_get_contents();
		ob_end_clean();
		$info = stristr($info, 'gd version');
		preg_match('/\d/', $info, $match);
		$gd_ver = $match[0];
		return $match[0];
	}

}

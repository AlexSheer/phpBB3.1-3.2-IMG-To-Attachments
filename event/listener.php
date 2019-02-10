<?php
/**
*
* @package phpBB Extension - IMG to Attachment
* @copyright (c) 2015 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace sheer\img_to_attach\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'							=> 'load_language_on_setup',
			'core.modify_posting_parameters'			=> 'posting_parameters',
			'core.posting_modify_message_text'			=> 'modify_message',
			'core.posting_modify_submit_post_after'		=> 'submit_post_after',
			'core.permissions'							=> 'add_permission',
		);
	}

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb	emplate	emplate */
	protected $template;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/**
	* Constructor
	*/
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\template\template $template,
		\phpbb\request\request_interface $request,
		\phpbb\auth\auth $auth,
		$helper
		)
	{
		$this->db = $db;
		$this->template = $template;
		$this->request = $request;
		$this->auth = $auth;
		$this->helper = $helper;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'sheer/img_to_attach',
			'lang_set' => 'img_to_attach_lng',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function posting_parameters($event)
	{
		if($this->auth->acl_get('u_convert_img'))
		{
			$preview = $event['preview'];
			$upload = ($this->request->variable('upload', false));
			$this->template->assign_vars(array(
				'S_UPLOAD_OPTIONS'	=> ($upload) ? ' checked="checked"' : '',
				'S_CAN_UPLOAD_IMG'	=> true,
			));

			$event['preview'] = ($upload) ? $upload : $preview;
		}
	}

	public function modify_message($event)
	{
		if($this->auth->acl_get('u_convert_img'))
		{
			$upload = ($this->request->variable('upload', false));
			$post_data = $event['post_data'];
			if ($upload)
			{
				$message_parser = $event['message_parser'];
				$attachments= array();
				$attachment_data = $message_parser->attachment_data;

				$text = $message_parser->message;
				preg_match_all('#\[img\]((.*?)|(.*?).jpg|(.*?).jpeg|(.*?).png|(.*?).gif)\[\/img\]#i', $text, $current_posted_img);
				if (!empty($current_posted_img[1]))
				{
					foreach($current_posted_img[1] as $posted_img)
					{
						$url			= preg_replace(array('#&\#46;#', '#&\#58;#', '/\[(.*?)\]/'), array('.', ':', ''), $posted_img);
						$url			= str_replace('https', 'http', $url);
						$filename		= strrchr($url,"/");
						$filename		= substr($filename, 1);
						$filename		= strtolower(preg_replace('#[^a-zA-Z0-9_+.-]#', '', $filename));
						$filename_img	= substr($filename, 0, -4);
						if ($this->helper->url_exists($url))
						{
							$post_id = (isset($post_data['post_id'])) ? $post_data['post_id'] : 0;
							$topic_id = (isset($post_data['topic_id'])) ? $post_data['topic_id'] : 0;

							$attachments[] = $this->helper->create_attach($url, $filename, $post_data['poster_id'], $post_id, $topic_id);
						}
					}

					if (!empty($attachments))
					{
						$img_number = sizeof($attachments);
						$text = preg_replace_callback('#\[img\]((.*?)|(.*?).jpg|(.*?).jpeg|(.*?).png|(.*?).gif)\[\/img\]#',
							function ($matches) use (&$img_number)
							{
								return "[attachment=" . --$img_number . "]" . substr($matches[1], strrpos($matches[1], '/') + 1) . "[/attachment]";
							},
							$text
						);

						$event['message_parser']->message = $text;
						$event['message_parser']->attachment_data = $attachments;
					}
				}
			}
		}
	}

	public function submit_post_after($event)
	{
		$upload = ($this->request->variable('upload', false));
		if ($this->auth->acl_get('u_convert_img') && $upload)
		{
			$data = $event['data'];
			$attachment = $data['attachment_data'];
			if (!empty($attachment))
			{
				foreach ($attachment as $att)
				{
					$attach_ids[] = $att['attach_id'];
				}
				$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
					SET post_msg_id = ' . $data['post_id'] . ', topic_id = ' . $data['topic_id'] . ', is_orphan = 0
					WHERE ' . $this->db->sql_in_set('attach_id', $attach_ids);
				$this->db->sql_query($sql);
			}
		}
	}

	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['u_convert_img']	= array('lang' => 'ACL_U_CONVERT_IMG', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}
}

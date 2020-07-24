<?php
/**
 * Glog specific functions & style for the phpBB Forum
 *
 * @copyright (c) 2020 Dominique Cavailhez
 * @license GNU General Public License, version 24(GPL-240)
 */

/*//TODO
text-indent de <p>
*/

namespace Dominique92\Blog\event;

if (!defined('IN_PHPBB'))
{
	exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	// List of externals
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\language\language $language
	) {
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->language = $language;

		// Includes language and style files of this extension
		$this->ns = explode ('\\', __NAMESPACE__);
		$this->ext_path = 'ext/'.$this->ns[0].'/'.$this->ns[1].'/';
		$this->language->add_lang ('common', $this->ns[0].'/'.$this->ns[1]);
		$template->set_style ([
			$this->ext_path.'styles',
			'styles', // core styles
		]);
	}

	static public function getSubscribedEvents() {
		return [
			'core.page_header' => 'page_header',
		];
	}

	function page_header() {
		$this->template->assign_var ('EXT_PATH', $this->ext_path);

		$sql = 'SELECT * FROM '. ATTACHMENTS_TABLE .' ORDER BY attach_id DESC, post_msg_id ASC';
		$result = $this->db->sql_query($sql);
		$attachments = $update_count_ary = [];
		while ($row = $this->db->sql_fetchrow($result))
			$attachments[$row['post_msg_id']][] = $row;
		$this->db->sql_freeresult($result);

		$subject = $this->request->variable ('s', 'megaliths');
		$sql = "
			SELECT p.topic_id, p.post_id, p.post_text, p.bbcode_uid, p.bbcode_bitfield,
				t.topic_posts_approved, t.topic_last_post_id,
				c.post_id AS comment_id, c.post_text AS comment_text,
				c.bbcode_uid AS comment_bbcode_uid, c.bbcode_uid AS comment_bbcode_bitfield
			FROM ".TOPICS_TABLE." AS t
				JOIN ".POSTS_TABLE." AS p ON (p.post_id = t.topic_first_post_id)
				JOIN ".POSTS_TABLE." AS c ON (c.post_id = t.topic_last_post_id)
			WHERE t.topic_title LIKE '$subject %'
			ORDER BY t.topic_title
		";
		$result = $this->db->sql_query($sql);
		$txt = '';
		while ($row = $this->db->sql_fetchrow($result)) {
			// BBCodes et attachements
			$row['display_text'] = generate_text_for_display(
				$row['post_text'],
				$row['bbcode_uid'], $row['bbcode_bitfield'],
				OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS
			);
			if (!empty($attachments[$row['post_id']]))
				parse_attachments($row['forum_id'], $row['display_text'], $attachments[$row['post_id']], $update_count_ary);

			foreach (explode ('<br>', $row['display_text']) AS $v)
				$txt .= preg_replace ([
						'/[[:cntrl:]]/',
						'/<br \/>/',
						'/<div class="inline-attachment">.*(id=[0-9]+).*<\/div>/',
					],['', ' ', 'download/file.php?$1'],
					$v
				).'<br>';
			
			// Commentaires
			$txt .= " <div class='comment'>";
			$comment = trim (strip_tags (generate_text_for_display(
				$row['comment_text'],
				$row['comment_bbcode_uid'], $row['comment_bbcode_bitfield'],
				OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS
			)));

			if (strlen ($comment) > 100)
				$comment = substr ($comment, 0, 100).'...';

			if ($row['topic_posts_approved'] > 1)
				$txt .= "Commentaire : <i>{$comment}</i> ".
					"<a href='viewtopic.php?f=3&t={$row['topic_id']}#p{$row['topic_last_post_id']}'>Voir la discussion</a><br />";

			// Edit link
			if ($this->auth->acl_get('m_'))
				$txt .= "<a style='float:right' href='posting.php?mode=edit&f=3&p={$row['post_id']}'>*</a> ";

			$txt .= "<a href='posting.php?mode=reply&f=3&t={$row['topic_id']}'>Ecrire un commentaire</a></div><br>";
		}

		$html = '';
		foreach (explode ('<br>', $txt) AS $l) {
			$i = intval ($l);
			$ls = explode (' ', $l, 2);

			// Titres (commencent par 0 à 9)
			if (strlen ($ls[0]) == 1) {
				if ($last_ls0_len != 1) // Sections indivisibles au print
					$html .= "</div><div>\n";
				$i++;
				$html .= "<h$i>{$ls[1]}</h$i>\n";
			}
			// Images (commencent par 10+)
			elseif ($i) {
				$ls = explode (' ', $l, 4);
				$i = "<img style='height:{$ls[0]}px' src='{$ls[1]}' />";
				$a = $z = '';
				if (count ($ls) > 2) {
					$ref = explode ('(', str_replace ([')','_'], ['',' '], $ls[2]), 2);
					$a = count ($ref) == 2
						? "(<a title='{$ref[0]}' href='{$ref[0]}'>{$ref[1]}</a>)"
						: ($ref[0] ? "({$ref[0]})" : "");
				}
				if (count ($ls) > 3)
					$z = $ls[3];
				$html .= "<div class='image'>$i<div>$z $a</div></div>\n";
			}
			// Textes (commencent par ' ')
			else
				$html .= "<p>{$ls[1]}</p>\n";

			$last_ls0_len = strlen ($ls[0]);
		}

		$this->template->assign_var ('ARTICLE', preg_replace (
			'/<a .* class="postlink">(.+)<\/a> \(([0-9]+)\)/',
			'(<a href="$1">$2</a>)',
			$html
		));
	}
}
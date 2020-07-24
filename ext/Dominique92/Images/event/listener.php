<?php
/**
 * @package Images
 * @copyright (c) 2016 Dominique Cavailhez
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * download/file.php?id=<ID>&size=<MAX_PIXELS> resize to fit into a MAX_PIXELS square
 */

/*//TODO
Template miniatures pour réorganisation
*/

namespace Dominique92\Images\event;

if (!defined('IN_PHPBB'))
{
	exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template
	) {
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->ns = explode ('\\', __NAMESPACE__);
	}

	// Liste des hooks et des fonctions associées
	static public function getSubscribedEvents() {
		return [
			// Change template
			'core.viewtopic_assign_template_vars_before' => 'viewtopic_assign_template_vars_before',
			'core.page_footer' => 'page_footer',

			//Slideshow
			'core.viewtopic_modify_post_data' => 'viewtopic_modify_post_data',

			// Image resize
			'core.download_file_send_to_browser_before' => 'download_file_send_to_browser_before',

			/*
			'core.viewtopic_post_rowset_data' => 'viewtopic_post_rowset_data',
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',
			'core.parse_attachments_modify_template_data' => 'parse_attachments_modify_template_data',
			*/
		];
	}
// List template vars : phpbb/template/context.php line 134
//echo"<pre style='background-color:white;color:black;font-size:14px;'> = ".var_export($ref,true).'</pre>';

	function viewtopic_assign_template_vars_before($vars) {
		// Change template if '*slideshow' is in the forum descriptor
		$this->my_template = $this->request->variable (
			'template',
			strpos ($vars['topic_data']['forum_desc'], '*slideshow')
				? "@{$this->ns[0]}_{$this->ns[1]}/viewtopic.html"
				: ''
		);
	}

	function page_footer($vars) {
		// Assign a template
		if ($this->my_template)
			$this->template->set_filenames([
				'body' => $this->my_template,
			]);
	}

	function viewtopic_modify_post_data($vars) {
		$previous_post = 0;
		foreach ($vars['attachments'] AS $post_id => $post_attachments) {
			$row = $vars['rowset'][$post_id];
			$row['previous_post'] = $previous_post;
			$previous_post = $post_id;

			// BBCodes
			$row['message'] = generate_text_for_display(
				$row['post_text'],
				$row['bbcode_uid'], $row['bbcode_bitfield'],
				OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS
			);

			$this->template->assign_block_vars (
				'post',
				array_change_key_case ($row, CASE_UPPER)
			);

			$previous_attach = 0;
			foreach ($post_attachments AS $attachment) {
				$row['previous_attach'] = $previous_attach;
				$previous_attach = $attachment['attach_id'];

				// Caractères indésirables
				foreach ($row AS $k=>$v)
					$row[$k] = preg_replace( ['/"/', '/[^[:print:]]/'], ['&quot;', ''], $v);

				$this->template->assign_block_vars (
					'post.slide',
					array_change_key_case (
						array_merge ($attachment, $row),
						CASE_UPPER
					)
				);
			}
		}

//		$this->attachments = $vars['attachments'];
	}

	function download_file_send_to_browser_before($vars) {
		$attachment = $vars['attachment'];
		if (!is_dir ('../cache/geo/'))
			mkdir ('../cache/geo/');

		if (is_file('../'.$attachment['real_filename'])) // Fichier relatif à la racine du site
			$attachment ['physical_filename'] = '../'.$attachment ['real_filename']; // script = download/file.php

//TODO seulement quand l'info n'est pas dans la base / ne pas oublier d'effacer !
		if ($exif = @exif_read_data ('../files/'.$attachment['physical_filename'])) {
			$fls = explode ('/', @$exif ['FocalLength']);
			if (count ($fls) == 2)
				$info[] = round($fls[0]/$fls[1]).'mm';

			$aps = explode ('/', @$exif ['FNumber']);
			if (count ($aps) == 2)
				$info[] = 'f/'.round($aps[0]/$aps[1], 1).'';

			$exs = explode ('/', @$exif ['ExposureTime']);
			if (count ($exs) == 2)
				$info[] = '1/'.round($exs[1]/$exs[0]).'s';

			if (@$exif['ISOSpeedRatings'])
				$info[] = $exif['ISOSpeedRatings'].'ASA';

			if (@$exif ['Model']) {
				if (@$exif ['Make'] &&
					strpos ($exif ['Model'], $exif ['Make']) === false)
					$info[] = $exif ['Make'];
				$info[] = $exif ['Model'];
			}

			$this->db->sql_query (implode (' ', [
				'UPDATE '.ATTACHMENTS_TABLE,
				'SET exif = "'.implode (' ', $info ?: ['~']).'",',
					'filetime = '.(strtotime(@$exif['DateTimeOriginal']) ?: @$exif['FileDateTime'] ?: @$attachment['filetime']),
				'WHERE attach_id = '.$attachment['attach_id']
			]));
		}

		// Reduction de la taille de l'image
		if ($max_size = request_var('size', 0)) {
			$img_size = @getimagesize ('../files/'.$attachment['physical_filename']);
			$isx = $img_size [0]; $isy = $img_size [1];
			$reduction = max ($isx / $max_size, $isy / $max_size);
			if ($reduction > 1) { // Il faut reduire l'image
				$pn = pathinfo ($attachment['physical_filename']);
				$temporaire = '../cache/geo/'.$pn['basename'].'.'.$max_size.@$pn['extension'];

				// Si le fichier temporaire n'existe pas, il faut le creer
				if (!is_file ($temporaire)) {
					$mimetype = explode('/',$attachment['mimetype']);

					// Get source image
					$imgcreate = 'imagecreatefrom'.$mimetype[1]; // imagecreatefromjpeg / imagecreatefrompng / imagecreatefromgif
					$image_src = $imgcreate ('../files/'.$attachment['physical_filename']);

					// Detect orientation
					$angle = [
						3 => 180,
						6 => -90,
						8 =>  90,
					];
					$a = @$angle [$exif ['Orientation']];
					if ($a)
						$image_src = imagerotate ($image_src, $a, 0);
					if (abs ($a) == 90) {
						$tmp = $isx;
						$isx = $isy;
						$isy = $tmp;
					}

					// Build destination image
					$image_dest = imagecreatetruecolor ($isx / $reduction, $isy / $reduction);
					imagecopyresampled ($image_dest, $image_src, 0,0, 0,0, $isx / $reduction, $isy / $reduction, $isx, $isy);

					// Convert image
					$imgconv = 'image'.$mimetype[1]; // imagejpeg / imagepng / imagegif
					$imgconv ($image_dest, $temporaire);

					// Cleanup
					imagedestroy ($image_dest);
					imagedestroy ($image_src);
				}
				$attachment['physical_filename'] = $temporaire;
			}
		}

		$vars['attachment'] = $attachment;
	}

/*
	// Appelé lors de la première passe sur les données des posts qui lit les données SQL de phpbb-posts
	function viewtopic_post_rowset_data($vars) {
		return;
		// Mémorise les données SQL du post pour traitement plus loin
		$this->post_data [$vars['row']['post_id']] = $vars['row'];
	}

	// Insère des miniatures des liens.jpg insérés dans les messages
	function viewtopic_modify_post_row($vars) { // ligne 2006
		return;
		global $db;
		$post_row = $vars['post_row'];
		preg_match_all('/href="(http[^"]*\.(jpe?g|png))"[^>]*>([^<]*\.(jpe?g|png))<\/a>/i', $post_row['MESSAGE'], $imgs); // Récupère les urls d'images

		foreach ($imgs[1] AS $k=>$href) {
			$sql_rch = "SELECT * FROM ".ATTACHMENTS_TABLE." WHERE real_filename = '".addslashes($href)."'";
			$result = $this->db->sql_query_limit($sql_rch, 1);
			$r = $this->db->sql_fetchrow($result);
			if(!$r) { // L'image n'est pas dans la base
				$sql_ary = array(
					'physical_filename'	=> $href,
					'attach_comment'	=> $href,
					'real_filename'		=> $href,
					'extension'			=> 'jpg',
					'mimetype'			=> 'image/jpeg',
					'filesize'			=> 0,
					'filetime'			=> time(),
					'thumbnail'			=> 0,
					'is_orphan'			=> 0,
					'in_message'		=> 0,
					'post_msg_id'		=> $vars['row']['post_id'],
					'topic_id'			=> $vars['row']['topic_id'],
					'poster_id'			=> $vars['poster_id'],
				);
				$db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
				$result = $this->db->sql_query_limit($sql_rch, 1);
				$r = $this->db->sql_fetchrow($result);
			}

			$post_row['MESSAGE'] = str_replace (
				$href.'">'.$imgs[3][$k].'<',
				$href.'"><img title="'.$href.'" alt="'.$href.'" style="border:5px solid #F3E358" src="download/file.php?id='.$r['attach_id'].'&size=200&'.time().'"><',
				$post_row['MESSAGE']
			);
		}
		$vars['post_row'] = $post_row;
	}

	function parse_attachments_modify_template_data($vars) {
		return;
		if (@$this->attachments) {
			$post_id = $vars['attachment']['post_msg_id'];

			// Assigne les valeurs au template
			$this->block_array = $vars['block_array'];
			$this->block_array['TEXT_SIZE'] = strlen (@$this->post_data[$post_id]['post_text']) * count($this->attachments[$post_id]);
			$this->block_array['DATE'] = str_replace (' 00:00', '', $this->user->format_date($vars['attachment']['filetime']));
			$this->block_array['AUTEUR'] = $vars['row']['user_sig']; // GEO-TODO DCMM Retrouver le nom du "poster_id" : $vars['attachment']['poster_id'] ??
			$this->block_array['EXIF'] = $vars['attachment']['exif'];
			foreach ($vars['attachment'] AS $k=>$v)
				$this->block_array[strtoupper($k)] = $v;
			$vars['block_array'] = $this->block_array;

			// Ceci va assigner un template à {postrow.attachment.DISPLAY_ATTACHMENT}
			$nf = 'viewtopic_'.request_var('view', 'body').'_photo.html';
			if (file_exists ($this->root_path.'styles/'.$this->user->style['style_name'].'/template/'.$nf))
				$this->template->set_filenames ([
					'attachment_tpl' => $nf
				]);
		}
	}
	*/
}
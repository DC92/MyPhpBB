<?php
/**
 * Add usefull tricks to for phpBB
 *
 * Includes language and style files of this extension
 * Clickable banner
 * Prevent an empty post title or text
 * Warning to wait until end loading of attached files
 * Log posts edit
 *
 * DEBUG :
 * Disable Varnish
 * List template vars
 *
 * @copyright (c) 2020 Dominique Cavailhez
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Dominique92\MyPhpBB\event;

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
		$this->language = $language;

		// Include language files of this extension
		$ns = explode ('\\', __NAMESPACE__);
		$this->language->add_lang('common', $ns[0].'/'.$ns[1]);
	}

	// List of hooks and related functions
	// We find the calling point by searching in the software of PhpBB 3.x: "event core.<XXX>"
	static public function getSubscribedEvents() {

		// For debug, Varnish will not be caching pages where you are setting a cookie
		if (defined('DEBUG_CONTAINER'))
			setcookie('disable-varnish', microtime(true), time()+600, '/');

		return [
			// All
			'core.page_header' => 'page_header',
			'core.twig_environment_render_template_before' => 'twig_environment_render_template_before',

			// Posting
			'core.posting_modify_submission_errors' => 'posting_modify_submission_errors',
			'core.posting_modify_template_vars' => 'posting_modify_template_vars',
			'core.modify_submit_notification_data' => 'modify_submit_notification_data',
		];
	}

	function page_header() {
//*DCMM*/echo"<pre style='background:white;color:black;font-size:14px'>REQUEST_URI = ".var_export($this->request->get_super_global(\phpbb\request\request_interface::SERVER)['REQUEST_URI'],true).'</pre>';
//*DCMM*/echo"<pre style='background:white;color:black;font-size:14px'>COOKIE = ".var_export($this->request->get_super_global(\phpbb\request\request_interface::COOKIE),true).'</pre>';
	}

	function twig_environment_render_template_before($vars) {
		// Display the template variables
/*DCMM*/return;
		if($vars['name'] != 'attachment.html') {
			echo '<p><b>TEMPLATE '.$vars['name'].' : '.count($vars['context']).' variables</b></p>';
			foreach($vars['context'] AS $k=>$v)
				if (gettype ($v) != 'object')
					echo"<pre>$k (".gettype ($v).") = ".var_export($v,true).'</pre>';
		}
	}

	/**
		POSTING.PHP
	*/
	function posting_modify_submission_errors($vars) {
		$error = $vars['error'];

		// Allows entering a POST with empty text
		foreach ($error AS $k=>$v)
			if ($v == $this->user->lang['TOO_FEW_CHARS'])
				unset ($error[$k]);

		$vars['error'] = $error;
	}

	// Called when viewing the post page
	function posting_modify_template_vars($vars) {
		$post_data = $vars['post_data'];
		$page_data = $vars['page_data'];

		// Prevent an empty title to invalidate the full page and input.
		if (!$post_data['post_subject'])
			$page_data['DRAFT_SUBJECT'] = $this->post_name ?: 'New';

		// Keep trace of values prior to modifications
		// Create a log file with the post existing data if there is none

		// Create the LOG directory if none
		if (!is_dir('LOG'))
			mkdir('LOG');
		// Add a blank file if none
		file_put_contents ('LOG/index.html', '');

		// Create the file with the existing post data
		$file_name = 'LOG/'.$post_data['post_id'].'.txt';
		if (!file_exists ($file_name))
			file_put_contents ($file_name,
				pack('CCC',0xef,0xbb,0xbf). // UTF-8 encoding
				date('r').PHP_EOL.
				'Titre: '.$post_data['post_subject'].PHP_EOL.
				$post_data['post_text'].PHP_EOL.
				$this->specific_data($post_data).PHP_EOL
			);

		$vars['page_data'] = $page_data;
	}

	// Called after the post validation
	function modify_submit_notification_data($vars) {
		$post_data = $vars['data_ary'];
		$post = $this->request->get_super_global(\phpbb\request\request_interface::POST);

		// Log new post data
		$file_name = 'LOG/'.$post_data['post_id'].'.txt';
		file_put_contents ($file_name,
			'_______________________________'.PHP_EOL.
			date('r').' '.$this->user->data['username'].PHP_EOL.
			'Titre: '.$post['subject'].PHP_EOL.
			$post['message'].PHP_EOL.
			$this->specific_data($post).PHP_EOL,
		FILE_APPEND);
	}

	function specific_data($post_data) {
		$r = '';
		foreach ($post_data AS $k=>$v)
			if ($k[3] == '_' &&
				$v &&
				$v != '00' &&
				$v != '0' &&
				$v != '?' &&
				$v != 'off')
				$r .= $k.': '.(is_array($v) ? implode(',',$v) : $v).PHP_EOL;
		return $r;
	}
}
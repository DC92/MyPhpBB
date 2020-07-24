<?php
/**
*
* This file is part of the french language pack for the Gym Forum Software package.
*
* @copyright (c) 2020 Dominique Cavailhez
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

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ « » “ ” …
//

$lang = array_merge($lang, array(
	// Login
	'USERNAME' => 'Prénom',

	// Posting.php
	'SUBJECT' => 'Titre',
	'SUBMIT' => 'Enregistrer',
	'EDIT_POST' => 'Modifier une fiche',
	'POST_REPLY' => 'Créer une nouvelle fiche',
	'DELETE_POST_PERMANENTLY' => array(
		1	=> 'Supprimer cette fiche',
		2	=> 'Supprimer ces fiches',
	),
	'DELETE_MESSAGE_CONFIRM' => 'Êtes-vous sûr de vouloir supprimer cette fiche ?',
	'DELETE_POST_CONFIRM' => 'Êtes-vous sûr de vouloir supprimer cette fiche ?',
	'STICK_TOPIC_FOR_EXPLAIN' => '',
	'CONTACT_ADMIN' => 'Nous contacter',

	// Footer
	'FINAL_INTEGRATION_BY' =>
		'&copy; Chavil\'GYM 2020'.
		' : <a href="memberlist.php?mode=contactadmin">Contact</a>'.
		' - Réalisé par <a href="https://github.com/Dominique92">Dominique</a>'.
		' &amp; <a href="http://www.phpbb-fr.com/">phpBB</a>',
));

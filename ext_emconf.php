<?php

########################################################################
# Extension Manager/Repository config file for ext: "linkhandler"
#
# Auto generated 20-04-2009 11:43
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'AOE link handler',
	'description' => 'Enables user friendly links to records like tt_news etc... Configure new Tabs to the link-wizard. (by AOE  GmbH)',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.0.6',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Daniel Poetzinger, Michael Klapper',
	'author_email' => 'daniel.poetzinger@aoe.com,michael.klapper@aoe.com',
	'author_company' => 'AOE GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.1.0-0.0.0',
		),
		'conflicts' => array(
			'ch_rterecords',
			'tinymce_rte',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array(
	),
);

?>

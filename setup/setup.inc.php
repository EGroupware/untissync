<?php
/**
 * EGroupware - UntisSync
 */

if (!defined('UNTISSYNC_APP'))
{
	define('UNTISSYNC_APP','untissync');
}

$setup_info[UNTISSYNC_APP]['name']      = 'untissync';
$setup_info[UNTISSYNC_APP]['title']     = 'UntisSync';
$setup_info[UNTISSYNC_APP]['version']   = '20.1.001';  //anything you like, as long as it is fitting the schema of a version number
$setup_info[UNTISSYNC_APP]['app_order'] = 100;        // at the end

$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_teacher';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_class';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_subject';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_room';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_substitution';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_participant';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_timetable';
$setup_info[UNTISSYNC_APP]['tables'][]    = 'egw_untissync_timegrid';






$setup_info[UNTISSYNC_APP]['enable']    = 1;


/* The hooks this app includes, needed for hooks registration */
$setup_info[UNTISSYNC_APP]['hooks']['sidebox_menu'] = 'untissync_hooks::all_hooks';
$setup_info[UNTISSYNC_APP]['hooks']['settings'] = 'untissync_hooks::settings';
$setup_info[UNTISSYNC_APP]['hooks']['search_link']	= 'untissync_hooks::search_link';

// Setup
/*$setup_info[UNTISSYNC_APP]['check_install'] = array(
	'Text_Diff'	=> array(
		'func'	=> 'pear_check',
		'from'	=> 'UntisSync (diff in notifications)'
	)
);*/

/* Dependencies for this app to work */
$setup_info[UNTISSYNC_APP]['depends'][] = array
(
	'appname'  => 'api',
	'versions' => Array('20.1')
);

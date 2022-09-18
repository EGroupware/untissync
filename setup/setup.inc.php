<?php
/**
 * EGroupware - UntisSync
 *
 * @link http://www.egroupware.org
 * @author Axel Wild
 * @package untissync
 * @subpackage setup
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

if (!defined('UNTISSYNC_APP'))
{
	define('UNTISSYNC_APP','untissync');
}

$setup_info[UNTISSYNC_APP]['name']      = 'untissync';
$setup_info[UNTISSYNC_APP]['title']     = 'UntisSync';
$setup_info[UNTISSYNC_APP]['version']   = '21.1.1';
$setup_info[UNTISSYNC_APP]['app_order'] = 100;

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
$setup_info[UNTISSYNC_APP]['hooks']['settings'] = 'untissync_hooks::settings';
$setup_info[UNTISSYNC_APP]['hooks']['admin'] = 'untissync_hooks::all_hooks';
$setup_info[UNTISSYNC_APP]['hooks']['sidebox_menu'] = 'untissync_hooks::all_hooks';
$setup_info[UNTISSYNC_APP]['hooks']['search_link']	= 'untissync_hooks::search_link';

/* Dependencies for this app to work */
$setup_info[UNTISSYNC_APP]['depends'][] = array
(
	'appname'  => 'api',
	'versions' => Array('21.1')
);

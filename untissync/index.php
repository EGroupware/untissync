<?php
/**
 * UntisSync - index
 *
 */
use EGroupware\Api\Framework;

include_once('./setup/setup.inc.php');
$ts_version = $setup_info['untissync']['version'];
unset($setup_info);

$GLOBALS['egw_info'] = array(
    'flags' => array(
        'currentapp'	=> 'untissync',
        'noheader'		=> True,
        'nonavbar'		=> True
    ));
include('../header.inc.php');

if ($ts_version != $GLOBALS['egw_info']['apps']['untissync']['version'])
{
    Framework::render('<p style="text-align: center; color:#ff0000; font-weight: bold;">' .
        lang('Your database is NOT up to date (%1 vs. %2), please run %3setup%4 to update your database.',
            $ts_version,$GLOBALS['egw_info']['apps']['schulmanager']['version'],
            '<a href="../setup/">','</a>')."</p>\n", null, true);
            exit();
}

Framework::redirect_link('/index.php',array('menuaction'=>'untissync.untissync_ui.list'));


<?php
/**
 * EGroupware - Schulmanager - setup table updates
 *
 * @link http://www.egroupware.org
 * @author Wild Axel
 * @package schulmanager
 * @subpackage setup
 * @copyright (c) 2019 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

use EGroupware\Api;

function untissync_upgrade19_1_001()
{
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_class','kl_egw_group_id',array('type' => 'int',	'precision' => '11', 'comment' => 'egw group uid'	));    
    
    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.002';
}

function untissync_upgrade19_1_002()
{
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_clean',array('type' => 'int',	'precision' => '4', 'comment' => 'if false, timetable has to be updated'	));    
    
    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.003';
}

function untissync_upgrade19_1_003()
{
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_timetable','tt_clean',array('type' => 'int',	'precision' => '4', 'comment' => 'if false, timetable has to be updated'	));
    
    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.004';
}

function untissync_upgrade19_1_004()
{
    $GLOBALS['egw_setup']->oProc->CreateTable('egw_untissync_timegrid', array(
        'fd' => array(
            'tg_id' => array('type' => 'auto','nullable' => False,'comment' => 'object id -> sub_id or tt_id'),
            'tg_day' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'subtsitution id, or timetable id'),
            'tg_name' => array('type' => 'varchar','precision' => '20','nullable' => False,'comment' => 'name'),
            'tg_start' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'start time'),
            'tg_end' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'start time'),
            'tg_created' => array('type' => 'timestamp','meta' => 'timestamp','default' => 'current_timestamp','comment' => 'creation time'),
        ),
        'pk' => array('tg_id'),
        'fk' => array(),
        'ix' => array(),
        'uc' => array()
    ));
    
    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.005';
}

function untissync_upgrade19_1_005()
{
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_teacher', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'teacher list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_teacher_long', array('type' => 'varchar','precision' => '255','nullable' => False,'comment' => 'teacher list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_teacher_org', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'teacher org list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_teacher_org_long', array('type' => 'varchar','precision' => '255','nullable' => False,'comment' => 'teacher org list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_klasse', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klasse list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_klasse_org', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klasse org list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_room', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'room list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_room_org', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'room org list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_subject', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'subject list'));
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_substitution','sub_subject_org', array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'subject org list'));

    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.006';
}

function untissync_upgrade19_1_006()
{
    $GLOBALS['egw_setup']->oProc->AlterColumn('egw_untissync_substitution','sub_date', array('type' => 'int','precision' => '10','nullable' => False,'comment' => 'date'));
    $GLOBALS['egw_setup']->oProc->AlterColumn('egw_untissync_timetable','tt_date', array('type' => 'int','precision' => '10','nullable' => False,'comment' => 'date'));

    return $GLOBALS['setup_info']['untissync']['currentver'] = '19.1.007';
}

function untissync_upgrade19_1_007()
{
    return $GLOBALS['setup_info']['untissync']['currentver'] = '20.1.001';
}

function untissync_upgrade20_1_001()
{
    $GLOBALS['egw_setup']->oProc->CreateIndex('egw_untissync_participant', array('pa_parentid','pa_parenttable','pa_partid','pa_parttype'),false);
    $GLOBALS['egw_setup']->oProc->CreateIndex('egw_untissync_class', array('kl_uid'),false);
    $GLOBALS['egw_setup']->oProc->CreateIndex('egw_untissync_timetable', array('tt_egw_cal_id'),false);
    return $GLOBALS['setup_info']['untissync']['currentver'] = '21.1';
}

function untissync_upgrade21_1()
{
    $GLOBALS['egw_setup']->oProc->AddColumn('egw_untissync_teacher','te_last_untis_sync', array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last untis sync'));
    return $GLOBALS['setup_info']['untissync']['currentver'] = '21.1.1';
}
function untissync_upgrade21_1_1()
{
    return $GLOBALS['setup_info']['untissync']['currentver'] = '23.1';
}




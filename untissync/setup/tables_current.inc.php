<?php
/**
 * eGroupWare - Setup
 * http://www.egroupware.org
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package stundenplan
 * @subpackage setup
 * @version $Id$
 */


$phpgw_baseline = array(
	// Benutzer aus ASV	
	'egw_untissync_teacher' => array(
		'fd' => array(
			'te_id' => array('type' => 'auto','nullable' => False,'comment' => 'tt id'),
			'te_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Teacher Untis-ID'),
			'te_name' => array('type' => 'varchar','precision' => '40','nullable' => False,'comment' => 'name'),
			'te_forename' => array('type' => 'varchar','precision' => '40','nullable' => False,'comment' => 'name'),
			'te_longname' => array('type' => 'varchar','precision' => '40','nullable' => False,'comment' => 'name lang'),
		    'te_active' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0','comment' => '1=active, 0=do not sync'),
			'te_egw_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'egw cuser uid'),
		    'te_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
		    'te_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),

		),
		'pk' => array('te_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_untissync_class' => array(
		'fd' => array(
			'kl_id' => array('type' => 'auto','nullable' => False,'comment' => 'klasse id'),
			'kl_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Untis-ID'),
			'kl_name' => array('type' => 'varchar','precision' => '10','nullable' => False,'comment' => 'name'),
			'kl_longname' => array('type' => 'varchar','precision' => '25','nullable' => False,'comment' => 'name lang'),
		    'kl_active' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0','comment' => '1=active, 0=do not sync'),
		    'kl_egw_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'egw cuser uid'),
		    'kl_egw_group_id' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'egw group uid'),
		    'kl_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
		    'kl_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
		),
		'pk' => array('kl_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_untissync_subject' => array(
		'fd' => array(
			'su_id' => array('type' => 'auto','nullable' => False,'comment' => 'subject id'),
			'su_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Untis-ID'),
			'su_name' => array('type' => 'varchar','precision' => '10','nullable' => False,'comment' => 'name'),
			'su_longname' => array('type' => 'varchar','precision' => '25','nullable' => False,'comment' => 'name'),
		    'su_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
		    'su_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
		),
		'pk' => array('su_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_untissync_room' => array(
		'fd' => array(
			'ro_id' => array('type' => 'auto','nullable' => False,'comment' => 'subject id'),
			'ro_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Untis-ID'),
			'ro_name' => array('type' => 'varchar','precision' => '10','nullable' => False,'comment' => 'name'),
			'ro_longname' => array('type' => 'varchar','precision' => '25','nullable' => False,'comment' => 'name'),
		    'ro_active' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0','comment' => '1=active, 0=do not sync'),
			'ro_egw_res_id' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'egroupware ressource id'),
		    'ro_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
		    'ro_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
		),
		'pk' => array('ro_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_untissync_substitution' => array(
		'fd' => array(
			'sub_id' => array('type' => 'auto','nullable' => False,'comment' => 'subject id'),
			'sub_type' => array('type' => 'varchar','precision' => '16','nullable' => False,'comment' => 'name'),
			'sub_lsid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'lesson Untis-ID'),
			'sub_date' => array('type' => 'int','precision' => '10','nullable' => False,'comment' => 'date'),
			'sub_starttime' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'start time'),
			'sub_endtime' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'end time'),			
			'sub_txt' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'text'),			
		    'sub_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
			'sub_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
			'sub_clean' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0','comment' => 'if false, timetable has to be updated'),
            'sub_teacher' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'teacher list'),
            'sub_teacher_long' => array('type' => 'varchar','precision' => '255','nullable' => False,'comment' => 'teacher list'),
            'sub_teacher_org' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'teacher list org'),
            'sub_teacher_org_long' => array('type' => 'varchar','precision' => '255','nullable' => False,'comment' => 'teacher list org'),
            'sub_klasse' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klasse list'),
            'sub_klasse_org' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klasse org list'),
            'sub_room' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'room list'),
            'sub_room_org' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'room list org'),
            'sub_subject' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'subject list'),
            'sub_subject_org' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'subject org list'),
        ),
		'pk' => array('sub_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
    'egw_untissync_participant' => array(
        'fd' => array(
            'pa_id' => array('type' => 'auto','nullable' => False,'comment' => 'object id -> sub_id or tt_id'),
            'pa_parentid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'subtsitution id, or timetable id'),
            'pa_parenttable' => array('type' => 'varchar','precision' => '8','nullable' => False,'comment' => 'parent table, timetable or substitution'),            
            'pa_partid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'klassen id'),
            'pa_parttype' => array('type' => 'varchar','precision' => '16','nullable' => False,'comment' => 'type of participant: te, ro, su or kl'),            
            'pa_partname' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klassen name'),
            'pa_partorgid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'klassen original id'),
            'pa_partorgname' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klassen original name'),            
            'pa_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
            'pa_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
        ),
        'pk' => array('pa_id'),
        'fk' => array(),
        'ix' => array(),
        'uc' => array()
    ),
    'egw_untissync_timetable' => array(
        'fd' => array(
            'tt_id' => array('type' => 'auto','nullable' => False,'comment' => 'subject id'),
            'tt_uid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Untis-ID'), 
            'tt_teuid' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'Untis Teacher ID'), 
            'tt_egw_cal_id' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'egroupware calendar id'),
            'tt_date' => array('type' => 'int','precision' => '10','nullable' => False,'comment' => 'date'),
            'tt_starttime' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'start time'),
            'tt_endtime' => array('type' => 'int','precision' => '11','nullable' => False,'comment' => 'end time'),                     
            'tt_lstype' => array('type' => 'varchar','precision' => '20','nullable' => False,'comment' => 'name'),            
            'tt_code' => array('type' => 'varchar','precision' => '20','nullable' => False,'comment' => 'klassen id'),
            'tt_lstext' => array('type' => 'varchar','precision' => '100','nullable' => False,'comment' => 'klassen name'),
            'tt_statsflags' => array('type' => 'varchar','precision' => '25','nullable' => False,'comment' => 'klassen original id'),
            'tt_activitytype' => array('type' => 'varchar','precision' => '50','nullable' => False,'comment' => 'klassen original name'),  
            'tt_created' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the creation date'),
		    'tt_modified' => array('type' => 'int','meta' => 'timestamp','precision' => '8','comment' => 'timestamp of the last modificatione'),
            'tt_clean' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0','comment' => 'if false, timetable has to be updated'),
        ),
        'pk' => array('tt_id'),
        'fk' => array(),
        'ix' => array(),
        'uc' => array()
    ),
    'egw_untissync_timegrid' => array(
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
    ),
);


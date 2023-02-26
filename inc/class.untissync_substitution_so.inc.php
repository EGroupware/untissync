<?php

/**
 * EGroupware - UntisSync - Substitution object
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api;
use EGroupware\Api\Storage;

/**
 * Substitutions need to be updated after the table has been cleared.
 *
 * Substitutions are updated regularly. Before this process starts, all substitutions are marked as unclean. If a new updated substitution is identical with a saved substitution, this substitution is marked as clean.
 * After this process, all teachers of unclean substitutions are filtered. For these teachers, theri timetable will be updated. At the end, all substitutions are marked as clean.
 * CleanFlag -1: not used anymore, 0: new, 1: same as before
 */

class untissync_substitution_so extends Api\Storage {
    
    var $types = array(
        'cancel' => 'cancellation',
        'subst' => 'teacher substitution',
        'add' => 'additional period',
        'shift' => 'shifted period',
        'rmchg' => 'room change',
        'rmlk' => 'locked period',
        'bs' => 'break supervision',
        'oh' => 'office hour',
        'sb' => 'standby',
        'other' => 'foreign substitutions',
        'free' => 'free period',
        'exam' => 'exam',
        'ac' => 'activity',
        'holi' => 'holiday',
        'stxt' => 'substitution text',
    );

    var $types_german = array(
        'cancel' => 'Entfall',
        'subst' => 'Lehrervertretung',
        'add' => 'zusätzliche Stunde',
        'shift' => 'verschobene Stunde',
        'rmchg' => 'Raumwechsel',
        'rmlk' => 'gesperrte Stunde',
        'bs' => 'Pausenaufsicht',
        'oh' => 'Bürostunde',
        'sb' => 'Reserve',
        'other' => 'sonstige',
        'free' => 'Freistunde',
        'exam' => 'Prüfung',
        'ac' => 'Aktivität',
        'holi' => 'Ferien',
        'stxt' => 'Vertretungstext',
    );
    
    var $weekdays = array(
        1 => 'So',
        2 => 'Mo',
        3 => 'Di',
        4 => 'Mi',
        5 => 'Do',
        6 => 'Fr',
        7 => 'Sa',               
    );
    
    var $untissync_substitution_table = 'egw_untissync_substitution';
    
    var $value_col = array();
    
    var $participant_so;
    
    public function __construct(){
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_substitution_table);
        
        $this->participant_so = new untissync_participant_so();
        
        $this->setup_table('untissync', $this->untissync_substitution_table);
        
        $this->debug = 0;
        
        $this->value_col['sub_id'] = 'sub_id';
        $this->value_col['sub_type'] = 'sub_type';
        $this->value_col['sub_lsid'] = 'sub_lsid';
        $this->value_col['sub_date'] = 'sub_date';
        $this->value_col['sub_starttime'] = 'sub_starttime';
        $this->value_col['sub_endtime'] = 'sub_endtime';
        $this->value_col['sub_txt'] = 'sub_txt';
        $this->value_col['sub_created'] = 'sub_created';
        $this->value_col['sub_modified'] = 'sub_modified';
        $this->value_col['sub_clean'] = 'sub_clean';

        // added data to increase performance of database queries
        $this->value_col['sub_teacher'] = 'sub_teacher';
        $this->value_col['sub_teacher_long'] = 'sub_teacher_long';
        $this->value_col['sub_teacher_org'] = 'sub_teacher_org';
        $this->value_col['sub_teacher_org_long'] = 'sub_teacher_org_long';
        $this->value_col['sub_klasse'] = 'sub_klasse';
        $this->value_col['sub_klasse_org'] = 'sub_klasse_org';
        $this->value_col['sub_room'] = 'sub_room';
        $this->value_col['sub_room_org'] = 'sub_room_org';
        $this->value_col['sub_subject'] = 'sub_subject';
        $this->value_col['sub_subject_org'] = 'sub_subject_org';
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
    }
    
    /**
     * 
     * @param unknown $sub_type
     * @param unknown $sub_lsid
     * @param unknown $sub_date
     * @param unknown $sub_starttime
     * @param unknown $sub_endtime
     * @param unknown $sub_kl_ids
     * @param unknown $sub_te_ids
     * @param unknown $sub_ro_ids
     * @param unknown $sub_su_ids
     * @param unknown $sub_txt
     * @return boolean true if event has been updated and egw calendar has to be updated. false means events was not modified
     */
    function write($sub_type, $sub_lsid, $sub_date, $sub_starttime, $sub_endtime, $sub_kl, $sub_te, $sub_ro, $sub_su, $sub_txt, array $untisTeacherSet){
        $time = time();
        $key_col = "";
        
        $substitution = array(
            'sub_type' => $sub_type,
            'sub_lsid' => $sub_lsid,
            'sub_date' => $sub_date,
            'sub_starttime' => $sub_starttime,
            'sub_endtime' => $sub_endtime,           
            'sub_txt' => $sub_txt,
            'sub_modified' => $time,
            'sub_clean' => 0,
        );
        $this->data = $substitution;

        // test if sub has been modified
        $filter = array(
            'sub_type' => $sub_type,
            'sub_lsid' => $sub_lsid,
            'sub_date' => $sub_date,
            'sub_starttime' => $sub_starttime,
            'sub_endtime' => $sub_endtime,
            'sub_txt' => $sub_txt,
        );
            
        $result = $this->read($filter);
        
        if(is_array($result)){
            // substitution exists in DB
            $updateFields = array();
            $updateFields['sub_modified'] = $time;

            // TODO Check if this can be removed?
            $updateFields['sub_teacher'] = $this->implode_filter(', ', $sub_te, 'name');
            $updateFields['sub_teacher_long'] = $this->implode_filterTeacher(', ', $sub_te, $untisTeacherSet, 'id', 'name');
            $updateFields['sub_teacher_org'] = $this->implode_filter(', ', $sub_te, 'orgname');
            $updateFields['sub_teacher_org_long'] = $this->implode_filterTeacher(', ', $sub_te, $untisTeacherSet, 'orgid', 'orgname');
            $updateFields['sub_klasse'] = $this->implode_filter(', ', $sub_kl, 'name');
            $updateFields['sub_klasse_org'] = $this->implode_filter(', ', $sub_kl, 'orgname');
            $updateFields['sub_room'] = $this->implode_filter(', ', $sub_ro, 'name');
            $updateFields['sub_room_org'] = $this->implode_filter(', ', $sub_ro, 'orgname');
            $updateFields['sub_subject'] = $this->implode_filter(', ', $sub_su, 'name');
            $updateFields['sub_subject_org'] = $this->implode_filter(', ', $sub_su, 'orgname');
            //

            if($this->equals($substitution, $result, $sub_kl, $sub_te, $sub_ro, $sub_su)){
                // substitution not modified
                $updateFields['sub_clean'] = 1; 
                if(parent::update($updateFields) != 0) return false;
                return false;
                // finished - nothing to do anymore
            }
            else{
                // v modified
                // delete participants, set flag for unclean state
                $updateFields['sub_clean'] = 0;                
                $this->participant_so->deleteAllParticipants( $result['sub_id'], 'sub');
                if(parent::update($updateFields) != 0) return false;
            }
            
        }
        else{
            // new substitution
            $this->data = $substitution;
            $this->data['sub_created'] = $time;
            // ================ new ============
            // save data in substitution table, so GROUP BY and GROUP_CONCAT ist not necessary anymore!
            $this->data['sub_teacher'] = $this->implode_filter(', ', $sub_te, 'name');
            $this->data['sub_teacher_long'] = $this->implode_filterTeacher(', ', $sub_te, $untisTeacherSet, 'id', 'name');
            $this->data['sub_teacher_org'] = $this->implode_filter(', ', $sub_te, 'orgname');
            $this->data['sub_teacher_org_long'] = $this->implode_filterTeacher(', ', $sub_te, $untisTeacherSet, 'orgid', 'orgname');
            $this->data['sub_klasse'] = $this->implode_filter(', ', $sub_kl, 'name');
            $this->data['sub_klasse_org'] = $this->implode_filter(', ', $sub_kl, 'orgname');
            $this->data['sub_room'] = $this->implode_filter(', ', $sub_ro, 'name');
            $this->data['sub_room_org'] = $this->implode_filter(', ', $sub_ro, 'orgname');
            $this->data['sub_subject'] = $this->implode_filter(', ', $sub_su, 'name');
            $this->data['sub_subject_org'] = $this->implode_filter(', ', $sub_su, 'orgname');
            
            if(parent::save() != 0) return false;     
            
            $filter = array(
                'sub_type' => $sub_type,
                'sub_lsid' => $sub_lsid,
                'sub_date' => $sub_date,
                'sub_starttime' => $sub_starttime,
                'sub_endtime' => $sub_endtime,
                'sub_txt' => $sub_txt,
                'sub_modified' => $time,
            );
            
            $result = $this->read($filter); // get id from new inserted record
        }
        
        // save participants
        if(isset($result['sub_id'])){
            $parent_id = $result['sub_id'];
        
            foreach ($sub_kl as &$kl) {
                // ($pa_parentid, $pa_parenttable, $pa_partid, $pa_parttype, $pa_partname, $pa_partorgid, $pa_partorgname){
                $this->participant_so->write($parent_id, 'sub', $kl['id'], 'kl', $kl['name'], $kl['orgid'], $kl['orgname']);
            }
            // te
            foreach ($sub_te as &$te) {
                $this->participant_so->write($parent_id, 'sub', $te['id'], 'te', $te['name'], $te['orgid'], $te['orgname']);
            }
            // su
            foreach ($sub_su as &$su) {
                $this->participant_so->write($parent_id, 'sub', $su['id'], 'su', $su['name'], $su['orgid'], $su['orgname']);
            }
            // ro
            foreach ($sub_ro as &$ro) {
                $this->participant_so->write($parent_id, 'sub', $ro['id'], 'ro', $ro['name'], $ro['orgid'], $ro['orgname']);
            }
        }
    
        return true;
        /**/
    }

    /**
     * This function filters an array and implodes its values
     * @param string $glue
     * @param array $pieces
     * @param string $key id, name orgid, orgname
     * @return string
     */
    private function implode_filter(string $glue, array $pieces, string $key){
        $array_filtered = array_column($pieces, $key);
        return implode($glue, $array_filtered);
    }

    /**
     * @param string $glue
     * @param array $arrTeacher
     * @param array $untisTeacherSet
     * @param string $id_key
     * @param string $name_key
     * @return string
     */
    private function implode_filterTeacher(string $glue, array $arrTeacher, array $untisTeacherSet, string $id_key, string $name_key){
        $result  = array();
        foreach ($arrTeacher as &$te) {
            $teacher = $untisTeacherSet[$te[$id_key]];
            if(isset($teacher)){
                $result[] = $teacher['te_longname'];
            }
            elseif (isset($te[$name_key])){
                $result[] = $te[$name_key];
            }
        }

        return implode($glue, $result);
    }


    /**
     * Tests if two substitutions are equals, incl. their participant
     */
    private function equals(array $sub1, array $sub2, $sub1_kl, $sub1_te, $sub1_ro, $sub1_su){
        $result = false;

        // test attributes
        $result = $sub1['sub_type'] == $sub2['sub_type'] &&  $sub1['sub_lsid'] == $sub2['sub_lsid'] &&  $sub1['sub_date'] == $sub2['sub_date'] && $sub1['sub_starttime'] == $sub2['sub_starttime'] && $sub1['sub_endtime'] == $sub2['sub_endtime'] && $sub1['sub_txt'] == $sub2['sub_txt'];
        
        if($result){
            // test participants       
            $part1_keys = array();
            $part2_keys = array();
            $parts2 = $this->participant_so->loadParticipants($sub2['sub_id'], 'sub');
            foreach($parts2 as $p2){
                $key = $p2['pa_parttype'].'-'.$p2['pa_partid'].'-'.$p2['pa_partorgid'];
                $part2_keys[$key] = $key;               
            }
            
            $this->participant_so->createParticipantKeys($part1_keys, $sub1_kl, 'kl');
            $this->participant_so->createParticipantKeys($part1_keys, $sub1_te, 'te');
            $this->participant_so->createParticipantKeys($part1_keys, $sub1_ro, 'ro');
            $this->participant_so->createParticipantKeys($part1_keys, $sub1_su, 'su');
            
            $result = $part1_keys == $part2_keys;

        }

        return $result;
    }

    /**
     * Set clean mark for all substitutions. Maybe some substitutions habe been deleted in untis! $clean = -1 : undefined (deleted?), 0 : unclean, 1 : clean
     * TODO: test if substitution is in past
     */
    function markAllUnClean(){
        $subs = $this->query_list($this->value_col);
        foreach ($subs as &$sub) {
            $sub['sub_clean'] = -1;
            $this->data = $sub;
            if(parent::save() != 0) return false;         
        }
    }

    /**
     * removes all records with sub_clean = -1; these record have timestamps in the past or where deleted from webuntis
     * @return unknown
     */
    function cleanUp(){
        $keys = array('sub_clean' => -1);
        $subs = $this->delete($keys, true);
        if(is_array($subs)){
            foreach($subs as $sub){
                $this->participant_so->deleteAllParticipants($sub, 'sub');
            }
        }
        $result = $this->delete($keys, false);
        return $result;        
    }

    /**
     * search all teacher ids (untis-id) from substitutions with sub_clean = 0
     * @param $startYmd
     * @return array
     */
    function parseTeacherUpdate($startYmd){
        $teacher = array();

        $tables = 'egw_untissync_substitution';
        $cols = 'egw_untissync_participant.pa_partid, egw_untissync_participant.pa_partorgid';
        $where = array(
            "egw_untissync_substitution.sub_clean <= 0",
            "egw_untissync_participant.pa_parenttable = 'sub'",
            "egw_untissync_participant.pa_parttype = 'te'",
            "egw_untissync_substitution.sub_date >= $startYmd",
        );
        
        $join = ' INNER JOIN egw_untissync_participant ON egw_untissync_substitution.sub_id = egw_untissync_participant.pa_parentid ';
        
        $result = $this->db->select($tables, $cols, $where, '', '', False, '', False, 0, $join);
        
        foreach($result as $te){
            if($te['pa_partid'] > 0){
                $teacher[$te['pa_partid']] = $te['pa_partid'];
            }
            if($te['pa_partorgid'] > 0){
                $teacher[$te['pa_partorgid']] = $te['pa_partorgid'];
            }
            
        }
        
        return $teacher;
    }
    
    
    /* QUERY, very slow! do not use! Still here as souvenir!
      SELECT
	egw_untissync_substitution.sub_id,
	egw_untissync_substitution.sub_date,
	egw_untissync_substitution.sub_type,
	egw_untissync_substitution.sub_txt,
	egw_untissync_substitution.sub_starttime,
	egw_untissync_substitution.sub_endtime,

	GROUP_CONCAT(DISTINCT IF(part_le.pa_partname='', null, part_le.pa_partname)) AS Vertretung,
	GROUP_CONCAT(DISTINCT IF(teacher.te_longname='', null, teacher.te_longname)) AS teacher_long,
	GROUP_CONCAT(DISTINCT IF(part_le.pa_partorgname='', null, part_le.pa_partorgname)) AS teacher,
   GROUP_CONCAT(DISTINCT IF(part_kl.pa_partname='', null, part_kl.pa_partname)) AS Klasse,
   GROUP_CONCAT(DISTINCT IF(part_ro.pa_partname='', null, part_ro.pa_partname)) AS Raum,
   GROUP_CONCAT(DISTINCT IF(part_ro.pa_partorgname='', null, part_ro.pa_partorgname)) AS Raum_alt,
   GROUP_CONCAT(DISTINCT IF(part_su.pa_partname='', null, part_su.pa_partname)) AS Fach

    FROM egw_untissync_substitution
    -- actual teacher
    LEFT JOIN egw_untissync_participant AS part_le ON (egw_untissync_substitution.sub_id = part_le.pa_parentid AND part_le.pa_parenttable = 'sub' AND part_le.pa_parttype = 'te')
     LEFT JOIN egw_untissync_teacher AS teacher ON part_le.pa_partid = teacher.te_uid
    -- actual class
    LEFT JOIN egw_untissync_participant AS part_kl ON (egw_untissync_substitution.sub_id = part_kl.pa_parentid AND part_le.pa_parenttable = 'sub' AND part_kl.pa_parttype = 'kl')
    -- room
    LEFT JOIN egw_untissync_participant AS part_ro ON (egw_untissync_substitution.sub_id = part_ro.pa_parentid AND part_ro.pa_parenttable = 'sub' AND part_ro.pa_parttype = 'ro')
    -- subject
    LEFT JOIN egw_untissync_participant AS part_su ON (egw_untissync_substitution.sub_id = part_su.pa_parentid AND part_su.pa_parenttable = 'sub' AND part_su.pa_parttype = 'su')
    
     WHERE teacher.te_longname LIKE '%LuW%' OR part_le.pa_partname LIKE '%LuW%' -- OR part_ro.pa_partname LIKE '%LuW%' OR part_kl.pa_partname LIKE '%LuW%'
    -- WHERE egw_untissync_substitution.sub_id  = 1
    
    GROUP BY egw_untissync_substitution.sub_id
    ORDER BY egw_untissync_substitution.sub_date, egw_untissync_substitution.sub_starttime
     */

    /**
     * Returns an array like "2-755-840" => "1. Stunde"
     * DayInWeek - start - end => name of lesson from Webuntis
     */
    public function getSubstitutionList(&$query_in, &$rows, &$readonlys, $id_only=false, array $tgStart, array $tgEnd){
        $query_search = $query_in['search'];
        $searchSQL = $this->db->quote('%'.$query_search.'%');

        $startDate = (new DateTime())->format('Ymd');

        $where = array();
        $where[] = "sub_date >= ".$startDate;

        if(!empty($query_search)){
            $where[] = "(egw_untissync_substitution.sub_teacher LIKE ".$searchSQL." OR egw_untissync_substitution.sub_teacher_org LIKE ".$searchSQL
                        ." OR egw_untissync_substitution.sub_teacher_long LIKE ".$searchSQL." OR egw_untissync_substitution.sub_teacher_org_long LIKE ".$searchSQL
                        ." OR egw_untissync_substitution.sub_room LIKE ".$searchSQL." OR egw_untissync_substitution.sub_klasse LIKE ".$searchSQL.")";
        }
        if(!empty($query_in['filter']) && $query_in['filter'] == 'hide_canceled'){
            $where[] = "egw_untissync_substitution.sub_type != 'cancel'";
        }

        $append = "ORDER BY egw_untissync_substitution.sub_date, egw_untissync_substitution.sub_starttime";

        $result = $this->query_list($this->value_col, '', $where, $append);

        if($query_in['start'] == 0){
            $query_in['total'] = sizeof($result);//->NumRows();
        }

        $rowIndex = $query_in['start'];

        $dayInWeekBefore = -1;

        foreach($result as $item){
            $date_Ymd = $item['sub_date'];
            $date = DateTime::createFromFormat('Ymd', $date_Ymd);
            $dayInWeek = $date->format('w') + 1;
            $starttime = $item['sub_starttime'];
            $endtime = $item['sub_endtime'];

            // lesson
            $keyStart = $dayInWeek.'-'.$starttime;
            $keyEnd = $dayInWeek.'-'.$endtime;

            $lsnStart = $tgStart[$keyStart];
            $lsnEnd = $tgEnd[$keyEnd];

            $lesson = $lsnStart;
            if(!empty($lsnStart) && $lsnStart != $lsnEnd){
                $lesson = $lsnStart.' - '.$lsnEnd;
            }
            elseif(empty($lsnStart)){
                // lesson 4/5 pause between lessons
                $lsnStart = $tgEnd[$keyStart];
                $lsnEnd = $tgStart[$keyEnd];
                if($lsnStart + 1 == $lsnEnd){
                    $lesson = $lsnStart.'/'.$lsnEnd;
                }
            }

            // time
            $starttime = substr($starttime, 0, strlen($starttime) - 2).':'.substr($starttime, -2);
            $endtime = substr($endtime, 0, strlen($endtime) - 2).':'.substr($endtime, -2);


            $cssClass = 'row_'.($dayInWeek % 3).'_'.($rowIndex % 2);
            if($dayInWeekBefore != -1 && $dayInWeek != $dayInWeekBefore){
                $cssClass = $cssClass.' row_day_newday';
            }

            $rows[] = array(
                'nm_id' => $item['sub_id'],
                'date' => $this->weekdays[$dayInWeek].' '.$date->format('d.m.Y'),
                'type' => $this->types_german[$item['sub_type']],
                'txt' => $item['sub_txt'],
                'lesson' => $lesson,
                'start' => $starttime,
                'end' => $endtime,
                'teacher' => $item['sub_teacher_long'],
                'teacher_org' => $item['sub_teacher_org'],
                'klasse' => $item['sub_klasse'],
                'room' => $item['sub_room'],
                'room_org' => $item['sub_room_org'],
                'subject' => $item['sub_subject'],
                //'class' => ($dayInWeek % 3 == 0 ? 'row_dayeven_' : 'row_dayodd_').($rowIndex % 2),
                'class' => $cssClass,
            );

            $rowIndex++;
            $dayInWeekBefore = $dayInWeek;
        }
    }
}
<?php

/**
 * EGroupware - UntisSync - Timetable storage object object
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

class untissync_timetable_so extends Api\Storage {
    
    var $untissync_timetable_table = 'egw_untissync_timetable';
    
    var $value_col = array();
    
    var $participant_so;
    
    public function __construct(){
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_timetable_table);
        
        $this->participant_so = new untissync_participant_so();
        
        $this->setup_table('untissync', $this->untissync_timetable_table);
        
        $this->debug = 0;
        
        $this->value_col['id'] = 'tt_id';
        $this->value_col['uid'] = 'tt_uid';
        $this->value_col['teuid'] = 'tt_teuid';
        $this->value_col['egw_cal_id'] = 'tt_egw_cal_id';
        $this->value_col['date'] = 'tt_date';
        $this->value_col['starttime'] = 'tt_starttime';
        $this->value_col['endtime'] = 'tt_endtime';
        $this->value_col['lstype'] = 'tt_lstype';
        $this->value_col['code'] = 'tt_code';
        $this->value_col['lstext'] = 'tt_lstext';
        $this->value_col['statsflags'] = 'tt_statsflags';
        $this->value_col['atcivitytype'] = 'tt_activitytype';
        $this->value_col['created'] = 'tt_created';
        $this->value_col['modified'] = 'tt_modified';       
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
    }
    
    /**
     * Saves a timetable object. First it is checked whether there are any changes. If necessary, this object will be updated with the new data
     * @param unknown $tt_uid
     * @param unknown $tt_date
     * @param unknown $tt_starttime
     * @param unknown $tt_endtime
     * @param unknown $tt_lstype
     * @param unknown $tt_code
     * @param unknown $tt_lstext
     * @param unknown $tt_statsflags
     * @param unknown $tt_activitytype
     * @param unknown $tt_kl
     * @param unknown $tt_te
     * @param unknown $tt_ro
     * @param unknown $tt_su
     * @return boolean  false if nothing has to be uodated in egw calendar, timetable event else
     */
    function write($tt_uid, $tt_teuid, $tt_date, $tt_starttime, $tt_endtime, $tt_lstype, $tt_code, $tt_lstext, $tt_statsflags, $tt_activitytype, $tt_kl, $tt_te, $tt_ro, $tt_su){
        $time = time();
        $key_col = "";
        
        $timetable = array(
            'tt_uid' => $tt_uid,   
            'tt_teuid' => $tt_teuid,   
            'tt_date' => $tt_date,
            'tt_starttime' => $tt_starttime,
            'tt_endtime' => $tt_endtime,
            'tt_lstype' => $tt_lstype,
            'tt_code' => $tt_code,
            'tt_lstext' => $tt_lstext,
            'tt_statsflags' => $tt_statsflags,
            'tt_activitytype' => $tt_activitytype,
            'tt_modified' => $time,
        );
        
        $filter = array(
            'tt_uid' => $tt_uid,           
        );
        
        $result = $this->read($filter);

        if(is_array($result)){
            // timetable exists in DB
            $updateFields = array();
            $updateFields['tt_modified'] = $time;
            if($this->equals($timetable, $result, $tt_kl, $tt_te, $tt_ro, $tt_su)){
                // substitution not modified
                $updateFields['tt_clean'] = 1; 
                if(parent::update($updateFields) != 0) return false;
                return false;
                // finished - nothing to do anymore
            }
            else{
                // v modified
                // delete participants
                $updateFields['tt_clean'] = 0;                
                $this->participant_so->deleteAllParticipants( $result['tt_id'], 'tt');
                if(parent::update($updateFields) != 0) return false;
            }
            
        }
        else{
            // new timetable
            $this->data = $timetable;
            $this->data['tt_created'] = $time;
            $updateFields['tt_clean'] = 0; 
            
            if(parent::save() != 0) return false;     
            
            $filter = array(
                'tt_uid' => $tt_uid,
            );
                    
            $result = $this->read($filter);
        }
        
        if(isset($result['tt_id'])){
            $parent_id = $result['tt_id'];
            
            // delete all participants
            $this->participant_so->deleteAllParticipants($parent_id, 'tt');
            // klassen
            foreach ($tt_kl as &$kl) {
                // ($pa_parentid, $pa_parenttable, $pa_partid, $pa_parttype, $pa_partname, $pa_partorgid, $pa_partorgname){
                $this->participant_so->write($parent_id, 'tt', $kl['id'], 'kl', $kl['name'], $kl['orgid'], $kl['orgname']);
            }
            // te
            foreach ($tt_te as &$te) {
                $this->participant_so->write($parent_id, 'tt', $te['id'], 'te', $te['name'], $te['orgid'], $te['orgname']);
            }
            // su
            foreach ($tt_su as &$su) {
                $this->participant_so->write($parent_id, 'tt', $su['id'], 'su', $su['name'], $su['orgid'], $su['orgname']);
            }
            // ro
            foreach ($tt_ro as &$ro) {
                $this->participant_so->write($parent_id, 'tt', $ro['id'], 'ro', $ro['name'], $ro['orgid'], $ro['orgname']);
            }

            return $result;
        }
                
        return false;
    }
    
    
    /**
     * checks, if the two timetable events are equal.
     * @param array $tt1
     * @param array $tt2
     * @param array $tt1_kl
     * @param array $tt1_te
     * @param array $tt1_ro
     * @param array $tt1_su
     * @return boolean
     */
    private function equals(array $tt1, array $tt2, array $tt1_kl, array $tt1_te, array $tt1_ro, array $tt1_su){
        $result = false;
        
        // test attributes
        // possible that more than one teacher are members of one event
        $result = $tt1['tt_uid'] == $tt2['tt_uid']
                && $tt1['tt_starttime'] == $tt2['tt_starttime'] &&  $tt1['tt_endtime'] == $tt2['tt_endtime'] &&  $tt1['tt_lstype'] == $tt2['tt_lstype'] && $tt1['tt_code'] == $tt2['tt_code'] && $tt1['tt_lstext'] == $tt2['tt_lstext'] && $tt1['tt_statsflag'] == $tt2['tt_statsflag']
                && $tt1['tt_activitytype'] == $tt2['tt_activitytype'];
        
        if($result){
            // test participants
            $part1_keys = array();
            $part2_keys = array();
            $parts2 = $this->participant_so->loadParticipants($tt2['tt_id'], 'tt');

            if(is_array($parts2)) {
                foreach ($parts2 as $p2) {
                    $key = $p2['pa_parttype'] . '-' . $p2['pa_partid'] . '-' . $p2['pa_partorgid'];
                    $part2_keys[$key] = $key;
                }
            }
            
            $this->participant_so->createParticipantKeys($part1_keys, $tt1_kl, 'kl');
            $this->participant_so->createParticipantKeys($part1_keys, $tt1_te, 'te');
            $this->participant_so->createParticipantKeys($part1_keys, $tt1_ro, 'ro');
            $this->participant_so->createParticipantKeys($part1_keys, $tt1_su, 'su');
            
            $result = $part1_keys == $part2_keys;
        }
        return $result;
    }

    /**
     * updates the egw calender event id to timetable item
     * @param $tt
     * @param $calid
     * @return bool
     */
    public function updateEgwCalendarEventID($tt, $calid){
        $time = time();
        $timetable = array(
            'tt_id' => $tt['tt_id'],
            'tt_egw_cal_id' => $calid,
            'tt_modified' => $time,
            );
       
        $this->data = $timetable;
        if(parent::update($timetable, true) != 0) return false;
        return true;
    }

    /**
     * marks all timetable events of untis object id $uid to $clean
     * @param $teacherUntisID
     * @return mixed
     */
    public function markUnClean($teacherUntisID){
        $config = untissync_config::read();
        $delOldEgwEventsDays =  $config['cleanup_cal_events_days'] ?: 0;
        $todayYMD = (new DateTime())->format('Ymd');
        $pastYMD = (new DateTime())->modify("-".$delOldEgwEventsDays." day")->format('Ymd');

        $updateSQL = "UPDATE egw_untissync_timetable, egw_untissync_participant "
                    ."SET egw_untissync_timetable.tt_clean = -1 "
                    ."WHERE egw_untissync_timetable.tt_id = egw_untissync_participant.pa_parentid "
                    ."AND egw_untissync_participant.pa_parenttable = 'tt' AND egw_untissync_participant.pa_parttype = 'te' "
                    ."AND egw_untissync_participant.pa_partid = $teacherUntisID "
                    ."AND (egw_untissync_timetable.tt_date < $pastYMD OR egw_untissync_timetable.tt_date >= $todayYMD)";

        return $this->db->query($updateSQL);
    }

    /*
    SELECT egw_untissync_timetable.tt_id,
    egw_untissync_timetable.tt_egw_cal_id,
    egw_untissync_timetable.tt_clean,
    COUNT(*) AS count_te_parts,
    GROUP_CONCAT(te_other.pa_partid)
    FROM egw_untissync_timetable
    INNER JOIN egw_untissync_participant ON egw_untissync_timetable.tt_id = egw_untissync_participant.pa_parentid
    LEFT JOIN egw_untissync_participant AS te_other ON (egw_untissync_timetable.tt_id = te_other.pa_parentid AND te_other.pa_parenttable = 'tt' AND te_other.pa_parttype = 'te')
    where egw_untissync_participant.pa_parenttable = 'tt'
    AND egw_untissync_participant.pa_parttype = 'te'
    AND egw_untissync_timetable.tt_clean < 5
    AND egw_untissync_participant.pa_partid = 123
    GROUP BY egw_untissync_timetable.tt_id
    HAVING count_te_parts > 0;
     */
    /**
     * searchs all timetableEvents with $teacherUntisID as participant and tt_clean < 0
     * @param $techaerUntisID
     * @returns array with timetable-ids, and number of participants
     */
    public function searchUnClean($techaerUntisID)
    {
        $result = array();

        $tables = 'egw_untissync_timetable';
        $cols = "egw_untissync_timetable.tt_id, egw_untissync_timetable.tt_egw_cal_id, egw_untissync_timetable.tt_clean, egw_untissync_participant.pa_id, COUNT(*) AS count_te_parts, GROUP_CONCAT(te_other.pa_partid)";
        $where = array(
            "egw_untissync_participant.pa_parenttable = 'tt'",
            "egw_untissync_participant.pa_parttype = 'te'",
            "egw_untissync_timetable.tt_clean < 0",
            "egw_untissync_participant.pa_partid = $techaerUntisID",
        );
        $join = " INNER JOIN egw_untissync_participant ON egw_untissync_timetable.tt_id = egw_untissync_participant.pa_parentid" .
            " LEFT JOIN egw_untissync_participant AS te_other ON (egw_untissync_timetable.tt_id = te_other.pa_parentid AND te_other.pa_parenttable = 'tt' AND te_other.pa_parttype = 'te')";
        $append = ' GROUP BY egw_untissync_timetable.tt_id ';

        $adoRecordSet = $this->db->select($tables, $cols, $where, __LINE__, __FILE__, False, $append, False, 0, $join);

        while($row = $adoRecordSet->fetchRow()){
            $result[$row['tt_id']] = $row;
        }

        return $result;
    }

    /**
     * Search all orphaned calendar events
     * SQL: SELECT COUNT(egw_cal.cal_id)
     * FROM egw_cal
     * LEFT JOIN egw_untissync_timetable ON egw_cal.cal_id = egw_untissync_timetable.tt_egw_cal_id
     * WHERE egw_cal.cal_owner = 299 AND egw_cal.cal_category = 100 AND egw_untissync_timetable.tt_egw_cal_id IS null;
     * @return array
     */
    public function searchOrphanedCalEvents(){
        $result = array();

        $config = untissync_config::read();

        if(!$config['webuntis_cal_event_owner']){
            return; // not able to delete orphaned events!
        }
        $event_owner = $config['webuntis_cal_event_owner'];
        if($config['cal_category']){
            $cal_category = $config['cal_category'];
        }

        $tables = 'egw_cal';
        $cols = "egw_cal.cal_id";
        $where = array(
            "egw_cal.cal_owner = $event_owner",
            "egw_untissync_timetable.tt_egw_cal_id IS null",
        );
        if(isset($cal_category)){
            $where[] = "egw_cal.cal_category = $cal_category";
        }

        $join = "LEFT JOIN egw_untissync_timetable ON egw_cal.cal_id = egw_untissync_timetable.tt_egw_cal_id";
        $append = '';

        $adoRecordSet = $this->db->select($tables, $cols, $where, __LINE__, __FILE__, False, $append, False, 0, $join);

        while($row = $adoRecordSet->fetchRow()){
            $result[] = $row['cal_id'];
        }

        return $result;
    }
    
}
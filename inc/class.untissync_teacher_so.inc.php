<?php

/**
 * EGroupware - UntisSync - Teacher object
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

class untissync_teacher_so extends Api\Storage {
    
    var $untissync_teacher_table = 'egw_untissync_teacher';
    
    var $value_col = array();
    
    public function __construct(){
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_teacher_table);
        
        $this->setup_table('untissync', $this->untissync_teacher_table);
        
        $this->debug = 0;
        
        $this->value_col['id'] = 'te_id';
        $this->value_col['uid'] = 'te_uid';
        $this->value_col['name'] = 'te_name';
        $this->value_col['forename'] = 'te_forename';
        $this->value_col['longname'] = 'te_longname';
        $this->value_col['active'] = 'te_active';
        $this->value_col['egw_uid'] = 'te_egw_uid';
        $this->value_col['created'] = 'te_created';
        $this->value_col['modified'] = 'te_modified';
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
    }
    
    /**
     * 
     * @param unknown $te_uid webuntis teacher id
     * @param unknown $te_name
     * @param unknown $te_forename
     * @param unknown $te_longname
     * @param unknown $te_egw_uid EGroupware user id
     * @return boolean
     */
    function save($te_uid, $te_name, $te_forename, $te_longname, $te_egw_uid, $active){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "te_uid=".$te_uid;        
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $teacher = array(
            'te_uid' => $te_uid,
            'te_name' => $te_name,
            'te_forename' => $te_forename,
            'te_longname' => $te_longname,
            'te_active' => $active,
            'te_egw_uid' => $te_egw_uid,
            'te_modified' => $time,
        );
        
        
        // array_key_first php >= 7.3
        if(sizeof($result) == 0){    
            $teacher['te_created'] = $time;
            $this->data = $teacher;
            if(parent::save() != 0) return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $teacher['te_id'] = $ids[0];
            
            $this->data = $teacher;
            if(parent::update($teacher, true) != 0) return false;          
        }
        else{
            return false;
        }
        return true;
    }

    /**
     * Updates EGroupware user id, representing this teacher
     * @param $egw_uid
     * @param $te_id
     * @return bool
     */
    public function updateEgwUid($egw_uid, $te_id){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "te_id=".$te_id;
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $teacher = array(
            'te_id' => $te_id,            
            'te_egw_uid' => $egw_uid,
            'te_modified' => $time,
        );

        if(sizeof($result) == 0){
            return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $teacher['te_id'] = $ids[0];
            
            $this->data = $teacher;
            if(parent::update($teacher, true) != 0) return false;
        }
        else{
            return false;
        }
        return true;
    }
    
    /**
     * nextmatch get rows
     * @param unknown $query_in
     * @param unknown $rows
     * @return unknown
     */
    function get_rows(&$query_in,&$rows){
        $filter = array();
        $filter[] = 'te_egw_uid >= -1';

        $order = 'te_name, te_forename';
        
        if(!empty($query_in['search'])){
            $search = $this->db->quote($query_in['search'].'%');
            $filter[] = "(te_name like ".$search." OR te_forename like ".$search." OR te_longname like ".$search.")";
        }
        
        $result = $this->query_list($this->value_col, '', $filter, $order);
        $index = 0;
        foreach($result as $te){
            $rows[$index] = $te;
            $rows[$index]['nm_id'] = $index;   
            $rows[$index]['nr'] = $index + 1;            
            
            $index++;
        }      
        return count($result);
    }

    /**
     * return all teachers in an array with untis-id as key
     */
    public function getUntisTeacherSet(){
        $result = array();
        $rows = $this->query_list($this->value_col);
        foreach($rows as $te){
            $result[$te['uid']] = $te;
        }
        return $result;
    }

    /**
     * requests EGroupware user id by untis teacher id
     * @param unknown $te_uid
     * @return unknown
     */
    public function getTeacherByUntisID($te_uid){
        $filter = array(
            'te_uid' => $te_uid,
        );
        $result = $this->read($filter);
        return $result;
    }

    /**
     * truncates the table
     * @return mixed
     */
    public function truncate(){
        $sql = "TRUNCATE $this->untissync_teacher_table";
        return $this->db->query($sql, __LINE__, __FILE__);
    }
    
    
    /**
     * lists all teacher of a class
     * 
     * SELECT DISTINCT egw_untissync_teacher.te_name FROM egw_untissync_teacher
        INNER JOIN egw_untissync_participant AS pteacher ON (pteacher.pa_partid = egw_untissync_teacher.te_uid AND pteacher.pa_parttype = 'te' AND pteacher.pa_parenttable = 'tt')
        INNER JOIN egw_untissync_participant AS pclass ON (pclass.pa_parentid = pteacher.pa_parentid AND pclass.pa_parttype = 'kl' AND pclass.pa_parenttable = 'tt')
        INNER JOIN egw_untissync_class ON (egw_untissync_class.kl_uid = pclass.pa_partid)
        WHERE egw_untissync_class.kl_uid = '123'
     */
    public function listTeacherByClass($class_kl_uid, $only_ids = false){
        $teacher = array();
        $tables = 'egw_untissync_teacher';//, egw_untissync_participant ';
        
        $cols = 'DISTINCT egw_untissync_teacher.te_name, egw_untissync_teacher.te_egw_uid';
        
        $where = array(
            "egw_untissync_class.kl_uid = '$class_kl_uid'",
        );
        
        $join = " INNER JOIN egw_untissync_participant AS pteacher ON (pteacher.pa_partid = egw_untissync_teacher.te_uid AND pteacher.pa_parttype = 'te' AND pteacher.pa_parenttable = 'tt')"
                ." INNER JOIN egw_untissync_participant AS pclass ON (pclass.pa_parentid = pteacher.pa_parentid AND pclass.pa_parttype = 'kl' AND pclass.pa_parenttable = 'tt')"
                ." INNER JOIN egw_untissync_class ON (egw_untissync_class.kl_uid = pclass.pa_partid) ";
        
        $result = $this->db->select($tables, $cols, $where, '', '', False, '', False, 0, $join);           
       
        $index = 0;
        foreach($result as $te){
            if($only_ids && $te['te_egw_uid'] > 0){
                $teacher[$index++] = $te['te_egw_uid']; 
            }
            elseif(!$only_ids){
                $teacher[$index++] = $te;
            }
        }
        return $teacher;
    }
}
<?php

/**
 * EGroupware - UntisSync - Room object
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

class untissync_room_so extends Api\Storage {
    
    var $untissync_room_table = 'egw_untissync_room';
    
    var $value_col = array();
    
    public function __construct(){
        //parent::__construct('schulmanager', $this->sm_note_gew_table);
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_room_table);
        
        $this->setup_table('untissync', $this->untissync_room_table);
        
        $this->debug = 0;
        
        $this->value_col['id'] = 'ro_id';
        $this->value_col['uid'] = 'ro_uid';
        $this->value_col['name'] = 'ro_name';
        $this->value_col['longname'] = 'ro_longname';
        $this->value_col['active'] = 'ro_active';
        $this->value_col['egw_res_id'] = 'ro_egw_res_id';
        $this->value_col['created'] = 'ro_created';
        $this->value_col['modified'] = 'ro_modified';
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
    }
    
    /**
     * 
     * @param unknown $ro_uid webuntis room id
     * @param unknown $ro_name
     * @param unknown $ro_longname
     * @param unknown $ro_egw_res_id EGroupware resource id
     * @return boolean
     */
    function write($ro_uid, $ro_name, $ro_longname, $ro_egw_res_id, $active){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "ro_uid=".$ro_uid;        
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $room = array(
            'ro_uid' => $ro_uid,
            'ro_name' => $ro_name,           
            'ro_longname' => $ro_longname,
            'ro_active' => $active,
            'ro_egw_res_id' => $ro_egw_res_id,
            'ro_modified' => $time,
        );
        
        
        // array_key_first php >= 7.3
        if(sizeof($result) == 0){    
            $room['ro_created'] = $time;
            $this->data = $room;
            if(parent::save() != 0) return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $room['ro_id'] = $ids[0];
            
            $this->data = $room;
            if(parent::update($room, true) != 0) return false;          
        }
        else{
            return false;
        }
        return true;
    }

    /**
     * Updates EGroupware ressource id, representing this room
     * @param $egw_res_id
     * @param $ro_id
     * @return bool
     */
    public function updateEgwResId($egw_res_id, $ro_id){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "ro_id=".$ro_id;
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $room = array(
            'ro_id' => $ro_id,
            'ro_egw_res_id' => $egw_res_id,
            'ro_modified' => $time,
        );
        
        
        // array_key_first php >= 7.3
        if(sizeof($result) == 0){
            return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $teacher['ro_id'] = $ids[0];
            
            $this->data = $room;
            if(parent::update($room, true) != 0) return false;
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
    function get_rows($query_in,&$rows,&$readonlys,$join='',$need_full_no_count=false,$only_keys=false,$extra_cols=array()){
    //function get_rows(&$query_in,&$rows){
        $filter = array();
        $filter[] = "ro_egw_res_id >= -1";
        
        $search = $this->db->quote($query_in['search'].'%');
        
        if(!empty($query_in['search'])){
            $filter[] = "(ro_name like ".$search." OR ro_longname like ".$search.")";
        }
        
        $result = $this->query_list($this->value_col, '', $filter);
        $index = 0;
        foreach($result as $ro){
            $rows[$index] = $ro;
            $rows[$index]['nm_id'] = $index;
            $rows[$index]['nr'] = $index + 1;          
            
            $index++;
        }
        return count($result);
    }
    
    
    /**
     * requests EGroupware ressource id by untis room id
     * @param unknown $te_uid
     * @return unknown
     */
    public function getRoomByUntisID($ro_uid){
        $filter = array(
            'ro_uid' => $ro_uid,
        );
        $result = $this->read($filter);
        return $result;
    }

    /**
     * Truncates the table
     * @return mixed
     */
    public function truncate(){
        $sql = "TRUNCATE $this->untissync_room_table";
        
        return $this->db->query($sql, __LINE__, __FILE__);
    }
    

}
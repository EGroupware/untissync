<?php

/**
 * EGroupware - UntisSync - school class object
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

class untissync_class_so extends Api\Storage {
    
    var $untissync_class_table = 'egw_untissync_class';
    
    var $value_col = array();
    
    var $prefix_class_username;
    var $classAccounts;    
    
    var $prefix_class_teacher;
    var $classGroups;
    
    public function __construct(){
        $config = untissync_config::read();
        //parent::__construct('schulmanager', $this->sm_note_gew_table);
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_class_table);
        
        $this->setup_table('untissync', $this->untissync_class_table);
        
        $this->debug = 0;
        
        $this->value_col['id'] = 'kl_id';
        $this->value_col['uid'] = 'kl_uid';
        $this->value_col['name'] = 'kl_name';
        $this->value_col['longname'] = 'kl_longname';
        $this->value_col['active'] = 'kl_active';
        $this->value_col['egw_uid'] = 'kl_egw_uid';
        $this->value_col['egw_group_id'] = 'kl_egw_group_id';
        $this->value_col['created'] = 'kl_created';
        $this->value_col['modified'] = 'kl_modified';
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
        
        $this->prefix_class_username = $config['webuntis_mapping_prefix_class'];        
        if(!isset($this->prefix_class_username)){
            $this->prefix_class_username = "Klasse_";
        }        
        $this->classAccounts = $this->getPotentialEGWAccounts();
        
        $this->prefix_class_teacher = $config['webuntis_mapping_prefix_teacher'];        
        if(!isset($this->prefix_class_teacher)){
            $this->prefix_class_teacher = "Lehrer_";
        }
        $this->classGroups = $this->getPotentialTeacherGroups();
    }

    /**
     * saves a class
     * @param array|null $kl_uid
     * @param array|string|null $kl_name
     * @param $kl_longname
     * @param $kl_egw_uid
     * @param $kl_egw_group_id
     * @param $active
     * @return bool|int
     */
    public function write($kl_uid, $kl_name, $kl_longname, $kl_egw_uid, $kl_egw_group_id, $active){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "kl_uid=".$kl_uid;        
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $klasse = array(
            'kl_uid' => $kl_uid,
            'kl_name' => $kl_name,
            'kl_longname' => $kl_longname,
            'kl_active' => $active,
            'kl_egw_uid' => $kl_egw_uid,
            'kl_egw_group_id' => $kl_egw_group_id,
            'kl_modified' => $time,
        );

        if(sizeof($result) == 0){ 
            $klasse['kl_created'] = $time;
            $this->data = $klasse;            
            if(parent::save() != 0) return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $klasse['kl_id'] = $ids[0];
            
            $this->data = $klasse;
            if(parent::update($klasse, true) != 0) return false;
        }
        else{
            return false;
        }
        return true;
    }

    /**
     * saves EGroupware user id, representing this school class
     * @param $egw_uid
     * @param $kl_id
     * @param $kl_egw_group_id
     * @return bool
     */
    public function updateEgwUid($egw_uid, $kl_id, $kl_egw_group_id){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "kl_id=".$kl_id;
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $class = array(
            'kl_id' => $kl_id,
            'kl_egw_uid' => $egw_uid,
            'kl_egw_group_id' => $kl_egw_group_id,
            'kl_modified' => $time,
        );

        // array_key_first php >= 7.3
        if(sizeof($result) == 0){
            return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $class['kl_id'] = $ids[0];
            
            $this->data = $class;
            if(parent::update($class, true) != 0) return false;
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
        //$filter[] = "kl_egw_uid >= -1";
        $filter[] = "1=1";
        
        $search = $this->db->quote($query_in['search'].'%');
        
        if(!empty($query_in['search'])){
            
            $search = $this->db->quote($query_in['search'].'%');
            
            $filter[] = "(kl_name like ".$search." OR kl_longname like ".$search.")";
        }
        
        $result = $this->query_list($this->value_col, '', $filter);
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
     * requests EGroupware classes id by untis classesr id
     * @param unknown $te_uid
     * @return unknown
     */
    public function getClassByUntisID($kl_uid){
        $filter = array(
            'kl_uid' => $kl_uid,
        );
        $result = $this->read($filter);
        return $result;
    }

    /**
     * @return mixed
     */
    public function truncate(){
        $sql = "TRUNCATE $this->untissync_class_table";
        
        return $this->db->query($sql, __LINE__, __FILE__);
    }
    
    /**
     * search all potential accounts, representing a system class user
     */
    private function getPotentialEGWAccounts(){
        $accounts =  API\Accounts::getInstance();        
        
        $param = array();
        $param['type'] = 'accounts';
        $param['query'] = $this->prefix_class_username;
        $param['query_type'] = 'all';
        $param['active'] = False;
        
        return $accounts->search($param);       
    }
    
    /**
     * search all potential accounts, representing a system class user
     */
    private function getPotentialTeacherGroups(){
        $accounts =  API\Accounts::getInstance();

        $param = array();
        $param['type'] = 'groups';
        $param['query'] = $this->prefix_class_teacher;
        $param['query_type'] = 'all';
        $param['active'] = False;
        
        return $accounts->search($param);
    }
    
    /**
     * returns the egroupware account id by the corresponding class name 
     * @param unknown $name
     */
    public function getClassAccountID($className){
        $lid = $this->prefix_class_username.$className;
        foreach($this->classAccounts as $key => $acc){
            if($acc['account_lid'] == $lid){
                return $key;
            }            
        }
        return 0;        
    }
    
    /**
     * returns the egroupware teacher group id by the corresponding class name
     * @param unknown $name
     */
    public function getClassTeacherGroupID($className){
        $lid = $this->prefix_class_teacher.$className;
        foreach($this->classGroups as $key => $acc){
            if($acc['account_lid'] == $lid){
                return $key;
            }
        }
        return 0;
    }
}
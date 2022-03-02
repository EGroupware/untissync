<?php

/**
 * EGroupware - UntisSync - Subject object
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

class untissync_subject_so extends Api\Storage {
    
    var $untissync_subject_table = 'egw_untissync_subject';
    
    var $value_col = array();
    
    public function __construct(){
        //parent::__construct('schulmanager', $this->sm_note_gew_table);
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_subject_table);
        
        $this->setup_table('untissync', $this->untissync_subject_table);
        
        $this->debug = 0;
        
        $this->value_col[] = 'su_id';
        $this->value_col[] = 'su_uid';
        $this->value_col[] = 'su_name';
        $this->value_col[] = 'su_longname';
        $this->value_col[] = 'su_created';
        $this->value_col[] = 'su_modified';
        
        $this->customfields = Storage\Customfields::get($app, false, null, $db);
    }
    
    /**
     * 
     * @param unknown $su_uid webuntis subject id
     * @param unknown $su_name
     * @param unknown $su_longname
     * @return boolean
     */
    function write($su_uid, $su_name, $su_longname){
        $time = time();
        $key_col = "";
        
        $filter = array();
        $filter[] = "su_uid=".$su_uid;        
        
        $result = $this->query_list($this->value_col, $key_col, $filter);
        
        $subject = array(
            'su_uid' => $su_uid,
            'su_name' => $su_name,
            'su_longname' => $su_longname,
            'su_modified' => $time,
        );

        if(sizeof($result) == 0){     
            $subject['su_created'] = $time;
            $this->data = $subject;
            if(parent::save() != 0) return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $subject['su_id'] = $ids[0];
            
            $this->data = $subject;
            if(parent::update($subject, true) != 0) return false;          
        }
        else{
            return false;
        }
    }

    /**
     * requests subject id by untis subject id
     * @param int $ro_uid
     * @return unknown
     */
    public function getSubjectByUntisID($su_uid){
        $filter = array(
            'su_uid' => $su_uid,
        );
        $result = $this->read($filter);
        return $result;
    }

    /**
     * Truncates the table
     * @return mixed
     */
    public function truncate(){
        $sql = "TRUNCATE $this->untissync_subject_table";
        
        return $this->db->query($sql, __LINE__, __FILE__);
    }
}
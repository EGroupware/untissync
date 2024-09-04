<?php

/**
 * EGroupware - UntisSync - Participant object
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

class untissync_participant_so extends Api\Storage {
    
    var $untissync_participant_table = 'egw_untissync_participant';
    
    var $value_col = array();
    
    public function __construct(){
        //parent::__construct('schulmanager', $this->sm_note_gew_table);
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_teacher_table);
        
        $this->setup_table('untissync', $this->untissync_participant_table);
        
        $this->debug = 0;
        
        $this->value_col['id'] = 'pa_id';               // db id
        $this->value_col['parentid'] = 'pa_parentid';         // foreign key timetable or substitution
        $this->value_col['parenttable'] = 'pa_parenttable';      // table: timetible or substitution
        $this->value_col['partid'] = 'pa_partid';           // foreign key te, kl, su, ro
        $this->value_col['parttype'] = 'pa_parttype';         // type: te, su, kl, ro
        $this->value_col['partname'] = 'pa_partname';         // name 
        $this->value_col['partorgid'] = 'pa_partorgid';        // original id
        $this->value_col['partorgname'] = 'pa_partorgname';      // original name
        $this->value_col['created'] = 'pa_created';          // created
        $this->value_col['modified'] = 'pa_modified';         // modified

        $this->customfields = Api\Storage\Customfields::get('untissync');
    }
    
    /**
     * Saves a participant
     * @param unknown $pa_parentid
     * @param unknown $pa_parenttable
     * @param unknown $pa_partid
     * @param unknown $pa_parttype
     * @param unknown $pa_partname
     * @param unknown $pa_partorgid
     * @param unknown $pa_partorgname
     * @return boolean
     */
    public function write($pa_parentid, $pa_parenttable, $pa_partid, $pa_parttype, $pa_partname, $pa_partorgid, $pa_partorgname){
        $time = time();
        
        $participant = array(
            'pa_parentid' => $pa_parentid,
            'pa_parenttable' => $pa_parenttable,
            'pa_partid' => $pa_partid,
            'pa_parttype' => $pa_parttype,
            'pa_partname' => $pa_partname,
            'pa_partorgid' => $pa_partorgid,
            'pa_partorgname' => $pa_partorgname,
            'pa_created' => $time,
            'pa_modified' => $time,
        );
               
        $this->data = $participant;
        if(parent::save() != 0) return false;
        
    }
    
    /**
     * deletes all participants for given $parentid and specified parenttable 'tt' for timetable or 'su' for substitutions
     * @param int $parentid untis id
     * @param string $key_parenttable ('tt' or 'sub')
     */
    public function deleteAllParticipants($parentid, $key_parenttable){
        $pas = $this->loadParticipants($parentid, $key_parenttable);
        if(is_array($pas)){
            foreach($pas as &$pa){
                $this->delete($pa['pa_id']);
            }
        }
    }
    
    
    /**
     * Loads all participants bei timetable id
     * @param unknown $tt_id
     */
    public function loadParticipants($parentid, $key_parenttable){
        $filter = array(
            'pa_parentid' => $parentid,
            'pa_parenttable' => $key_parenttable
        );
        //$result = $this->query_list($this->value_col, $key_col, $filter);
        $result = $this->search($filter, false);        

        return $result;
    }
    
    /**
     * Creates keys to compare participant arrays
     * @param array $arr
     * @param unknown $prefix
     */
    public function createParticipantKeys(array &$keys, array $parts, $prefix){
        foreach($parts as $part){
            $id = array_key_exists('id', $part) ? $part['id'] : 0;
            $orgid = array_key_exists('orgid', $part) ? $part['orgid'] : 0;
            $key = $prefix.'-'.$id.'-'.$orgid;
            $keys[$key] = $key;
            
        }
    }




    
    
}
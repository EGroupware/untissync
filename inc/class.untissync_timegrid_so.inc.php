<?php

/**
 * EGroupware - UntisSync - Timegrid object
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

class untissync_timegrid_so extends Api\Storage {
    
    var $untissync_timegrid_table = 'egw_untissync_timegrid';
    
    var $value_col = array();
    
    public function __construct(){
        //parent::__construct('schulmanager', $this->sm_note_gew_table);
        $this->db = clone($GLOBALS['egw']->db);
        $this->db->set_app('untissync');
        $this->all_tables = array($this->untissync_timegrid_table);
        
        $this->setup_table('untissync', $this->untissync_timegrid_table);
        
        $this->debug = 0;
        
        $this->value_col['tg_id'] = 'tg_id';
        $this->value_col['tg_day'] = 'tg_day';
        $this->value_col['tg_name'] = 'tg_name';
        $this->value_col['tg_start'] = 'tg_start';
        $this->value_col['tg_end'] = 'tg_end';

        $this->customfields = Api\Storage\Customfields::get('untissync');
    }

    /**
     * Write to DB.
     * @param $tg_day
     * @param $tg_name
     * @param $tg_start
     * @param $tg_end
     * @return false|void
     */
    function write($tg_day, $tg_name, $tg_start, $tg_end){
        
        $filter = array();
        $filter['tg_day'] = $tg_day;
        $filter['tg_name'] = $tg_name;
        
        $result = $this->query_list($this->value_col, '', $filter);
        
        $subject = array(
            'tg_day' => $tg_day,
            'tg_name' => $tg_name,
            'tg_start' => $tg_start,
            'tg_end' => $tg_end,
        );
        
        
        // array_key_first php >= 7.3
        if(sizeof($result) == 0){
            $this->data = $subject;
            if(parent::save() != 0) return false;
        }
        elseif (sizeof($result) == 1){
            $ids = array_keys($result);
            $subject['tg_id'] = $ids[0];
            
            $this->data = $subject;
            if(parent::update($subject, true) != 0) return false;
        }
        else{
            return false;
        }
        
        /**/
    }

    /**
     * Truncates the table
     * @return mixed
     */
    function truncate(){
        $sql = "TRUNCATE $this->untissync_timegrid_table";
        return $this->db->query($sql, __LINE__, __FILE__);
    }

    /**
     * Returns an array like "2-755-840" => "1. Stunde"
     * DayInWeek - start - end => name of lesson from Webuntis
     */
    public function getTimegridSet(){
        $result = array();
         
        $timegrid = $this->query_list($this->value_col);
        
        foreach($timegrid as $item){
            $result[$item['tg_day'].'-'.$item['tg_start'].'-'.$item['tg_end']] = $item['tg_name'];
        }	
        return $result;
    }

    /**
     * fills the arrays by keys combined like day-start
     * @param array $start
     * @param array $end
     */
    public function getTimegridSetStartEnd(array &$start, array &$end){
        $timegrid = $this->query_list($this->value_col);
        
        foreach($timegrid as $item){
            $start[$item['tg_day'].'-'.$item['tg_start']] = $item['tg_name'];
            $end[$item['tg_day'].'-'.$item['tg_end']] = $item['tg_name'];
        }
    }
}
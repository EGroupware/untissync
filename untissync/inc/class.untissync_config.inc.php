<?php

/**
 * EGroupware - config
 *
 * Read and save config values also from async services; can possibly be removed later.
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

class untissync_config{
    
    /**
     * Name of the config table
     *
     */
    const TABLE = 'egw_config';
    /**
     * Reference to the global db class
     *
     * @var Db
     */
    static private $db;

    /**
     * nextmatch get rows
     * @return array config key value pairs
     */
    public static function read(){   
        $config = array();
        self::$db = $GLOBALS['egw']->db;
        
        $where = array(
            'config_app' => 'untissync',
        );
        
        foreach(self::$db->select(self::TABLE,'*',$where,__LINE__,__FILE__) as $row)
        {
            $config[$row['config_name']] = $row['config_value'];            
        }           
        return $config;
    }
    
    /**
     * saves value
     * @param unknown $name
     * @param unknown $value
     * @throws Exception\WrongParameter
     * @return boolean|unknown
     */
    public static function save_value($name, $value)
    {        
        if (!isset($value) || $value === '')
        {
            self::$db->delete(self::TABLE,array('config_app'=>'untissync','config_name'=>$name),__LINE__,__FILE__);
        }
        else
        {            
            self::$db->insert(self::TABLE,array('config_value'=>$value),array('config_app'=>'untissync','config_name'=>$name),__LINE__,__FILE__);
        }        
        return self::$db->affected_rows();
    }
}
<?php

/**
 * EGroupware - UntisSync - Logging object
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

define('ASYNC_LOG','/tmp/async.log');

use EGroupware\Api;

class untissync_log {
	static $debug = true;

	static $async = false;

	public function __construct()
	{		
	}

	/**
	 * enables or disables logging for asyncservices
	 */
	public function enableAsyncLog($enabled){
		self::$async = $enabled;

		if(!self::$debug){
		    return;
        }

		if(self::$async){
			$msg =  date('Y/m/d H:i:s ').'UNTISSYNC_LOG async log enabled'.PHP_EOL;		
			$f = fopen(ASYNC_LOG,'a+');
			if($f) {
                fwrite($f, $msg);
                fclose($f);
            }
		}
		else{
			//error_log(date('Y/m/d H:i:s ')."UNTISSYNC_LOG async log disabled");
		}
	}

	/**
	 * log
	 */
	public function log($msg, $method = ""){
		if(self::$debug && !self::$async){
			error_log("UNTISSYNC_LOG ".$method." msg: ".$msg);
		}
		elseif(self::$debug && self::$async && defined('ASYNC_LOG')){
			$msg =  date('Y/m/d H:i:s ').'UNTISSYNC_LOG '.$msg.PHP_EOL;		
			$f = fopen(ASYNC_LOG,'a+');
            if($f) {
                fwrite($f, $msg);
                fclose($f);
            }
		}
	}
}
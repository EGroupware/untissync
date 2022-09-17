<?php

/**
 * EGroupware - UntisSync - UI object
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api;
use EGroupware\Api\Etemplate;

/**
 * General userinterface object for UntisSync
 *
 * @package untissync
 */
class untissync_ui
{
	/**
	 * Name of the async job for cleaning up shares
	 */
	const ASYNC_JOB_UPD_TT_ID = 'untissync_update_timetable';
	const ASYNC_JOB_UPD_SU_ID = 'untissync_update_substitutions';

	var $debug_log;

	/**
	 * instance of the bo-class
	 * @var untissync_bo
	 */
	var $bo;

	var $public_functions = array(
		'index'		=> True,
	    'list' => True,
	    'info' => True,
	);

	var $list_filter = array(
	    'show_all' => 'alle Einträge anzeigen',
        'hide_canceled' => 'entfallene Stunden ausblenden',
    );

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		$this->debug_log = new untissync_log();	
		$this->tmpl	= new Etemplate('untissync.index');
		$this->bo = new untissync_bo();
	}

	/**
	 * Show some information data for special users
	 * @param array $content
	 * @param string $msg
	 */
	function index($content='', $msg='')
	{
		$sel_options = array();
		$preserv = array();
		if (is_array($content))
		{
			$button = key($content['button']);
			unset($content['button']);
			if ($button)
			{
				if($button == 'update_timegrid')
				{
				    $content['exec_button'] = "update_substitutions";
				    $result = $this->bo->startImportTimegrid();
				    $this->bo->requestLastImportTime($msg);
				    $content['post_result'] = $result;
				}
				elseif($button == 'create_async_substitution')
				{
				    $this->createAsyncJobSubstitution();
				}
				elseif($button == 'create_async_timetable')
				{
				    $this->createAsyncJobTimetable();
				}
				elseif($button == 'delete_async')
				{
				    $this->deleteAsyncJobs();
				}
				elseif($button == 'update_groupmembers'){
					$this->bo->updateClassGroupsMembers();
				}
                elseif($button == 'apply_category'){
                    untissync_config::save_value('cal_category', $content['category'] );
                }
			}
		}
		else {
			$content = array();
		}

		$content['msg'] = $msg ? $msg : $_GET['msg'];

		$config = untissync_config::read();
		$url = $this->bo->getURL().'?school='.$this->bo->getSchool();				
				
		// link webuntis instance
		$content['webuntis_link'] = $url;
		// timestamps last imports

		if(isset($config['last_update_subs'])){
		    $dt = new Api\DateTime($config['last_update_subs'], Api\DateTime::$user_timezone);
		    $tsf = $dt->format('d.m.Y H:i:s');
		    $content['last_update_subs'] = $tsf;
		}
		else{
		    $content['last_update_subs'] = '-';
		}

		if(isset($config['last_update_timetable'])){
		    $dt = new Api\DateTime($config['last_update_timetable'], Api\DateTime::$user_timezone);
		    $tsf = $dt->format('d.m.Y H:i:s');
		    $content['last_update_timetable'] = $tsf;	
		}
		else{
		    $content['last_update_timetable'] = '-';	
		}
		
		if(isset($config['last_webuntis_import'])){
		    $dt = new Api\DateTime($config['last_webuntis_import'], Api\DateTime::$user_timezone);
		    $tsf = $dt->format('d.m.Y H:i:s');
		    $content['last_webuntis_import'] = $tsf;
		}
		else{
		    $content['last_webuntis_import'] = '-';
		}

		$content['my_groups'] = API\Accounts::getInstance()->memberships($GLOBALS['egw_info']['user']['account_lid'], true);
				
		$content['admin'] = isset($GLOBALS['egw_info']['user']['apps']['admin']);
		$content['webuntis_user'] = $config['webuntis_user'];
		

		$readonlys = array(
			'button[update_timetable]'     => false,
			'button[update_substitution]'  => false,
		    'button[update_timegrid]'  => false,
		    'button[update_groupmembers]'  => false,
		    'button[create_async_timetable]'       => false,
		    'button[create_async_substitution]'       => false,
		    'button[delete_async]'       => false,
		);

        $content['category'] = $config['cal_category'];
		$sel_options['category'] = $this->loadCalCategories();

        $teacher_so = new untissync_teacher_so();
        $activeTeachers = $teacher_so->getActiveTeachers('te_uid', false);
        Api\Cache::setSession('untissync', 'activeTeachers', $activeTeachers);

        $content['teacher_active_count'] = count($activeTeachers);

		$this->tmpl->read('untissync.index');
		return $this->tmpl->exec('untissync.untissync_ui.index',$content,$sel_options,$readonlys,$preserv);
	}

    /**
     * Shows some information data for all users
     * @param string $content
     * @return string
     * @throws Api\Exception
     * @throws Api\Exception\AssertionFailed
     */
	function info($content='')
	{
	    $sel_options = array();
	    $preserv = array();
	    $msg = '';
	    $content = array();

	    $content['msg'] = $msg ? $msg : $_GET['msg'];
	    
	    $config = untissync_config::read();
	    
	    if(isset($config['last_update_subs'])){
	        $dt = new Api\DateTime($config['last_update_subs'], Api\DateTime::$user_timezone);
	        $tsf = $dt->format('d.m.Y H:i:s');
	        $content['last_update_subs'] = $tsf;
	    }
	    else{
	        $content['last_update_subs'] = '-';
	    }

	    if(isset($config['last_update_timetable'])){
	        $dt = new Api\DateTime($config['last_update_timetable'], Api\DateTime::$user_timezone);
	        $tsf = $dt->format('d.m.Y H:i:s');
	        $content['last_update_timetable'] = $tsf;
	    }
	    else{
	        $content['last_update_timetable'] = '-';
	    }
	    
	    if(isset($config['last_webuntis_import'])){
	        $dt = new Api\DateTime($config['last_webuntis_import'], Api\DateTime::$user_timezone);
	        $tsf = $dt->format('d.m.Y H:i:s');
	        $content['last_webuntis_import'] = $tsf;
	    }
	    else{
	        $content['last_webuntis_import'] = '-';
	    }

	    $content['my_groups'] = API\Accounts::getInstance()->memberships($GLOBALS['egw_info']['user']['account_lid'], true);
	    
	    $content['admin'] = isset($GLOBALS['egw_info']['user']['apps']['admin']);
	    $content['webuntis_user'] = $config['webuntis_user'];

        $readonlys = array();
	    
	    $this->tmpl->read('untissync.info');
	    return $this->tmpl->exec('untissync.untissync_ui.info',$content,$sel_options,$readonlys,$preserv);
	}

	
	/**
	 * List substitutions for several days
	 *
	 * @param array $content
	 * @param string $msg
	 */
	function list($content='')
	{
	    $config = untissync_config::read();
	    $sel_options = array();
	    $preserv = array();
	    $msg = '';
	    
	    $search = Api\Cache::getSession('untissync', 'search_subs');
	    if(empty($filter)){
	        $filter = 0;
	    }
	    
	    $content = array();
	   
	    
	    $content['msg'] = $msg ? $msg : $_GET['msg'];
	    
	    $content['nm']['get_rows']		= 'untissync.untissync_ui.get_rows';
	    //$content['nm']['no_filter'] = false;
	    $content['nm']['filter_no_lang'] = true;
	    $content['nm']['no_cat']	= true;
	    $content['nm']['no_search']	= False;
	    $content['nm']['no_filter2']	= true;
	    $content['nm']['bottom_too']	= true;
	    $content['nm']['order']		= 'nm_id';
	    $content['nm']['sort']		= 'ASC';	   
	    $content['nm']['row_id']	= 'nm_id';
	    $content['nm']['favorites'] = true;
	    $content['nm']['search'] = $search;
	    //	$content['nm']['onExecute'] = 0;
	   	$content['nm']['num_rows'] = true;
	    $content['nm']['lettersearch'] = false;
	    $content['nm']['header_title'] = "Vertretungsplan";	   
	    $content['nm']['row_modified'] = 'modified';
	    
	    // last import to webuntis
	    if(isset($config['last_webuntis_import'])){
	        $dt = new Api\DateTime($config['last_webuntis_import'], Api\DateTime::$user_timezone);
	        $tsf = $dt->format('d.m.Y H:i:s');
	        $content['last_webuntis_import'] = $tsf;
	    }
	    else{
	        $content['last_webuntis_import'] = '-';
	    }
	    
	    // last import from webuntis
	    if(isset($config['last_update_subs'])){
	        $dt = new Api\DateTime($config['last_update_subs'], Api\DateTime::$user_timezone);
	        $tsf = $dt->format('d.m.Y H:i:s');
	        $content['last_update_subs'] = $tsf;
	    }
	    else{
	        $content['last_update_subs'] = '-';
	    }

        $content['nm']['options-filter'] = $this->list_filter;

	    $content['admin'] = isset($GLOBALS['egw_info']['user']['apps']['admin']);
	    $content['webuntis_user'] = $config['webuntis_user'];

	    $readonlys = array();
	    
	    $this->tmpl->read('untissync.list');
	    return $this->tmpl->exec('untissync.untissync_ui.list',$content,$sel_options,$readonlys,$preserv);
	}
	
	/**
	 * 
	 * @param unknown $query_in
	 * @param unknown $rows
	 * @param unknown $readonlys
	 * @param boolean $id_only
	 * @return number
	 */
	function get_rows(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
	    if(isset($query_in['search'])){
	        Api\Cache::setSession('untissync', 'list_search', $query_in['search']);
	    }
	    else{
	        $query_in['search'] = Api\Cache::getSession('untissync', 'list_search');
	    }

        if(isset($query_in['filter'])){
            Api\Cache::setSession('untissync', 'list_filter', $query_in['filter']);
        }
        else{
            $query_in['filter'] = Api\Cache::getSession('untissync', 'list_filter');
        }
	    
	    $this->bo->getSubstitutionList($query_in,$rows,$readonlys,$id_only);
	    
	    return $query_in['total'];
	}
	
	/**
	 * Creates async job to import substitutions
	 */
	function createAsyncJobSubstitution(){
		// import substitutions
		$async = new Api\Asyncservice();
		$method = 'untissync.untissync_bo.async_update_substitutions';
		$job = $async->read(self::ASYNC_JOB_UPD_SU_ID);
		$job = $job[self::ASYNC_JOB_UPD_SU_ID];
		$job['id'] = self::ASYNC_JOB_UPD_SU_ID;
		$job['method'] = $method;		
		$job['times'] = array(
		    'dow' => "0-6",
		    'min' => $this->createRandMin(),
		);

		if(!$async->write($job, true)){			
		    $ret = $async->set_timer($job['times'],self::ASYNC_JOB_UPD_SU_ID,$method,null);			
			if($ret){
			    $this->debug_log->log(" created update async job successfully: ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);				
			}
			else{
			    $this->debug_log->log(" $async->set_timer returned false: ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);
			}
		}
		else{
		    $this->debug_log->log(" updates async job successfully: ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);
		}
	}

    /**
     * Creates async job to import timetables
     * @throws Exception
     */
	function createAsyncJobTimetable(){
		// import timetable
		$async = new Api\Asyncservice();
		$method = 'untissync.untissync_bo.async_update_timetable';
		$job = $async->read(self::ASYNC_JOB_UPD_TT_ID);
		$job = $job[self::ASYNC_JOB_UPD_TT_ID];
		$job['id'] = self::ASYNC_JOB_UPD_TT_ID;
		$job['method'] = $method;
		$job['times'] = array(
		    'dow' => "0-6",
		    'hour' => (string)random_int(0, 4),
		    'min' => (string)random_int(0, 59),
		);

		if(!$async->write($job, true)){			
		    $ret = $async->set_timer($job['times'],self::ASYNC_JOB_UPD_TT_ID,$method,null);			
			if($ret){
				$this->debug_log->log(" created update async job successfully: ".self::ASYNC_JOB_UPD_TT_ID, __METHOD__);				
			}
			else{
				$this->debug_log->log(" $async->set_timer returned false: ".self::ASYNC_JOB_UPD_TT_ID, __METHOD__);
			}
		}
		else{
			$this->debug_log->log(" updates async job successfully: ".self::ASYNC_JOB_UPD_TT_ID, __METHOD__);
		}
	}
	
	
	/**
	 * Delete async job
	 */
	function deleteAsyncJobs(){
	    
	    // import substitutions
	    $async = new Api\Asyncservice();	    
	    
	    if(!$async->delete(self::ASYNC_JOB_UPD_SU_ID)){
	        $this->debug_log->log(" deleted async job successfully: ".self::ASYNC_JOB_UPD_SU_ID."; ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);	        
	    }
	    else{
	        $this->debug_log->log(" could not delete async job: ".self::ASYNC_JOB_UPD_SU_ID."; ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);
	    }
	    
	    if(!$async->delete(self::ASYNC_JOB_UPD_TT_ID)){
	        $this->debug_log->log(" deleted async job successfully: ".self::ASYNC_JOB_UPD_TT_ID."; ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);
	    }
	    else{
	        $this->debug_log->log(" could not delete async job: ".self::ASYNC_JOB_UPD_TT_ID."; ".self::ASYNC_JOB_UPD_SU_ID, __METHOD__);
	    }	    	    
	}
	
	/**
	 * Creates random times every hour
	 * @return string|number
	 */
	function createRandMin(){
	    $start = random_int(0, 14);
	    $result = (string)$start;
	    
	    while(($start = $start + 20) < 60){
	        $result = $result.','.(string)$start;	       
	   }
	   return $result;
	}

    /**
     * Loads EGroupware calendar categories
     * @return mixed
     */
	function loadCalCategories(){
	    $result = array();
	    $egw_categories = new Api\Categories($GLOBALS['egw_info']['user']['account_id'], 'calendar');
        $categories = $egw_categories->return_sorted_array(0,False,'','ASC','cat_name',True, 0,true,null);


        foreach($categories as $key => &$cat) {
            $label = $key;
            if (!is_array($cat)) {
                $label = html_entity_decode($cat, ENT_NOQUOTES, 'utf-8');
            } elseif ($cat['label']) {
                $label = html_entity_decode($cat['label'], ENT_NOQUOTES, 'utf-8');
            }

            $result[$cat['id']] = array(
                'label' => $cat['name'],
            );
        }

	    return $result;
    }

    /**
     * Imports substitutions via AJAX
     */
    public function ajax_importSubstitutions(){
        $msg = '';
        $result = $this->bo->importSubstitutions();
        if($result == 1) {
            $msg = $msg . $result . " Vertretungsplan wurde importiert!";
        }
        else{
            $msg = $msg . $result . " Vertretungspläne wurden importiert!";
        }

        $this->bo->requestLastImportTime($msg);
        $config = untissync_config::read();

        $dt = new Api\DateTime($config['last_webuntis_import'], Api\DateTime::$user_timezone);
        $ts_lwi = $dt->format('d.m.Y H:i:s');

        $dt = new Api\DateTime($config['last_update_subs'], Api\DateTime::$user_timezone);
        $ts_lus = $dt->format('d.m.Y H:i:s');

        $data = array(
            'msg' => $msg,
            'last_webuntis_import' => $ts_lwi,
            'last_update_subs' => $ts_lus,
        );
        Api\Json\Response::get()->data($data);
    }

    /**
     * Imports timetable via AJAX
     * Don't use this function because of long time running task by this action
     * @deprecated
     */
    /*public function ajax_importTimetable(){
        $msg = '';
        $result = $this->bo->importTimetable($msg);
        if($result == 1){
            $msg = $msg.$result." Stundenplan wurde importiert!";
        }
        else{
            $msg = $msg.$result." Stundenpläne wurden importiert!";
        }

        $this->bo->requestLastImportTime($msg);
        $config = untissync_config::read();

        $dt = new Api\DateTime($config['last_webuntis_import'], Api\DateTime::$user_timezone);
        $ts_lwi = $dt->format('d.m.Y H:i:s');

        $dt = new Api\DateTime($config['last_update_timetable'], Api\DateTime::$user_timezone);
        $ts_lut = $dt->format('d.m.Y H:i:s');

        $data = array(
            'msg' => $msg,
            'last_webuntis_import' => $ts_lwi,
            'last_update_timetable' => $ts_lut,
        );
        Api\Json\Response::get()->data($data);
    }*/

    /**
     * Import timetables by long task dialog
     * @param $index Index of active teacher to import
     * @return void
     */
    public function ajax_importTimetableLT(int $index){
        $start = hrtime(true);
        $msg = '';
        $response = Api\Json\Response::get();
        $activeTeachers = Api\Cache::getSession('untissync', 'activeTeachers');

        if($index >= count($activeTeachers)){
            $msg = "Index out of bounds";

            return $response->data($msg);
        }
        else{
            $ids = array($activeTeachers[$index]['te_uid']);
            $longname = $activeTeachers[$index]['te_longname'];

            $result = $this->bo->importTimetable($msg, $ids, $index == 0, $index == count($activeTeachers) -1 , $index == 0, $index == count($activeTeachers)-1);
            $end = hrtime(true);
            $msg = ($index + 1).'/'.count($activeTeachers).' '.$longname.' OK ('.number_format(($end - $start) / 1000000000, 2).' s)';
        }
        $response->data($msg);
    }

    /**
     * Test connection via AJAX
     */
    public function ajax_testConnection(){
        $msg = '';
        $status = 'failed';
        $test = $this->bo->testConnection($msg);

        if($test){
            $status = 'success';
            $msg = $msg." Successfully authenticated";
        }
        else{
            $msg = $msg." Could not connect ";
        }

        $data = array(
            'msg' => $msg,
            'status' => $status,
        );
        Api\Json\Response::get()->data($data);
    }

    /**
     * Delete orphaned calendar events
     */
    public function ajax_cleanupOrphaned(){
        $msg = '';
        $result = $this->bo->cleanupOrphaned($msg);
        $data = array(
            'msg' => $msg." $result calendar events deleted!",
        );
        Api\Json\Response::get()->data($data);
    }

    /**
     * Delete all timetables items and calendar events
     */
    public function ajax_deleteTimetables(){
        $msg = '';

        $result = $this->bo->deleteTimetables($msg);

        $data = array(
            'msg' => $msg." $result calendar events deleted!",
        );
        Api\Json\Response::get()->data($data);
    }

    /**
     * Delete all timetables items and calendar events
     */
    public function ajax_deleteSubstitutions(){
        $msg = '';

        $result = $this->bo->deleteSubstitutions($msg);

        $data = array(
            'msg' => $msg." $result substitutions deleted!",
        );
        Api\Json\Response::get()->data($data);
    }
}


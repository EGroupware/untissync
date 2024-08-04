<?php

/**
 * EGroupware - UntisSync - Business object
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

if (!defined('UNTISSYNC_APP'))
{
	define('UNTISSYNC_APP','untissync');
}

define('CLIENT_ID', 'EGW-UNTISSYNC');

define('COOKIEFILE', $GLOBALS['egw_info']['server']['temp_dir'].DIRECTORY_SEPARATOR.'webuntis_cookie.txt');

define('LAST_UPDATE_SUBS', 'last_update_subs');
define('LAST_WEBUNTIS_IMPORT', 'last_webuntis_import');
define('LAST_UPDATE_TIMETABLE', 'last_update_timetable');
define('UPDATE_TIMETABLE_DAYS', 'update_timetable_days');
define('WEBUNTIS_CAL_EVENT_OWNER', 'webuntis_cal_event_owner');
define('WEBUNTIS_MAPPING_PREFIX_CLASS', 'webuntis_mapping_prefix_class');
define('WEBUNTIS_MAPPING_PREFIX_TEACHER', 'webuntis_mapping_prefix_teacher');
define('WEBUNTIS_PSWD', 'webuntis_pswd');
define('WEBUNTIS_SCHOOL', 'webuntis_school');
define('WEBUNTIS_SERVER', 'webuntis_server');
define('WEBUNTIS_USER', 'webuntis_user');
define('CLEANUP_CAL_EVENTS', 'cleanup_cal_events');
define('CREATE_CAL_EVENTS', 'create_cal_events');
define('UPDATE_TEACHER_GROUPS', 'update_teacher_groups');

use EGroupware\Api;

class untissync_bo {
	var $debug_log;
	
    var $so_teacher;
    var $so_class;
    var $so_room;
    var $so_subject;
    var $so_timegrid;
    var $so_timetable;
    var $so_participant;
    var $so_substitution;
    var $so_resources;
    
    var $bo_calendar_update;
    var $so_calendar;

    /** webuntis-doc: identifies the request, is repeated in the response; not really needed right now*/
	var $post_id = '73647893';

	var $lstype;
	
	// timegrid array day-start-end => name, needed for searching the name of a lesson by start- and endtime
	var $timegrid = array();

	/**
	 * Constructor untisSync BO
	 */
	function __construct()
	{
		$this->debug_log = new untissync_log();	

	    $this->so_teacher = new untissync_teacher_so();
	    $this->so_class = new untissync_class_so();
	    $this->so_room = new untissync_room_so();
	    $this->so_subject = new untissync_subject_so();
	    $this->so_timegrid = new untissync_timegrid_so();
	    $this->so_timetable = new untissync_timetable_so();
	    $this->so_participant = new untissync_participant_so();
	    
	    $this->so_substitution = new untissync_substitution_so();
	    
	    $this->so_resources = new resources_so();
	    
	    $this->bo_calendar_update = new calendar_boupdate();
        $this->so_calendar = new calendar_so();

        $this->lstype = array(
            'ls' => 'Unterricht',
            'oh' => 'Büro',
            'sb' => 'Präsenz',
            'bs' => 'Aufsicht',
            'ex' => 'Prüfung',
        );
	}

    /**
     * Imports subjects and all timetables for each teacher
     * @param $msg
     * @param false|array $teacherUntisIDs untis ids of teachers that need updating
     * @return int number of imported timetables
     */
	public function importTimetable(&$msg, $teacherUntisIDs = false, $doLogin = true, $doLogout = true, $doImportTimeGrid = true, $doUpdClassGroups = true, &$syName = '', &$eventsCount = 0)
	{
	    set_time_limit(2400);
	    $this->debug_log->log(" set time limit: 2400 ", __METHOD__);

	    $config = untissync_config::read();
	    // time intervall
	    $startDate = new DateTime();
	    $update_timetable_days =  isset($config['update_timetable_days']) ? $config['update_timetable_days'] : 14;
	    $endDate = new DateTime();
	    $endDate = $endDate->modify("+$update_timetable_days day");

        if($doLogin){
            // authenticate
            $result = $this->authenticate($msg);
            if($result == false){
                $msg = $msg." Authentication failed!";
                return $result;
            }
        }

        // limit startdate and enddate by start and end of actual or next school year by first login
        if($doLogin || empty(Api\Cache::getSession('untissync', 'activeSchoolYear'))){
            $this->limitDates($startDate, $endDate, $syName);
            $activeSchoolYear = array(
                "startDate" => $startDate,
                "endDate" => $endDate,
                "name" => $syName,
            );
            Api\Cache::setSession('untissync', 'activeSchoolYear', $activeSchoolYear);
        }
        else{
            $activeSchoolYear = Api\Cache::getSession('untissync', 'activeSchoolYear');
            $startDate = $activeSchoolYear['startDate'];
            $endDate = $activeSchoolYear['endDate'];
            $syName = $activeSchoolYear['name'];
        }

	    if($teacherUntisIDs == False){
	        // update all timetables
	        $criteria = array(
	            "te_egw_uid > 0 AND te_active = 1",
	        );
	        $order = "te_last_untis_sync";
	        $teachers = $this->so_teacher->search($criteria, False, $order); //$this->so_teacher->queryMapped();
	        $teacherUntisIDs = array();
	        foreach($teachers as $teacher){
	            $teacherUntisIDs[$teacher['te_uid']] = array(
                    'te_id' => $teacher['te_id'],
                    'te_uid' => $teacher['te_uid'],
                );
	        }	        

	        $jobStart = time();
	        untissync_config::save_value('last_update_timetable', $jobStart);
	        
	        // import subjects
	        $result = $this->importSubjects();
	        $this->debug_log->log(" subjects imported ", __METHOD__);
	    }

        // import participants data if configured
        if($config['sync_participants'] && $doLogin){
            $this->importClasses();
            $this->importRooms();
        }

        if($doImportTimeGrid){
            // update timegrid
            $this->updateTimegrid();
            $this->timegrid = $this->so_timegrid->getTimegridSet();
            //this->debug_log->log(" timegrid imported ", __METHOD__);
        }

	    // update timetables from untis and egw calendar
        if(!$startDate){
            $startDate = new DateTime();
        }
        if(!$endDate){
            $endDate = new DateTime();
            $endDate = $endDate->modify("+$update_timetable_days day");
        }
	    $eventsCount = $this->updateTeacherTimetable($startDate->format('Ymd'), $endDate->format('Ymd'), $teacherUntisIDs);
	    
	    $this->debug_log->log(" startDate: ".$startDate->format('Ymd')."; endDate: ".$endDate->format('Ymd'), __METHOD__);
        if($doLogout){
            // LOGOUT
            $this->logout();
            Api\Cache::unsetSession('untissync', 'activeSchoolYear');
        }

        if($doUpdClassGroups){
            // update class group members
            $this->updateClassGroupsMembers();
        }

	    return sizeof($teacherUntisIDs);
	}

    /**
     * called from user action
     * Imports all substitutions data
     * @return false|int|string
     */
	public function importSubstitutions()
	{	    	    
	    if(!$this->authenticate()){
	        $this->debug_log->log("not authenticated!");
	        return False;
	    }
	    
	    $config = untissync_config::read();
	    
	    $jobStart = time();
	    untissync_config::save_value('last_update_subs', $jobStart);
		
		// set all clean marks to 0
		$this->so_substitution->markAllUnClean();

	    // start - enddate
	    $startDate = new DateTime();
	    $startYmd = $startDate->format('Ymd');
	    $update_timetable_days =  isset($config['update_timetable_days']) ? $config['update_timetable_days'] : 14;
	    $endDate = new DateTime();
	    $endDate = $endDate->modify("+$update_timetable_days day");
	    
		$result = $this->importSubstitutionsPeriod($startYmd, $endDate->format('Ymd'));

        // 1. search all teacher untis id, who has to be updated or will be deleted
        $teacher2Upd = $this->so_substitution->parseTeacherUpdate($startYmd);

		// 2. delete substitutions with clean = -1
		$this->so_substitution->cleanUp();

        // 3. load active teacher list
        $activeTeachers = $this->so_teacher->getActiveTeachers(); //$this->so_teacher->queryMapped();
        $teacherIDs = array_intersect_key($activeTeachers, $teacher2Upd); // TODO refactor!

        //$this->debug_log->log("config->create_cal_events: ".$config['create_cal_events']);
		if(!$config['create_cal_events']){
		    return $result; // do not create cal events, so update timetable is not necessarry
		}

        $this->debug_log->log("implode teacherids: ".implode('|', $teacherIDs));
		
		if(isset($teacherIDs) && !empty($teacherIDs)){
		    $this->timegrid = $this->so_timegrid->getTimegridSet();
		    $this->updateTeacherTimetable($startYmd, $endDate->format('Ymd'), $teacherIDs);
		}

        $this->logout();
	    return $result;
	}

    /**
     * requests last import timetamp from webuntis server
     * @param $msg
     * @return int|string|void
     */
	public function requestLastImportTime(&$msg)
	{
	    if(!$this->authenticate()){
			$this->debug_log->log("not authenticated!");
			return;
		}

		$url = $this->getURL();
		$school = $this->getSchool();
		$url = $url.'?school='.$school;
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getLatestImportTime',
	        'jsonrpc' => '2.0',	        
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
		//curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
		}
		
		$jsonContent = $this->getJsonContent($response);
		$result = $jsonContent['result']; // WebUntis return epoch time in MILLISECONDS!!
		$result = (int)($result / 1000); // in milliseconds!!!		
	    curl_close($ch);

	    $this->logout();
	    untissync_config::save_value('last_webuntis_import', $result);
	    
	    return $result;
	}

    /**
     * Authenticate the given user and start a session
     * @param string $msg
     * @return bool
     */
	private function authenticate(&$msg = '')
	{
		$url = $this->getURL();
		$user = $this->getUSername();
		$pswd = $this->getPswd();
		$school = $this->getSchool();

		$url = $url.'?school='.$school;

		$data = array(
			'id' => $this->post_id,
			'method' => 'authenticate',
			'jsonrpc' => '2.0',
			'params' => array(
				'user' => $user,
				'password' => $pswd,
				'client' => CLIENT_ID,
			),
		);

		$ch = curl_init($url);
		$json_payload = json_encode($data);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Content-Type: text/plain;charset=UTF-8'
                                            ));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
		curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
		curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($ch);
		
		// check HTTP status code
		if (!curl_errno($ch)) {
		    switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
		        case 200:  # OK
		            $jsonContent = $this->getJsonContent($json);
		            //$result = json_decode($json, true);
		            $sessionID = $jsonContent['result']['sessionId'];
		            if(!isset($sessionID)){		
		                $msg = "Authentication failed!";
		                return false;
		            }
		            break;
                case 404:
                    $msg = " Connection failed! HTTP_CODE: 404; ";
                default:
                    $msg = " Connection failed! HTTP_CODE: ".$http_code;
		            return false;
		    }
		}
        curl_close($ch);
		return true;
	}

    /**
     * Checks server connection and login
     */
	public function testConnection(&$msg = ''){
	    $result = false;
	    if($this->authenticate($msg)){
            $this->logout();
            $result = true;
        }
	    return $result;
    }

    /**
     * Cleanup orphaned calendar events
     */
    public function cleanupOrphaned(){
        $eventIDs = $this->so_timetable->searchOrphanedCalEvents();

        foreach ($eventIDs as $id){
            $this->so_calendar->delete($id);
        }
        return sizeof($eventIDs);
    }

    /**
     * Delete all timetables
     */
    public function deleteTimetables(&$msg = '', $te_uid = -1){
        $resultCount = 0;
        $filter = array();
        if($te_uid > 0){
            $filter[] = "tt_teuid = ".$te_uid;
        }

        $result = $this->so_timetable->search(null, false, null, null, null, false, 'AND', false, $filter);

        if(is_countable($result)){
            foreach ($result as $tt){
                // delete participants
                $this->so_participant->deleteAllParticipants($tt['tt_id'], 'tt');
                if($tt['tt_egw_cal_id']) {
                    // delete egroupware cal event
                    $this->so_calendar->delete($tt['tt_egw_cal_id']);
                }
                // delete timetable event
                $this->so_timetable->delete($tt['tt_id']);
            }

            $resultCount = sizeof($result);
        }
        return $resultCount;
    }

    /**
     * Delete all substitutinos
     */
    public function deleteSubstitutions(&$msg = ''){
        $intResult = 0;
        $result = $this->so_substitution->search('');

        foreach ($result as $sub){
            // delete participants
            $this->so_participant->deleteAllParticipants($sub['sub_id'], 'sub');
            // delete substitution
            $this->so_substitution->delete($sub['sub_id']);
        }
        if(is_countable($result)){
            $intResult = sizeof($result);
        }
        return $intResult;
    }

    /**
     * Loads current school year
     * @param $start
     * @param $end
     * @param $syName Name of school year
     * @return bool|string
     */
	private function getCurrentSchoolYear(&$start, &$end, &$syName){
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getCurrentSchoolyear',
	        'jsonrpc' => '2.0',
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK	                
	                break;
	            default:	                
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $jsonContent = $this->getJSONContent($response);
	    $start = $jsonContent['result']['startDate'];
	    $end = $jsonContent['result']['endDate'];
        $syName = $jsonContent['result']['name'];

	    curl_close($ch);
	    return true;
	}

    /**
     * Get next school year. This is usefull, if today is outside current school year because of holidays
     * @param $start
     * @param $end
     * @param $syName
     * @return void
     */
    private function getComingSchoolYear(&$start, &$end, &$syName){
        $schoolYears = $this->getSchoolYears();
        $today = intval(date("Ymd"));
        $diffDays = 30000; // search only within the next 3 years
        foreach($schoolYears as $key => $val) {
            if($today < $val['startDate'] && ($val['startDate'] - $today < $diffDays)){
                $start = $val['startDate'];
                $end = $val['endDate'];
                $syName = $val['name'];
                $diffDays = $val['startDate'] - $today;
            }
        }
    }


    /**
     * Load school years
     * @return array of school years
     */
    private function getSchoolYears(){
        $url = $this->getURL();

        $data = array(
            'id' => $this->post_id,
            'method' => 'getSchoolyears',
            'jsonrpc' => '2.0',
        );

        $ch = curl_init($url);
        $json_payload = json_encode($data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/plain;charset=UTF-8'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // check HTTP status code
        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    break;
                default:
                    return 'Unerwarter HTTP-Code: '. $http_code;
            }
        }
        $responseBody = $this->getJSONContent($response);
        curl_close($ch);
        return $responseBody['result'];
    }
	
	/**
	 * Limits two DateTime objects to current schoolyear
	 * @param DateTime $startDate
	 * @param DateTime $endDate
	 */
	private function limitDates(DateTime &$startDate, DateTime &$endDate, &$syName){
        $start = '';
	    $end = '';
	    
	    $this->getCurrentSchoolYear($start, $end, $syName);
        // get coming school year if current school year is null
        if(empty($start)){
            $this->getComingSchoolYear($start, $end, $syName);
        }

	    $startD = DateTime::createFromFormat('Ymd', $start);
	    $endD = DateTime::createFromFormat('Ymd', $end);
	    

        if($startDate < $startD){
	        // $startDate before first day in school year
	        $startDate = $startD;
	    }

        if($endDate > $endD){
	        // $endDate after last day in school year
	        $endDate = $endD;
	    }
	}

	/**
	 * Terminate session
	 */
	private function logout()
	{
		$url = $this->getURL();
		$data = array(
			'id' => $this->post_id,
			'method' => 'logout',
			'jsonrpc' => '2.0',
			'params' => array()
		);

		// Create a new cURL resource
		$ch = curl_init($url);
		$payload = json_encode($data);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

    /**
     * Getlist of substitutions
     * @param $startDate
     * @param $endDate
     * @return int|string
     */
	private function importSubstitutionsPeriod($startDate, $endDate)
	{
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getSubstitutions',
	        'jsonrpc' => '2.0',
	        'params' => array(
	            'departmentId' => 0,
	            'startDate' => $startDate,
	            'endDate' => $endDate,
	        ),
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                break;
	            default:	               
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);

		$untisTeacherSet = $this->so_teacher->getUntisTeacherSet();
		
	    foreach ($result['result'] as &$val) {
    	    $val['txt'] = $val['txt'] ?? ''; 
    	    $this->so_substitution->write($val['type'], $val['lsid'],  $val['date'], $val['startTime'], $val['endTime'], $val['kl'], $val['te'], $val['ro'], $val['su'], $val['txt'], $untisTeacherSet);
	    }

	    curl_close($ch);
	    return sizeof($result['result']);
	}
	
	/**
	 * Updates the timetable of the teachers given by its untis ids. First mark all its events as unclean (-1), while updating checks if events have been changed (0: changed, 1:equals).
	 * If events stays with flag -1, delete them (and partivipants an egroupware cal-event). If events stays with flag 0, reset participants an update egw cal-event.
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param array $teacherUntisID
	 */
	public function updateTeacherTimetable($startDate, $endDate, array $teacherIDs)
	{
	    // TODO reload teacher, class and rooms from cache!

	    $len = sizeof($teacherIDs);
	    $counter = 0;
        $eventsCount = 0;
        $so_teacher = new untissync_teacher_so();

	    foreach($teacherIDs as $key => $val){
            $te_uid = $val['te_uid'];
	        $this->debug_log->log("Import teacher timetable for  teacher untis id:".$te_uid, __METHOD__);
	        
	        // mark tt events as unclean, ignore events of the past n days , if delete_expired_events_days > 0
	        $this->so_timetable->markUnClean($te_uid);
	        // import events, return array with modified events
	        $ttevents = $this->importSingleTeacherTimetable($startDate, $endDate, $te_uid);
            $eventsCount += count($ttevents);
	        if(is_array($ttevents)){
	            foreach($ttevents as $tt){
	                // update a single event, incl. set flag for clean up
                    $this->updateSingleCalItem($tt);
	            }
	        }
	        
	        // delete alle timetable events with tt_clean = -1, delete old events only if config[cleanup_cal_events] == 1
	        $this->cleanUpTimetableEvents($te_uid);

            // update last untis sync timestamp
            $so_teacher->update_untis_syn_ts($val['te_id']);

            if($counter % 10 == 0){
	            $this->debug_log->log(" ... ".$counter." teacher imported; ".number_format(100 * $counter / $len, 2)." %", __METHOD__);
	        }
	        $counter++;
	    }
        return $eventsCount;
	}
	
	/**
	 * Imports a single timetable for a mapped teacher.
	 * @param unknown $startDate
	 * @param unknown $endDate
     * @param unknown $object_id teachers object id
	 * @param $type 1 = klasse, 2 = teacher, 3 = subject, 4 = room, 5 = student
	 * @return array ttevents which has been changed
	 */
	private function importSingleTeacherTimetable($startDate, $endDate, $object_id, $type = 2)
	{
	    $ttevents = array();
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getTimetable',
	        'jsonrpc' => '2.0',
	        'params' => array(
	            'id' => $object_id,
	            'type' => $type,
	            'startDate' => $startDate,
	            'endDate' => $endDate,
	        ),
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                break;
	            default:
	                echo 'unexpected HTTP-Code: ', $http_code, "\n";
	                return 'unexpected HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);
	    
	    if(is_array($result)){	        	   
    	    foreach ($result['result'] as &$val) {
    	        //$tt = $this->so_timetable->write($val['id'], $object_id, $val['date'], $val['startTime'], $val['endTime'], $val['lstype'], $val['code'], $val['lstext'], $val['statsflags'], $val['activityType'], $val['kl'], $val['te'], $val['ro'], $val['su']);
                $tt = $this->so_timetable->write($val, $object_id);
    	        if(is_array($tt)){
    	            $ttevents[] = $tt;
    	        }
    	    }
	    }
	    	    
	    curl_close($ch);
	    return $ttevents;
	}	

	/**
	 * updates teacher data
	 */
	public function updateTeachers(&$msg){
	    $result = $this->authenticate();
	    if($result == true) {
	       $result = $this->importTeachers($msg);	
	    }
	    $this->logout();
	    return $result;
	}
	
	/**
	 * updates rooms data
	 */
	public function updateRooms(){
	    $result = $this->authenticate();
	    if($result == true) {
	        $result = $this->importRooms();
	    }
	    $this->logout();
	    return $result;
	}
	
	/**
	 * updates classes data
	 */
	public function updateClasses(){
	    $result = $this->authenticate();
	    if($result == true) {
	        $result = $this->importClasses();
	    }
	    $this->logout();
	    return $result;
	}

	/**
	 * Getlist of teachers
	 */
	private function importTeachers(&$msg)
	{
	    $url = $this->getURL();
	    $school = $this->getSchool();
		$url = $url.'?school='.$school;

        $config = untissync_config::read();
        $activation = false;
        if(isset($config['import_teacher_activation'])){
            $activation = $config['import_teacher_activation'] == 1;
        }

	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getTeachers',
	        'jsonrpc' => '2.0',
	        'params' => array(	           
	        ),
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                          
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }	    	   	    
		$result = $this->getJSONContent($response);	
		
		if(isset($result['error'])){
			// error!
			$msg = $msg.PHP_EOL.$result['error']['message'];
			return false;
		}
	    
	    $options = array();
	    $options['account_type'] = 'accounts';
	    
	    foreach ($result['result'] as &$val) {	
	        $active = false;
	        $pattern = Api\Accounts::email($val['foreName'], $val['longName'], "");
	        //$pattern =  $val['longName'];
	        // check if corresponding egroupware user can be found
	        $ids = Api\Accounts::link_query($pattern, $options);

	        if(count($ids) == 0){
	            // not found, use different pattern
                $pattern2 = Api\Accounts::email($val['foreName'], $val['name'], "");
                $ids = Api\Accounts::link_query($pattern2, $options);
            }

	        $egw_uid = 0;
            $teacher = $this->so_teacher->getTeacherByUntisID($val['id']);
            if(!$teacher){
                if(count($ids) == 1){
                    // egroupware user was newly found
                    $egw_uid = array_key_first($ids);
                    $active = $activation; // depends on config
                }
                $this->so_teacher->write($val['id'], $val['name'], $val['foreName'],  $val['longName'],  $egw_uid, $active);
            }


            //elseif(count($ids) != 1){
	            // unique egroupware user was NOT found
            //    $egw_uid = 0;
            //   $active = false;
	            // if teacher was marked as active, then keep binding
	        //    $teacher = $this->so_teacher->getTeacherByUntisID($val['id']);
	            //if(isset($teacher) && $teacher['te_egw_uid']){
	            //    $active = true;
	            //    $egw_uid = $teacher['te_egw_uid'];
	            //}
	        //}

	        //$this->so_teacher->write($val['id'], $val['name'], $val['foreName'],  $val['longName'],  $egw_uid, $active);
	    }
	    curl_close($ch);
	    return true;
	}
	
	// TEACHER
    /**
     * Saves teacher mapping, modified by user
     * @param $egw_uid
     * @param $te_id
     * @return bool
     */
	public function updateTeacherMapping($te_id, $te_egw_uid){
	    return $this->so_teacher->updateMapping($te_id, $te_egw_uid);
	}

    /**
     * deletes single teacher
     * @param array $teacher
     * @return bool
     */
	public function deleteTeacher(array $teacher){
	    $this->so_teacher->delete($teacher['te_id']);
	    return true;
	}
	/**
	 * Truncates teacher mapping, modified by user
	 */
	public function truncateTeachers(){
	    return $this->so_teacher->truncate();
	}
	
	// ROOM
    /**
     * Saves room mapping, modified by user
     * @param $egw_res_id
     * @param $ro_id
     * @return bool
     */
	public function updateRoomMapping($ro_id, $egw_res_id){
	    return $this->so_room->updateMapping($ro_id, $egw_res_id);
	}

    /**
     * @param array $room
     * @return bool
     */
	public function deleteRoom(array $room){
	    $this->so_room->delete($room['id']);
	    return true;
	}
	
	/**
	 * Truncates room mapping, modified by user
	 */
	public function truncateRooms(){
	    return $this->so_room->truncate();
	}
	
	// CLASSES
    /**
     * Saves class mapping, modified by user
     * @param $kl_id untis class id
     * @param $egw_uid
     * @param $egw_group_id
     * @return bool
     */
	public function updateClassMapping($kl_id, $egw_uid, $egw_group_id){
	    return $this->so_class->updateMapping($kl_id, $egw_uid, $egw_group_id);
	}

    /**
     * @param array $class
     * @return bool
     */
	public function deleteClass(array $class){
	    $this->so_class->delete($class['id']);
	    return true;
	}
	/**
	 * Truncates classes mapping, modified by user
	 */
	public function truncateClasses(){
	    return $this->so_class->truncate();
	}
	
	/**
	 * Get list of rooms
	 */
	private function importRooms()
	{
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getRooms',
	        'jsonrpc' => '2.0',
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);
	    
	    $options = array();
	    $options['account_type'] = 'accounts';
	    	    
	    $res_value_col = array();
	    $res_value_col['id'] = 'res_id';
	    
	    foreach ($result['result'] as &$val) {
	        //$pattern = Api\Accounts::email($val['foreName'], $val['longName'], "");	       
	        //$ids = Api\Accounts::link_query($pattern, $options);
	        $egw_res_id  = 0;
	        $filter = array();
	        $filter[] = "name = '".$val['name']."'";	    
	        
	        $result = $this->so_resources->query_list($res_value_col, '', $filter);
            //$result = $this->so_resources->read($filter);
    	    
    	    if(is_array($result) && count($result) == 1){
    	        // egroupware user was found
    	        $egw_res_id = array_key_first($result);
    	        $active = true;
    	    }
    	    else{
    	        // egroupware user was NOT found
    	        $room = $this->so_room->getRoomByUntisID($val['id']);
    	        if(isset($room) && $room['ro_active'] && $room['ro_egw_res_id']){
    	            $active = true;
    	            $egw_res_id = $room['ro_egw_res_id'];
    	        }
    	    }

    	    $this->so_room->write($val['id'], $val['name'],$val['longName'],  $egw_res_id, $active);
	    }
	    
	    curl_close($ch);
	    return $response;
	}

	/**
	 * return an array with available rooms
	 * @return array of rooms
	 */	
	public function getAvailableRooms(){
	    $res_value_col = array(
	        'res_id' => 'res_id',
	        'name' => 'name',
	        'short_description' => 'short_description',
	        'cat_id' => 'cat_id',
	    );

	    $filter = array();
	    $filter[] = "name LIKE 'Klasse%'";
	    
	    return $this->so_resources->query_list($res_value_col);
	}

	/**
	 * Getlist of classes
	 */
	private function importClasses()
	{
	    $options = array();
	    $options['account_type'] = 'accounts';
	    
	    $config = untissync_config::read();

	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getKlassen',
	        'jsonrpc' => '2.0',
	        'params' => array(
	        ),
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    //curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);
	    
	    foreach ($result['result'] as &$val) {
	        $egw_uid = $this->so_class->getClassAccountID($val['name']);
	        $egw_gid = $this->so_class->getClassTeacherGroupID($val['name']);

	        $active = $egw_uid > 0 && $egw_gid < 0;	       

	        $this->so_class->write($val['id'], $val['name'], $val['longName'], $egw_uid, $egw_gid, $active);
	    }
	    
	    curl_close($ch);
	    return $response;
	}

	/**
	 * Getlist of subjects
	 */
	private function importSubjects()
	{
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getSubjects',
	        'jsonrpc' => '2.0',
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);
	    
	    foreach ($result['result'] as &$val) {
	        $this->so_subject->write($val['id'], $val['name'], $val['longName']);
	    }
	    
	    curl_close($ch);
	    return $response;
	}

	/**
	 * called from ui
	 */
	public function startImportTimegrid(&$msg){
	    // authenticate
	    $result = $this->authenticate($msg);
	    if($result == false) return $result;
	    
	    // timegrid import
	    // todo into loop, only reload once
	    $result = $this->updateTimegrid();
	    $this->timegrid = $this->so_timegrid->getTimegridSet();
	    $this->debug_log->log(" timegrid updated ", __METHOD__);

        if(empty($this->timegrid)){
            $msg = $msg."Aktuelles Stundenraster ist leer!";
        }
        else{
            $msg = $msg."Aktualisiertes Stundenraster mit ".count($this->timegrid)." Stunden importiert";
        }
	    // LOGOUT
	    $this->logout();    	    	    
	}

	/**
	 * Get timegrid from webuntis server. 1 = sunday, 2 = monday, ..., 7 = saturday
	 */
	private function updateTimegrid()
	{
	    $url = $this->getURL();
	    
	    $data = array(
	        'id' => $this->post_id,
	        'method' => 'getTimegridUnits',
	        'jsonrpc' => '2.0',
	    );
	    
	    $ch = curl_init($url);
	    $json_payload = json_encode($data);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: text/plain;charset=UTF-8'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    
	    // check HTTP status code
	    if (!curl_errno($ch)) {
	        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
	            case 200:  # OK
	                break;
	            default:
	                echo 'Unerwarter HTTP-Code: ', $http_code, "\n";
	                return 'Unerwarter HTTP-Code: '. $http_code;
	        }
	    }
	    $result = $this->getJSONContent($response);

        if($result['result']){
            // only possible between school year boundaries
            $this->so_timegrid->truncate();
            foreach ($result['result'] as &$val) {
                $day = $val['day'];
                foreach($val['timeUnits'] as &$unit){
                    $this->so_timegrid->write($day, $unit['name'], $unit['startTime'], $unit['endTime']);
                }
            }
        }
	    curl_close($ch);
	    return $response;
	}
	
	/**
	 * updates a single egw calendar event, if event.code == 'cancelled and event exists, delete event
	 * @param array $tt
	 */
	private function updateSingleCalItem($tt){
	    // load participants
	    $parts = $this->so_participant->loadParticipants($tt['tt_id'], 'tt');	
	    
	    $te = array();
	    $ro = array();
	    $kl = array();
        $su = array();
		// read egw id for every participant
		if(is_array($parts)){
			foreach ($parts as &$part) {
				switch($part['pa_parttype']){
					case "te":
						$item = $this->so_teacher->getTeacherByUntisID($part['pa_partid']);
						if($item > 0){
						    $te[] = $item;
						}
						break;
					case "ro":
					    $item = $this->so_room->getRoomByUntisID($part['pa_partid']);
					    if($item > 0){
					        $ro[] = $item;
						}
						break;
					case "kl":
					    $item = $this->so_class->getClassByUntisID($part['pa_partid']);
					    if($item > 0){
					        $kl[] = $item;
						}
						break;
					case "su":
					    $item = $this->so_subject->getSubjectByUntisID($part['pa_partid']);
					    if($item > 0){
					        $su[] = $item;
						}
						break;
				}
			}
		}		
	    
	    $this->createUpdateCalendarEvent($tt, $te, $ro, $kl, $su);
	}

    /**
     * create, delete or update a single egw calendar event
     * @param $ttevent
     * @param array $te
     * @param array $ro
     * @param array $kl
     * @param array $su
     * @return array|bool|int|mixed|void
     * @throws Api\Exception
     */
	private function createUpdateCalendarEvent($ttevent, array $te, array $ro, array $kl, $su = '') {
	    $config = untissync_config::read();
	    
	    if(!$config['create_cal_events']){
	       return; // do not create cal events!
	    }
	    
	    $cal_owner = $config['webuntis_cal_event_owner'];
	    
	   // change Gi format to Hi (930 -> 0930)
	    $starttime = str_pad($ttevent['tt_starttime'], 4, '0', STR_PAD_LEFT);
	    $endtime = str_pad($ttevent['tt_endtime'], 4, '0', STR_PAD_LEFT);
	    
	    //$erste_stunde->format('Y-m-d') . ' ' . $content['stunde']['start'],
	    $dateStart = DateTime::createFromFormat('Ymd Hi', $ttevent['tt_date'].' '.$starttime);
	    $dateStart = new Api\DateTime($dateStart, Api\DateTime::$user_timezone);
	    //$dateStart = 
	    $dateEnd = DateTime::createFromFormat('Ymd Hi', $ttevent['tt_date'].' '.$endtime);
	    $dateEnd = new Api\DateTime($dateEnd, Api\DateTime::$user_timezone);

	    $category = isset($config['cal_category']) ? $config['cal_category'] : 0;

        $classes = implode(',', array_column($kl, 'kl_name'));

	    $event = array(
	        'title' => $this->createEventTitle($ttevent, $te, $ro, $kl, $su),
	        'start' => $dateStart->format('ts'), //$dateStart->format('Y-m-d Hi'),
	        'end' => $dateEnd->format('ts'), //$dateEnd->format('Y-m-d Hi'),
	        'description' => $ttevent['tt_activitytype'].' ('.$classes.')',
	        'location' => $this->arraytoCSV($ro, 'ro_name')
        );

	    if($category > 0){
            $event['category'] = $category;
        }
	    
	    if(isset($ttevent['tt_egw_cal_id'])){
	        $event['id'] = $ttevent['tt_egw_cal_id'];
	    }
	    
	    if(isset($cal_owner)){//isset($ttevent['tt_egw_cal_id'])){
	        $event['owner'] = $cal_owner;//$ttevent['tt_egw_cal_id'];
        }
        else if(!empty($te)){
            $event['owner'] = $te[0]['te_egw_uid'];
        }
        else{
            error_log(__METHOD__." No Teacher found for Timetable with title ".$event['title']);
            return; // no teacher found! 
        }        
	        
	    // Teilnehmer und Raum hinzuf�gen
		$this->createParticipantsInfo($event, $te, $kl, $ro);
		$msg = "";

        $calid = -1;
        if($ttevent['tt_code'] == 'cancelled') {
            // timetable-event has been cancelled
            if($ttevent["tt_egw_cal_id"] > 0) {
                $this->bo_calendar_update->delete($ttevent["tt_egw_cal_id"], 0, true);
                $this->so_timetable->updateEgwCalendarEventID($ttevent, $calid); // update id an timestamp
            }
        }
        else{
            $calid = $this->bo_calendar_update->update($event, true, true, true, true, $msg, "NOPUSH"); // true for ignore_conflicts, update modifier, ignore acl
            $this->so_timetable->updateEgwCalendarEventID($ttevent, $calid); // update id an timestamp
        }


	    
	    return $calid;
	}
	
	/**
	 * creates specific event title
	 * @param unknown $ttevent
	 * @param array $te
	 * @param array $ro
	 * @param array $kl
	 * @param array $su
	 * @return string|unknown
	 */
	private function createEventTitle($ttevent, array $te, array $ro, array $kl, array $su){
	    // get name of lesson
	    $date_Ymd = $ttevent['tt_date'];
	    $starttime = $ttevent['tt_starttime'];
	    $endtime = $ttevent['tt_endtime'];	    
	    $date = DateTime::createFromFormat('Ymd', $date_Ymd);
	    $dayInWeek = $date->format('w') + 1; // 1= sunday in timegrid array
	    
	    $title = $this->getLessonName($dayInWeek, $starttime, $endtime, '. ');

	    switch($ttevent['tt_lstype']){
	        case "ls":
	            // Unterricht
	            $title = $title.$this->arrayToCSV($su, 'su_name');
	            $title = $title.' ';
	            $title = $title.$this->arrayToCSV($kl, 'kl_name');
	            break;
	        case "oh":	            
	            // Büro
	            $title = $title.$this->arrayToCSV($su, 'su_name');
	            break;
	        case "sb":
	            // Präsenz
	            $title = 'Präsenz';//$title.utf8_encode("Präsenz");
	            break;
	        case "bs":
	            // Aufsicht Pause
	            $title = $title.$this->arrayToCSV($ro, 'ro_name').'(Aufs.)';
	            break;
	        case "ex":
	            // Prüfung
	            $title = $title.'Prüfung';
	            break;
	        default:
	            // Unterricht
	            $title = $title.$this->arrayToCSV($su, 'su_name');
	            $title = $title.' ';
	            $title = $title.$this->arrayToCSV($kl, 'kl_name');
	    }
	    return $title;
	}

    /**
     * get name of lesson
     * @param $dayInWeek
     * @param $starttime
     * @param $endtime
     * @param string $append
     * @return string
     */
	private function getLessonName($dayInWeek, $starttime, $endtime, $append = ''){
	    $result = '';
	    	    
	    $key = $dayInWeek.'-'.$starttime.'-'.$endtime;
	    
	    if(is_array($this->timegrid) && array_key_exists($key, $this->timegrid)){
	        $result = $this->timegrid[$key].$append;
	    }
	    return $result;	    
	}

    /**
     * gets an 2dimensional array like myarr[item[0]{key1=>val1,key2=>val2},item[1]{key1=>val1,key2=>val2}]
     * filters from all items the $key-value abd creates a csv result
     * @param $arr
     * @param $key
     * @param string $sep
     * @param string $prefix
     * @param string $postfix
     * @return string
     */
	private function arrayToCSV($arr, $key, $sep = ',', $prefix = '', $postfix = ''){
	    if(empty($arr)){
	        return '';
	    }
	    
	    $result = $prefix;
	    foreach($arr as &$item){
	        if($result != $prefix){
	            $result = $result.$sep;
	        }
	        $result = $result.$item[$key];
	    }
	    $result = $result.$postfix;
	    return $result;
	}

    /**
     * Complements the array $eventarray with the keys 'participants' and
     * 'participant_types'
     * participants = array(12=>'ACHAIR', r32=>'A') // 1. UserID 12, 2. ResourceID 32
     * participant_types = array('u'=>array(12 => 'ACHAIR'), 'r'=> array(32=>'A'))
     * @param $event
     * @param $te
     * @param $kl
     * @param $ro
     */
	private function createParticipantsInfo(&$event, $te, $kl, $ro){
	    $participantIDs = array();
	    $participantIDs_types = array();
	    
	    // add teachers
	    foreach($te as $teacher){
	        $uid = $teacher['te_egw_uid'];
	        if($uid > 0){
    	        $participantIDs[$uid] = 'ACHAIR';
    	        $participantIDs_types['u'] = array($uid => 'ACHAIR');
	        }
	    }
	    // add class
	    foreach($kl as $klasse){
	        $uid = $klasse['kl_egw_uid'];
	        if($uid > 0){
	            $participantIDs[$uid] = 'ACHAIR';
	            $participantIDs_types['u'] = array($uid => 'ACHAIR');
	        }
	    }	    
	    // add room as resource
	    foreach($ro as $room){
	        $res_id = $room['ro_egw_res_id'];
	        if($res_id > 0){
	            $participantIDs['r'.$res_id] = 'A';
	            $participantIDs_types['r'] = array($res_id => 'A');
	        }
	    }
	    $event['participants'] = $participantIDs;
	    $event['participant_types'] = $participantIDs_types;	    
	}

	/**
	 * get URL from config
	 */
	public function getURL(){
	    $config = untissync_config::read();
		return $config['webuntis_server'];
	}

	/**
	 * get username from config
	 */
	private function getUsername(){
	    $config = untissync_config::read();
		return $config['webuntis_user'];
	}

	/**
	 * get password from config
	 */
	private function getPswd(){
	    $config = untissync_config::read();
		return $config['webuntis_pswd'];
	}

	/**
	 * get webuntis school from config
     * performs urlencoding if the character % is not included
	 */
	public function getSchool(){
	    $config = untissync_config::read();
        $result = $config['webuntis_school'];
        return strpos($result, "%") === false ? urlencode($result) : $result;
	}

    /**
     * Reads content from mixed plain text and json content
     * @param $plainText
     * @return array
     */
	private function getJSONContent($plainText){
	    $result = array();
	    $lines = array_filter(preg_split('/\R/', $plainText));
	    
	    foreach ($lines as &$line) {
	        $decode = json_decode($line, true);	     
	        if(!empty($decode)){
	           $result = array_merge($result, $decode);	  
	        }
	    }
	    return $result;	    
	}

    /**
     * @param $query_in
     * @param $rows
     * @return unknown
     */
	public function getTeacherMapping(&$query_in,&$rows)
	{
        $readonlys = array();
	    $result = $this->so_teacher->get_rows($query_in,$rows, $readonlys);
	    return $result;	   
	}

    /**
     * @param $query_in
     * @param $rows
     * @return unknown
     */
	public function getRoomMapping(&$query_in,&$rows)
	{
        $readonlys = array();
	    $result = $this->so_room->get_rows($query_in,$rows, $readonlys);
	    // add egw room name
	    foreach($rows as &$room){
	        if($room['egw_res_id'] > 0){
	            $egw_res = $this->so_resources->read(array('res_id' => $room['egw_res_id']));
	            if($egw_res){
	                $room['egw_name'] = $egw_res['name'];
	            }
	        }
	    }
	    return $result;
	}

    /**
     * @param $query_in
     * @param $rows
     * @return unknown
     */
	public function getClassMapping(&$query_in,&$rows)
	{
        $readonlys = array();
	    $result = $this->so_class->get_rows($query_in,$rows, $readonlys);
	    return $result;
	}
	
	/**
	 * updates all groups, representing a class, groups are used for email distribution
	 */
	public function updateClassGroupsMembers(){
	    $config = untissync_config::read();
	    if(!$config['update_teacher_groups'] ){
	        return; // do not update groups
	    }
	    
	    $criteria = array(
	       'kl_egw_group_id < 0',  
	    );
	    // group ids are negativ!
	    $classes = $this->so_class->search($criteria, False);
	    
	    if(is_array($classes)){
    	    foreach($classes as $class){
    	        $this->debug_log->log($class['kl_name'], __METHOD__);
    	        
    	        $teacherList = $this->so_teacher->listTeacherByClass($class['kl_uid'], True);    	           	        
    	        
    	        $accounts =  API\Accounts::getInstance();
    	        
    	        $accounts->set_members($teacherList, $class['kl_egw_group_id']);    	
    	    }
	    }
	}
	
	/**
	 * clean up all timetable items: participants, egroupware calendar event, timttable itself for the teacher with the given untis id
	 * this is called, when substitutions has been changed
	 * @param unknown $teacherUnitsID
	 */
	private function cleanUpTimetableEvents($teacherUntisID){
	    $config = untissync_config::read();
        $delOldEgwEvents = ($config['cleanup_cal_events'] == 1);         // delete egw cal events in the past?
        $todayYMD = (new DateTime())->format('Ymd');

	    $tt_events = $this->so_timetable->searchUnClean($teacherUntisID);

	    if(is_array($tt_events)){
    	    foreach($tt_events as &$tt_event){
    	        $cal_egw_id = $tt_event['tt_egw_cal_id'];

                if($tt_event['count_te_parts'] <= 1){
                    // delete event, this teacher was the last teacher participant
                    $this->so_participant->deleteAllParticipants($tt_event['tt_id'], 'tt');
                    // delete egw Cal event
                    if($cal_egw_id > 0 && ($delOldEgwEvents || $tt_event['tt_date'] >= $todayYMD)){
                        // $delOldEgwEvents => delete events in the past
                        $this->bo_calendar_update->delete($cal_egw_id, 0, true);
                    }
                    $this->so_timetable->delete($tt_event['tt_id']);
                }
                else{
                    // update event, there are more teacher participants
                    // delete single participant
                    $this->so_participant->delete($tt_event['pa_id']);
                    // remove participant and update event
                    $egw_teacher = $this->so_teacher->getTeacherByUntisID($teacherUntisID);
                    $cal_event = $this->so_calendar->read($tt_event['tt_egw_cal_id']);
                    unset($cal_event[$tt_event['tt_egw_cal_id']]['participants'][$egw_teacher['te_egw_uid']]);
                    unset($cal_event[$tt_event['tt_egw_cal_id']]['participant_types']['u'][$egw_teacher['te_egw_uid']]);

                    $this->so_timetable->updateEgwCalendarEventID($cal_event, $tt_event['tt_egw_cal_id']);
                }
    	    }
	    }
	}

	/**
	 * Update timetable by asyncservice
	 */
	public function async_update_timetable(){
        $config = untissync_config::read();
		$this->debug_log->enableAsyncLog(true);

		$msg = "START async_update_timetable";
		$this->debug_log->log($msg);
		
		$len = $this->importTimetable($msg);
		$msg = $msg.$len." timetables imported";
		$this->debug_log->log($msg);

        if($config['cleanup_cal_orphaned']){
            // cleanup orphaned cal events
            $this->cleanupOrphaned();
        }

		$msg = "FINISHED async_update_timetable";
		$this->debug_log->log($msg);
        $this->debug_log->enableAsyncLog(false);
	}

	/**
	 * Asnyc job updating substitutions, if last untis import occur after last async job
	 */
	public function async_update_substitutions(){
	    $this->debug_log->enableAsyncLog(true);	    	    
	    	    
	    $config = untissync_config::read();
	    $jobStartBefore =  isset($config['last_update_subs']) ? $config['last_update_subs'] : 0;
	    
	    $dt = new Api\DateTime($jobStartBefore, Api\DateTime::$user_timezone);
	    $ljr = $dt->format('r');
	    
		$lastImportTime = $this->requestLastImportTime($msg);
		
		$dt = new Api\DateTime($lastImportTime, Api\DateTime::$user_timezone);
		$tsf = $dt->format('r');//'Y-m-d H:i:s');

		if($lastImportTime > $jobStartBefore){
		    $this->debug_log->log("Start import substitutions from asnyc job: last import: ".$tsf."; last job run: " .$ljr);		    
		    $this->importSubstitutions();		    
		}else{
		    $this->debug_log->log("Disclaim starting import substitutions: last import: ".$tsf."; last job run: " .$ljr);
		}
        $this->debug_log->enableAsyncLog(false);
	}
	
	/**
	 * load nextmatch rows
	 * @param unknown $query_in
	 * @param unknown $rows
	 * @param unknown $readonlys
	 * @param boolean $id_only
	 */
	public function getSubstitutionList(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
	    $tgStart = array();
	    $tgEnd = array();
	    $this->so_timegrid->getTimegridSetStartEnd($tgStart, $tgEnd);
	    $this->so_substitution->getSubstitutionList($query_in,$rows,$readonlys,$id_only, $tgStart, $tgEnd);
	}
}
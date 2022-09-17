<?php

/**
 * EGroupware - UntisSync - Mapping ui object
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api;
use EGroupware\Api\Link;
use EGroupware\Api\Framework;
use EGroupware\Api\Acl;
use EGroupware\Api\Etemplate;

/**
 * General userinterface object for mapping teacher, classes and groups
 *
 * @package untissync
 */
class untissync_mapping_ui
{

	/**
	 * instance of the bo-class
	 * @var untissync_bo
	 */
	var $bo;

	var $public_functions = array(
		'te_mapping'	=> True,
	    'te_edit'		=> True,
	    'kl_mapping'	=> True,
	    'kl_edit'		=> True,
	    'ro_mapping'	=> True,
	    'ro_edit'		=> True,
	);

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		//$this->tmpl	= new Etemplate('untissync.mapping_te');
		$this->bo = new untissync_bo();
	}
	
	
	//######################
	// TEACHER
	/**
	 * Get actions / context menu for index
	 *
	 * Changes here, require to log out, as $content['nm'] get stored in session!
	 *
	 * @return array see nextmatch_widget::egw_actions()
	 */	
	public static function get_te_actions(array $content)
	{
	    $actions = array(
	        'edit' => array(
	            'caption' => 'Edit',
	            'default' => true,
	            'allowOnMultiple' => false,
	            'onExecute' => 'javaScript:app.untissync.onTeacherMappingEdit',
	        ),
	        'delete' => array(
	            'caption' => 'Delete',
	            'allowOnMultiple' => true,
	        ),
            'status'       => array(
                'caption'      => 'status',
                'disableClass' => 'noEdit',
                'children'     => array(
                    'enable' => array(
                        'caption'   => 'enable',
                        'allowOnMultiple' => true,
                        'icon'      => 'enable',
                        'onExecute' => 'javaScript:app.untissync.teacher_enable',
                    ),
                    'disable' => array(
                        'caption'   => 'disable',
                        'allowOnMultiple' => true,
                        'icon'      => 'disable',
                        'onExecute' => 'javaScript:app.untissync.teacher_disable',
                    ),
                ),
            ),
	    );
	    
	    return $actions;
	}
	
	
	
	/**
	 * apply an action
	 *
	 * @param string/int $action 'status_to',set status to timeshhets
	 * @param array $checked timesheet id's to use if !$use_all
	 * @param boolean $use_all if true use all timesheets of the current selection (in the session)
	 * @param int &$success number of succeded actions
	 * @param int &$failed number of failed actions (not enought permissions)
	 * @param string &$action_msg translated verb for the actions, to be used in a message like %1 timesheets 'deleted'
	 * @param string/array $session_name 'index' or 'email', or array with session-data depending if we are in the main list or the popup
	 * @return boolean true if all actions succeded, false otherwise
	 */
	private function te_action($action,$checked,$use_all,&$success,&$failed,&$action_msg,$session_name,&$msg)
	{
	    $success = $failed = 0;
	    
	    switch($action)
	    {
	        
	        case 'delete':
	            $action_msg = lang('deleted');
	            
	            $rows = Api\Cache::getSession('untissync', 'mapping_te_rows');
	            
	            foreach($checked as $n => &$id)
	            {
	                $ret =  $this->bo->deleteTeacher($rows[$id]);
	                
	                if ($ret)
	                {
	                    $success++;
	                }
	                else
	                {
	                    $msg = "ERROR, could not delete teacher \n";
	                    $failed++;
	                }
	            }
	            break;
	    }
	    return $failed == 0;
	}

	/**
	 * List untissync teacher mapping
	 *
	 * @param array $content
	 * @param string $msg
	 */
	public function te_mapping(array $content = null,$msg='')
	{
	    $etpl = new Etemplate('untissync.mapping_te');
		$sel_options = array();
		$preserv = array();
		
		if ($_GET['msg']) $msg = $_GET['msg'];
		
		if (is_array($content))
		{
            if(is_array($content['nm']['button'])) {
                $button = key($content['nm']['button']);
                unset($content['nm']['button']);
                if ($button) {
                    if ($button == 'update') {
                        $result = $this->bo->updateTeachers($msg);
                        if ($result) {
                            $msg = $msg . PHP_EOL . "Update succeeded!";
                        } else {
                            $msg = $msg . PHP_EOL . "Update failed!";
                        }
                    } elseif ($button == 'truncate') {
                        $result = $this->bo->truncateTeachers();
                        if ($result) {
                            $msg = "Deleted successfully!";
                        } else {
                            $msg = "Could not be deleted successfully!";
                        }
                    }
                }
            }
		    
		    // action
		    if ($content['nm']['action'])
		    {
		        if (!count($content['nm']['selected']) && !$content['nm']['select_all'])
		        {
		            $msg = lang('You need to select some entries first!');
		        }
		        else
		        {
		            if ($this->te_action($content['nm']['action'],$content['nm']['selected'],$content['nm']['select_all'],
		                $success,$failed,$action_msg,'untissync_te_mapping_nm',$msg))
		            {
		                $msg .= lang('%1 Kopplung %2',$success,$action_msg);
		            }
		            elseif(empty($msg))
		            {
		                $msg .= lang('%1 Kopplung(en) %2, %3 failed because of insufficent rights !!!',$success,$action_msg,$failed);
		            }
		            else
		            {
		                $msg .= lang('%1 Kopplung(en) %2, %3 failed',$success,$action_msg,$failed);
		            }
		        }
		    }
		}
		else {
			$content = array(
			    'msg' => $msg,
			);
		}

		$content['msg'] = $msg ? $msg : $_GET['msg'];

        $filter = Api\Cache::getSession('untissync', 'te_mapping_filter');
        $options = array('account_type' => 'groups');
        $groups = Api\Accounts::link_query('',$options);

        $groupsFilter = array();
        $groupsFilter[0] = "alle";
        foreach($groups as $key => $group){
            $groupsFilter[$key] = $group;
        }

		Api\Cache::unsetSession('untissync', 'mapping_te_search');

		// Teacher mapping		
	    $content['nm'] = array();
	    $content['msg'] = $msg;
	    
	    $content['nm']['get_rows']		= 'untissync.untissync_mapping_ui.get_te_rows';
	    $content['nm']['no_filter'] 	= false;
	    $content['nm']['filter_no_lang'] = true;
	    $content['nm']['no_cat']	= true;
	    $content['nm']['no_search']	= true;
	    $content['nm']['no_filter2']	= false;
	    $content['nm']['bottom_too']	= true;
	    $content['nm']['order']		= 'te_name, te_forename';
	    $content['nm']['sort']		= 'ASC';
	    $content['nm']['store_state']	= 'get_rows';
	    $content['nm']['row_id']	= 'nm_id';
	    $content['nm']['favorites'] = false;
	    $content['nm']['filter'] = $filter;
        $content['nm']['options-filter'] = $groupsFilter;
        $content['nm']['options-filter2'] = array('all', 'enabled', 'disabled');
	    $content['nm']['actions'] = self::get_te_actions($content);
	    $content['nm']['default_cols']  = '!legacy_actions';
	    $content['nm']['no_columnselection'] = false;
					
		$readonlys = array(
		    'button[update]'     => false,
		    'button[truncate]'     => false,
		);
		
		$preserv = $sel_options;

		$etpl->read('untissync.mapping_te');
		return $etpl->exec('untissync.untissync_mapping_ui.te_mapping',$content,$sel_options,$readonlys,$preserv);
	}
	
	/**
	 * loads teacher objects for nextmatch widget
	 * @param unknown $query_in
	 * @param unknown $rows
	 * @param unknown $readonlys
	 * @param boolean $id_only
	 * @return unknown
	 */
	public function get_te_rows(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
	    $total = 0;
	    if(isset($query_in['search'])){
	        Api\Cache::setSession('untissync', 'mapping_te_search', $query_in['search']);
	    }
	    else{
	        // edit records
	        $query_in['search'] = Api\Cache::getSession('untissync', 'mapping_te_search');
	    }
	    
	    $total = $this->bo->getTeacherMapping($query_in,$rows);
	    Api\Cache::setSession('untissync', 'mapping_te_rows', $rows);
	    return $total;
	}
	
	


    /**
     * enable teacher
     */
    public function ajax_teacher_enable($enable, $teachers){
        $msg = '';

        $result = 0;
        $rows = Api\Cache::getSession('untissync', 'mapping_te_rows');

        $so_teacher = new untissync_teacher_so();
        foreach($teachers as $tid){
            if($rows[$tid]['te_egw_uid'] > 0 && $rows[$tid]['te_active'] != $enable) {
                // change only if teacher is mapped and status will be changed
                $so_teacher->write($rows[$tid]['te_uid'], $rows[$tid]['te_name'], $rows[$tid]['te_forename'], $rows[$tid]['te_longname'], $rows[$tid]['te_egw_uid'], $enable);
                $result++;
            }
        }

        $data = array(
            'msg' => $msg." $result teachers " . ($enable ? "enabled!" : "disabled!"),
        );
        Api\Json\Response::get()->data($data);
    }

    /**
     * edit teacher mapping (laoding)
     */
    public function ajax_onTeacherMappingEdit($rowid){
        $msg = '';

        $result = 0;
        $rows = Api\Cache::getSession('untissync', 'mapping_te_rows');
        Api\Cache::setSession('untissync', 'mapping_te_id', $rows[$rowid]['te_id'],);

        $result = array(
            'msg' => $msg." $result teachers ",
            'te_forename' => $rows[$rowid]['te_forename'],
            'te_name' => $rows[$rowid]['te_name'],
            'te_longname' => $rows[$rowid]['te_longname'],
        );
        Api\Json\Response::get()->data($result);
    }
    public function ajax_onTeacherMappingCommit($te_egw_uid){
        $msg = '';
        $te_id = Api\Cache::getSession('untissync', 'mapping_te_id');
        $result = $this->bo->updateTeacherMapping($te_id, $te_egw_uid);
        $data = array();
        if($result){
            $data['msg'] = $msg." Successfully updated!";
        }
        else{
            $data['msg'] = $msg." Update failed!";
        }
        Api\Json\Response::get()->data($data);
    }

    /**
     * edit klasse mapping (laoding)
     */
    public function ajax_onKlasseMappingEdit($rowid){
        $msg = '';

        $result = 0;
        $rows = Api\Cache::getSession('untissync', 'mapping_kl_rows');
        Api\Cache::setSession('untissync', 'mapping_kl_id', $rows[$rowid]['id']);

        $result = array(
            'msg' => $msg." $result klassen ",
            'longname' => $rows[$rowid]['longname'],
            'name' => $rows[$rowid]['name'],
        );
        Api\Json\Response::get()->data($result);
    }
    public function ajax_onKlasseMappingCommit($egw_uid, $egw_group_id){
        $msg = '';
        $kl_id = Api\Cache::getSession('untissync', 'mapping_kl_id');
        $result = $this->bo->updateClassMapping($kl_id, $egw_uid, $egw_group_id);
        $data = array();
        if($result){
            $data['msg'] = $msg." Successfully updated!";
        }
        else{
            $data['msg'] = $msg." Update failed!";
        }
        Api\Json\Response::get()->data($data);
    }

    /**
     * edit klasse mapping (laoding)
     */
    public function ajax_onRoomMappingEdit($rowid){
        $msg = '';

        $result = 0;
        $rows = Api\Cache::getSession('untissync', 'mapping_ro_rows');
        Api\Cache::setSession('untissync', 'mapping_ro_id', $rows[$rowid]['id'],);

        $result = array(
            'msg' => $msg." $result rooms ",
            'longname' => $rows[$rowid]['longname'],
            'name' => $rows[$rowid]['name'],
        );

        // list rooms
        $result['rooms'] = array();
        $rooms = $this->bo->getAvailableRooms();
        foreach($rooms as $key => $value){
            $result['rooms'][$key] = $value['name'];
        }

        Api\Json\Response::get()->data($result);
    }
    public function ajax_onRoomMappingCommit($ro_egw_uid){
        $msg = '';
        $ro_id = Api\Cache::getSession('untissync', 'mapping_ro_id');
        $result = $this->bo->updateRoomMapping($ro_id, $ro_egw_uid);
        $data = array();
        if($result){
            $data['msg'] = $msg." Successfully updated!";
        }
        else{
            $data['msg'] = $msg." Update failed!";
        }
        Api\Json\Response::get()->data($data);
    }
	
	//######################
	// ROOMS	
	/**
	 * Get actions / context menu for index
	 *
	 * Changes here, require to log out, as $content['nm'] get stored in session!
	 *
	 * @return array see nextmatch_widget::egw_actions()
	 */
	public static function get_ro_actions(array $content)
	{
	    $actions = array(
            'edit' => array(
                'caption' => 'Edit',
                'default' => true,
                'allowOnMultiple' => false,
                'onExecute' => 'javaScript:app.untissync.onRoomMappingEdit',
            ),
	        'delete' => array(
	            'caption' => 'Delete',
	            'allowOnMultiple' => true,
	        ),
	    );
	    
	    return $actions;
	}
	
	
	
	/**
	 * apply an action
	 *
	 * @param string/int $action 'status_to'
	 * @param array $checked id's to use if !$use_all
	 * @param boolean $use_all if true use all rooms of the current selection (in the session)
	 * @param int &$success number of succeded actions
	 * @param int &$failed number of failed actions (not enought permissions)
	 * @param string &$action_msg translated verb for the actions, to be used in a message like %1 rooms 'deleted'
	 * @param string/array $session_name 'index' or 'email', or array with session-data depending if we are in the main list or the popup
	 * @return boolean true if all actions succeded, false otherwise
	 */
	private function ro_action($action,$checked,$use_all,&$success,&$failed,&$action_msg,$session_name,&$msg)
	{
	    $success = $failed = 0;
	    
	    switch($action)
	    {
	        
	        case 'delete':
	            $action_msg = lang('deleted');
	            
	            $rows = Api\Cache::getSession('untissync', 'mapping_ro_rows');
	            
	            foreach($checked as $n => &$id)
	            {
	                $ret =  $this->bo->deleteRoom($rows[$id]);
	                
	                if ($ret)
	                {
	                    $success++;
	                }
	                else
	                {
	                    $msg = $error . "\n";
	                    $failed++;
	                }
	            }
	            break;
	    }
	    return $failed == 0;
	}
	
	/**
	 * List untissync rooms mapping
	 *
	 * @param array $content
	 * @param string $msg
	 */
	public function ro_mapping(array $content = null,$msg='')
	{
	    $etpl = new Etemplate('untissync.mapping_ro');
	    $sel_options = array();
	    $preserv = array();
	    
	    if ($_GET['msg']) $msg = $_GET['msg'];
	    
	    if (is_array($content))
	    {
            if(is_array($content['nm']['button'])) {
                $button = key($content['nm']['button']);
                unset($content['nm']['button']);
                if ($button) {
                    if ($button == 'update') {
                        $result = $this->bo->updateRooms();
                        if ($result) {
                            $msg = "Update succeeded!";
                        } else {
                            $msg = "Update failed!";
                        }
                    } elseif ($button == 'truncate') {
                        $result = $this->bo->truncateRooms();
                        if ($result) {
                            $msg = "Deleted successfully!";
                        } else {
                            $msg = "Could not be deleted successfully!";
                        }
                    }
                }
            }
	        
	        // action
	        if ($content['nm']['action'])
	        {
	            if (!count($content['nm']['selected']) && !$content['nm']['select_all'])
	            {
	                $msg = lang('You need to select some entries first!');
	            }
	            else
	            {
	                if ($this->ro_action($content['nm']['action'],$content['nm']['selected'],$content['nm']['select_all'],
	                    $success,$failed,$action_msg,'untissync_ro_mapping_nm',$msg))
	                {
	                    $msg .= lang('%1 Kopplung %2',$success,$action_msg);
	                }
	                elseif(empty($msg))
	                {
	                    $msg .= lang('%1 Kopplung(en) %2, %3 failed because of insufficent rights !!!',$success,$action_msg,$failed);
	                }
	                else
	                {
	                    $msg .= lang('%1 Kopplung(en) %2, %3 failed',$success,$action_msg,$failed);
	                }
	            }
	        }
	    }
	    else {
	        $content = array(
	            'msg' => $msg,
	        );
	    }
	    
	    $content['msg'] = $msg ? $msg : $_GET['msg'];
	    
	    Api\Cache::unsetSession('untissync', 'mapping_ro_search');
	    
	    // Room mapping
	    $content['nm'] = array();
	    $content['msg'] = $msg;
	    
	    $content['nm']['get_rows']		= 'untissync.untissync_mapping_ui.get_ro_rows';
	    $content['nm']['no_filter'] 	= true;
	    $content['nm']['filter_no_lang'] = true;
	    $content['nm']['no_cat']	= true;
	    $content['nm']['no_search']	= true;
	    $content['nm']['no_filter2']	= true;
	    $content['nm']['bottom_too']	= true;
	    $content['nm']['order']		= 'nm_id';
	    $content['nm']['sort']		= 'ASC';
	    $content['nm']['store_state']	= 'get_rows';
	    $content['nm']['row_id']	= 'nm_id';
	    $content['nm']['favorites'] = false;
	    $content['nm']['filter'] = $filter;
	    $content['nm']['actions'] = self::get_ro_actions($content);
	    $content['nm']['default_cols']  = '!legacy_actions';
	    $content['nm']['no_columnselection'] = false;
	    
	    $readonlys = array(
	        'button[update]'     => false,
	        'button[truncate]'     => false,
	    );
	    
	    $preserv = $sel_options;
	    
	    $etpl->read('untissync.mapping_ro');
	    return $etpl->exec('untissync.untissync_mapping_ui.ro_mapping',$content,$sel_options,$readonlys,$preserv);
	}
	
	/**
	 * loads teacher objects for nextmatch widget
	 * @param unknown $query_in
	 * @param unknown $rows
	 * @param unknown $readonlys
	 * @param boolean $id_only
	 * @return unknown
	 */
	public function get_ro_rows(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
	    $total = 0;
	    if(isset($query_in['search'])){
	        Api\Cache::setSession('untissync', 'mapping_ro_search', $query_in['search']);
	    }
	    else{
	        // edit records
	        $query_in['search'] = Api\Cache::getSession('untissync', 'mapping_ro_search');
	    }
	    
	    $total = $this->bo->getRoomMapping($query_in,$rows);
	    Api\Cache::setSession('untissync', 'mapping_ro_rows', $rows);
	    return $total;
	}
	

	
	//######################
	// KLASSEN
	/**
	 * Get actions / context menu for index
	 *
	 * Changes here, require to log out, as $content['nm'] get stored in session!
	 *
	 * @return array see nextmatch_widget::egw_actions()
	 */
	public static function get_kl_actions(array $content)
	{
	    $actions = array(
            'edit' => array(
                'caption' => 'Edit',
                'default' => true,
                'allowOnMultiple' => false,
                'onExecute' => 'javaScript:app.untissync.onKlasseMappingEdit',
            ),
	        'delete' => array(
	            'caption' => 'Delete',
	            'allowOnMultiple' => true,
	        ),
	    );
	    
	    return $actions;
	}
	
	
	
	/**
	 * apply an action
	 *
	 * @param string/int $action 'status_to',set status to timeshhets
	 * @param array $checked timesheet id's to use if !$use_all
	 * @param boolean $use_all if true use all timesheets of the current selection (in the session)
	 * @param int &$success number of succeded actions
	 * @param int &$failed number of failed actions (not enought permissions)
	 * @param string &$action_msg translated verb for the actions, to be used in a message like %1 timesheets 'deleted'
	 * @param string/array $session_name 'index' or 'email', or array with session-data depending if we are in the main list or the popup
	 * @return boolean true if all actions succeded, false otherwise
	 */
	private function kl_action($action,$checked,$use_all,&$success,&$failed,&$action_msg,$session_name,&$msg)
	{
	    $success = $failed = 0;
	    
	    switch($action)
	    {
	        
	        case 'delete':
	            $action_msg = lang('deleted');
	            
	            $rows = Api\Cache::getSession('untissync', 'mapping_kl_rows');
	            
	            foreach($checked as $n => &$id)
	            {
	                $ret =  $this->bo->deleteClass($rows[$id]);
	                
	                if ($ret)
	                {
	                    $success++;
	                }
	                else
	                {
	                    $msg = $error . "\n";
	                    $failed++;
	                }
	            }
	            break;
	    }
	    return $failed == 0;
	}
	
	/**
	 * List untissync klasse mapping
	 *
	 * @param array $content
	 * @param string $msg
	 */
	public function kl_mapping(array $content = null,$msg='')
	{
	    $etpl = new Etemplate('untissync.mapping_kl');
	    $sel_options = array();
	    $preserv = array();
	    
	    if ($_GET['msg']) $msg = $_GET['msg'];
	    if ($_GET['nm_id']) $nm_id = $_GET['nm_id'];
	    
	    if (is_array($content))
	    {
            if(is_array($content['nm']['button'])) {
                $button = key($content['nm']['button']);
                unset($content['nm']['button']);
                if ($button) {
                    if ($button == 'update') {
                        $result = $this->bo->updateClasses();
                        if ($result) {
                            $msg = "Update succeeded!";
                        } else {
                            $msg = "Update failed!";
                        }
                    } elseif ($button == 'truncate') {
                        $result = $this->bo->truncateClasses();
                        if ($result) {
                            $msg = "Deleted successfully!";
                        } else {
                            $msg = "Could not be deleted successfully!";
                        }
                    }
                }
            }
	        
	        // action
	        if ($content['nm']['action'])
	        {
	            if (!count($content['nm']['selected']) && !$content['nm']['select_all'])
	            {
	                $msg = lang('You need to select some entries first!');
	            }
	            else
	            {
	                if ($this->kl_action($content['nm']['action'],$content['nm']['selected'],$content['nm']['select_all'],
	                    $success,$failed,$action_msg,'untissync_kl_mapping_nm',$msg))
	                {
	                    $msg .= lang('%1 Kopplung %2',$success,$action_msg);
	                }
	                elseif(empty($msg))
	                {
	                    $msg .= lang('%1 Kopplung(en) %2, %3 failed because of insufficent rights !!!',$success,$action_msg,$failed);
	                }
	                else
	                {
	                    $msg .= lang('%1 Kopplung(en) %2, %3 failed',$success,$action_msg,$failed);
	                }
	            }
	        }
	    }
	    else {
	        $content = array(
	            'msg' => $msg,
	        );
	    }
	    
	    $content['msg'] = $msg ? $msg : $_GET['msg'];
	    
	    Api\Cache::unsetSession('untissync', 'mapping_kl_search');
	    
	    // Room mapping
	    $content['nm'] = array();
	    $content['msg'] = $msg;
	    
	    $content['nm']['get_rows']		= 'untissync.untissync_mapping_ui.get_kl_rows';
	    $content['nm']['no_filter'] 	= true;
	    $content['nm']['filter_no_lang'] = true;
	    $content['nm']['no_cat']	= true;
	    $content['nm']['no_search']	= true;
	    $content['nm']['no_filter2']	= true;
	    $content['nm']['bottom_too']	= true;
	    $content['nm']['order']		= 'nm_id';
	    $content['nm']['sort']		= 'ASC';
	    $content['nm']['store_state']	= 'get_rows';
	    $content['nm']['row_id']	= 'nm_id';
	    $content['nm']['favorites'] = false;
	    $content['nm']['filter'] = $filter;
	    $content['nm']['actions'] = self::get_kl_actions($content);
	    $content['nm']['default_cols']  = '!legacy_actions';
	    $content['nm']['no_columnselection'] = false;
	    
	    $readonlys = array(
	        'button[update]'     => false,
	        'button[truncate]'     => false,
	    );
	    
	    $preserv = $sel_options;
	    
	    $etpl->read('untissync.mapping_kl');
	    return $etpl->exec('untissync.untissync_mapping_ui.kl_mapping',$content,$sel_options,$readonlys,$preserv);
	}
	
	/**
	 * loads teacher objects for nextmatch widget
	 * @param unknown $query_in
	 * @param unknown $rows
	 * @param unknown $readonlys
	 * @param boolean $id_only
	 * @return unknown
	 */
	public function get_kl_rows(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
	    $total = 0;
	    if(isset($query_in['search'])){
	        Api\Cache::setSession('untissync', 'mapping_kl_search', $query_in['search']);
	    }
	    else{
	        // edit records
	        $query_in['search'] = Api\Cache::getSession('untissync', 'mapping_kl_search');
	    }
	    
	    $total = $this->bo->getClassMapping($query_in,$rows);
	    Api\Cache::setSession('untissync', 'mapping_kl_rows', $rows);
	    return $total;
	}
	
	
	
}


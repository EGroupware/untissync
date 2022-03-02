/**
 * EGroupware - Untissync - Javascript UI
 *
 * @link http://www.egroupware.org
 * @package untissync
 * @author Axel Wild
 * @copyright (c) 2020 by Axel Wild
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

import 'jquery';
import 'jqueryui';
import '../jsapi/egw_global';
import '../etemplate/et2_types';

import {EgwApp} from '../../api/js/jsapi/egw_app';
import {et2_nextmatch} from "../../api/js/etemplate/et2_extension_nextmatch";
import {etemplate2} from "../../api/js/etemplate/etemplate2";

/**
 * UI for untissync
 *
 * @augments AppJS
 */
class UntissyncApp extends EgwApp
{

	constructor()
	{
		super('untissync');
	}

	/**
	 * Destructor
	 */
	destroy(_app)
	{
		delete this.et2;
		super.destroy(_app);
	}

	/**
	 * This function is called when the etemplate2 object is loaded
	 * and ready.  If you must store a reference to the et2 object,
	 * make sure to clean it up in destroy().
	 *
	 * @param et2 etemplate2 Newly ready object
	 * @param string name
	 */
	et2_ready(et2, name: string)
	{
		// call parent
		super.et2_ready(et2, name);		
	}

	/**
	 * Import substitutions via AJAX
	 */
	import_substitutions(){
		var et2 = this.et2;
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_importSubstitutions',[], function(_data){
			egw.loading_prompt('untissync', false);
			document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
			document.getElementById('untissync-index_last_update_subs').innerText = _data.last_update_subs;
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * Import timetables via AJAX
	 */
	import_timetable(){
		var et2 = this.et2;
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_importTimetable',[], function(_data){
			egw.loading_prompt('untissync', false);
			document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
			document.getElementById('untissync-index_last_update_timetable').innerText = _data.last_update_timetable;
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * Import timetables via AJAX
	 */
	test_connection(){
		var et2 = this.et2;
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_testConnection',[], function(_data){
			egw.loading_prompt('untissync', false);
			if(_data.status == 'success'){
				document.getElementById('untissync-index_test_connection_success').style.display = 'inline';
				document.getElementById('untissync-index_test_connection_failed').style.display = 'none';
				egw(window).message(_data.msg, 'success');
			}
			else{
				document.getElementById('untissync-index_test_connection_success').style.display = 'none';
				document.getElementById('untissync-index_test_connection_failed').style.display = 'inline';
				egw(window).message(_data.msg, 'error');
			}

		}).sendRequest(true);
	}

	/**
	 * Cleanup orphaned calendar items
	 */
	cleanup_orphaned(){
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_cleanupOrphaned',[], function(_data){
			egw.loading_prompt('untissync', false);
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * Cleanup timetables
	 */
	delete_timetables(){
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_deleteTimetables',[], function(_data){
			egw.loading_prompt('untissync', false);
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * Cleanup substitutions
	 */
	delete_substitutions(){
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_deleteSubstitutions',[], function(_data){
			egw.loading_prompt('untissync', false);
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

}

app.classes.untissync = UntissyncApp;

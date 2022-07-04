/**
 * EGroupware - Untissync - Javascript UI
 *
 * @link http://www.egroupware.org
 * @package untissync
 * @author Axel Wild
 * @copyright (c) 2020 by Axel Wild
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

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
		this.egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		this.egw.json('untissync.untissync_ui.ajax_importSubstitutions',[], function(_data){
			egw.loading_prompt('untissync', false);
			document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
			document.getElementById('untissync-index_last_update_subs').innerText = _data.last_update_subs;
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * Import timetables via AJAX
	 */
	/*import_timetable(){
		egw.loading_prompt('untissync',true,egw.lang('please wait...'));
		egw.json('untissync.untissync_ui.ajax_importTimetable',[], function(_data){
			egw.loading_prompt('untissync', false);
			document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
			document.getElementById('untissync-index_last_update_timetable').innerText = _data.last_update_timetable;
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}*/

	/**
	 * Import timetables via AJAX and long task
	 */
	import_timetableLT(){
		var activeCount = parseInt(document.getElementById("untissync-index_teacher_active_count").innerText);

		var menuaction = 'untissync.untissync_ui.ajax_importTimetableLT';
		var indices = [];
		var msg1 = egw.lang('import %1 timetables', ""+activeCount);

		for (var i=0;i< activeCount;i++)
		{
			indices[i] = i;
		}

		var callbackDialog = function (btn){
			if (btn === et2_dialog.YES_BUTTON)
			{
				// long task dialog for de/activation accounts
				et2_dialog.long_task(function(_val, _resp){
					if (_val && _resp.type !== 'error')
					{
						console.log(_val,_resp);
					}
					else
					{

					}
				}, msg1, 'import timetables', menuaction, indices, 'untissync');
			}
		};
		// confirmation dialog
		et2_dialog.show_dialog(callbackDialog, egw.lang('Are you sure you want to import %1 timetables?', activeCount), egw.lang('Import timetables?'), {},
			et2_dialog.BUTTON_YES_NO, et2_dialog.WARNING_MESSAGE, undefined, egw);
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

	/**
	 * enable teachers
	 */
	teacher_enable(_action, _senders){
		let enable = true;
		let teachers = [];
		egw.loading_prompt('untissync',true,egw.lang('please wait 1...'));
		for(let i = 0; i < _senders.length; i++)
		{
			teachers.push(_senders[i].id.split("::").pop());
		}
		egw.json('untissync.untissync_mapping_ui.ajax_teacher_enable',[enable, teachers], function(_data){
			egw.loading_prompt('untissync', false);
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * disable teachers
	 */
	teacher_disable(_action, _senders){
		var enable = false;
		let teachers = [];
		egw.loading_prompt('untissync',true,egw.lang('please wait 1...'));
		for(let i = 0; i < _senders.length; i++)
		{
			teachers.push(_senders[i].id.split("::").pop());
		}
		egw.json('untissync.untissync_mapping_ui.ajax_teacher_enable',[enable, teachers], function(_data){
			egw.loading_prompt('untissync', false);
			egw(window).refresh(_data.msg, 'untissync', null, 'update');
		}).sendRequest(true);
	}

	/**
	 * edit teachers mapping
	 */
	onTeacherMappingEdit(_action, _senders){
		var row_id = _senders[0]._index;
		var func = 'untissync.untissync_mapping_ui.ajax_onTeacherMappingEdit';

		this.egw.json(func, [row_id], function (result) {
			var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
			modal.style.display = "block";

			for (var key in result){
				var widget_id = 'untissync-mapping_te_' + key;

				var widget = <HTMLInputElement>document.getElementById(widget_id);
				if (widget) {
					widget.innerText = result[key];
				}
			}
		}).sendRequest(true);
	}

	/**
	 * commit teacher mapping
	 * @param _action
	 * @param _senders
	 */
	onTeacherMappingCommit(_action, _senders){
		var te_egw_uid = document.getElementById("untissync-mapping_te_te_egw_uid").value;
		var func = 'untissync.untissync_mapping_ui.ajax_onTeacherMappingCommit';

		this.egw.json(func, [te_egw_uid], function (result) {
			// todo msg
			egw(window).refresh(result.msg, 'untissync', null, 'update');
		}).sendRequest(true);

		var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
		modal.style.display = "none";
	}
	onTeacherMappingCancel(_action, _senders){
		var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
		modal.style.display = "none";
	}
}

app.classes.untissync = UntissyncApp;

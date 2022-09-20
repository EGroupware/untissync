"use strict";
/**
 * EGroupware - Untissync - Javascript UI
 *
 * @link http://www.egroupware.org
 * @package untissync
 * @author Axel Wild
 * @copyright (c) 2020 by Axel Wild
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
var egw_app_1 = require("../../api/js/jsapi/egw_app");
/**
 * UI for untissync
 *
 * @augments AppJS
 */
var UntissyncApp = /** @class */ (function (_super) {
    __extends(UntissyncApp, _super);
    function UntissyncApp() {
        return _super.call(this, 'untissync') || this;
    }
    /**
     * Destructor
     */
    UntissyncApp.prototype.destroy = function (_app) {
        delete this.et2;
        _super.prototype.destroy.call(this, _app);
    };
    /**
     * This function is called when the etemplate2 object is loaded
     * and ready.  If you must store a reference to the et2 object,
     * make sure to clean it up in destroy().
     *
     * @param et2 etemplate2 Newly ready object
     * @param string name
     */
    UntissyncApp.prototype.et2_ready = function (et2, name) {
        // call parent
        _super.prototype.et2_ready.call(this, et2, name);
    };
    /**
     * Import substitutions via AJAX
     */
    UntissyncApp.prototype.import_substitutions = function () {
        var et2 = this.et2;
        this.egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        this.egw.json('untissync.untissync_ui.ajax_importSubstitutions', [], function (_data) {
            egw.loading_prompt('untissync', false);
            document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
            document.getElementById('untissync-index_last_update_subs').innerText = _data.last_update_subs;
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
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
    UntissyncApp.prototype.import_timetableLT = function () {
        var activeCount = parseInt(document.getElementById("untissync-index_teacher_active_count").innerText);
        var menuaction = 'untissync.untissync_ui.ajax_importTimetableLT';
        var indices = [];
        var msg1 = egw.lang('import %1 timetables', "" + activeCount);
        for (var i = 0; i < activeCount; i++) {
            indices[i] = i;
        }
        var callbackDialog = function (btn) {
            if (btn === et2_dialog.YES_BUTTON) {
                // long task dialog for de/activation accounts
                et2_dialog.long_task(function (_val, _resp) {
                    if (_val && _resp.type !== 'error') {
                        console.log(_val, _resp);
                    }
                    else {
                    }
                }, msg1, 'import timetables', menuaction, indices, 'untissync');
            }
        };
        // confirmation dialog
        et2_dialog.show_dialog(callbackDialog, egw.lang('Are you sure you want to import %1 timetables?', activeCount), egw.lang('Import timetables?'), {}, et2_dialog.BUTTON_YES_NO, et2_dialog.WARNING_MESSAGE, undefined, egw);
    };
    /**
     * Import timetables via AJAX
     */
    UntissyncApp.prototype.test_connection = function () {
        var et2 = this.et2;
        egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        egw.json('untissync.untissync_ui.ajax_testConnection', [], function (_data) {
            egw.loading_prompt('untissync', false);
            if (_data.status == 'success') {
                document.getElementById('untissync-index_test_connection_success').style.display = 'inline';
                document.getElementById('untissync-index_test_connection_failed').style.display = 'none';
                egw(window).message(_data.msg, 'success');
            }
            else {
                document.getElementById('untissync-index_test_connection_success').style.display = 'none';
                document.getElementById('untissync-index_test_connection_failed').style.display = 'inline';
                egw(window).message(_data.msg, 'error');
            }
        }).sendRequest(true);
    };
    /**
     * Cleanup orphaned calendar items
     */
    UntissyncApp.prototype.cleanup_orphaned = function () {
        egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        egw.json('untissync.untissync_ui.ajax_cleanupOrphaned', [], function (_data) {
            egw.loading_prompt('untissync', false);
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
    UntissyncApp.prototype.delete_timetablesLT = function () {
        var activeCount = parseInt(document.getElementById("untissync-index_teacher_all_count").innerText);
        var menuaction = 'untissync.untissync_ui.ajax_deleteTimetablesLT';
        var indices = [];
        var msg1 = egw.lang('clear %1 timetables', "" + activeCount);
        for (var i = 0; i < activeCount; i++) {
            indices[i] = i;
        }
        var callbackDialog = function (btn) {
            if (btn === et2_dialog.YES_BUTTON) {
                // long task dialog for de/activation accounts
                et2_dialog.long_task(function (_val, _resp) {
                    if (_val && _resp.type !== 'error') {
                        console.log(_val, _resp);
                    }
                    else {
                    }
                }, msg1, 'clear timetables', menuaction, indices, 'untissync');
            }
        };
        // confirmation dialog
        et2_dialog.show_dialog(callbackDialog, egw.lang('Are you sure you want to clear %1 timetables?', activeCount), egw.lang('Clear timetables?'), {}, et2_dialog.BUTTON_YES_NO, et2_dialog.WARNING_MESSAGE, undefined, egw);
    };
    /**
     * Cleanup substitutions
     */
    UntissyncApp.prototype.delete_substitutions = function () {
        egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        egw.json('untissync.untissync_ui.ajax_deleteSubstitutions', [], function (_data) {
            egw.loading_prompt('untissync', false);
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
    /**
     * enable teachers
     */
    UntissyncApp.prototype.teacher_enable = function (_action, _senders) {
        var enable = true;
        var teachers = [];
        egw.loading_prompt('untissync', true, egw.lang('please wait 1...'));
        for (var i = 0; i < _senders.length; i++) {
            teachers.push(_senders[i].id.split("::").pop());
        }
        egw.json('untissync.untissync_mapping_ui.ajax_teacher_enable', [enable, teachers], function (_data) {
            egw.loading_prompt('untissync', false);
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
    /**
     * disable teachers
     */
    UntissyncApp.prototype.teacher_disable = function (_action, _senders) {
        var enable = false;
        var teachers = [];
        egw.loading_prompt('untissync', true, egw.lang('please wait 1...'));
        for (var i = 0; i < _senders.length; i++) {
            teachers.push(_senders[i].id.split("::").pop());
        }
        egw.json('untissync.untissync_mapping_ui.ajax_teacher_enable', [enable, teachers], function (_data) {
            egw.loading_prompt('untissync', false);
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
    /**
     * edit teachers mapping
     */
    UntissyncApp.prototype.onTeacherMappingEdit = function (_action, _senders) {
        var row_id = _senders[0]._index;
        var func = 'untissync.untissync_mapping_ui.ajax_onTeacherMappingEdit';
        this.egw.json(func, [row_id], function (result) {
            var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
            modal.style.display = "block";
            for (var key in result) {
                var widget_id = 'untissync-mapping_te_' + key;
                var widget = document.getElementById(widget_id);
                if (widget) {
                    widget.innerText = result[key];
                }
            }
        }).sendRequest(true);
    };
    /**
     * commit teacher mapping
     * @param _action
     * @param _senders
     */
    UntissyncApp.prototype.onTeacherMappingCommit = function (_action, _senders) {
        var te_egw_uid = document.getElementById("untissync-mapping_te_te_egw_uid").value;
        var func = 'untissync.untissync_mapping_ui.ajax_onTeacherMappingCommit';
        this.egw.json(func, [te_egw_uid], function (result) {
            // todo msg
            egw(window).refresh(result.msg, 'untissync', null, 'update');
        }).sendRequest(true);
        var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
        modal.style.display = "none";
    };
    UntissyncApp.prototype.onTeacherMappingCancel = function (_action, _senders) {
        var modal = document.getElementById("untissync-mapping_te_showtemapmodal");
        modal.style.display = "none";
    };
    /**
     * edit klasse mapping
     */
    UntissyncApp.prototype.onKlasseMappingEdit = function (_action, _senders) {
        var row_id = _senders[0]._index;
        var func = 'untissync.untissync_mapping_ui.ajax_onKlasseMappingEdit';
        this.egw.json(func, [row_id], function (result) {
            var modal = document.getElementById("untissync-mapping_kl_showklmapmodal");
            modal.style.display = "block";
            for (var key in result) {
                var widget_id = 'untissync-mapping_kl_' + key;
                var widget = document.getElementById(widget_id);
                if (widget) {
                    widget.innerText = result[key];
                }
            }
        }).sendRequest(true);
    };
    /**
     * commit klasse mapping
     * @param _action
     * @param _senders
     */
    UntissyncApp.prototype.onKlasseMappingCommit = function (_action, _senders) {
        var egw_uid = document.getElementById("untissync-mapping_kl_egw_uid").value;
        var egw_group_id = document.getElementById("untissync-mapping_kl_egw_group_id").value;
        var func = 'untissync.untissync_mapping_ui.ajax_onKlasseMappingCommit';
        this.egw.json(func, [egw_uid, egw_group_id], function (result) {
            // todo msg
            egw(window).refresh(result.msg, 'untissync', null, 'update');
        }).sendRequest(true);
        var modal = document.getElementById("untissync-mapping_kl_showklmapmodal");
        modal.style.display = "none";
    };
    UntissyncApp.prototype.onKlasseMappingCancel = function (_action, _senders) {
        var modal = document.getElementById("untissync-mapping_kl_showklmapmodal");
        modal.style.display = "none";
    };
    /**
     * edit klasse mapping
     */
    UntissyncApp.prototype.onRoomMappingEdit = function (_action, _senders) {
        var row_id = _senders[0]._index;
        var func = 'untissync.untissync_mapping_ui.ajax_onRoomMappingEdit';
        this.egw.json(func, [row_id], function (result) {
            var modal = document.getElementById("untissync-mapping_ro_showromapmodal");
            modal.style.display = "block";
            for (var key in result) {
                if (key != "rooms") {
                    var widget_id = 'untissync-mapping_ro_' + key;
                    var widget = document.getElementById(widget_id);
                    if (widget) {
                        widget.innerText = result[key];
                    }
                }
                else {
                    var widget = document.getElementById('untissync-mapping_ro_' + key);
                    if (widget) {
                        var length = widget.options.length;
                        for (var i = length - 1; i >= 0; i--) {
                            widget.options[i] = null;
                        }
                        for (var res in result['rooms']) {
                            var opt = document.createElement("option");
                            opt.value = res;
                            opt.text = result['rooms'][res];
                            widget.options.add(opt);
                        }
                    }
                    widget.style.display = "block";
                }
            }
        }).sendRequest(true);
    };
    /**
     * commit klasse mapping
     * @param _action
     * @param _senders
     */
    UntissyncApp.prototype.onRoomMappingCommit = function (_action, _senders) {
        var ro_egw_uid = document.getElementById("untissync-mapping_ro_rooms").value;
        var func = 'untissync.untissync_mapping_ui.ajax_onRoomMappingCommit';
        this.egw.json(func, [ro_egw_uid], function (result) {
            // todo msg
            egw(window).refresh(result.msg, 'untissync', null, 'update');
        }).sendRequest(true);
        var modal = document.getElementById("untissync-mapping_ro_showromapmodal");
        modal.style.display = "none";
    };
    UntissyncApp.prototype.onRoomMappingCancel = function (_action, _senders) {
        var modal = document.getElementById("untissync-mapping_ro_showromapmodal");
        modal.style.display = "none";
    };
    return UntissyncApp;
}(egw_app_1.EgwApp));
app.classes.untissync = UntissyncApp;
//# sourceMappingURL=app.js.map
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
require("jquery");
require("jqueryui");
require("../jsapi/egw_global");
require("../etemplate/et2_types");
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
        egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        egw.json('untissync.untissync_ui.ajax_importSubstitutions', [], function (_data) {
            egw.loading_prompt('untissync', false);
            document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
            document.getElementById('untissync-index_last_update_subs').innerText = _data.last_update_subs;
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
    };
    /**
     * Import timetables via AJAX
     */
    UntissyncApp.prototype.import_timetable = function () {
        var et2 = this.et2;
        egw.loading_prompt('untissync', true, egw.lang('please wait...'));
        egw.json('untissync.untissync_ui.ajax_importTimetable', [], function (_data) {
            egw.loading_prompt('untissync', false);
            document.getElementById('untissync-index_last_webuntis_import').innerText = _data.last_webuntis_import;
            document.getElementById('untissync-index_last_update_timetable').innerText = _data.last_update_timetable;
            egw(window).refresh(_data.msg, 'untissync', null, 'update');
        }).sendRequest(true);
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
    return UntissyncApp;
}(egw_app_1.EgwApp));
app.classes.untissync = UntissyncApp;
//# sourceMappingURL=app.js.map
/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Shopware UI - Media Manager Thumbnail Controller
 *
 * The thumbnail controller handles the thumbnail main window,
 * its elements and the batch calls for the thumbnail generation.
 *
 * @category    Shopware
 * @package     MediaManager
 * @copyright   Copyright (c) shopware AG (http://www.shopware.de)
 */

//{namespace name="backend/swag_import_export/view/main"}
//{block name="backend/swag_import_export/controller/import"}
Ext.define('Shopware.apps.SwagImportExport.controller.Import', {
    extend: 'Ext.app.Controller',
    snippets: {
        window: '{s name=swag_import_export/import/window_title}Import window{/s}',
        finished: '{s name=swag_import_export/import/finished}Importing finished successfully {/s}',
        process: '{s name=swag_import_export/import/process}Importing... {/s}',
        start: '{s name=swag_import_export/import/start}Start importing{/s}',
        close: '{s name=swag_import_export/import/close}Close{/s}'
    },
    /**
     * This method creates listener for events fired from the import 
     */
    init: function() {
        var me = this;

        me.control({
            // Import button
            'swag-import-export-manager-import button[action=swag-import-export-manager-import-button]': {
                click: me.onImport
            },
            'swag-import-export-manager-window-import': {
                startProcess: me.onStartProcess,
                cancelProcess: me.onCancelProcess
            }
        });

        me.callParent(arguments);
    },
    /**
     * 
     * @param object btn
     * @param object Ext.button.Button btn
     */
    onImport: function(btn) {
		Shopware.Notification.createGrowlMessage(
				'{s name=swag_import_export/import/error_title}Swag import export{/s}',
				'Import is currently disabled'
				);
//            return false;
//        var me = this,
//                form = btn.up('form'),
//                values = form.getValues();
//        
//        if (Ext.isEmpty(values.profile) || Ext.isEmpty(values.importFile))
//        {
//            Shopware.Notification.createGrowlMessage(
//                    '{s name=swag_import_export/import/error_title}Swag import export{/s}',
//                    '{s name=swag_import_export/import/error_msg}Please fill import configuration fields{/s}'
//                    );
//            return false;
//        }
//
//        me.parameters = values;
//
//        me.onCreateImportWindow();
    },
    /**
     * Creates batch configuration
     */
    onCreateImportWindow: function() {
        var me = this;

        me.getBatchConfig = me.getConfig();

    },
    /**
     * Triggers if the start exporting button was pressed
     * in the export window.
     * 
     * @param object Enlight.app.SubWindow win
     * @param object Ext.button.Button btn
     */
    onStartProcess: function(win, btn) {
        var me = this;

        me.cancelOperation = false;

        me.runRequest(win);

        btn.hide();
        win.cancelButton.show();
//        win.closeButton.disable();
    },
    /**
     * Returns the needed configuration for the next batch call
     */
    getConfig: function() {
        var me = this;

        me.batchConfig = {
            requestUrl: '{url controller="SwagImportExport" action="import"}',
            action: 'close-window-import',
            params: {
                profileId: me.parameters.profile,
                importFile: me.parameters.importFile
            },
            snippets: me.snippets
        };

        Ext.Ajax.request({
            url: '{url controller="SwagImportExport" action="prepareImport"}',
            method: 'POST',
            params: me.batchConfig.params,
            success: function(response) {
                var result = Ext.decode(response.responseText);
                me.batchConfig.position = result.position;
                me.batchConfig.totalCount = result.count;
                me.batchConfig.snippet = me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount;

                me.window = me.getView('Shopware.apps.SwagImportExport.view.manager.window.Import').create({
                    batchConfig: me.batchConfig
                }).show();

            },
            failure: function(response) {
                Shopware.Msg.createStickyGrowlMessage({
                    title: 'An error occured',
                    text: "The server could not handle the request."
                });
            }
        });
    },
    /**
     * This function sends a request to export data
     *
     * @param object Enlight.app.SubWindow win
     */
    runRequest: function(win) {
        var me = this,
                config = me.batchConfig,
                params = config.params;

        // if cancel button was pressed
        if (me.cancelOperation) {
            win.closeButton.enable();
            return;
        }

        Ext.Ajax.request({
            url: config.requestUrl,
            method: 'POST',
            params: params,
            timeout: 4000000,
            success: function(response) {
                var result = Ext.decode(response.responseText);

                me.batchConfig.params = result.data;
                me.batchConfig.position = result.data.position;

                win.importProgress.updateProgress(
                        me.batchConfig.position / me.batchConfig.totalCount,
                        me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount,
                        true
                        );

                if (me.batchConfig.position === me.batchConfig.totalCount) {
                    me.onProcessFinish(win);
                } else {
                    me.runRequest(win);
                }

            },
            failure: function(response) {
                Shopware.Msg.createStickyGrowlMessage({
                    title: 'An error occured',
                    text: "The server could not handle the request."
                });

                me.onProcessFinish(win);
            }
        });
    },
    /**
     * Sets cancelOperation to true which will be checked in the
     * next batch call and will stop.
     *
     * @param btn
     */
    onCancelProcess: function(btn) {
        var me = this;

        btn.disable();

        me.cancelOperation = true;
    },
    /**
     * Will be called when export finish
     *
     * @param object Enlight.app.SubWindow win
     */
    onProcessFinish: function(win) {
        var me = this;
        
        win.closeButton.enable();
        win.cancelButton.disable();
        win.importProgress.updateText(me.snippets.finished + me.batchConfig.position + ' / ' + me.batchConfig.totalCount);
    }
    
});
//{/block}
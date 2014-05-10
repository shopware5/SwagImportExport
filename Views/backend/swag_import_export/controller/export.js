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
//{block name="backend/swag_import_export/controller/export"}
Ext.define('Shopware.apps.SwagImportExport.controller.Export', {
    extend: 'Ext.app.Controller',
    snippets: {
    },
    /**
     * This method creates listener for events fired from the export 
     */
    init: function() {
        var me = this;

        me.control({
            // Export button
            'swag-import-export-manager-export button[action=swag-import-export-manager-export-button]': {
                click: me.onExport
            },
            'swag-import-export-manager-window-export': {
                startProcess: me.onStartProcess,
                cancelProcess: me.onCancelProcess
            }
        });

        me.callParent(arguments);
    },
    onExport: function(btn) {
        var me = this,
                form = btn.up('form'),
                values = form.getValues();

        //todo: make validation

        me.parameters = values;

        me.onCreateExportWindow();
    },
    onCreateExportWindow: function() {
        var me = this;

        me.getBatchConfig = me.getConfig();

    },
    onStartProcess: function(win, btn) {
        var me = this;
        
        me.cancelOperation = false;

        me.runRequest(win);
//
//        btn.hide();
//        win.cancelButton.show();
//        win.closeButton.disable();
    },
    /**
     * Returns the needed configuration for the next batch call
     *
     * @param win
     * @returns Object
     */
    getConfig: function() {
        var me = this;
        
        me.batchConfig = { 
            requestUrl: '{url controller="SwagImportExport" action="export"}',
            params: {
                profileId: me.parameters.profile,
                format: me.parameters.format
            }
        };
        
        Ext.Ajax.request({
            url: '{url controller="SwagImportExport" action="prepare"}',
            method: 'POST',
            params: me.batchConfig.params,
            success: function(response) {
                var result = Ext.decode(response.responseText);
                me.batchConfig.position = result.position;
                me.batchConfig.totalCount = result.count;
                me.batchConfig.snippet = 'Exporting... ' + me.batchConfig.position + ' / ' + me.batchConfig.totalCount;    
                
                me.window = me.getView('Shopware.apps.SwagImportExport.view.manager.window.Export').create({
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
     * @param offset
     * @param win
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
                if (result.msg){
                    alert(result.msg);
                } else {
                    me.batchConfig.params = result.data;
                    me.batchConfig.position = result.data.position;
                    
                    win.exportProgress.updateProgress(
                            me.batchConfig.position / me.batchConfig.totalCount,
                            'Exporting... ' + me.batchConfig.position + ' / ' + me.batchConfig.totalCount,
                            true
                    );
//                    win.exportProgress.updateText('Exporting... ' + me.batchConfig.position + ' / ' + me.batchConfig.totalCount);
                    
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
    onCancelWatermarkProcess: function(btn) {
        var me = this;

        btn.disable();

        me.cancelOperation = true;
    },
    /**
     * Will be called when export finish
     *
     * @param win
     */
    onProcessFinish: function(win) {

    }
});
//{/block}

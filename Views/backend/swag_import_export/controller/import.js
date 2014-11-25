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
        finished: '{s name=swag_import_export/import/finished}Importing finished successfully. {/s}',
        process: '{s name=swag_import_export/import/process}Importing... {/s}',
        start: '{s name=swag_import_export/import/start}Start importing{/s}',
        close: '{s name=swag_import_export/import/close}Close{/s}',
        failure: '{s name=swag_import_export/import/failure-title}An error occured{/s}',
        unprocess: '{s name=swag_import_export/import/unprocessed}Start importing unprocessed data{/s}'
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
            },
            'swag-import-export-manager-operation': {
                resumeImport: me.onResume
            },
            'swag-import-export-manager-import html5fileupload': {
                uploadReady: me.onReload
            }
        });

        me.callParent(arguments);
    },
    /**
     * Triggers when the file is uploaded via drag and drop
     * 
     * @param [object] target
     */
    onReload: function(target){
        var me = this,
            response = Ext.decode(target.responseText);
        
        if (response.success === false) {
            return;
        }
        
        var path = response.data.path;
    
        me.setFilePath(path);
    },
    /**
     * 
     * @param object btn
     * @param object Ext.button.Button btn
     */
    onImport: function(btn) {
        var me = this,
                form = btn.up('form').getForm(),
                values = form.getValues();
        
        if (Ext.isEmpty(values.profile))
        {
            Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/import/error_title}Swag import export{/s}',
                    '{s name=swag_import_export/import/error_msg_profle}Please select a profile{/s}'
            );
            return;
        }
        
        var localFile = Ext.getCmp('importSelectFile').getValue();

        if (!Ext.isEmpty(values.importFile) && Ext.isEmpty(localFile))
        {
            me.parameters = values;

            me.onCreateImportWindow();
            return;
        }

        if (!Ext.isEmpty(localFile)){
            var me = this;
            form.submit({
                url: '{url module=backend controller="swagImportExport" action="uploadFile"}',
                waitMsg: 'Uploading',
                success: function(fp, response) {
                    me.setFilePath(response.result.data.path);
                    me.parameters = btn.up('form').getForm().getValues();
                    me.onCreateImportWindow();
                },
                failure: function(fp, response) {
                    Shopware.Msg.createStickyGrowlMessage({
                        title: me.snippets.failure,
                        text: response.result.message
                    });
                    var mask = Ext.get(Ext.getBody().query('.x-mask'));
                    mask.hide();
                }
            });
        } else {
            Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/import/error_title}Swag import export{/s}',
                    '{s name=swag_import_export/import/error_msg_file}No file was selected{/s}'
            );
            return;
        }
    },
    /**
     * Creates batch configuration
     */
    onCreateImportWindow: function() {
        var me = this;

        me.getBatchConfig = me.getConfig();

    },
    onResume: function(record, sessionStore) {
        var me = this;

        me.parameters = {
            sessionId: record.get('id'),
            profile: record.get('profileId'),
            importFile: 'media/unknown/' + record.get('fileName'),
            format: record.get('format')
        };
        
        me.sessionStore = sessionStore;
        
        me.getConfig();
        
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
                sessionId: me.parameters.sessionId,
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
                
                if(result.success === false){
                    Shopware.Msg.createStickyGrowlMessage({
                        title: 'Import Error',
                        text: result.msg
                    });
                    var mask = Ext.get(Ext.getBody().query('.x-mask'));
                    mask.hide();
                    return;
                }
                
                me.batchConfig.position = result.position;
                me.batchConfig.totalCount = result.count;
                me.batchConfig.snippet = me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount;
                me.batchConfig.progress = me.batchConfig.position / me.batchConfig.totalCount;

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
    setFilePath: function(path){
        var fileField = Ext.getCmp('swag-import-export-file');
        fileField.setValue(path);
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
                
                if(result.success == false){
                    Shopware.Msg.createStickyGrowlMessage({
                        title: 'Import Error',
                        text: result.msg
                    });
                    return;
                }

                me.batchConfig.params = result.data;
                me.batchConfig.position = result.data.position;

                if (result.data.count) {
                    me.batchConfig.totalCount = result.data.count;
                }

                if (result.data.load === true) {
                    Shopware.Notification.createGrowlMessage(
                        'Import',
                        me.snippets.finished + me.snippets.unprocess
                    );

                    win.importProgress.updateProgress(
                        me.batchConfig.position / me.batchConfig.totalCount,
                        me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount,
                        true
                    );

                    win.setLoading(true);

                    //sets artificial delay of 2 secs
                    setTimeout(function () {
                        win.setLoading(false);
                        me.runRequest(win);
                    }, 2000);

                } else {
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
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

    refs: [
        {
            ref: 'importManager',
            selector: 'swag-import-export-manager-import'
        }
    ],

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
            'swag-import-export-manager-session': {
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
            name,
            response = Ext.decode(target.responseText);
        
        if (response.success === false) {
            return;
        }
        
        name = response.data.fileName;
        me.setFilePath(name);
    },

    /**
     * 
     * @param { Ext.button.Button } btn
     */
    onImport: function(btn) {
        var me = this,
            form = btn.up('form').getForm(),
            values = form.getValues(),
            localFile;
        
        if (Ext.isEmpty(values.profile) || values.profile < 1) {
            Shopware.Notification.createGrowlMessage(
                '{s name=swag_import_export/import/error_title}Swag import export{/s}',
                '{s name=swag_import_export/import/error_msg_profle}Please select a profile{/s}'
            );
            return;
        }
        
        localFile = btn.up('form').down('#importSelectFile').getValue();

        if (!Ext.isEmpty(values.importFile) && Ext.isEmpty(localFile)) {
            me.parameters = values;
            me.onCreateImportWindow();
            return;
        }

        if (!Ext.isEmpty(localFile)) {
            me = this;
            form.submit({
                url: '{url module=backend controller="SwagImportExport" action="uploadFile"}',
                waitMsg: 'Uploading',
                scope: me,
                success: function(fp, response) {
                    me.setFilePath(response.result.data.fileName);
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
            importFile: record.get('fileUrl'),
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
    },

    /**
     * Returns the needed configuration for the next batch call
     */
    getConfig: function() {
        var me = this;

        me.batchConfig = {
            requestUrl: '{url controller="SwagImportExportImport" action="import"}',
            action: 'close-window-import',
            params: {
                profileId: me.parameters.profile,
                sessionId: me.parameters.sessionId,
                importFile: me.parameters.importFile
            },
            snippets: me.snippets
        };

        Ext.Ajax.request({
            url: '{url controller="SwagImportExportImport" action="prepareImport"}',
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
                    text: response.responseText
                });
            }
        });
    },

    setFilePath: function(path){
        var me = this,
            fileField = me.getImportManager().down('#swag-import-export-file');

        fileField.setValue(path);
    },

    /**
     * This function sends a request to export data
     *
     * @param { Enlight.app.SubWindow } win
     * @param { boolean } extension
     */
    runRequest: function(win, extension) {
        var me = this,
                config = me.batchConfig,
                params = config.params;

        if (!Ext.isDefined(extension)) {
            extension = false;
        }

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
                    var msg = '{s name=swag_import_export/import/error}Import error{/s}';

                    if (extension) {
                        msg = '{s name=swag_import_export/import/extension_import_error}Extension import error{/s}'
                    }

                    return Shopware.Msg.createStickyGrowlMessage({
                        title: msg,
                        text: result.msg
                    });
                }

                me.batchConfig.params = result.data;
                me.batchConfig.position = result.data.position;

                if (result.data.count) {
                    me.batchConfig.totalCount = result.data.count;
                }

                if (result.data.load === true) {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: '{s name=swag_import_export/import/window_title}Import{/s}',
                        text: me.snippets.finished + me.snippets.unprocess
                    });

                    win.importProgress.updateProgress(
                        me.batchConfig.position / me.batchConfig.totalCount,
                        me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount,
                        true
                    );

                    win.setLoading(true);

                    //sets artificial delay of 2 secs
                    setTimeout(function () {
                        win.setLoading(false);
                        me.runRequest(win, true);
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
                    title: '{s name=swag_import_export/import/failure-title}An error occured{/s}',
                    text: response.responseText
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
        if (!Ext.isEmpty(me.sessionStore)){
            me.sessionStore.reload();
        }
    }
    
});
//{/block}
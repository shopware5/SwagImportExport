// {namespace name="backend/swag_import_export/view/main"}
// {block name="backend/swag_import_export/controller/export"}
Ext.define('Shopware.apps.SwagImportExport.controller.Export', {
    extend: 'Ext.app.Controller',
    snippets: {
        exportWindow: '{s name=swag_import_export/export/window_title}Export window{/s}',
        finished: '{s name=swag_import_export/export/finished}Export finished successfully {/s}',
        process: '{s name=swag_import_export/export/process}Exporting... {/s}'
    },

    /**
     * This method creates listener for events fired from the export
     */
    init: function() {
        var me = this;

        me.control({
            // Export button
            'swag-import-export-manager-export': {
                export: me.onExport
            },
            'swag-import-export-manager-window-export': {
                startProcess: me.onStartProcess,
                downloadFile: me.onDownloadFile,
                cancelProcess: me.onCancelProcess
            },
            'swag-import-export-manager-session': {
                resumeExport: me.onResume
            }
        });

        me.callParent(arguments);
    },

    onExport: function(btn) {
        var me = this,
            form = btn.up('form'),
            values = form.getValues();

        if (Ext.isEmpty(values.profile) || values.profile < 1 || Ext.isEmpty(values.format)) {
            Shopware.Notification.createGrowlMessage(
                '{s name=swag_import_export/export/error_title}Swag import export{/s}',
                '{s name=swag_import_export/export/error_msg}Please select export configuration{/s}'
            );
            return false;
        }

        me.parameters = values;

        me.onCreateExportWindow();
    },

    /**
     * Triggers if the resume button was pressed
     * in the previous operation window.
     *
     * @param object Shopware.apps.SwagImportExport.model.Session
     */
    onResume: function(record, sessionStore) {
        var me = this;

        me.parameters = {
            sessionId: record.get('id'),
            profile: record.get('profileId'),
            format: record.get('format')
        };

        me.sessionStore = sessionStore;

        me.getConfig();
    },

    /**
     * Creates batch configuration
     */
    onCreateExportWindow: function() {
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
        win.closeButton.disable();
    },

    /**
     * Returns the parameters that will be sent to the backend
     */
    getParams: function() {
      var me = this;

      return {
        profileId: me.parameters.profile,
        sessionId: me.parameters.sessionId,
        format: me.parameters.format,
        limit: me.parameters.limit,
        offset: me.parameters.offset,
        categories: me.parameters.category,
        variants: me.parameters.variants,
        ordernumberFrom: me.parameters.ordernumberFrom,
        dateFrom: me.parameters.dateFrom,
        dateTo: me.parameters.dateTo,
        orderstate: me.parameters.orderstate,
        paymentstate: me.parameters.paymentstate,
        stockFilter: me.parameters.stockFilter,
        customFilterDirection: me.parameters.customFilterCombo,
        customFilterValue: me.parameters.filterThanValue,
        customerStreamId: me.parameters.customerStreamId
      };
    },
    /**
     * Returns the needed configuration for the next batch call
     */
    getConfig: function() {
        var me = this;

        me.batchConfig = {
            requestUrl: '{url controller="SwagImportExportExport" action="export"}',
            params: me.getParams()
        };

        Ext.Ajax.request({
            url: '{url controller="SwagImportExportExport" action="prepareExport"}',
            method: 'POST',
            params: me.batchConfig.params,
            success: function(response) {
                var result = Ext.decode(response.responseText);
                me.batchConfig.position = result.position;
                me.batchConfig.totalCount = result.count;
                me.batchConfig.snippet = me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount;
                me.batchConfig.progress = me.batchConfig.position / me.batchConfig.totalCount;

                me.window = me.getView('Shopware.apps.SwagImportExport.view.manager.window.Export').create({
                    batchConfig: me.batchConfig
                }).show();
            },
            failure: function(response) {
                Shopware.Msg.createStickyGrowlMessage({
                    title: '{s name=swag_import_export/export/error_occured}An error occured{/s}',
                    text: response.responseText
                });
            }
        });
    },

    /**
     * Returns the parameters that will be sent to the backend
     */
    getParams: function() {
        var me = this;

        return {
            profileId: me.parameters.profile,
            sessionId: me.parameters.sessionId,
            format: me.parameters.format,
            limit: me.parameters.limit,
            offset: me.parameters.offset,
            categories: me.parameters.category,
            variants: me.parameters.variants,
            ordernumberFrom: me.parameters.ordernumberFrom,
            dateFrom: me.parameters.dateFrom,
            dateTo: me.parameters.dateTo,
            orderstate: me.parameters.orderstate,
            paymentstate: me.parameters.paymentstate,
            stockFilter: me.parameters.stockFilter,
            customFilterDirection: me.parameters.customFilterCombo,
            customFilterValue: me.parameters.filterThanValue,
            customerStreamId: me.parameters.customerStreamId
        };
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

                if (result.success === false) {
                    Shopware.Msg.createStickyGrowlMessage({
                        title: '{s name=swag_import_export/export/error}Export error{/s}',
                        text: result.msg
                    });

                    win.closeButton.enable();
                    win.cancelButton.disable();
                    return;
                }

                me.batchConfig.params = result.data;
                me.batchConfig.position = result.data.position;

                win.exportProgress.updateProgress(
                        me.batchConfig.position / me.batchConfig.totalCount,
                        me.snippets.process + me.batchConfig.position + ' / ' + me.batchConfig.totalCount,
                        true
                        );

                if (me.batchConfig.position === me.batchConfig.totalCount) {
                    me.fileName = result.data.fileName;
                    me.onProcessFinish(win);
                } else {
                    me.runRequest(win);
                }
            },
            failure: function(response) {
                Shopware.Msg.createStickyGrowlMessage({
                    title: '{s name=swag_import_export/export/error_occured}An error occured{/s}',
                    text: response.responseText
                });

                win.closeButton.enable();
                win.cancelButton.disable();
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
        win.cancelButton.hide();
        win.downloadButton.show();
        win.exportProgress.updateText(me.snippets.finished + me.batchConfig.position + ' / ' + me.batchConfig.totalCount);

        if (!Ext.isEmpty(me.sessionStore)) {
            me.sessionStore.reload();
        }
    },

    onDownloadFile: function() {
        var me = this,
            url = '{url action="downloadFile"}' + '?fileName=' + me.fileName;

        window.open(url, '_blank');
    }
});
// {/block}

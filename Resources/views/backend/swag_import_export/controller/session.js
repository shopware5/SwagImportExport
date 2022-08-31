// {namespace name="backend/swag_import_export/view/session"}
// {block name="backend/swag_import_export/controller/session"}
Ext.define('Shopware.apps.SwagImportExport.controller.Session', {
    extend: 'Ext.app.Controller',

    refs: [
        { ref: 'grid', selector: 'swag-import-export-manager-session{ isVisible(true) }' }
    ],

    init: function() {
        var me = this;

        me.control({
            'swag-import-export-manager-session{ isVisible(true) }': {
                deleteSession: me.onDeleteSession,
                resumeSession: me.onResumeSession,
                showSessionDetails: me.onShowDetails
            },
            'swag-import-export-manager-window-log{ isVisible(true) }': {
                deleteSession: function(sessionId) {
                    var store = me.getGrid().getStore(),
                        record = store.getById(sessionId);
                    me.onDeleteSession(me.getGrid(), [record]);
                },
                resumeSession: function(sessionId, storeObj) {
                    var grid = me.getGrid(),
                        store = grid.getStore(),
                        record = store.getById(sessionId);

                    grid.fireEvent('resumeExport', record, storeObj);
                }
            }
        });

        me.callParent(arguments);
    },

    onDeleteSession: function(view, records) {
        var store = view.getStore();

        if (records.length == 0) {
            return;
        }
        // ask the user if he is sure.
        Ext.MessageBox.confirm(
            '{s name="swag_import_export/manager/log/delete_operations_title"}Delete selected operation(s)?{/s}',
            '{s name="swag_import_export/manager/log/delete_operations_confirm"}{/s}',
            function (response) {
                if (response !== 'yes') {
                    return;
                }

                store.remove(records);
                store.sync({
                    callback: function () {
                        Shopware.Notification.createGrowlMessage(
                            '{s name="swag_import_export/manager/log/delete_operations_success_title"}Success{/s}',
                            '{s name="swag_import_export/manager/log/delete_operations_delete_success"}The selected operation(s) have been removed{/s}',
                            '{s name="swag_import_export/manager/log/delete_operations_growlmessage"}Operation{/s}'
                        );
                        store.load();
                    }
                });
            }
        );
    },

    onResumeSession: function(view, record) {
        var grid = view.up('grid'),
            type = record.get('type');

        switch (type) {
            case 'export':
                grid.fireEvent('resumeExport', record, view.getStore());
                break;
            case 'import':
                grid.fireEvent('resumeImport', record, view.getStore());
                break;
        }
    },

    onShowDetails: function(view, record) {
        var me = this;

        me.logWindow = Ext.create('Shopware.apps.SwagImportExport.view.manager.window.Log', {
            data: record.getData(),
            sessionId: record.get('id')
        });

        me.logWindow.show();
    }
});
// {/block}

// {namespace name="backend/swag_import_export/view/main"}
// {block name="backend/swag_import_export/controller/main"}
Ext.define('Shopware.apps.SwagImportExport.controller.Main', {
    extend: 'Ext.app.Controller',

    init: function() {
        var me = this;

        me.mainWindow = me.getView('Window').create({}).show();
    }
});
// {/block}

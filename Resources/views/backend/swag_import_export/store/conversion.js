// {block name="backend/swag_import_export/store/conversion"}
Ext.define('Shopware.apps.SwagImportExport.store.Conversion', {
    extend: 'Ext.data.Store',
    model: 'Shopware.apps.SwagImportExport.model.Conversion',
    proxy: {
        type: 'ajax',
        api: {
            create: '{url controller="SwagImportExportConversion" action="createConversion"}',
            read: '{url controller="SwagImportExportConversion" action="getConversions"}',
            update: '{url controller="SwagImportExportConversion" action="updateConversion"}',
            destroy: '{url controller="SwagImportExportConversion" action="deleteConversion"}'
        },
        actionMethods: 'POST',
        reader: {
            type: 'json',
            root: 'data'
        },
        writer: {
            type: 'json',
            root: 'data'
        }
    }
});
// {/block}

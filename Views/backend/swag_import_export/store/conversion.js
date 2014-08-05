Ext.define('Shopware.apps.SwagImportExport.store.Conversion', {
    extend: 'Ext.data.Store',
    model: 'Shopware.apps.SwagImportExport.model.Conversion',
    proxy: {
        type: 'ajax',
        api: {
            create: '{url controller="SwagImportExport" action="createConversion"}',
            read: '{url controller="SwagImportExport" action="getConversions"}',
            update: '{url controller="SwagImportExport" action="updateConversion"}',
            destroy: '{url controller="SwagImportExport" action="deleteConversion"}'
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
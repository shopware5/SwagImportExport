// {block name="backend/swag_import_export/store/section"}
Ext.define('Shopware.apps.SwagImportExport.store.Section', {
    /**
     * Define that this component is an extension of the Ext.data.TreeStore
     */
    extend: 'Ext.data.Store',

    /**
     * Define the used model for this store
     * @string
     */
    model: 'Shopware.apps.SwagImportExport.model.Section',

    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="SwagImportExportProfile" action="getSections"}'
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

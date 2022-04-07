// {block name="backend/swag_import_export/store/column"}
Ext.define('Shopware.apps.SwagImportExport.store.Column', {
    /**
     * Define that this component is an extension of the Ext.data.TreeStore
     */
    extend: 'Ext.data.Store',

    /**
     * Define the used model for this store
     * @string
     */
    model: 'Shopware.apps.SwagImportExport.model.Column',

    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="SwagImportExportProfile" action="getColumns"}'
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

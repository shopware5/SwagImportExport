// {block name="backend/swag_import_export/store/parent_key"}
// deprecated since 2.4.2 and will be removed with 3.0.0
// {block name="backend/swag_import_export/store/column"}
Ext.define('Shopware.apps.SwagImportExport.store.ParentKey', {
    /**
     * Define that this component is an extension of the Ext.data.TreeStore
     */
    extend: 'Shopware.apps.SwagImportExport.store.Column',

    /**
     * Define the used model for this store
     * @string
     */
    model: 'Shopware.apps.SwagImportExport.model.Column',

    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="SwagImportExportProfile" action="getParentKeys"}'
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
// {/block}

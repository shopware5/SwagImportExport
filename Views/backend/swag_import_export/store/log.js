//{block name="backend/swag_import_export/store/log"}
Ext.define('Shopware.apps.SwagImportExport.store.Log', {
    /**
     * Define that this component is an extension of the Ext.data.TreeStore
     */
    extend : 'Ext.data.Store',
    
    autoLoad: false,

    remoteFilter: true,

    remoteSort: true,

    /**
     * Define the used model for this store
     * @string
     */
    model : 'Shopware.apps.SwagImportExport.model.Log'
});
//{/block}
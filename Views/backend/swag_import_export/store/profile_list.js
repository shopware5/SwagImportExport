//{block name="backend/swag_import_export/store/profile_list"}
Ext.define('Shopware.apps.SwagImportExport.store.ProfileList', {
    /**
     * Define that this component is an extension of the Ext.data.TreeStore
     */
    extend : 'Ext.data.Store',
    
    autoLoad: false,

    remoteSort: true,

    remoteFilter: true,

    /**
     * Define the used model for this store
     * @string
     */
    model : 'Shopware.apps.SwagImportExport.model.ProfileList',

    pageSize: 15
});
//{/block}
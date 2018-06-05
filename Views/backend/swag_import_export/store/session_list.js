// {block name="backend/swag_import_export/store/session_list"}
Ext.define('Shopware.apps.SwagImportExport.store.SessionList', {
    extend: 'Ext.data.Store',

    autoLoad: false,

    remoteSort: true,

    remoteFilter: true,

    /**
     * Define the used model for this store
     * @string
     */
    model: 'Shopware.apps.SwagImportExport.model.SessionList'
});
// {/block}

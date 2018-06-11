// {block name="backend/swag_import_export/store/profile"}
Ext.define('Shopware.apps.SwagImportExport.store.Profile', {
    extend: 'Ext.data.TreeStore',
    model: 'Shopware.apps.SwagImportExport.model.Profile',
    defaultRootId: '/',
    root: {
        expanded: true,
        text: 'Root'
    },
    proxy: {
        type: 'ajax',
        api: {
            create: '{url controller="SwagImportExportProfile" action="createNode"}',
            read: '{url controller="SwagImportExportProfile" action="getProfile"}',
            update: '{url controller="SwagImportExportProfile" action="updateNode"}',
            destroy: '{url controller="SwagImportExportProfile" action="deleteNode"}'
        },
        actionMethods: 'POST',
        reader: {
            type: 'json'
        },
        writer: {
            type: 'json',
            root: 'data'
        }
    }
});
// {/block}

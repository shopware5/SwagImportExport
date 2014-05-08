Ext.define('Shopware.apps.SwagImportExport.store.Profile', {
    extend: 'Ext.data.TreeStore',
    model: 'Shopware.apps.SwagImportExport.model.Profile',
    requires: 'Shopware.apps.SwagImportExport.model.Profile',
    root: {
        text: 'Root',
        expanded: true        
    },
    proxy: {
        type: 'ajax',
		api:{
			read:'{url controller="SwagImportExport" action="getProfile"}'
        },
        actionMethods: 'POST',
        reader: {
            type: 'json'
        }
    }
});
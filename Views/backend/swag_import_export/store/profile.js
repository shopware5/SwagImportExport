Ext.define('Shopware.apps.SwagImportExport.store.Profile', {
    extend: 'Ext.data.TreeStore',
    model: 'Shopware.apps.SwagImportExport.model.Profile',
    root: {
//        text: 'Root',
//        expanded: true        
    },
    proxy: {
        type: 'ajax',
		api:{
			create: '{url controller="SwagImportExport" action="createProfile"}',
			read:	'{url controller="SwagImportExport" action="getProfile"}',
			update: '{url controller="SwagImportExport" action="updateProfile"}',
			destroy: '{url controller="SwagImportExport" action="deleteProfile"}'
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
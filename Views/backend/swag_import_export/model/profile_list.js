//{block name="backend/swag_import_export/model/profile_list"}
Ext.define('Shopware.apps.SwagImportExport.model.ProfileList', {
    /**
     * Extends the standard ExtJS 4
     * @string
     */
    extend : 'Ext.data.Model',
    /**
     * Configure the data communication
     * @object
     */
    fields: [
        // {block name="backend/swag_import_export/model/profile_list/fields"}{/block}
        { name: 'id', type: 'int', useNull: true },
        { name: 'type', type: 'string' },
        { name: 'baseProfile', type: 'int', useNull: true },
        { name: 'name', type: 'string' },
        { name: 'translation', persist: false },
        { name: 'default', type: 'boolean', persist: false },
        { name: 'tree', type: 'string' }
    ],
    
    proxy: {
        type: 'ajax',
        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
			create:'{url controller="SwagImportExportProfile" action="createProfiles"}',
            read:'{url controller="SwagImportExportProfile" action="getProfiles"}',
            update: '{url controller="SwagImportExportProfile" action="updateProfiles"}',
            destroy: '{url controller="SwagImportExportProfile" action="deleteProfiles"}'
        },
        /**
         * Configure the data reader
         * @object
         */
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
//{/block}
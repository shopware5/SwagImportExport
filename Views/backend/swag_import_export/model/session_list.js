//{block name="backend/swag_import_export/model/session_list"}
Ext.define('Shopware.apps.SwagImportExport.model.SessionList', {
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
        // {block name="backend/swag_import_export/model/session/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'profileId', type: 'string' },
        { name: 'profileName', type: 'string' },
        { name: 'type', type: 'string' },
        { name: 'position', type: 'string' },
        { name: 'totalCount', type: 'string' },
        { name: 'username', type: 'string' },
        { name: 'fileName', type: 'string' },
        { name: 'fileUrl', type: 'string' },
        { name: 'format', type: 'string' },
        { name: 'fileSize', type: 'string' },
        { name: 'state', type: 'string' },
        { name: 'createdAt', type: 'date' }
    ],
    
    proxy: {
        type: 'ajax',
        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
            read:'{url controller="SwagImportExportSession" action="getSessions"}',
            destroy:'{url controller="SwagImportExportSession" action="deleteSession"}'
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
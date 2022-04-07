// {block name="backend/swag_import_export/model/log"}
Ext.define('Shopware.apps.SwagImportExport.model.Log', {
    /**
     * Extends the standard ExtJS 4
     * @string
     */
    extend: 'Ext.data.Model',
    /**
     * Configure the data communication
     * @object
     */
    fields: [
        // {block name="backend/swag_import_export/model/session_list/fields"}{/block}
        { name: 'message', type: 'string' },
        { name: 'logDate', type: 'date', format: 'timestamp' },
        { name: 'errorState', type: 'boolean' }
    ],

    proxy: {
        type: 'ajax',
        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
            read: '{url controller="SwagImportExport" action="getLogs"}'
        },
        /**
         * Configure the data reader
         * @object
         */
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
// {/block}

//{block name="backend/swag_import_export/model/column"}
Ext.define('Shopware.apps.SwagImportExport.model.Column', {
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
        // {block name="backend/swag_import_export/model/column/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'name', type: 'string' },
        { name: 'default', type: 'string' },
        { name: 'type', type: 'string' }
    ]
});
//{/block}
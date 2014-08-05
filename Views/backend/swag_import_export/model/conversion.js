Ext.define('Shopware.apps.SwagImportExport.model.Conversion', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'id', type: 'int' },
        { name: 'profileId', type: 'int' },
        { name: 'variable', type: 'string' },
        { name: 'exportConversion', type: 'string' },
        { name: 'importConversion', type: 'string' }
    ]
});

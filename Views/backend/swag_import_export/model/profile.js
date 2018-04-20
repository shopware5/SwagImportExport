//{block name="backend/swag_import_export/model/profile"}
Ext.define('Shopware.apps.SwagImportExport.model.Profile', {
	extend: 'Ext.data.Model',
	fields: [
        { name: 'id', type: 'string', mapping: 'id' },
        { name: 'text', type: 'string', mapping: 'text' },
        { name: 'index', type: 'int', mapping: 'index' },
		{ name: 'adapter', type: 'string', mapping: 'adapter' },
		{ name: 'parentKey', type: 'string', mapping: 'parentKey' },
		{ name: 'type', type: 'string', mapping: 'type' },
		{ name: 'swColumn', type: 'string', mapping: 'swColumn' },
		{ name: 'defaultValue', type: 'string', mapping: 'defaultValue' },
		{ name: 'inIteration', type: 'boolean', mapping: 'inIteration' }
	]
});
Ext.data.NodeInterface.decorate('Shopware.apps.SwagImportExport.model.Profile');
//{/block}
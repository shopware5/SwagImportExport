Ext.define('Shopware.apps.SwagImportExport.model.Profile', {
	extend: 'Ext.data.Model',
	fields: [
//        { name: 'id', type: 'int', mapping: 'Id' },
        { name: 'text', type: 'string', mapping: 'text' },
//        { name: 'leaf', type: 'boolean', mapping: 'Leaf' },
//        { name: 'loaded', type: 'boolean', mapping: 'Loaded', defaultValue: false },
//        { name: 'expanded', defaultValue: true },
		{ name: 'adapter', type: 'string', mapping: 'adapter' },
		{ name: 'parentKey', type: 'string', mapping: 'parentKey' },
		{ name: 'type', type: 'string', mapping: 'type' },
		{ name: 'swColumn', type: 'string', mapping: 'swColumn' },
		{ name: 'iteration', type: 'string', mapping: 'iteration' },
		{ name: 'inIteration', type: 'boolean', mapping: 'inIteration' }
	]
});
Ext.data.NodeInterface.decorate('Shopware.apps.SwagImportExport.model.Profile');
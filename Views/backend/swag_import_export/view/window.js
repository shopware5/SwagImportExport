//{namespace name=backend/swag_gift_packaging/view/main}
//{block name="backend/swag_gift_packaging/view/main/window"}
Ext.define('Shopware.apps.SwagImportExport.view.Window', {
	
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend: 'Enlight.app.Window',
	
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-window',
	
    height: 600,
    width: 1000,
    
    layout: 'fit',
	
    title: '{s name=swag_import_export/window/title}Import / Export{/s}',
    
    initComponent: function() {
        var me = this;

        //add the order list grid panel and set the store
        me.items = [me.createTabPanel()];
        me.callParent(arguments);
    },

    createTabPanel: function() {
        var me = this;
        var aclItems = [];

        /*{if {acl_is_allowed privilege=export} OR {acl_is_allowed privilege=import}}*/
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.manager.Manager'));
        /*{/if}*/

        /*{if {acl_is_allowed privilege=profile}}*/
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.profile.Grid', {
            // using new instance here because grid uses filtering
            store: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList', {
                sorters: [
                    { property: 'name', direction: 'ASC' }
                ],
                listeners: {
                    load: function(store, records) {
                        var record = store.findRecord('type', 'customersComplete');

                        if (record) {
                            store.remove([record]);
                        }
                    }
                }
            }),
            listeners: {
                activate: function(grid) {
                    grid.getStore().load();
                }
            }
        }));
        /*{/if}*/

        return Ext.create('Ext.tab.Panel', {
            name: 'main-tab',
            items: aclItems
        });
    }
});
//{/block}

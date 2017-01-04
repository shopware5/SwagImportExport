//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/manager"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Manager', {
    extend: 'Ext.container.Container',

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-manager',
    height: 450,
    
    title: '{s name=swag_import_export/manager/manager/title}Import / Export manager{/s}',
    layout: 'fit',
    style: {
        background: '#F0F2F4;'
    },

    initComponent: function() {
        var me = this;

        me.items = [
            me.createTabPanel()
        ];

        me.callParent(arguments);

        me.on('activate', function() {
            me.tabPanel.getActiveTab().fireEvent('activate', me.tabPanel.getActiveTab());
        });
    },

    /**
     * Creates the main tab Panel 'Import/Export Manager' (first tab)
     *
     * @returns Ext.tab.Panel
     */
    createTabPanel: function() {
        var me = this,
            aclItems = [];

        /* {if {acl_is_allowed privilege=export}} */
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.manager.Export', {
            itemId: 'exportmanager',
            border: false,
            sessionStore: me.sessionStore,
            listeners: {
                activate: {
                    buffer: 150,
                    fn: function(container) {
                        var combo = container.profileCombo,
                            store = combo.getStore();

                        store.filters.removeAtKey('search');
                        store.load({
                            callback: function() {
                                if (combo.isDirty() && !store.getById(combo.getValue())) {
                                    combo.clearValue();
                                }
                            }
                        });
                    }
                }
            }
        }));
        /* {/if} */

        /* {if {acl_is_allowed privilege=import}} */
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.manager.Import', {
            itemId: 'importManager',
            listeners: {
                activate: function(container) {
                    var combo = container.profileCombo,
                        store = combo.getStore();

                    store.filters.removeAtKey('search');
                    store.load({
                        callback: function() {
                            if (combo.isDirty() && !store.getById(combo.getValue())) {
                                combo.clearValue();
                            }
                        }
                    });
                }
            }
        }));
        /* {/if} */

        /* {if {acl_is_allowed privilege=export} OR {acl_is_allowed privilege=import}} */
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.manager.Session', {
            listeners: {
                activate: function(grid) {
                    grid.getStore().load();
                }
            }
        }));
        /* {/if} */

        me.tabPanel = Ext.create('Ext.tab.Panel', {
            name: 'manager-main-tab',
            items: aclItems
        });

        return me.tabPanel;
    }
});
//{/block}

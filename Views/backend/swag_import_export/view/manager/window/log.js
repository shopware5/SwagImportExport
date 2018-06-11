// {namespace name=backend/swag_import_export/view/session}
// {block name="backend/swag_import_export/view/manager/window/log"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.window.Log', {
    extend: 'Enlight.app.SubWindow',
    alias: 'widget.swag-import-export-manager-window-log',

    width: 620,
    height: 580,
    title: '{s name=swag_import_export/manager/window/log/title}Detail information{/s}',

    config: {
        sessionId: null
    },

    bodyStyle: 'background-color: #F0F2F4;',
    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    initComponent: function() {
        var me = this;

        me.items = me.buildItems();

        me.callParent(arguments);
    },

    buildItems: function() {
        var me = this;

        me.propertyGrid = me.buildPropertyGrid();
        me.logGrid = me.buildLogGrid();

        return [
            me.propertyGrid,
            me.logGrid
        ];
    },

    buildPropertyGrid: function() {
        var me = this;

        return Ext.create('Ext.grid.property.Grid', {
            flex: 1,
            sortableColumns: false,
            title: '',
            source: {},
            loader: {
                url: '{url controller="SwagImportExportSession" action="getSessionDetails"}',
                renderer: function(loader, response, active) {
                    var data = Ext.JSON.decode(response.responseText).data;

                    loader.getTarget().setSource(data);
                },
                autoLoad: true,
                baseParams: {
                    sessionId: me.getSessionId()
                }
            },
            dockedItems: [{
                xtype: 'toolbar',
                ui: 'shopware-ui',
                dock: 'top',
                items: [{
                    text: '{s name=swag_import_export/manager/window/log/button_repeat}Repeat{/s}',
                    iconCls: 'sprite-arrow-circle-315',
                    handler: function() {
                        me.fireEvent(
                            'resumeSession',
                            me.getSessionId(),
                            {
                                reload: function() {
                                    me.propertyGrid.getLoader().load();
                                    me.logGrid.getStore().reload();
                                }
                            }
                        );
                    }
                }, {
                    text: '{s name=swag_import_export/manager/window/log/button_delete}Delete{/s}',
                    iconCls: 'sprite-minus-circle-frame',
                    handler: function() {
                        me.fireEvent(
                            'deleteSession',
                            me.getSessionId()
                        );
                        me.close();
                    }
                }]
            }],
            listeners: {
                beforeedit: function() {
                    return false;
                }
            }
        });
    },

    buildLogGrid: function() {
        var me = this;

        return Ext.create('Shopware.apps.SwagImportExport.view.log.Log', {
            margins: '5 0 0 0',
            flex: 1,
            logStore: Ext.create('Shopware.apps.SwagImportExport.store.Log', {
                autoLoad: true,
                sorters: [
                    { property: 'logDate', direction: 'DESC' }
                ],
                filters: [
                    { property: 'session', value: me.getSessionId(), expression: '=' }
                ]
            })
        });
    }
});
// {/block}

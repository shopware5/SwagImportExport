//{namespace name=backend/swag_import_export/view/profile/iterator}
//{block name="backend/swag_import_export/view/profile/window/iterator"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.window.Iterator', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend: 'Enlight.app.Window',
    padding: 5,

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-iterator-window',

    width: '70%',
    height: '70%',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    config: {
        treePanel: null,
        profileId: null
    },

    title: '{s name=swag_import_export/profile/iterator/title}Extend dataset{/s}',

    initComponent: function() {
        var me = this,
            treePanel;

        me.items = me.buildItems();
        me.dockedItems = me.buildDockedItems();

        me.callParent(arguments);
    },

    buildItems: function() {
        var me = this;

        me.formPanel = me.buildFormPanel();
        me.columnGrid = me.buildColumnGrid();

        return [
            me.formPanel,
            me.columnGrid
        ];
    },

    buildDockedItems: function() {
        var me = this;

        me.bottomBar = Ext.create('Ext.toolbar.Toolbar', {
            xtype: 'toolbar',
            dock: 'bottom',
            ui: 'shopware-ui',
            cls: 'shopware-toolbar',
            style: {
                backgroundColor: '#F0F2F4',
                borderRight: '1px solid #A4B5C0',
                borderLeft: '1px solid #A4B5C0',
                borderTop: '1px solid #A4B5C0',
                borderBottom: '1px solid #A4B5C0'
            },
            items: ['->', {
                text: '{s name=swag_import_export/profile/iterator/close}Close{/s}',
                cls: 'secondary',
                handler: function() {
                    me.close();
                }
            }, {
                text: '{s name=swag_import_export/profile/iterator/save}Save{/s}',
                cls: 'primary',
                handler: function () {
                    if (me.formPanel.getForm().isValid()) {
                        me.fireEvent('addIterator', me);
                    }
                }
            }]
        });

        return me.bottomBar;
    },

    buildFormPanel: function() {
        var me = this;

        return Ext.create('Ext.form.Panel', {
            defaults: {
                anchor: '100%'
            },
            fieldDefaults: {
                labelWidth: 200
            },
            flex: 1,
            border: false,
            bodyPadding: 10,
            items: [{
                xtype: 'textfield',
                fieldLabel: '{s name=swag_import_export/profile/iterator/node_name}Node name{/s}',
                name: 'nodeName',
                allowBlank: false
            }, {
                xtype: 'combo',
                fieldLabel: '{s name=swag_import_export/profile/iterator/adapter}Adapter{/s}',
                emptyText: '{s name=swag_import_export/profile/iterator/select_adapter}Select extension{/s}',
                queryMode: 'local',
                store: Ext.create('Shopware.apps.SwagImportExport.store.Section', {
                    autoLoad: true,
                    listeners: {
                        beforeload: {
                            single: true,
                            fn: function(store) {
                                store.getProxy().setExtraParam('profileId', me.getProfileId());
                            }
                        }
                    }
                }),
                valueField: 'id',
                displayField: 'name',
                name: 'adapter',
                allowBlank: false,
                editable: false,
                listeners: {
                    change: function(field, newValue) {
                        var keyField = field.nextSibling('combo[name=parentKey]'),
                            loadParams = {
                                params: {
                                    profileId: me.getProfileId(),
                                    adapter: newValue
                                }
                            };
                        keyField.clearValue();
                        keyField.getStore().load(loadParams);
                        me.columnGrid.getStore().load(loadParams);
                    }
                }
            }, {
                xtype: 'combobox',
                fieldLabel: '{s name=swag_import_export/profile/iterator/parent_key}Parent key{/s}',
                emptyText: '{s name=swag_import_export/profile/iterator/select_parent_key}Select parent key{/s}',
                store: Ext.create('Shopware.apps.SwagImportExport.store.ParentKey'),
                queryMode: 'local',
                editable: false,
                valueField: 'id',
                displayField: 'name',
                allowBlank: false,
                name: 'parentKey'
            }]
        });
    },

    buildColumnGrid: function() {
        var me = this;

        return Ext.create('Ext.grid.Panel', {
            flex: 2,
            border: false,
            title: '{s name=swag_import_export/profile/iterator/grid_title}Set columns{/s}',
            selType: 'rowmodel',
            plugins: [
                Ext.create('Ext.grid.plugin.RowEditing', {
                    clicksToEdit: 1
                })
            ],
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop'
                }
            },
            store: Ext.create('Ext.data.Store', {
                fields: [
                    { name: 'swColumn', mapping: 'name' },
                    { name: 'nodeName', mapping: 'name' },
                    { name: 'select', type: 'boolean', defaultValue: true }
                ],
                proxy: {
                    type: 'ajax',
                    api: {
                        read: '{url controller="SwagImportExportProfile" action="getColumns"}'
                    },
                    actionMethods: 'POST',
                    reader: {
                        type: 'json',
                        root: 'data'
                    },
                    writer: {
                        type: 'json',
                        root: 'data'
                    }
                },
                listeners: {
                    scope: me,
                    load: function() {
                        me.columnGrid.getSelectionModel().selectAll();
                    }
                }
            }),
            columns: [
                {
                    header: '{s name=swag_import_export/profile/iterator/grid_column_column}Shopware column{/s}',
                    dataIndex: 'swColumn',
                    flex: 1
                },
                {
                    header: '{s name=swag_import_export/profile/iterator/grid_column_name}Name{/s}',
                    dataIndex: 'nodeName',
                    flex: 1,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    header: '{s name=swag_import_export/profile/iterator/grid_column_selected}Selected{/s}',
                    dataIndex: 'select',
                    width: 80,
                    renderer: function(value) {
                        var cls = 'sprite-tick';

                        if (value == false) {
                            cls = 'sprite-cross';
                        }

                        return Ext.String.format(
                            '<div class="[0]" style="width: 13px; height: 13px;">&nbsp;</div>',
                            cls
                        );
                    },
                    editor: {
                        xtype: 'checkbox',
                        inputValue: true,
                        uncheckedValue: false
                    }
                }
            ]
        });
    }
});
//{/block}

//{namespace name=backend/swag_import_export/view/profile/column}
//{block name="backend/swag_import_export/view/profile/window/column"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.window.Column', {
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
    alias: 'widget.swag-import-export-column-window',

    width: 500,
    height: 200,

    layout: 'fit',
    title: '{s name=swag_import_export/profile/column/title}Add column{/s}',

    config: {
        treePanel: null,
        profileId: null,
        adapter: null
    },

    initComponent: function() {
        var me = this;

        me.items = me.buildItems();
        me.dockedItems = me.buildDockedItems();

        me.callParent(arguments);
    },

    buildItems: function() {
        var me = this;

        me.formPanel = Ext.create('Ext.form.Panel', {
            defaults: {
                anchor: '100%'
            },
            fieldDefaults: {
                labelWidth: 150
            },
            border: false,
            bodyPadding: 10,
            items: [{
                xtype: 'textfield',
                fieldLabel: '{s name=swag_import_export/profile/column/node_name}Name{/s}',
                name: 'nodeName',
                allowBlank: false
            }, {
                xtype: 'combobox',
                fieldLabel: '{s name=swag_import_export/profile/column/shopware_column}Database mapping{/s}',
                editable: false,
                emptyText: '{s name=swag_import_export/profile/column/select_column}Select column{/s}',
                queryMode: 'local',
                store: Ext.create('Shopware.apps.SwagImportExport.store.Column', {
                    autoLoad: true,
                    proxy: {
                        type: 'ajax',
                        api: {
                            read: '{url controller="SwagImportExportProfile" action="getColumns"}'
                        },
                        extraParams: {
                            adapter: me.getAdapter(),
                            profileId: me.getProfileId()
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
                    }
                }),
                valueField: 'id',
                displayField: 'name',
                name: 'swColumn',
                allowBlank: false,
                listeners: {
                    scope: me,
                    select: function(combo, records) {
                        var selection = records[0];

                        if (me.formPanel.items.getCount() === 3) {
                            me.formPanel.items.getAt(2).destroy();
                        }
                        if (selection.get('default') === 'true') {
                            me.createDefaultValueField(selection.get('type'));
                        }
                    }
                }
            }]
        });

        return me.formPanel;
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
                text: '{s name=swag_import_export/profile/column/close}Close{/s}',
                cls: 'secondary',
                handler: function() {
                    me.close();
                }
            }, {
                text: '{s name=swag_import_export/profile/column/save}Save{/s}',
                cls: 'primary',
                handler: function () {
                    if (me.formPanel.getForm().isValid()) {
                        me.fireEvent('addNode', me);
                    }
                }
            }]
        });

        return me.bottomBar;
    },

    createDefaultValueField: function(type) {
        var me = this,
            fieldConfig;

        switch (type) {
            case 'id':
                fieldConfig = { xtype: 'numberfield', minValue: 1 };
                break;
            case 'integer':
            case 'decimal':
            case 'float':
                var precision = 0;
                if (type.precision) {
                    precision = type.precision
                } else if (type.type == 'float') {
                    precision = 3;
                } else if (type.type == 'decimal') {
                    precision = 3;
                }
                fieldConfig = { xtype: 'numberfield', decimalPrecision: precision };
                break;
            case 'string':
            case 'text':
                fieldConfig = { xtype: 'textfield' };
                break;
            case 'boolean':
                fieldConfig = {
                    xtype: 'checkbox',
                    inputValue: 1,
                    uncheckedValue: 0
                };
                break;
            case 'date':
            case 'dateTime':
                fieldConfig = {
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    submitFormat: 'Y-m-d'
                };
                break;
            default:
                fieldConfig = {
                    hidden: true
                };
                break;
        }

        fieldConfig = Ext.apply(fieldConfig, {
            fieldLabel: '{s name=swag_import_export/profile/column/default_value}Default value{/s}',
            name: 'defaultValue'
        });

        me.formPanel.add(fieldConfig);
    }
});
//{/block}
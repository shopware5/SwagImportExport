//{namespace name=backend/swag_import_export/view/profile/grid}
//{block name="backend/swag_import_export/view/profile/grid"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.swag-import-export-profile-grid',

    title: '{s name=swag_import_export/profile/grid/title}Configuration{/s}',

    initComponent: function() {
        var me = this;

        me.dockedItems = me.buildDockedItems();
        me.columns = me.buildColumns();
        me.selModel = me.buildSelectionModel();

        me.callParent(arguments);
    },

    buildColumns: function() {
        var me = this;

        return {
            defaults: {
                menuDisabled: true,
                draggable: false
            },
            items: [{
                header: '{s name=swag_import_export/profile/grid/header_name}Name{/s}',
                dataIndex: 'name',
                flex: 1,
                renderer: me.renderName
            }, {
                header: '{s name=swag_import_export/profile/grid/header_default_profile}Default{/s}',
                dataIndex: 'default',
                width: 90,
                renderer: me.renderDefault
            }, {
                xtype: 'actioncolumn',
                header: '{s name=swag_import_export/profile/grid/header_actions}Actions{/s}',
                width: 100,
                items: [{
                    iconCls: 'sprite-magnifier',
                    handler: function(view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('showProfile', view, record);
                    },
                    getClass: function(value, meta, record) {
                        if (!record.get('default')) {
                            return 'x-hide-display';
                        }
                    }
                }, {
                    iconCls: 'sprite-pencil',
                    handler: function(view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('editProfile', view, record);
                    },
                    getClass: function(value, meta, record) {
                        if (record.get('default')) {
                            return 'x-hide-display';
                        }
                    }
                }, {
                    iconCls: 'sprite-minus-circle-frame',
                    handler: function (view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('deleteProfile', view, [record]);
                    },
                    getClass: function(value, meta, record) {
                        if (record.get('default')) {
                            return 'x-hide-display';
                        }
                    }
                }, {
                    iconCls: 'sprite-blue-document-copy',
                    cls:'duplicate',
                    handler: function (view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('duplicateProfile', view, record);
                    }
                }]
            }]
        };
    },

    buildDockedItems: function() {
        var me = this;

        return [
            me.buildMenuBar(),
            me.buildPagingBar()
        ];
    },

    buildMenuBar: function() {
        var me = this;

        return {
            xtype: 'toolbar',
            ui: 'shopware-ui',
            dock: 'top',
            items: [{
                xtype: 'button',
                text: '{s name=swag_import_export/profile/grid/button_add_profile}Add profile{/s}',
                iconCls: 'sprite-plus-circle-frame',
                handler: function() {
                    me.fireEvent('addProfile');
                }
            }, {
                xtype: 'button',
                text: '{s name=swag_import_export/profile/grid/button_delete_profile}Delete selected profile{/s}',
                iconCls: 'sprite-minus-circle-frame',
                handler: function() {
                    me.fireEvent('deleteProfile', me, me.getSelectionModel().getSelection());
                }
            }, {
                xtype: 'checkbox',
                itemId: 'defaultprofilefilter',
                margin: '0 0 0 5',
                boxLabel: '{s name=swag_import_export/profile/grid/boxlabel_hide_default_profiles}Hide default profiles{/s}',
                listeners: {
                    change: function(cb, newValue) {
                        me.fireEvent('checkboxfilterchange', cb, newValue);
                    }
                }
            }, '->', {
                xtype:'textfield',
                itemId:'searchfield',
                cls:'searchfield',
                width:170,
                emptyText: '{s name=swag_import_export/profile/grid/search}Search...{/s}',
                enableKeyEvents:true,
                checkChangeBuffer:500,
                listeners: {
                    change: function(field, newValue) {
                        me.fireEvent('searchfilterchange', field, newValue);
                    }
                }
            }]
        };
    },

    buildPagingBar: function() {
        var me = this;

        return {
            xtype: 'pagingtoolbar',
            dock: 'bottom',
            displayInfo: true,
            store: me.getStore()
        };
    },

    buildSelectionModel: function() {
        return {
            selType: 'checkboxmodel',
            showHeaderCheckbox: false,
            allowDeselect: true,
            mode: 'SINGLE'
        };
    },

    renderName: function(value, meta, record) {
        if (Ext.isEmpty(record.get('translation'))) {
            return value;
        }
        return Ext.String.format('[0] ([1])', record.get('translation'), value);
    },

    renderDefault: function(value, meta, record) {
        if (value) {
            return '<div class="sprite-tick" style="margin: 0 auto; display: block; width: 13px; height: 13px;">&nbsp;</div>';
        }
    }
});
//{/block}

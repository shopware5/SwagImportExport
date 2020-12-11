// {namespace name=backend/swag_import_export/view/session}
// {block name="backend/swag_import_export/view/manager/session"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Session', {
    extend: 'Ext.grid.Panel',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-session',
    title: '{s name="swag_import_export/manager/log/title"}Protocol{/s}',

    initComponent: function() {
        var me = this;

        me.store = me.buildStore();
        me.selModel = me.buildSelectionModel();
        me.columns = me.buildColumns();
        me.dockedItems = me.buildDockedItems();

        me.on('selectionchange', function(selModel, selected) {
            me.down('#deletebutton').setDisabled(selected.length === 0);
        });

        me.callParent(arguments);
    },

    buildStore: function() {
        return Ext.create('Shopware.apps.SwagImportExport.store.SessionList', {
            sorters: [
                { property: 'createdAt', direction: 'DESC' }
            ]
        });
    },

    buildSelectionModel: function() {
        return {
            selType: 'checkboxmodel',
            allowDeselect: true,
            mode: 'SIMPLE'
        };
    },

    buildColumns: function() {
        var me = this;

        return {
            defaults: {
                menuDisabled: true,
                draggable: false
            },
            items: [{
                xtype: 'datecolumn',
                header: '{s name="swag_import_export/manager/log/header_date"}Date{/s}',
                dataIndex: 'createdAt',
                format: 'd.m.Y H:i:s',
                flex: 1
            }, {
                header: '{s name="swag_import_export/manager/log/header_file"}File{/s}',
                dataIndex: 'fileName',
                renderer: function(value, view, record) {
                    return '<a href={url action="downloadFile"}' + '?type=' + record.get('type') + '&fileName=' + record.get('fileUrl') + ' >' + value + '</a>';
                },
                flex: 2
            }, {
                header: '{s name="swag_import_export/manager/log/header_status"}Status{/s}',
                dataIndex: 'state',
                width: 60,
                renderer: me.renderStatus
            }, {
                header: '{s name="swag_import_export/manager/log/header_type"}Type{/s}',
                dataIndex: 'type',
                width: 60,
                renderer: me.renderType
            }, {
                header: '{s name="swag_import_export/manager/log/header_profile"}Profile{/s}',
                dataIndex: 'profileName',
                flex: 1
            }, {
                header: '{s name="swag_import_export/manager/log/header_user"}User{/s}',
                dataIndex: 'username',
                width: 80
            }, {
                xtype: 'actioncolumn',
                header: '{s name="swag_import_export/manager/log/header_actions"}Actions{/s}',
                width: 80,
                items: [{
                    iconCls: 'sprite-magnifier',
                    handler: function(view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('showSessionDetails', view, record);
                    }
                }, {
                    iconCls: 'sprite-arrow-circle-315',
                    handler: function(view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('resumeSession', view, record);
                    },
                    getClass: function(value, meta, record) {
                        if (record.get('type') == 'import' && record.get('state') == 'closed') {
                            return 'x-hide-display';
                        }
                    }
                }, {
                    iconCls: 'sprite-minus-circle-frame',
                    handler: function (view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('deleteSession', view, [record]);
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
                text: '{s name="swag_import_export/manager/log/button_delete_operations"}Delete selected operation(s){/s}',
                iconCls: 'sprite-minus-circle-frame',
                itemId: 'deletebutton',
                disabled: true,
                handler: function() {
                    var selectionModel = me.getSelectionModel(),
                        records = selectionModel.getSelection();

                    if (records.length > 0) {
                        me.fireEvent('deleteSession', me, records);
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

    renderStatus: function(value) {
        var cls = 'sprite-cross';

        if (value == 'closed') {
            cls = 'sprite-tick';
        }

        return Ext.String.format(
            '<div class="[0]" style="width: 13px; height: 13px;">&nbsp;</div>',
            cls
        );
    },

    renderType: function(value) {
        if (value == 'export') {
            return '{s name="swag_import_export/manager/log/export"}export{/s}';
        }
        return '{s name="swag_import_export/manager/log/import"}import{/s}';
    }
});
// {/block}

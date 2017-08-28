//{namespace name=backend/swag_import_export/view/session}
//{block name="backend/swag_import_export/view/log/log"}
// deprecated since 2.4.2 and will be removed with 3.0.0
//{block name="backend/swag_import_export/view/manager/manager"}
Ext.define('Shopware.apps.SwagImportExport.view.log.Log', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.swag-import-export-log-log',

    title: '{s name=swag_import_export/logs/title}Logs{/s}',
    autoScroll: true,
    
    initComponent: function() {
        var me = this;

        me.columns = me.getColumns();
        me.store = me.logStore;
        me.dockedItems = [
            me.getPagingbar()
        ];
        
        me.callParent(arguments);
    },
    
    /**
     * Creates the grid columns
     *
     * @return [array] grid columns
     */
    getColumns: function () {
        var me = this;

        return [{
            xtype : 'datecolumn',
            header: '{s name=swag_import_export/logs/date}Date{/s}',
            format: 'Y-m-d H:i:s',
            dataIndex: 'logDate',
            flex: 1
        }, {
            header: '{s name=swag_import_export/logs/message}Message{/s}',
            dataIndex: 'message',
            renderer: function(v) {
                return v.replace(/\n/g,'<br>')
            },
            flex: 2
        }, {
            header: '{s name=swag_import_export/logs/status}Status{/s}',
            dataIndex: 'errorState',
            width: 60,
            renderer: me.renderErrorState
        }];
    },
    
    /**
     * Creates pagingbar shown at the bottom of the grid
     *
     * @return Ext.toolbar.Paging
     */
    getPagingbar: function () {
        var me = this;

        return Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock: 'bottom',
            displayInfo: true
        });
    },

    renderErrorState: function(value) {
        var cls = 'sprite-cross';

        if (value == false) {
            cls = 'sprite-tick';
        }

        return Ext.String.format(
            '<div class="[0]" style="width: 13px; height: 13px;">&nbsp;</div>',
            cls
        );
    }
});
//{/block}
//{/block}
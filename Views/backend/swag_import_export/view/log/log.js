/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
/**
 * Shopware SwagGiftPackaging Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagGiftPackaging
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/manager"}
Ext.define('Shopware.apps.SwagImportExport.view.log.Log', {
    extend: 'Ext.grid.Panel',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-log-log',

    title: '{s name=swag_import_export/log/log/title}Logs{/s}',
    
    autoScroll: true,
    
    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        column: {
            title: '{s name=swag_import_export/logs/title}Title{/s}',
            message: '{s name=swag_import_export/logs/message}Message{/s}',
            status: '{s name=swag_import_export/logs/error}Error{/s}',
            date: '{s name=swag_import_export/logs/date}Date{/s}'
        }
    },
    
    initComponent: function() {
        var me = this;

        me.columns = me.getColumns();
        me.store = me.logStore;
        me.dockedItems = [
            me.getPagingbar()
        ];
        
        me.callParent(arguments);
    },
    
    listeners: {
        activate: function(tab, opt){
            var me = this;
            me.logStore.reload();
        }
    },
    
    /**
     * Creates the grid columns
     *
     * @return [array] grid columns
     */
    getColumns: function () {
        var me = this;

        var columns = [{
                header: me.snippets.column.title,
                dataIndex: 'title',
                flex: 1
            }, {
                header: me.snippets.column.message,
                dataIndex: 'message',
                renderer:function(v) { return v.replace(/\n/g,'<br>') },
                flex: 1
            }, {
                header: me.snippets.column.status,
                dataIndex: 'state',
                flex: 1
            }, {
                xtype : 'datecolumn',
                header: me.snippets.column.date,
                format: 'Y-m-d H:i:s',
                dataIndex: 'logDate',
                flex: 1
            }];

        return columns;
    },
    
    /**
     * Creates pagingbar shown at the bottom of the grid
     *
     * @return Ext.toolbar.Paging
     */
    getPagingbar: function () {
        var me = this;
        var pagingbar =  Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock: 'bottom',
            displayInfo: true
        });

        return pagingbar;
    }
    
});
//{/block}

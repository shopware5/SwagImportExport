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
//{block name="backend/swag_import_export/view/manager/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Operation', {
    extend: 'Ext.container.Container',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-operation',
    title: '{s name=swag_import_export/manager/operation/title}Previous operations{/s}',
    layout: 'fit',
    style: {
        background: '#fff'
    },
    snippets: {
        file: '{s name=swag_import_export/column/file}File{/s}',
        type: '{s name=swag_import_export/column/type}Type{/s}',
        profile: '{s name=swag_import_export/column/profile}Profile{/s}',
        records: '{s name=swag_import_export/column/records}Records{/s}',
        date: '{s name=swag_import_export/column/date}Date{/s}',
        status: '{s name=swag_import_export/column/status}Status{/s}',
        resume: '{s name=swag_import_export/action/resume}Resume operation{/s}',
        download: '{s name=swag_import_export/action/download}Download file{/s}',
        deleteFile: '{s name=swag_import_export/action/delete}Delete file{/s}'
    },
    bodyPadding: 10,
    autoScroll: true,
    initComponent: function() {
        var me = this;
        me.items = [me.createGrid()];
        me.callParent(arguments);
    },
    createGrid: function() {
        var me = this;
        
        var files = Ext.create('Ext.data.Store', {
            fields: ['fileName', 'type', 'profile', 'records', 'date', 'status'],
            data: [
                { "fileName": 'Shopware-export-08-05-2014', "type": "categories", "profile": 'shopware', "records": "62", "date": "08/05/2014", "status": 'completed'},
                { "fileName": 'Shopware-export-07-04-2014', "type": "categories", "profile": 'shopware', "records": "50", "date": "08/05/2014", "status": 'closed'}
            ]
        });

        return Ext.create('Ext.grid.Panel', {
            title: me.snippets.panelTitle,
            id: 'operation-grid',
            store: files,
            multiSelect: true,
            viewConfig: {
                enableTextSelection: true
            },
            columns: me.getColumns()
        });
    },
    /**
     * Creates the grid columns
     *
     * @return [array] grid columns
     */
    getColumns: function() {
        var me = this;

        return [
            {
                header: me.snippets.file,
                dataIndex: 'fileName',
                flex: 3
            },
            {
                header: me.snippets.type,
                dataIndex: 'type',
                flex: 2
            },
            {
                header: me.snippets.profile,
                dataIndex: 'profile',
                flex: 2
            },
            {
                header: me.snippets.records,
                dataIndex: 'records',
                flex: 1
            },
            {
                header: me.snippets.date,
                dataIndex: 'date',
                flex: 2
            },
            {
                header: me.snippets.status,
                dataIndex: 'status',
                flex: 2
            },
            {
                /**
                 * Special column type which provides
                 * clickable icons in each row
                 */
                xtype:'actioncolumn',
                width:90,
                items:[
                    {
                        iconCls:'sprite-arrow-circle-315',
                        action:'resume',
                        tooltip: me.snippets.resume
                    },
                    {
                        iconCls:'sprite-inbox-download',
                        action:'download',
                        tooltip: me.snippets.download
                    },
                    {
                        iconCls:'sprite-minus-circle-frame',
                        action:'deleteFile',
                        tooltip: me.snippets.deleteFile
                    }
                ]
            }
        ];

    }

});
//{/block}
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
//{namespace name=backend/swag_gift_packaging/view/main}
//{block name="backend/swag_gift_packaging/view/main/window"}
Ext.define('Shopware.apps.SwagImportExport.view.tab.profile.Tree', {
	extend: 'Ext.tree.Panel',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-tab-profile-tree',
	
    requires: [
        'Ext.grid.plugin.CellEditing',
        'Ext.tree.plugin.TreeViewDragDrop',
        'Ext.grid.column.Action'
    ],
    title: 'Lists',
    store: 'Lists',
    hideHeaders: true,

    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'bottom',
            items: [
                {
                    iconCls: 'tasks-new-list',
                    tooltip: 'New List'
                },
                {
                    iconCls: 'tasks-delete-list',
                    id: 'delete-list-btn',
                    tooltip: 'Delete List'
                },
                {
                    iconCls: 'tasks-new-folder',
                    tooltip: 'New Folder'
                },
                {
                    iconCls: 'tasks-delete-folder',
                    id: 'delete-folder-btn',
                    tooltip: 'Delete Folder'
                }
            ]
        }
    ],

    viewConfig: {
        plugins: {
            ptype: 'tasksdragdrop',
            dragText: 'Drag to reorder',
            ddGroup: 'task'
        }
    },

    initComponent: function() {
        var me = this;
            
        /**
         * This Tree Panel's cell editing plugin
         * @property cellEditingPlugin
         * @type Ext.grid.plugin.CellEditing
         */
//        me.plugins = [me.cellEditingPlugin = Ext.create('Ext.grid.plugin.CellEditing')];
//
//        me.columns = [
//            {
//                xtype: 'treecolumn',
//                dataIndex: 'name',
//                flex: 1,
//                editor: {
//                    xtype: 'textfield',
//                    selectOnFocus: true,
//                    allowOnlyWhitespace: false
//                },
//                renderer: Ext.bind(me.renderName, me)
//            },
//            {
//                xtype: 'actioncolumn',
//                width: 24,
//                icon: 'resources/images/delete.png',
//                iconCls: 'x-hidden',
//                tooltip: 'Delete',
//                handler: Ext.bind(me.handleDeleteClick, me)
//            }
//        ];
		
        
        me.callParent(arguments);

//        me.addEvents(
//            'deleteclick',
//            'taskdrop',
//            'listdrop'
//        );

//        me.on('beforeedit', me.handleBeforeEdit, me);
//        me.relayEvents(me.getView(), ['taskdrop', 'listdrop'])

    },

    handleDeleteClick: function(gridView, rowIndex, colIndex, column, e) {
        // Fire a "deleteclick" event with all the same args as this handler
        this.fireEvent('deleteclick', gridView, rowIndex, colIndex, column, e);
    },

    handleBeforeEdit: function(editingPlugin, e) {
        return e.record.get('id') !== -1;
    },

    renderName: function(value, metaData, list, rowIndex, colIndex, store, view) {
        var tasksStore = Ext.StoreMgr.lookup('Tasks'),
            count = 0;

        (function countTasks(list) {
            count += tasksStore.queryBy(function(task, id) {
                // only show count for tasks that are not done
                return task.get('list_id') === list.get('id') && task.get('done') === false;
            }).getCount();

            list.eachChild(function(child) {
                countTasks(child);
            });
        })(list);

        return value + ' (' + count + ')';
    },

    /**
     * Triggers the list tree to refresh its view.  This is necessary in two scenarios:
     * 1) Since the lists and tasks are loaded asyncrounously, The Lists store may have finished
     *    loading before the tasks store.  In this case, the tasks data would not be available so all
     *    of the task counts would be rendered as (0).
     * 2) When a task is dragged and dropped onto a list, or when a list is deleted the task count won't automatially be updated
     *    because none of the data in the lists store actually changed (the renderer gets the count
     *    from the tasks store).
     *    
     * In both situations refreshing the lists view we ensure that the task counts are accurate.
     */
    refreshView: function() {
        // refresh the data in the view.  This will trigger the column renderers to run, making sure the task counts are up to date.
        this.getView().refresh();
    }
});
//{/block}

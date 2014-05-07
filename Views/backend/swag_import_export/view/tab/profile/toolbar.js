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
Ext.define('Shopware.apps.SwagImportExport.view.tab.profile.Toolbar', {
	extend: 'Ext.toolbar.Toolbar',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-tab-profile-toolbar',
	
	items: [
		{
			text: 'New',
			iconCls: 'tasks-new',
			menu: {
				items: [
					{
						text: 'New Task',
						iconCls: 'tasks-new'
					},
					{
						text: 'New List',
						iconCls: 'tasks-new-list'
					},
					{
						text: 'New Folder',
						iconCls: 'tasks-new-folder'
					}
				]
			}
		},
		{
			iconCls: 'tasks-delete-task',
			id: 'delete-task-btn',
			disabled: true,
			tooltip: 'Delete Task'
		},
		{
			iconCls: 'tasks-mark-complete',
			id: 'mark-complete-btn',
			disabled: true,
			tooltip: 'Mark Complete'
		},
		{
			iconCls: 'tasks-mark-active',
			id: 'mark-active-btn',
			disabled: true,
			tooltip: 'Mark Active'
		},
		'->',
		{
			iconCls: 'tasks-show-all',
			id: 'show-all-btn',
			tooltip: 'All Tasks',
			toggleGroup: 'status'
		},
		{
			iconCls: 'tasks-show-active',
			id: 'show-active-btn',
			tooltip: 'Active Tasks',
			toggleGroup: 'status'
		},
		{
			iconCls: 'tasks-show-complete',
			id: 'show-complete-btn',
			tooltip: 'Completed Tasks',
			toggleGroup: 'status'
		}

	]
});
//{/block}

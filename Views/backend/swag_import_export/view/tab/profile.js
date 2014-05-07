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
Ext.define('Shopware.apps.SwagImportExport.view.tab.Profile', {
	extend: 'Ext.container.Container',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-tab-profile',
	
    height: 450,
	
    title: '{s name=swag_import_export/tab/profile/title}Profile{/s}',

	layout: 'border',
	
	style: {
		background: '#fff'
	},
	
	bodyPadding: 10,
	
	autoScroll: true,
	
	initComponent: function() {
		var me = this;

		var store = Ext.create('Ext.data.TreeStore', {
			root: {
				expanded: true,
				children: [{
						text: "detention",
						leaf: true
					}, {
						text: "homework",
						expanded: true,
						children: [{
								text: "book report",
								leaf: true
							}, {
								text: "alegrbra",
								leaf: true
							}]
					}, {
						text: "buy lottery tickets",
						leaf: true
					}]
			}
		});

		me.items = [Ext.create('Shopware.apps.SwagImportExport.view.tab.profile.Toolbar', {
				region: 'north'
			}),
			Ext.create('Ext.tree.Panel', {
				region: 'west',
				store: store,
				viewConfig: {
					plugins: {
						ptype: 'treeviewdragdrop'
					}
				},
				height: 300,
				width: 250,
				title: 'Files',
				useArrows: true,
				dockedItems: [{
						xtype: 'toolbar',
						items: [{
								text: 'Expand All',
								handler: function() {
									tree.expandAll();
								}
							}, {
								text: 'Collapse All',
								handler: function() {
									tree.collapseAll();
								}
							}]
					}]
			})
					//,
//			{
//				xtype: 'listTree',
//				region: 'west',
//				width: 300,
//				collapsible: true,
//				split: true
//			},
//			{
//				region: 'center',
//				xtype: 'taskGrid',
//				title: 'All Lists'
//			}
		];
		me.callParent(arguments);
	}
});
//{/block}

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
		
    title: '{s name=swag_import_export/tab/profile/title}Profile{/s}',

	layout: 'border',
	
	style: {
		background: '#fff'
	},
		
	autoScroll: false,
	
	initComponent: function() {
		var me = this;

		var store = Ext.create('Shopware.apps.SwagImportExport.store.Profile', {
			autoload: true
		});

		me.items = [Ext.create('Shopware.apps.SwagImportExport.view.tab.profile.Toolbar', {
				region: 'north',
				style: {
					borderRight: '1px solid #A4B5C0',
					borderLeft: '1px solid #A4B5C0',
					borderTop: '1px solid #A4B5C0',
					borderBottom: '1px solid #A4B5C0',
				}
			}), Ext.create('Ext.tree.Panel', {
				region: 'west',
				store: store,
				viewConfig: {
					plugins: {
						ptype: 'treeviewdragdrop'
					}
				},
				title: 'Profile',
				width: 300,
				useArrows: true,
				dockedItems: [{
						xtype: 'toolbar',
						style: {
							borderRight: '1px solid #A4B5C0',
							borderLeft: '1px solid #A4B5C0',
							borderTop: '1px solid #A4B5C0',
						},
						items: [{
								text: 'Create Child Node',
								handler: function() {
								}
							}, {
								text: 'Create Attribute',
								handler: function() {
								}
							}, {
								text: 'Delete Selected',
								handler: function() {
								}
							}]
					}]
			}), Ext.create('Ext.form.Panel', {
				region: 'center',
				bodyPadding: 12,
				defaultType: 'textfield',
				border: false,
				bodyStyle: {
					border: '0 !important'
				},
				items: [{
						fieldLabel: 'Node Name',
						width: 400,
						labelWidth: 150,
						name: 'nodeName',
						allowBlank: false
					}, {
						fieldLabel: 'Shopware Column',
						xtype: 'combobox',
						emptyText: 'Select Column',
						store: ['Id', 'Description'],
						width: 400,
						labelWidth: 150,
						name: 'swColumn',
						allowBlank: false
					}],
				dockedItems: [{
						xtype: 'toolbar',
						dock: 'bottom',
						ui: 'shopware-ui',
						cls: 'shopware-toolbar',
						style: {
							backgroundColor: '#F0F2F4'
						},
						items: ['->', {
								text: 'Save',
								cls: 'primary',
								action: 'swag-import-export-manager-export'
							}]
					}]
			})
		];
		me.callParent(arguments);
	}
});
//{/block}

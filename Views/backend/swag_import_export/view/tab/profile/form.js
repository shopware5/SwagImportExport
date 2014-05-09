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
Ext.define('Shopware.apps.SwagImportExport.view.tab.profile.Form', {
	extend: 'Ext.form.Panel',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-tab-profile-form',
	
	defaultType: 'textfield',
	
    initComponent: function() {
		var me = this;
		me.items = [me.mainFields()];
	},
	
	mainFields: function() {
		var me = this;

		return Ext.create('Ext.form.FieldSet', {
			padding: 12,
			defaults: {
				labelStyle: 'font-weight: 700; text-align: right;'
			},
			items: [{
					xtype: 'container',
					padding: '0 0 8',
					items: [{
							fieldLabel: 'First Name',
							name: 'first',
							allowBlank: false
						}, {
							fieldLabel: 'Last Name',
							name: 'last',
							allowBlank: false
						}]
				}],
			dockedItems: []
		});
	}
});
//{/block}

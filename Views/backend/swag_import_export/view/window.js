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
Ext.define('Shopware.apps.SwagImportExport.view.Window', {
	
	/**
	 * Define that the order main window is an extension of the enlight application window
	 * @string
	 */
	extend: 'Enlight.app.Window',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-window',
	
    height: 450,
	
    title: '{s name=swag_import_export/window/title}Import / Export{/s}',

    initComponent:function () {
        var me = this;

        //add the order list grid panel and set the store
        me.items = [ me.createTabPanel() ];
        me.callParent(arguments);
    },
	
    createTabPanel: function() {
        var me = this;

        return Ext.create('Ext.tab.Panel', {
            name: 'main-tab',
            items: [
                Ext.create('Shopware.apps.SwagImportExport.view.tab.Profile', {
					
				})
//                , Ext.create('Shopware.apps.Order.view.detail.Detail',{
//                    title: me.snippets.details,
//                    record: me.record,
//                    paymentsStore: me.paymentsStore,
//                    shopsStore: me.shopsStore,
//                    countriesStore: me.countriesStore
//                }), Ext.create('Shopware.apps.Order.view.detail.Communication',{
//                    title: me.snippets.communication,
//                    record: me.record
//                }), Ext.create('Shopware.apps.Order.view.detail.Position', {
//                    title: me.snippets.position,
//                    record: me.record,
//                    taxStore: me.taxStore,
//                    statusStore: me.statusStore
//                }), Ext.create('Shopware.apps.Order.view.detail.Document',{
//                    record: me.record,
//                    documentTypesStore: me.documentTypesStore
//                }), Ext.create('Shopware.apps.Order.view.detail.OrderHistory', {
//                    title: me.snippets.history,
//                    historyStore: me.historyStore,
//                    record: me.record,
//                    orderStatusStore: me.orderStatusStore,
//                    paymentStatusStore:  me.paymentStatusStore
//                })
            ]
        });
    }
});
//{/block}

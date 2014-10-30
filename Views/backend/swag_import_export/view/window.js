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
 * Shopware SwagImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImportExport
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
	
    height: 600,
    
    layout: 'fit',
	
    title: '{s name=swag_import_export/window/title}Import / Export{/s}',
    
    initComponent: function() {
        var me = this;

        //add the order list grid panel and set the store
        me.items = [me.createTabPanel()];
        me.callParent(arguments);
    },
    
    /*
     * profile store
     */

    /*{if {acl_is_allowed privilege=profile}}*/
    profilesStore: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList').load(),
    /*{/if}*/

    logStore: Ext.create('Shopware.apps.SwagImportExport.store.Log'),

    createTabPanel: function() {
        var me = this;
        var aclItems = [];

        /*{if {acl_is_allowed privilege=export} OR {acl_is_allowed privilege=import}}*/
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.manager.Manager', {
            profilesStore: me.profilesStore
        }));
        /*{/if}*/

        /*{if {acl_is_allowed privilege=profile}}*/
        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.profile.Profile', {
            profilesStore: me.profilesStore
        }));
        /*{/if}*/

        aclItems.push(Ext.create('Shopware.apps.SwagImportExport.view.log.Log', {
            logStore: me.logStore
        }));

        return Ext.create('Ext.tab.Panel', {
            name: 'main-tab',
            items: aclItems
        });
    }
});
//{/block}

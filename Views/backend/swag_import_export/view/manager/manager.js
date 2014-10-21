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
//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/manager"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Manager', {
    extend: 'Ext.container.Container',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-manager',
    height: 450,
    
    title: '{s name=swag_import_export/manager/manager/title}Import / Export manager{/s}',
    layout: 'fit',
    style: {
        background: '#fff'
    },
    autoScroll: true,
    /*
     * session store
     */
    sessionStore: Ext.create('Shopware.apps.SwagImportExport.store.SessionList'),
    initComponent: function() {
        var me = this;

        me.items = [me.createTabPanel()];

        me.callParent(arguments);
    },
    
    createTabPanel: function() {
        var me = this;
        
        return Ext.create('Ext.tab.Panel', {
            name: 'manager-main-tab',
            items: [
                /*{if {acl_is_allowed privilege=export}}*/
                Ext.create('Shopware.apps.SwagImportExport.view.manager.Export', {
                    profilesStore: me.profilesStore,
                    sessionStore: me.sessionStore
                }),
                /*{/if}*/
                /*{if {acl_is_allowed privilege=import}}*/
                Ext.create('Shopware.apps.SwagImportExport.view.manager.Import', {
                    profilesStore: me.profilesStore
                }),
                /*{/if}*/
                Ext.create('Shopware.apps.SwagImportExport.view.manager.Operation', {
                    sessionStore: me.sessionStore
                })
            ]
        });
    }
});
//{/block}

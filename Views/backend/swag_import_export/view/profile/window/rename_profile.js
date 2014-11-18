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
//{namespace name=backend/swag_import_export/view/profile/window}
//{block name="backend/swag_import_export/view/profile/window/rename_profile"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.window.RenameProfile', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend: 'Ext.form.Panel',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-window',

    width: 300,
    height: 110,
    bodyPadding: 12,
    border: false,
    bodyStyle: {
        border: '0 !important'
    },
    floating: true,
    closable: true,
    modal: true,

    title: '{s name=rename_profile}Rename profile{/s}',

    initComponent: function () {
        var me = this;

        me.items = me.createItems();

        me.dockedItems = me.createDockItems();

        me.callParent(arguments);
    },

    createItems: function () {
        var me = this;
        
        return [{
                    xtype: 'textfield',
                    itemId: 'profileName',
                    fieldLabel: '{s name=profile_name}Profile name{/s}',
                    name: 'profileName',
                    value: me.store.getById(me.profileId).get('name'),
                    allowBlank: false
                }];
    },

    createDockItems: function() {
        var me = this;

        return [{
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
                            action: 'swag-import-export-manager-profile-save',
                            handler: function() {
                                if (me.getForm().isValid()) {
                                    var model = me.store.getById(me.profileId);
                                    model.set('name', me.child('#profileName').getValue());
                                    me.setLoading(true);
                                    me.store.sync({
                                        success: function() {
                                            me.combo.setValue(me.profileId);
                                            me.setLoading(false);
                                            me.close();
                                        },
                                        failure: function() {
                                            me.setLoading(false);
                                            me.close();
                                        }
                                    });
                                } else {
                                    Ext.MessageBox.show({
                                        title: me.snippets.newProfile.failureTitle,
                                        msg: me.snippets.newProfile.notAllFieldsFilledError,
                                        icon: Ext.Msg.ERROR,
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            }
                        }]
                }];
    }

});
//{/block}
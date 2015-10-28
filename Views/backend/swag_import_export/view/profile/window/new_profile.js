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
//{block name="backend/swag_import_export/view/profile/window/new_profile"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.window.NewProfile', {
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

    snippets: {
        newProfile: {
            failureTitle: '{s name="swag_import_export/profile/new_profile/failure_title"}Create New Profile Failed{/s}',
            notAllFieldsFilledError: '{s name="swag_import_export/profile/new_profile/not_all_fields_filled_error"}Not all fields are filled!{/s}'
        },
        categories: '{s name=swag_import_export/profile/type/categories}Categories{/s}',
        articles: '{s name=swag_import_export/profile/type/articles}Articles{/s}',
        articlesInStock: '{s name=swag_import_export/profile/type/articlesInStock}Articles in stock{/s}',
        articlesPrices: '{s name=swag_import_export/profile/type/articlesPrices}Articles Prices{/s}',
        articlesImages: '{s name=swag_import_export/profile/type/articlesImages}Articles Images{/s}',
        articlesTranslations: '{s name=swag_import_export/profile/type/articlesTranslations}Articles Translations{/s}',
        orders: '{s name=swag_import_export/profile/type/orders}Orders{/s}',
        mainOrders: '{s name=swag_import_export/profile/type/mainOrders}Orders main data{/s}',
        customers: '{s name=swag_import_export/profile/type/customers}Customers{/s}',
        newsletter: '{s name=swag_import_export/profile/type/newsletter}Newsletter receiver{/s}',
        translations: '{s name=swag_import_export/profile/type/translations}Translations{/s}'
    },

    width: 500,

    height: 150,

    bodyPadding: 12,

    border: false,

    bodyStyle: {
        border: '0 !important'
    },

    floating: true,

    closable: true,

    modal: true,

    title: '{s name=new_profile}New Profile{/s}',

    initComponent: function () {
        var me = this;
        me.createProfileTypeStore = me.createProfileTypeStore();

        me.items = me.createItems();

        me.dockedItems = me.createDockItems();

        me.callParent(arguments);
    },

    createProfileTypeStore: function () {
        var me = this;

        return new Ext.data.SimpleStore({
            fields: ['type', 'label'],
            data: me.getProfileType()
        });
    },

    getProfileType: function () {
        var me = this;

        return [
                ['categories', me.snippets.categories],
                ['articles', me.snippets.articles],
                ['articlesInStock', me.snippets.articlesInStock],
                ['articlesPrices', me.snippets.articlesPrices],
                ['articlesImages', me.snippets.articlesImages],
                ['articlesTranslations', me.snippets.articlesTranslations],
                ['orders', me.snippets.orders],
                ['mainOrders', me.snippets.mainOrders],
                ['customers', me.snippets.customers],
                ['newsletter', me.snippets.newsletter],
                ['translations', me.snippets.translations]
            ];
    },
    
    createItems: function() {
        var me = this;
        
        return [{
                    xtype: 'textfield',
                    itemId: 'profileName',
                    fieldLabel: 'Profile Name',
                    name: 'profileName',
                    allowBlank: false
                }, {
                    xtype: 'combobox',
                    itemId: 'type',
                    fieldLabel: 'Type',
                    emptyText: 'Select Type',
                    store: me.createProfileTypeStore,
                    name: 'type',
                    valueField: 'type',
                    displayField: 'label',
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
                                    var model = me.combo.store.add({ type: me.child('#type').getValue(), name: me.child('#profileName').getValue(), tree: "" });
                                    me.setLoading(true);
                                    me.combo.store.sync({
                                        success: function() {
                                            me.combo.setValue(model[0].get('id'));
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
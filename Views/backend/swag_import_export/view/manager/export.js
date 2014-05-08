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
//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Export', {
    extend: 'Ext.container.Container',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-export',
    title: '{s name=swag_import_export/manager/export/title}Export{/s}',
    layout: 'fit',
    bodyPadding: 10,
    autoScroll: true,
    snippets: {
        configText: '{s name=configExportText}Depending on the data set you want to export, additional configuration options may needs to be set{/s}'
    },
    
    profilesStore: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList').load(),
    
    initComponent: function() {
        var me = this;

        me.items = [me.createFormPanel()];

        me.callParent(arguments);
    },
    /**
     * Creates the main form panel for the component which
     * features all neccessary form elements
     *
     * @return [object] me.formPnl - generated Ext.form.Panel
     */
    createFormPanel: function() {
        var me = this;

        // Form panel which holds off all options
        me.formPanel = Ext.create('Ext.form.Panel', {
            bodyPadding: 20,
            border: 0,
            autoScroll: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [
                me.mainFields(), me.additionalFields()
            ],
            dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    items: ['->', {
                            text: 'Export',
                            cls: 'primary',
                            action: 'swag-import-export-manager-export'
                        }]
                }]
        });

        return me.formPanel;
    },
    /**
     * Main fields
     * 
     * @return [object] generated Ext.form.FieldSet
     */
    mainFields: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            title: 'Export configuration',
            padding: 12,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                    xtype: 'container',
                    padding: '0 0 8',
                    items: [me.createProfileCombo(), me.createFormatCombo()]
            }],
            html: '<i style="color: grey" >' + me.snippets.configText + '</i>'
        });
    },
    additionalFields: function() {
        var me = this;

        me.additionalFields = Ext.create('Ext.form.FieldSet', {
            title: 'Additional export configuration',
            padding: 12,
            hidden: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                    xtype: 'container',
                    padding: '0 0 8',
                    items: [
                        me.createVariantsCheckbox(),
                        me.createCustomerGroupCheckbox(),
                        me.createTranslationCheckbox(),
                        me.createLimit(),
                        me.createOffset()
                    ]
                }]
        });

        return me.additionalFields;
    },
    createProfileCombo: function() {
        var me = this;

        return Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: 'Select profile',
            store: me.profilesStore,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 400,
            labelWidth: 150,
            valueField: 'id',
            displayField: 'name',
            editable: false,
            name: 'profile',
            listeners: {
                scope: me,
                change: function(value) {
                    var record = me.profilesStore.getById(value.getValue());
                    var type = record.get('type');
                    if (type === 'products') {
                        me.additionalFields.show();
                    } else {
                        me.additionalFields.hide();
                    }
                }
            },
        });
    },
    createFormatCombo: function() {
        var me = this;

        var formats = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            data: [{
                    "value": "xml",
                    "name": 'XML'
                }, {
                    "value": "csv",
                    "name": 'CSV'
                }]
        });

        return Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: 'Select export format',
            store: formats,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 400,
            labelWidth: 150,
            valueField: 'value',
            displayField: 'name',
            editable: false,
            name: 'format'
        });
    },
    createVariantsCheckbox: function() {
        return Ext.create('Ext.form.field.Checkbox', {
            name: 'variants',
            fieldLabel: 'Export variants',
            inputValue: 1,
            uncheckedValue: 0,
            width: 400,
            labelWidth: 150
        });
    },
    createCustomerGroupCheckbox: function() {
        return Ext.create('Ext.form.field.Checkbox', {
            name: 'customerGroup',
            fieldLabel: 'Include customer group specific prices',
            inputValue: 1,
            uncheckedValue: 0,
            width: 400,
            labelWidth: 150
        });
    },
    createTranslationCheckbox: function() {
        return Ext.create('Ext.form.field.Checkbox', {
            name: 'translation',
            fieldLabel: 'Include translations',
            inputValue: 1,
            uncheckedValue: 0,
            width: 400,
            labelWidth: 150
        });
    },
    createLimit: function() {
        return Ext.create('Ext.form.field.Number', {
            name: 'translation',
            fieldLabel: 'Limit',
            width: 400,
            labelWidth: 150
        });
    },
    createOffset: function() {
        return Ext.create('Ext.form.field.Number', {
            name: 'offset',
            fieldLabel: 'Offset',
            width: 400,
            labelWidth: 150
        });
    }
});
//{/block}
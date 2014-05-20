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
        configText: '{s name=wag_import_export/export/config_text}Depending on the data set you want to export, additional configuration options may needs to be set{/s}',
        exportButton: '{s name=swag_import_export/export/export_button}Export{/s}',
        fieldsetMain: '{s name=swag_import_export/export/fieldset_main}Export configuration{/s}',
        fieldsetAdditional: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
        selectProfile: '{s name=swag_import_export/export/select_profile}Select profile{/s}',
        selectFormat: '{s name=swag_import_export/export/select_format}Select export format{/s}',
        variants: '{s name=swag_import_export/export/variants}Export variants{/s}',
        customerGroup: '{s name=swag_import_export/export/customer_group}Include customer group specific prices{/s}',
        translations: '{s name=swag_import_export/export/translations}Include translations{/s}',
        limit: '{s name=swag_import_export/export/limit}Limit{/s}',
        offset: '{s name=swag_import_export/export/offset}Offset{/s}'
    },
    /*
     * profile store
     */
    profilesStore: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList'),
    initComponent: function() {
        var me = this;

        me.items = [me.createFormPanel()];

        me.callParent(arguments);
    },
    /*
     * Input elements width
     */
    configWidth: 400,
    /*
     * Label of the input elements width
     */
    configLabelWidth: 150,
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
                            text: me.snippets.exportButton,
                            cls: 'primary',
                            action: 'swag-import-export-manager-export-button',
                            handler: function(view, rowIndex, colIndex, item) {
                                me.fireEvent('export', view, me.sessionStore);
                            }
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
            title: me.snippets.fieldsetMain,
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
    /**
     * Additional fields
     * 
     * @return [object] generated Ext.form.FieldSet
     */
    additionalFields: function() {
        var me = this;

        me.additionalFields = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.fieldsetAdditional,
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
    /*
     * Profile drop down
     * 
     * @return [object] generated Ext.form.field.ComboBox
     */
    createProfileCombo: function() {
        var me = this;

        return Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: me.snippets.selectProfile,
            store: me.profilesStore,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
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
            }
        });
    },
    /*
     * Format drop down
     * 
     * @return [object] generated Ext.form.field.ComboBox
     */
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
            fieldLabel: me.snippets.selectFormat,
            store: formats,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            valueField: 'value',
            displayField: 'name',
            editable: false,
            name: 'format'
        });
    },
    /*
     * Products variants checkbox
     * 
     * @return [object] generated Ext.form.field.Checkbox
     */
    createVariantsCheckbox: function() {
        var me = this;

        return Ext.create('Ext.form.field.Checkbox', {
            name: 'variants',
            fieldLabel: me.snippets.variants,
            width: me.configWidth,
            labelWidth: me.configLabelWidth
        });
    },
    /*
     * Customer group checkbox
     * 
     * @return [object] generated Ext.form.field.Checkbox
     */
    createCustomerGroupCheckbox: function() {
        var me = this;

        return Ext.create('Ext.form.field.Checkbox', {
            name: 'customerGroup',
            fieldLabel: me.snippets.customerGroup,
            width: me.configWidth,
            labelWidth: me.configLabelWidth
        });
    },
    /*
     * Translation checkbox
     * 
     * @return [object] generated Ext.form.field.Checkbox
     */
    createTranslationCheckbox: function() {
        var me = this;

        return Ext.create('Ext.form.field.Checkbox', {
            name: 'translation',
            fieldLabel: me.snippets.translations,
            width: me.configWidth,
            labelWidth: me.configLabelWidth
        });
    },
    /*
     * Limit input field
     * 
     * @return [object] generated Ext.form.field.Number
     */
    createLimit: function() {
        var me = this;

        return Ext.create('Ext.form.field.Number', {
            name: 'translation',
            fieldLabel: me.snippets.limit,
            width: me.configWidth,
            labelWidth: me.configLabelWidth
        });
    },
    /*
     * Offset input field
     * 
     * @return [object] generated Ext.form.field.Number
     */
    createOffset: function() {
        var me = this;

        return Ext.create('Ext.form.field.Number', {
            name: 'offset',
            fieldLabel: me.snippets.offset,
            width: me.configWidth,
            labelWidth: me.configLabelWidth
        });
    }
});
//{/block}
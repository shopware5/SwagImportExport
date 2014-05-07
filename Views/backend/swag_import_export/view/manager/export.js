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
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [
                me.mainFields()
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
                }]
        });
    },
    createProfileCombo: function() {
        var me = this;

        var profiles = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            data: [{
                    "value": "categories",
                    "name": 'Categories'
                }, {
                    "value": "products",
                    "name": 'Products'
                }]
        });

        return Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: 'Select profile',
            store: profiles,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 400,
            labelWidth: 150,
            margin: '10 0 0 20',
            valueField: 'value',
            displayField: 'name',
            editable: false,
            name: 'profile'
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
            fieldLabel: 'Select export format',
            store: formats,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 400,
            labelWidth: 150,
            margin: '10 0 0 20',
            valueField: 'value',
            displayField: 'name',
            editable: false,
            name: 'format'
        });
    }
});
//{/block}
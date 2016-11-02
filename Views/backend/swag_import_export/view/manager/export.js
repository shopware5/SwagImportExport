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
        exportInfoText: '{s name=swag_import_export/export/export_info}With file export, you can save information from the database in profiles, either in CSV or XML format. These profiles contain information about which data was exported along with its structure. The default profiles can be individually extended and modified with custom profiles in the configuration.{/s}',
        exportButton: '{s name=swag_import_export/export/export_button}Export{/s}',
        fieldsetAdditional: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
        selectProfile: '{s name=swag_import_export/export/select_profile}Select profile{/s}',
        selectFormat: '{s name=swag_import_export/export/select_format}Select export format{/s}',
        variants: '{s name=swag_import_export/export/variants}Export variants{/s}',
        limit: '{s name=swag_import_export/export/limit}Limit{/s}',
        offset: '{s name=swag_import_export/export/offset}Offset{/s}',
        category: '{s name=swag_import_export/export/category}Category{/s}',
        orderNumberFrom:  '{s name=order_number_From}Ordernumber from{/s}',
        dateFrom:  '{s name=date_from}Date from{/s}',
        dateTo:  '{s name=date_to}Date to{/s}',
        orderState:  '{s name=order_state}Order state{/s}',
        paymentState:  '{s name=payment_state}Payment state{/s}',
        all: '{s name=swag_import_export/profile/filter/alle}all{/s}',
        inStock: '{s name=swag_import_export/profile/filter/inStock}in stock{/s}',
        notInStock : '{s name=swag_import_export/profile/filter/notInStock}not in stock{/s}',
        inStockOnSale: '{s name=swag_import_export/profile/filter/inStockOnSale}in Stock top selling{/s}',
        notInStockOnSale: '{s name=swag_import_export/profile/filter/notInStockOnSale}not in stock top selling{/s}',
        filter: '{s name=swag_import_export/profile/filter/filter}Filter{/s}',
        lessOrEqualsMinStock: '{s name=swag_import_export/profile/filter/notInStockMinStock}less than or equal than min stock{/s}',
        customFilterLabel: '{s name=swag_import_export/profile/filter/custom}Custom filter{/s}',
        customFilterGreaterThan: '{s name=swag_import_export/profile/filter/greaterThan}Greater than{/s}',
        customFilterLessThan: '{s name=swag_import_export/profile/filter/lessThan}Less than{/s}',
        customFilterThanValuePlaceholder: '{s name=swag_import_export/profile/filter/thanValuePlaceholder}value{/s}',
        customFilterThanValueLabel: '{s name=swag_import_export/profile/filter/thanValueLabel}The filter value{/s}',
        profileHelpText: '{s name=swag_import_export/export/profile_help}The default profiles can be individually extended and modified with custom profiles in the configuration.{/s}'
    },
    
    initComponent: function() {
        var me = this;

        me.items = [
            me.createFormPanel()
        ];

        me.callParent(arguments);
    },

    /*
     * Input elements width
     */
    configWidth: 500,

    /*
     * Label of the input elements width
     */
    configLabelWidth: 150,

    /**
     * current FilterValue
     */
    filterValue: 'all',

    /**
     * Creates the main form panel for the component which
     * features all neccessary form elements
     *
     * @return [object] me.formPnl - generated Ext.form.Panel
     */
    createFormPanel: function() {
        var me = this,
            formPanelItems = [me.mainFields()];
        
        formPanelItems = formPanelItems.concat(me.additionalFields());
        
        // Form panel which holds off all options
        me.formPanel = Ext.create('Ext.form.Panel', {
            bodyPadding: 10,
            border: 0,
            autoScroll: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: formPanelItems,
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: ['->', {
                    text: me.snippets.exportButton,
                    cls: 'primary',
                    action: 'swag-import-export-manager-export-button',
                    handler: function(view) {
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

        me.mainFieldset = Ext.create('Ext.form.FieldSet', {
            padding: 12,
            border: false,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [
                    me.createIntroductionTemplate(),
                    me.createFormatCombo(),
                    me.createProfileCombo()
                ]
            }]
        });

        return me.mainFieldset;
    },

    /**
     * Additional fields
     * 
     * @return [object] generated Ext.form.FieldSet
     */
    additionalFields: function() {
        var me = this;

        me.customFilterFields = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.customFilterLabel,
            padding: 12,
            hidden: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: me.createCustomFilterItems()
            }]
        });

        me.stockField = Ext.create('Ext.form.FieldSet',{
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
                    me.createStockFilterComboBox()
                ]
            }]
        });

        me.articleFields = Ext.create('Ext.form.FieldSet', {
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
                    me.createLimit(),
                    me.createOffset(),
                    me.createCategoryTreeCombo()
                ]
            }]
        });
        
        me.orderFields = Ext.create('Ext.form.FieldSet', {
            title: me.snippets.fieldsetAdditional,
            padding: 12,
            hidden: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: me.snippets.orderNumberFrom,
                    name: 'ordernumberFrom',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'datefield',
                    fieldLabel: me.snippets.dateFrom,
                    name: 'dateFrom',
                    maxValue: new Date(),
                    submitFormat: 'd.m.Y',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'datefield',
                    fieldLabel: me.snippets.dateTo,
                    name: 'dateTo',
                    maxValue: new Date(),
                    submitFormat: 'd.m.Y',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'combobox',
                    name: 'orderstate',
                    fieldLabel: me.snippets.orderState,
                    emptyText: me.snippets.choose,
                    store: Ext.create('Shopware.store.OrderStatus'),
                    editable: false,
                    displayField: 'description',
                    valueField: 'id',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'combobox',
                    name: 'paymentstate',
                    fieldLabel: me.snippets.paymentState,
                    emptyText: me.snippets.choose,
                    store: Ext.create('Shopware.store.PaymentStatus'),
                    editable: false,
                    displayField: 'description',
                    valueField: 'id',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }]
            }]
        });

        return [
            me.articleFields,
            me.orderFields,
            me.stockField,
            me.customFilterFields
        ];
    },

    createIntroductionTemplate: function() {
        var me = this;

        return Ext.create('Ext.container.Container', {
            html: '<i style="color: grey" >' + me.snippets.exportInfoText + '</i>'
        });
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
            helpText: me.snippets.profileHelpText,
            margin: '5 0 0 0',
            valueField: 'id',
            displayField: 'name',
            editable: false,
            name: 'profile',
            pageSize: 15,
            listConfig: {
                getInnerTpl: function (value) {
                    return Ext.XTemplate(
                        '{literal}'  +
                        '<tpl if="translation">{ translation } <i>({ name })</i>' +
                        '<tpl else>{ name }</tpl>' +
                        '{/literal}');
                }
            },
            displayTpl: new Ext.XTemplate(
                '<tpl for=".">' +
                '{literal}'  +
                '{[typeof values === "string" ? values : this.getFormattedName(values)]}' +
                '<tpl if="xindex < xcount">' + me.delimiter + '</tpl>' +
                '{/literal}' +
                '</tpl>',
                {
                    getFormattedName: function(values) {
                        if (values.translation) {
                            return Ext.String.format('[0] ([1])', values.translation, values.name);
                        }
                        return values.name;
                    }
                }
            ),
            listeners: {
                scope: me,
                change: function(cb, newValue, oldValue) {
                    if (!newValue) {
                        return;
                    }

                    var record = me.profilesStore.getById(newValue);
                    var type = record.get('type');

                    me.hideFields();

                    me.resetAdditionalFields(oldValue);

                    if (type === 'articles') {
                        me.articleFields.show();
                    } else if (type === 'orders' || type == 'mainOrders') {
                        me.orderFields.show();
                    } else if (type === 'articlesInStock') {
                        me.stockField.show();
                        if(me.filterValue === 'custom'){
                            me.customFilterFields.show();
                        }
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
                value: 'xml',
                name: 'XML'
            }, {
                value: 'csv',
                name: 'CSV'
            }]
        });

        return Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: me.snippets.selectFormat,
            margin: '20 0 0 0',
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

    /**
     * Returns category dropdown
     * @returns [ Shopware.form.field.ComboTree ]
     */
    createCategoryTreeCombo: function() {
        var me = this;

        var treeStore = Ext.create('Shopware.apps.Category.store.Tree').load();

        me.categoryTreeCombo = {
            fieldLabel: me.snippets.category,
            labelStyle: 'font-weight: 700; text-align: left;',
            xtype: 'combotree',
            allowBlank: true,
            store: treeStore,
            forceSelection: false,
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            name: 'category'
        };

        return me.categoryTreeCombo;
    },

    createStockFilterComboBox:function(){
        var me = this;

        me.stockFilter = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            data: [
                { "value": "all", "name": me.snippets.all },
                { "value": "inStock", "name": me.snippets.inStock },
                { "value": "notInStock", "name": me.snippets.notInStock },
                { "value": "inStockOnSale", "name": me.snippets.inStockOnSale },
                { "value": "notInStockOnSale", "name": me.snippets.notInStockOnSale },
                { "value": "notInStockMinStock", "name": me.snippets.lessOrEqualsMinStock },
                { "value": "custom", "name": me.snippets.customFilterLabel}
            ]
        });

        me.stockFilterComboBox = {
            fieldLabel: me.snippets.filter,
            labelStyle: 'font-weight: 700; text-align: left;',
            xtype: 'combobox',
            allowBlank: true,
            store: me.stockFilter,
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            valueField: 'value',
            displayField: 'name',
            value: me.stockFilter.getAt(0),
            name: 'stockFilter',
            listeners: {
                change: function(combo, newValue, oldValue, eOpts) {
                    if (newValue === 'custom') {
                        me.customFilterFields.show();
                    } else {
                        me.customFilterFields.hide();
                    }
                    me.filterValue = newValue;
                }
            }
        };

        return me.stockFilterComboBox;
    },

    createCustomFilterItems: function() {
        var me = this;

        var greaterLessFilter = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            data: [
                { value: 'greaterThan', name: me.snippets.customFilterGreaterThan },
                { value: "lessThan", name: me.snippets.customFilterLessThan }
            ]
        });

        me.customFilterCombo = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: me.snippets.filter,
            labelStyle: 'font-weight: 700; text-align: left;',
            allowBlank: false,
            store: greaterLessFilter,
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            valueField: 'value',
            displayField: 'name',
            value: greaterLessFilter.getAt(0),
            name: 'customFilterCombo'
        });

        me.filterThanValue = Ext.create('Ext.form.field.Text', {
            name: 'filterThanValue',
            emptyText: me.snippets.customFilterThanValuePlaceholder,
            fieldLabel: me.snippets.customFilterThanValueLabel,
            allowBlank: false,
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            margin: '0'
        });

        return [
            me.customFilterCombo,
            me.filterThanValue
        ];
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
     * Limit input field
     * 
     * @return [object] generated Ext.form.field.Number
     */
    createLimit: function() {
        var me = this;

        return Ext.create('Ext.form.field.Number', {
            name: 'limit',
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
    },

    /*
     * Hides all additional fields
     */
    hideFields: function() {
        var me = this;

        me.articleFields.hide();
        me.orderFields.hide();
        me.stockField.hide();
        me.customFilterFields.hide();
    },

    resetAdditionalFields: function(oldValue) {
        var me = this,
            fieldSet = null,
            record, oldType;

        if (Ext.isEmpty(oldValue)) {
            return;
        }

        record = me.profilesStore.getById(oldValue);
        oldType = record.get('type');

        switch (oldType) {
            case 'articles':
                fieldSet = me.articleFields;
                break;
            case 'articlesInStock':
                fieldSet = me.orderFields;
                break;
            case 'orders':
                fieldSet = me.customFilterFields;
                break;
            default:
                return;
        }

        Ext.each(fieldSet.query('field'), function(field) {
            field.reset();
        });
    }
});
//{/block}
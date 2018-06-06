/* global Shopware */

// {namespace name=backend/swag_import_export/view/main}
// {block name="backend/swag_import_export/view/manager/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Export', {
    extend: 'Ext.container.Container',

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-export',
    title: '{s name=swag_import_export/manager/export/title}Export{/s}',
    layout: 'fit',
    autoScroll: true,

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
            bodyPadding: 15,
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
                    text: '{s name=swag_import_export/export/export_button}Export{/s}',
                    cls: 'primary',
                    action: 'swag-import-export-manager-export-button',
                    handler: function(view) {
                        me.fireEvent('export', view);
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
        var me = this,
            factory = Ext.create('Shopware.attribute.SelectionFactory'),
            items;

        me.customFilterFields = Ext.create('Ext.form.FieldSet', {
            title: '{s name=swag_import_export/profile/filter/custom}Custom filter{/s}',
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

        me.stockField = Ext.create('Ext.form.FieldSet', {
            title: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
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
            title: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
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
            title: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
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
                    fieldLabel: '{s name=order_number_From}Ordernumber from{/s}',
                    name: 'ordernumberFrom',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'datefield',
                    fieldLabel: '{s name=date_from}Date from{/s}',
                    name: 'dateFrom',
                    maxValue: new Date(),
                    submitFormat: 'd.m.Y',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'datefield',
                    fieldLabel: '{s name=date_to}Date to{/s}',
                    name: 'dateTo',
                    maxValue: new Date(),
                    submitFormat: 'd.m.Y',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'combobox',
                    name: 'orderstate',
                    fieldLabel: '{s name=order_state}Order state{/s}',
                    emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
                    store: Ext.create('Shopware.store.OrderStatus'),
                    displayField: 'description',
                    valueField: 'id',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }, {
                    xtype: 'combobox',
                    name: 'paymentstate',
                    fieldLabel: '{s name=payment_state}Payment state{/s}',
                    emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
                    store: Ext.create('Shopware.store.PaymentStatus'),
                    displayField: 'description',
                    valueField: 'id',
                    labelWidth: me.configLabelWidth,
                    width: me.configWidth
                }]
            }]
        });

        items = [
            me.articleFields,
            me.orderFields,
            me.stockField,
            me.customFilterFields
        ];

        me.customerFields = Ext.create('Ext.form.FieldSet', {
            title: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
            padding: 12,
            hidden: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [
                    Ext.create('Shopware.form.field.CustomerStreamSingleSelection', {
                        fieldLabel: '{s name="swag_import_export/export/customer_stream"}{/s}',
                        name: 'customerStreamId',
                        helpText: '{s name="swag_import_export/export/customer_stream_help"}{/s}',
                        store: factory.createEntitySearchStore('Shopware\\Models\\CustomerStream\\CustomerStream')
                    })
                ]
            }]
        });

        items.push(me.customerFields);

        me.customerCompleteFields = Ext.create('Ext.form.FieldSet', {
            title: '{s name=swag_import_export/export/fieldset_additional}Additional export configuration{/s}',
            padding: 12,
            hidden: true,
            layout: 'anchor',
            defaults: {
                anchor: '100%',
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                layout: 'anchor',
                items: [
                    Ext.create('Shopware.form.field.CustomerSingleSelection', {
                        anchor: '100%',
                        fieldLabel: '{s name="swag_import_export/export/customer_selection"}{/s}',
                        helpText: '{s name="swag_import_export/export/customer_selection_help"}{/s}',
                        store: Ext.create('Shopware.attribute.SelectionFactory').createEntitySearchStore('Shopware\\Models\\Customer\\Customer'),
                        labelWidth: 150,
                        name: 'customerId'
                    })
                ]
            }]
        });

        items.push(me.customerCompleteFields);

        return items;
    },

    createIntroductionTemplate: function() {
        return Ext.create('Ext.container.Container', {
            html: '<i style="color: grey" >' + '{s name=swag_import_export/export/export_info}With file export, you can save information from the database in profiles, either in CSV or XML format. These profiles contain information about which data was exported along with its structure. The default profiles can be individually extended and modified with custom profiles in the configuration.{/s}' + '</i>'
        });
    },

    /*
     * Profile drop down
     *
     * @return [object] generated Ext.form.field.ComboBox
     */
    createProfileCombo: function() {
        var me = this;

        me.profileCombo = Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: '{s name=swag_import_export/export/select_profile}Select profile{/s}',
            store: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList', {
                sorters: [
                    { property: 'name', direction: 'ASC' }
                ],
                listeners: {
                    load: function(store, records) {
                        if (records.length === 0) {
                            store.add({
                                id: -1,
                                name: '{s name=swag_import_export/profile/no_data}No profiles found{/s}'
                            });
                        }
                    }
                }
            }),
            labelStyle: 'font-weight: 700; text-align: left;',
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            helpText: '{s name=swag_import_export/export/profile_help}The default profiles can be individually extended and modified with custom profiles in the profiles menu.{/s}',
            margin: '5 0 0 0',
            valueField: 'id',
            displayField: 'name',
            queryMode: 'remote',
            forceSelection: true,
            name: 'profile',
            emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
            matchFieldWidth: false,
            minChars: 3,
            listConfig: {
                width: 450,
                getInnerTpl: function (value) {
                    return Ext.XTemplate(
                        '{literal}' +
                        '<tpl if="translation">{ name } <i>({ translation })</i>' +
                        '<tpl else>{ name }</tpl>' +
                        '{/literal}');
                }
            },
            displayTpl: new Ext.XTemplate(
                '<tpl for=".">' +
                '{literal}' +
                '{[typeof values === "string" ? values : this.getFormattedName(values)]}' +
                '<tpl if="xindex < xcount">' + me.delimiter + '</tpl>' +
                '{/literal}' +
                '</tpl>',
                {
                    getFormattedName: function(values) {
                        if (values.translation) {
                            return Ext.String.format('[0] ([1])', values.name, values.translation);
                        }
                        return values.name;
                    }
                }
            ),
            listeners: {
                scope: me,
                change: {
                    buffer: 500,
                    fn: function(combo, newValue) {
                        var store = combo.getStore(),
                            searchString;

                        if (Ext.isEmpty(newValue)) {
                            combo.lastQuery = '';
                            store.filters.removeAtKey('search');
                            store.load();
                        } else if (Ext.isString(newValue)) {
                            searchString = Ext.String.trim(newValue);

                            // scroll the store to first page
                            store.currentPage = 1;
                            // Loads the store with a special filter
                            store.filter([
                                { id: 'search', property: 'name', value: '%' + searchString + '%', expression: 'LIKE' }
                            ]);
                        }
                    }
                },
                select: function(cb, selection) {
                    if (Ext.isEmpty(selection)) {
                        return;
                    }

                    var record = selection[0],
                        type = record.get('type');

                    me.resetAdditionalFields();

                    me.hideFields();

                    if (me.down('combo[name=format]').readOnly === true) {
                        me.down('combo[name=format]').setReadOnly(false);
                    }

                    if (type === 'articles') {
                        me.articleFields.show();
                    } else if (type === 'orders' || type == 'mainOrders') {
                        me.orderFields.show();
                    } else if (type === 'articlesInStock') {
                        me.stockField.show();
                        if (me.filterValue === 'custom') {
                            me.customFilterFields.show();
                        }
                    } else if (type === 'customers' || type === 'addresses') {
                        me.customerFields.show();
                    } else if (type === 'customersComplete') {
                        me.down('combo[name=format]').setValue('xml');
                        me.down('combo[name=format]').setReadOnly(true);
                        me.customerCompleteFields.show();
                    }
                }
            }
        });

        me.profileFilterCheckbox = Ext.create('Ext.form.field.Checkbox', {
            margin: '7 0 0 10',
            boxLabel: '{s name=swag_import_export/manager/hide_default_profiles}Hide default profiles{/s}',
            listeners: {
                change: function(cb, newValue) {
                    var store = me.profileCombo.getStore();
                    me.resetAdditionalFields();
                    me.hideFields();

                    store.currentPage = 1;

                    if (newValue) {
                        store.filters.add(
                            { id: 'default', property: 'default', value: false }
                        );
                    } else {
                        store.filters.removeAtKey('default');
                    }

                    if (me.profileCombo.isDirty()) {
                        return me.profileCombo.clearValue();
                    }
                    store.load();
                }
            }
        });

        me.profileFieldContainer = Ext.create('Ext.form.FieldContainer', {
            layout: 'hbox',
            width: 800,
            items: [
                me.profileCombo,
                me.profileFilterCheckbox
            ]
        });

        return me.profileFieldContainer;
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
            fieldLabel: '{s name=swag_import_export/export/select_format}Select export format{/s}',
            margin: '20 0 0 2',
            store: formats,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            valueField: 'value',
            displayField: 'name',
            editable: false,
            name: 'format',
            emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}'
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
            fieldLabel: '{s name=swag_import_export/export/category}Category{/s}',
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

    createStockFilterComboBox: function() {
        var me = this;

        me.stockFilter = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            data: [
                { 'value': 'all', 'name': '{s name=swag_import_export/profile/filter/alle}all{/s}' },
                { 'value': 'inStock', 'name': '{s name=swag_import_export/profile/filter/inStock}in stock{/s}' },
                { 'value': 'notInStock', 'name': '{s name=swag_import_export/profile/filter/notInStock}not in stock{/s}' },
                { 'value': 'inStockOnSale', 'name': '{s name=swag_import_export/profile/filter/inStockOnSale}in Stock top selling{/s}' },
                { 'value': 'notInStockOnSale', 'name': '{s name=swag_import_export/profile/filter/notInStockOnSale}not in stock top selling{/s}' },
                { 'value': 'notInStockMinStock', 'name': '{s name=swag_import_export/profile/filter/notInStockMinStock}less than or equal than min stock{/s}' },
                { 'value': 'custom', 'name': '{s name=swag_import_export/profile/filter/custom}Custom filter{/s}' }
            ]
        });

        me.stockFilterComboBox = {
            fieldLabel: '{s name=swag_import_export/profile/filter/filter}Filter{/s}',
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
                { value: 'greaterThan', name: '{s name=swag_import_export/profile/filter/greaterThan}Greater than{/s}' },
                { value: 'lessThan', name: '{s name=swag_import_export/profile/filter/lessThan}Less than{/s}' }
            ]
        });

        me.customFilterCombo = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: '{s name=swag_import_export/profile/filter/filter}Filter{/s}',
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
            emptyText: '{s name=swag_import_export/profile/filter/thanValuePlaceholder}value{/s}',
            fieldLabel: '{s name=swag_import_export/profile/filter/thanValueLabel}The filter value{/s}',
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
            fieldLabel: '{s name=swag_import_export/export/variants}Export variants{/s}',
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
            fieldLabel: '{s name=swag_import_export/export/limit}Limit{/s}',
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
            fieldLabel: '{s name=swag_import_export/export/offset}Offset{/s}',
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
        if (me.customerFields) {
            me.customerFields.hide();
        }
        me.customerCompleteFields.hide();
    },

    resetAdditionalFields: function() {
        var me = this,
            activeFieldset = me.mainFieldset.nextSibling('fieldset{ isVisible() }');

        if (activeFieldset) {
            Ext.each(activeFieldset.query('field'), function(field) {
                // combotree custom field needs special work to reset
                if (field.getXType() === 'combotree') {
                    if (field.treePanel) {
                        field.treePanel.getSelectionModel().deselectAll();
                        field.treePanel.collapseAll();
                    }
                    field.setFieldValue(null, '');
                } else {
                    field.reset();
                }
            });
        }
    }
});
// {/block}

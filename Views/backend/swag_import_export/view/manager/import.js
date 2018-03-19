//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Import', {
    extend: 'Ext.container.Container',

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-import',
    title: '{s name=swag_import_export/manager/import/title}Import{/s}',
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
     * Creates the main form panel for the component which
     * features all necessary form elements
     *
     * @return { Ext.form.Panel }
     */
    createFormPanel: function() {
        var me = this;

        // Form panel which holds off all options
        me.formPanel = Ext.create('Ext.form.Panel', {
            bodyPadding: 15,
            border: 0,
            autoScroll: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [
                me.createMainFieldset()
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: ['->', {
                    text: '{s name=swag_import_export/manager/import/import_button}Start import{/s}',
                    cls: 'primary',
                    action: 'swag-import-export-manager-import-button'
                }]
            }]
        });

        return me.formPanel;
    },

    createMainFieldset: function() {
        var me = this;

        me.mainFieldset = Ext.create('Ext.form.FieldSet', {
            padding: 12,
            border: false,
            defaults: {
                anchor: '100%',
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [
                    me.createInfoText(),
                    me.createInfoBox(),
                    me.createDropZone(),
                    me.createFileInput(),
                    me.createSelectFile(),
                    me.createProfileCombo()
                ]
            }]
        });

        return me.mainFieldset;
    },

    createInfoText: function() {
        var me = this;

        return Ext.create('Ext.container.Container', {
            margin: '0 0 20 0',
            html: '<i style="color: grey" >' + "{s name=swag_import_export/manager/import/import_description}With file import, you are able to extract information from CSV and XML documents and save it in your database using profiles. These profiles contain information about which data is imported along with its structure. The default profiles can be individually extended and modified with custom profiles in the profiles menu.{/s}" + '</i>'
        });
    },

    createInfoBox: function() {
        var me = this;

        return Shopware.Notification.createBlockMessage('{s name=swag_import_export/manager/import/import_notice}Warning: Importing can permanently overwrite existing data and database structures!{/s}', 'notice')
    },

    /**
     * Creates a new upload drop zone which uploads the dropped files
     * to the server and adds them to the active album
     *
     * @return { Ext.form.FieldSet }
     */
    createDropZone: function() {
        var me = this,
            id = Ext.id();

        me.dropZone = Ext.create('Shopware.app.FileUpload', {
            padding: '10 0 0 0',
            requestURL: '{url controller="swagImportExport" action="uploadFile"}',
            hideOnLegacy: true,
            maxAmount: 1,
            showInput: false,
            checkType: false,
            checkAmount: true,
            enablePreviewImage: false,
            dropZoneText: '{s name=swag_import_export/manager/import/drag_and_drop}SELECT FILE USING DRAG + DROP{/s}',
            height: 100,
            generatedId: id,
            html: '<div id="'+ id +'" style="display: none;"></div>'
        });

        return me.dropZone;
    },

    /**
     * Returns hidden file field
     * 
     * @returns { Ext.form.TextField }
     */
    createFileInput: function(){
        var me = this;
        
        return {
            xtype: 'textfield',
            itemId: 'swag-import-export-file',
            name: 'importFile',
            hidden: true,
            listeners: {
                change: function(element, value, eOpts) {
                    me.findProfile(value);

                    var dropZoneContainerEl = Ext.get(me.dropZone.generatedId);
                    dropZoneContainerEl.update('<b>Selected: ' + value + '</b> ');

                    if (!dropZoneContainerEl.isVisible()) {
                        dropZoneContainerEl.slideIn('t', {
                            duration: 200,
                            easing: 'easeIn',
                            listeners: {
                                afteranimate: function() {
                                    dropZoneContainerEl.highlight();
                                    dropZoneContainerEl.setWidth(null);
                                }
                            }
                        });
                    } else {
                        dropZoneContainerEl.highlight();
                    }
                }
            }
        };
    },

    /**
     * @returns { Ext.form.field.File }
     */
    createSelectFile: function() {
        var me = this;

        me.addBtn = Ext.create('Ext.form.field.File', {
            emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
            margin: '5 0 0 2',
            buttonText: '{s name=swag_import_export/manager/import/choose_button}Choose{/s}',
            buttonConfig: {
                cls: Ext.baseCSSPrefix + 'form-mediamanager-btn small secondary',
                iconCls: 'sprite-plus-circle-frame'
            },
            name: 'fileId',
            itemId: 'importSelectFile',
            width: me.configWidth,
            labelWidth: me.configLabelWidth,
            fieldLabel: '{s name=swag_import_export/manager/import/select_file}Select file{/s}',
            listeners: {
                change: function(element, value, eOpts) {
                    me.findProfile(value);
                }
            }
        });
        
        return me.addBtn;
    },

    /**
     * Returns profile combo box
     * 
     * @returns Ext.form.field.ComboBox
     */
    createProfileCombo: function() {
        var me = this;

        me.profileCombo = Ext.create('Ext.form.field.ComboBox', {
            allowBlank: false,
            fieldLabel: '{s name=swag_import_export/manager/import/select_profile}Select profile{/s}',
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
                        } else {
                            var record = store.findRecord('type', 'customersComplete');

                            if (record) {
                                store.remove([record]);
                            }
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
            name: 'profile',
            queryMode: 'remote',
            forceSelection: true,
            emptyText: '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
            matchFieldWidth: false,
            minChars: 3,
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
                            return Ext.String.format('[0] ([1])', values.name, values.translation);
                        }
                        return values.name;
                    }
                }
            ),
            listConfig: {
                width: 450,
                getInnerTpl: function (value) {
                    return Ext.XTemplate(
                        '{literal}'  +
                        '<tpl if="translation">{ name } <i>({ translation })</i>' +
                        '<tpl else>{ name }</tpl>' +
                        '{/literal}');
                }
            },
            listeners   : {
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

                            //scroll the store to first page
                            store.currentPage = 1;
                            //Loads the store with a special filter
                            store.filter([
                                { id: 'search', property: 'name', value: '%' + searchString + '%', expression: 'LIKE' }
                            ]);
                        }
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

                    if (newValue) {
                        store.filter([
                            { id: 'default', property: 'default', value: false }
                        ]);
                    } else {
                        store.filters.removeAtKey('default');
                        store.currentPage = 1;
                        if (me.profileCombo.isDirty()) {
                            return me.profileCombo.clearValue();
                        }
                        store.load();
                    }
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

    /**
     * Finds profile and preselect if exists
     * 
     * @param { string } value
     */
    findProfile: function(value) {
        var me = this,
            profileStore = me.profileCombo.getStore(),
            parts = value.split('-'),
            index,
            record,
            i;

        for (i = 0; i < parts.length; i++) {
            index = profileStore.find('name', parts[i]);
            
            if (index !== -1) {
                if (me.profileCombo.getValue() == undefined) {
                    record = profileStore.getAt(index);
                    me.profileCombo.setValue(record.get('id'));
                }
                return;
            }
        }
    }

});
//{/block}
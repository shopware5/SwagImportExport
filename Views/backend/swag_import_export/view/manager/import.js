
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
    style: {
        background: '#fff'
    },
    bodyPadding: 10,
    autoScroll: true,
    snippets: {
        configText: '{s name=swag_import_export/manager/import/config_import_text}New imports should always be tested in advance in a test environment. Before importing any file in your \n\
                    productive environment, carry out a complete backup of your datebase, so that in the event of a failing import, it can be reinstalled easily. \n\
                    Depending on you server system and the size of the file, the import could take quite a while.{/s}',
        configTitle: "{s name=swag_import_export/manager/import/config_title}Import configuration{/s}",
        selectProfile: "{s name=swag_import_export/manager/import/select_profile}Select profile{/s}",
        dragAndDrop: "{s name=swag_import_export/manager/import/drag_and_drop_title}Drag & Drop{/s}",
        dragAndDropFile: "{s name=swag_import_export/manager/import/drag_and_drop}SELECT FILE USING DRAG + DROP{/s}",
        selectFile: "{s name=swag_import_export/manager/import/select_file}Select file{/s}",
        choose:  '{s name=swag_import_export/manager/import/choose}Please choose{/s}',
        chooseButton:  '{s name=swag_import_export/manager/import/choose_button}Choose{/s}'
    },
    
    initComponent: function() {
        var me = this;

        me.items = [me.createFormPanel()];

        me.callParent(arguments);
    },
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
            bodyPadding: 20,
            border: 0,
            autoScroll: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [
                me.createDropZone(),
                me.createConfigurationFieldset()
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: ['->', {
                    text: 'Import',
                    cls: 'primary',
                    action: 'swag-import-export-manager-import-button'
                }]
            }]
        });

        return me.formPanel;
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
            requestURL: '{url controller="swagImportExport" action="uploadFile"}',
            hideOnLegacy: true,
            maxAmount: 1,
            showInput: false,
            checkType: false,
            checkAmount: true,
            enablePreviewImage: false,
            dropZoneText: me.snippets.dragAndDropFile,
            height: 100,
            generatedId: id,
            html: '<div id="'+ id +'" style="margin-top: 10px; display: none;"></div>'
        });

        return Ext.create('Ext.form.FieldSet', {
            title: me.snippets.dragAndDrop,
            padding: 12,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [
                    me.dropZone
                ]
            }]
        });

    },

    /**
     * @returns { Ext.form.FieldSet }
     */
    createConfigurationFieldset: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            title: me.snippets.configTitle,
            padding: 12,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            html: '<i style="color: grey" >' + me.snippets.configText + '</i>',
            items: [{
                xtype: 'container',
                padding: '0 0 8',
                items: [
                    me.createFileInput(),
                    me.createSelectFile(),
                    me.createProfileCombo()
                ]
            }]
        });
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
            emptyText: me.snippets.choose,
            buttonText: me.snippets.chooseButton,
            name: 'fileId',
            itemId: 'importSelectFile',
            width: 550,
            labelWidth: 150,
            fieldLabel: me.snippets.selectFile,
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
            fieldLabel: me.snippets.selectProfile,
            store: me.profilesStore,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 500,
            labelWidth: 150,
            valueField: 'id',
            displayField: 'name',
            editable: false,
            name: 'profile',
            pageSize: 15,
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
            listConfig: {
                getInnerTpl: function (value) {
                    return Ext.XTemplate(
                        '{literal}'  +
                        '<tpl if="translation">{ translation } <i>({ name })</i>' +
                        '<tpl else>{ name }</tpl>' +
                        '{/literal}');
                }
            }
        });
        
        return me.profileCombo;
    },

    /**
     * Finds profile and preselect if exists
     * 
     * @param { string } value
     */
    findProfile: function(value) {
        var me = this,
            parts = value.split('-'),
            index,
            record,
            i;

        for (i = 0; i < parts.length; i++) {
            index = me.profilesStore.find('name', parts[i]);
            
            if (index !== -1) {
                if (me.profileCombo.getValue() == undefined) {
                    record = me.profilesStore.getAt(index);
                    me.profileCombo.setValue(record.get('id'));
                }
                return;
            }
        }
    }

});
//{/block}
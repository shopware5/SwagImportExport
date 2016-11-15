//{namespace name=backend/swag_import_export/view/profile/window}
//{block name="backend/swag_import_export/view/profile/window/mappings"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.window.Mappings', {
	
	/**
	 * Define that the order main window is an extension of the enlight application window
	 * @string
	 */
	extend: 'Enlight.app.Window',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-mapping-window',
	
    width: 500,
    height: 400,
    
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    
    title: '{s name=swag_import_export/profile/window/conversion}Conversions{/s}',

    initComponent:function () {
        var me = this;
        
        me.selectedItem = null;
        
        //add the order list grid panel and set the store
        me.items = [
            me.createGridPanel(),
            me.createEditorsPanel()
        ];
        me.callParent(arguments);
    },
    
    createEditorsPanel: function() {
        var me = this;
        
        me.exportEditor = Ext.create('Ext.ux.aceeditor.Panel', {
            flex: 2,
            disabled: true,
            title: '{s name=swag_import_export/profile/window/export_conversion}Export conversion{/s}',
            sourceCode: '',
            parser: 'smarty'
        });
        me.importEditor = Ext.create('Ext.ux.aceeditor.Panel', {
            flex: 2,
            disabled: true,
            title: '{s name=swag_import_export/profile/window/import_conversion}Import conversion{/s}',
            sourceCode: '',
            parser: 'smarty'
        });

        return Ext.create('Ext.panel.Panel', {
            flex: 1,
            border: false,
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            items: [me.exportEditor, me.importEditor],
            dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    style: {
                        backgroundColor: '#F0F2F4'
                    },
                    items: ['->', {
                            text: '{s name=swag_import_export/profile/window/save}Save{/s}',
                            cls: 'primary',
                            action: 'swag-import-export-manager-profile-save',
                            handler: function() {
                                if (me.selectedItem !== null) {
                                    me.selectedItem.set('exportConversion', me.exportEditor.getValue());
                                    me.selectedItem.set('importConversion', me.importEditor.getValue());
                                    me.fireEvent('updateConversion', me.conversionsGrid.getStore(), true);
                                }
                            }
                        }]
                }]
        });
    },
    
    createGridPanel: function() {
        var me = this,
            store = Ext.create('Shopware.apps.SwagImportExport.store.Conversion');

        store.getProxy().setExtraParam('profileId', me.profileId);
        store.load();

        me.rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 2,
            autoCancel: true,
            listeners: {
                canceledit: function(editor, e) {
                    if (Ext.isEmpty(e.record.get('id'))) {
                        e.store.remove(e.record);
                    }
                }
            }
        });

        // create the Grid
        me.conversionsGrid = Ext.create('Ext.grid.Panel', {
            flex: 2,
            store: store,
            plugins: [
                me.rowEditor
            ],
            listeners: {
                edit: function(editor, e) {
                    me.fireEvent('updateConversion', me.conversionsGrid.getStore());
                }
            },
            style: {
                borderTop: '1px solid #A4B5C0'
            },
            viewConfig: {
                enableTextSelection: false,
                stripeRows: true
            },
            tbar: me.createGridToolbar(),
            selModel: me.getGridSelModel(),
            columns: [{
                    text: '{s name=swag_import_export/profile/window/shopware_field}Shopware field{/s}',
                    flex: 1,
                    sortable: true,
                    dataIndex: 'variable',
                    editor: {
                        xtype: 'combobox',
                        editable: false,
                        emptyText: '{s name=swag_import_export/profile/window/select_column}Select column{/s}',
                        queryMode: 'local',
                        store: Ext.create('Shopware.apps.SwagImportExport.store.Column').load({ params: { profileId: me.profileId, adapter: 'default' } }),
                        valueField: 'id',
                        displayField: 'name',
                        width: 400,
                        labelWidth: 150,
                        name: 'swColumn',
                        allowBlank: false
                    }
                }, {
                    xtype: 'actioncolumn',
                    width: 90,
                    items: [
                        {
                            iconCls: 'sprite-minus-circle-frame',
                            action: 'deleteConversion',
                            tooltip: '{s name=swag_import_export/profile/window/delete_mapping}Delete conversion{/s}',
                            handler: function(view, rowIndex, colIndex, item) {
                                me.fireEvent("deleteConversion", me.conversionsGrid.getStore(), rowIndex);
                            }
                        }]
                }]
        });

        return me.conversionsGrid;
    },
	
	/**
     * Creates the grid selection model for checkboxes
     *
     * @return [Ext.selection.CheckboxModel] grid selection model
     */
    getGridSelModel: function() {
        var me = this;
        
        var selModel = Ext.create('Ext.selection.CheckboxModel', {
            listeners: {
                // Unlocks the save button if the user has checked at least one checkbox
                selectionchange: function(sm, selections) {
                    me.deleteConversionsButton.setDisabled(selections.length === 0);
                    if (selections.length === 1) {
                        me.selectedItem = selections[0];
                        me.exportEditor.setValue(me.selectedItem.get('exportConversion'));
                        me.importEditor.setValue(me.selectedItem.get('importConversion'));
                        me.exportEditor.setDisabled(false);
                        me.importEditor.setDisabled(false);
                    } else {
                        me.selectedItem = null;
                        me.exportEditor.setValue('');
                        me.importEditor.setValue('');
                        me.exportEditor.setDisabled(true);
                        me.importEditor.setDisabled(true);
                    }
                }
            }
        });

        $sel = selModel;

        return selModel;
    },
	
	/**
     * Creates the toolbar for the position grid.
     * @return Ext.toolbar.Toolbar
     */
    createGridToolbar: function() {
        var me = this;

        me.deleteConversionsButton = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle-frame',
            text: '{s name=swag_import_export/profile/window/delete_selected}Delete selected{/s}',
            disabled: true,
            action: 'deleteConversion',
            handler: function() {
                me.fireEvent('deleteMultipleConversions', me.conversionsGrid.getStore(), me.conversionsGrid.getSelectionModel());
            }
        });

        me.addConversionButton = Ext.create('Ext.button.Button', {
            iconCls:'sprite-plus-circle-frame',
            text: '{s name=swag_import_export/profile/window/add_new}Add new{/s}',
            action:'addConversion',
            handler: function() {
                me.fireEvent('addConversion', me.conversionsGrid, me.rowEditor);
            }
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock:'top',
            ui: 'shopware-ui',
            items:[
                me.addConversionButton,
                me.deleteConversionsButton
            ]
        });
    }
});
//{/block}

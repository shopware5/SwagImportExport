//{namespace name=backend/swag_import_export/view/profile}
//{block name="backend/swag_import_export/view/profile/profile"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.Profile', {
    extend: 'Ext.panel.Panel',

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-profile-profile',

    config: {
        readOnly: false
    },

    layout: 'fit',
    title: '{s name=swag_import_export/profile/profile/title}Configuration{/s}',
    
    initComponent: function() {
        var me = this;

        me.initializeStores();

        me.selectedNodeId = 0;
        me.lastSelectedNode = null;

        me.items = me.buildItems();
        me.dockedItems = me.buildDockedItems();

        me.callParent(arguments);
    },

    getProfileId: function() {
        var me = this;

        return me.up('swag-import-export-profile-window').getProfileId();
    },

    initializeStores: function() {
        var me = this;

        me.treeStore = Ext.create('Shopware.apps.SwagImportExport.store.Profile');
        me.columnStore = Ext.create('Shopware.apps.SwagImportExport.store.Column');
        me.parentKeyStore = Ext.create('Shopware.apps.SwagImportExport.store.ParentKey');
        me.sectionStore = Ext.create('Shopware.apps.SwagImportExport.store.Section');
    },

    buildItems: function() {
        var me = this;

        return [{
            xtype: 'container',
            style: 'background-color: #F0F2F4;',
            itemId: 'configurator',
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            items: [
                me.createTreePanel(),
                me.createFormPanel()
            ]
        }]
    },

    buildDockedItems: function() {
        var me = this;

        me.toolbar = Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            disabled: me.readOnly,
            ui: 'shopware-ui',
            cls: 'shopware-toolbar',
            style: {
                backgroundColor: '#F0F2F4',
                borderRight: '1px solid #A4B5C0',
                borderLeft: '1px solid #A4B5C0',
                borderTop: '1px solid #A4B5C0',
                borderBottom: '1px solid #A4B5C0'
            },
            items: [{
                text: '{s name=newIterationNode}New iteration node{/s}',
                iconCls: 'sprite-plus-circle-frame',
                itemId: 'createIteration',
                disabled: true,
                handler: function () {
                    me.fireEvent('addNewIteration', me.treePanel, me.getProfileId());
                }
            }, {
                text:  '{s name=swag_import_export/profile/new_column}New column{/s}',
                iconCls: 'sprite-plus-circle-frame',
                itemId: 'createChild',
                disabled: true,
                handler: function () {
                    me.fireEvent('addNewNode', me.treePanel, me.getProfileId(), me.lastSelectedNode.get('adapter'));
                }
            }, {
                text: '{s name=newAttribute}New attribute{/s}',
                iconCls: 'sprite-plus-circle-frame',
                itemId: 'createAttribute',
                disabled: true,
                handler: function () {
                    me.fireEvent('addNewAttribute', me.treePanel, me.treeStore, me.selectedNodeId);
                }
            }, {
                text: '{s name=swag_import_export/profile/profile/remove_item}Remove selected item{/s}',
                iconCls: 'sprite-minus-circle-frame',
                itemId: 'deleteSelected',
                disabled: true,
                handler: function () {
                    me.fireEvent('deleteNode', me.treePanel);
                }
            }, '->', {
                itemId: 'conversionsMenu',
                iconCls: 'sprite-gear',
                text: '{s name=swag_import_export/profile/profile/toolbar/show_conversions}Show Conversions{/s}',
                handler: function () {
                    me.fireEvent('showMappings', me.getProfileId());
                }
            }]
        });

        return [
            me.toolbar
        ];
    },

    /**
     * @returns { Ext.tree.Panel }
     */
    createTreePanel: function() {
        var me = this;

        me.treePanel = Ext.create('Ext.tree.Panel', {
            flex: 1,
            border: false,
            store: me.treeStore,
            selModel: {
                selType: 'rowmodel',
                mode: 'MULTI'
            },
            viewConfig: {
                plugins: {
                    ptype: 'customtreeviewdragdrop',
                    pluginId: 'customtreeviewdragdrop',
                    enableDrag: !me.readOnly
                },
                listeners: {
                    drop: function (node, data, overModel, dropPosition, eOpts) {
                        me.treeStore.sync({
                            success: function () {
                                // fix selection
                                me.treePanel.getSelectionModel().deselectAll(true);
                                me.treePanel.expand();
                                me.treePanel.getSelectionModel().select(me.treeStore.getById(data.records[0].get('id')));
                            }
                        });
                    }
                }
            },
            rootVisible: false,
            useArrows: true,
            expandChildren: true,
            margin: '0 5 0 0',
            listeners: {
                scope: me,
                selectionchange: me.onTreeItemSelection,
                itemcontextmenu: me.onTreeItemContextMenu
            }
        });

        return me.treePanel;
    },

    createFormPanel: function() {
        var me = this,
            fieldDefaults = {};

        if (me.readOnly) {
            fieldDefaults['readOnly'] = true;
        }

        me.formPanel = Ext.create('Ext.form.Panel', {
            flex: 1,
            border: false,
            bodyPadding: 12,
            defaultType: 'textfield',
            fieldDefaults: fieldDefaults,
            items: [{
                itemId: 'nodeName',
                fieldLabel: '{s name=nodeName}Node name{/s}',
                hidden: true,
                disabled: true,
                width: 400,
                labelWidth: 150,
                name: 'nodeName',
                allowBlank: false
            }, {
                itemId: 'defaultValue',
                fieldLabel: '{s name=defaultValue}Default value{/s}',
                hidden: true,
                disabled: true,
                width: 400,
                labelWidth: 150,
                name: 'defaultValue',
                allowBlank: true
            }, {
                itemId: 'swColumn',
                fieldLabel: '{s name=shopwareColumn}Shopware column{/s}',
                hidden: true,
                disabled: true,
                xtype: 'combobox',
                editable: false,
                emptyText: '{s name=selectColumn}Select column{/s}',
                queryMode: 'local',
                store: me.columnStore,
                valueField: 'id',
                displayField: 'name',
                width: 400,
                labelWidth: 150,
                name: 'swColumn',
                allowBlank: false
            }, {
                itemId: 'adapter',
                fieldLabel: '{s name=adapter}Adapter{/s}',
                hidden: true,
                disabled: true,
                xtype: 'combobox',
                editable: false,
                emptyText: '{s name=selectColumn}Select column{/s}',
                queryMode: 'local',
                store: me.sectionStore,
                valueField: 'id',
                displayField: 'name',
                width: 400,
                labelWidth: 150,
                name: 'adapter',
                allowBlank: false,
                listeners: {
                    change: function (field, newValue, oldValue, eOpts) {
                        field.nextSibling('#parentKey').getStore().load({
                            params: {
                                profileId: me.getProfileId(),
                                adapter: newValue
                            }
                        });
                    }
                }
            }, {
                itemId: 'parentKey',
                fieldLabel: '{s name=parentKey}Parent key{/s}',
                hidden: true,
                disabled: true,
                xtype: 'combobox',
                editable: false,
                emptyText: '{s name=selectColumn}Select column{/s}',
                store: me.parentKeyStore,
                valueField: 'id',
                displayField: 'name',
                width: 400,
                labelWidth: 150,
                name: 'parentKey',
                allowBlank: false
            }]
        });

        return me.formPanel;
    },

    onTreeItemSelection: function (selModel, selection) {
        var me = this,
            toolbar = me.toolbar,
            rootSelected = false,
            record;

        // check if root not is selected
        for (var i = 0, count = selection.length; i < count; i++) {
            var selectedRecord = selection[i];

            if (selectedRecord.get('id') === 'root') {
                rootSelected = true;
                break;
            }
        }

        me.formPanel.setDisabled(selection.length > 1);

        toolbar.items.get('createIteration').setDisabled(true);
        toolbar.items.get('createAttribute').setDisabled(true);
        toolbar.items.get('createChild').setDisabled(true);
        toolbar.items.get('deleteSelected').setDisabled(rootSelected);

        if (selection.length === 1) {
            record = selection[0];
            me.selectedNodeId = record.get('id');
            me.lastSelectedNode = record;
            me.composeFormFields(record);
            if (!me.readOnly) {
                if (record.get('type') === 'leaf') {
                    toolbar.items.get('createIteration').setDisabled(false);
                    toolbar.items.get('createAttribute').setDisabled(false);
                    if (record.get('inIteration') === true && record.parentNode.get('type') === 'iteration') {
                        toolbar.items.get('createChild').setDisabled(false);
                    }
                } else if (record.get('type') === 'iteration') {
                    toolbar.items.get('createIteration').setDisabled(false);
                    toolbar.items.get('createAttribute').setDisabled(false);
                    toolbar.items.get('createChild').setDisabled(false);
                } else {
                    if (record.get('inIteration') === true) {
                        toolbar.items.get('createAttribute').setDisabled(false);
                    }
                    toolbar.items.get('createIteration').setDisabled(false);
                }
            }
        }
    },

    onTreeItemContextMenu: function(view, record, item, index, e) {
        var me = this,
            type = record.get('type'),
            menuItems;
        view.getSelectionModel().select(record);

        e.stopEvent();
        if (me.readOnly || Ext.isEmpty(me.getProfileId())) {
            return;
        }

        menuItems = [{
            text: '{s name=newIterationNode}New iteration Node{/s}',
            iconCls: 'sprite-plus-circle-frame',
            handler: function () {
                me.fireEvent('addNewIteration', me.treePanel, me.getProfileId());
            }
        }, {
            text:  '{s name=swag_import_export/profile/new_column}New column{/s}',
            iconCls: 'sprite-plus-circle-frame',
            handler: function () {
                me.fireEvent('addNewNode', me.treePanel, me.getProfileId(), me.lastSelectedNode.get('adapter'));
            }
        }, {
            text: '{s name=newAttribute}New attribute{/s}',
            iconCls: 'sprite-plus-circle-frame',
            handler: function () {
                me.fireEvent('addNewAttribute', me.treePanel, me.treeStore, record.get('id'));
            }
        }, {
            text: '{s name=swag_import_export/profile/profile/remove_item}Remove selected item{/s}',
            iconCls: 'sprite-minus-circle-frame',
            handler: function () {
                me.fireEvent('deleteNode', me.treeStore, me.selectedNodeId, me.treePanel.getSelectionModel());
            }
        }];

        if (type !== 'iteration' && type !== 'leaf') {
            delete menuItems[1];
        }

        if (type === 'attribute') {
            menuItems = menuItems[3];
        }

        if (!record.get('inIteration')) {
            delete menuItems[2];
        }

        if (record.getId() === 'root') {
            delete menuItems[3];
        }

        Ext.create('Ext.menu.Menu', {
            width: 200,
            items: menuItems,
            listeners: {
                // don´t pollute the DOM
                hide: function(cmp) {
                    cmp.destroy();
                }
            }
        }).showAt(e.getXY());
    },

    hideFormFields: function() {
        var me = this,
            form = me.formPanel;

        form.child('#nodeName').hide();
        form.child('#nodeName').disable();
        form.child('#swColumn').hide();
        form.child('#swColumn').disable();
    },

    composeFormFields: function(node) {
        var me = this,
            form = me.formPanel,
            parentNode,
            isFirst;

        form.child('#swColumn').hide();
        form.child('#swColumn').disable();

        form.child('#adapter').hide();
        form.child('#adapter').disable();

        form.child('#parentKey').hide();
        form.child('#parentKey').disable();

        form.child('#defaultValue').hide();
        form.child('#defaultValue').disable();

        form.child('#nodeName').show();
        form.child('#nodeName').enable();
        form.child('#nodeName').setValue(node.get('text'));
        form.child('#nodeName').resetOriginalValue();

        if (node.get('type') === 'attribute') {
            form.child('#swColumn').getStore().load({
                params: {
                    profileId: me.getProfileId(),
                    adapter: node.get('adapter')
                }
            });
            form.child('#swColumn').show();
            form.child('#swColumn').enable();
            form.child('#swColumn').setValue(node.get('swColumn'));
            form.child('#swColumn').resetOriginalValue();
        } else if (node.get('type') === 'leaf') {
            form.child('#swColumn').show();
            form.child('#swColumn').enable();
            form.child('#swColumn').setValue(node.get('swColumn'));
            form.child('#swColumn').resetOriginalValue();
            me.columnStore.load({
                params: {
                    profileId: me.getProfileId(),
                    adapter: node.get('adapter')
                },
                callback: function() {
                    me.createDefaultValueField(me.columnStore, node.get('swColumn'), node.get('defaultValue'));
                }
            }, me);
        } else if (node.get('type') === 'iteration') {
            form.child('#adapter').show();
            form.child('#adapter').enable();
            form.child('#adapter').setValue(node.get('adapter'));
            form.child('#adapter').resetOriginalValue();

            // check if it's the first iteration node
            parentNode = node.parentNode;
            isFirst = true;
            while (parentNode.get('id') !== 'root') {
                if (parentNode.get('type') === 'iteration') {
                    isFirst = false;
                }
                parentNode = parentNode.parentNode;
            }
            // enable the parentKey only if the node is not the first one
            if (!isFirst) {
                form.child('#parentKey').getStore().load({
                    params: {
                        profileId: me.getProfileId(),
                        adapter: node.get('adapter')
                    }
                });
                form.child('#parentKey').show();
                form.child('#parentKey').enable();
                form.child('#parentKey').setValue(node.get('parentKey'));
                form.child('#parentKey').resetOriginalValue();
            } else {
                form.child('#parentKey').clearValue();
            }
        }
    },

    /**
     * @param { Shopware.apps.SwagImportExport.store.Column } store
     * @param { string } value
     * @param { string } nodeValue
     */
    createDefaultValueField: function(store, value, nodeValue) {
        var me = this,
            formPanel = me.formPanel,
            fieldType,
            settings,
            record;

        formPanel.down('#defaultValue').destroy();

        //Create default value field
        fieldType = 'hidden';
        settings = {
            itemId: 'defaultValue',
            fieldLabel: '{s namespace=backend/swag_import_export/view/profile name=defaultValue}Default value{/s}',
            width: 400,
            labelWidth: 150,
            name: 'defaultValue',
            allowBlank: true
        };

        //Set new field type if selected column have default flag
        record = store.getById(value);
        if (record) {
            if (record.get('default')) {
                fieldType = record.get('type');
            }
        }
        //Merge component settings depending on field type
        settings = Ext.apply({ }, settings, me.getDefaultValueType(fieldType));

        //Add default field to grid
        formPanel.insert(1, settings);

        formPanel.child('#defaultValue').setValue(nodeValue);
    },

    /**
     * Helper method which returns xtype for current field
     *
     * @param column
     * @returns Object|boolean
     */
    getDefaultValueType: function(column) {
        if (!column) {
            return false;
        }

        switch (column) {
            case 'id':
                return { xtype: 'numberfield', minValue: 1 };
                break;
            case 'integer':
            case 'decimal':
            case 'float':
                var precision = 0;
                if (column.precision) {
                    precision = column.precision
                } else if (column.type == 'float') {
                    precision = 3;
                } else if (column.type == 'decimal') {
                    precision = 3;
                }
                return { xtype: 'numberfield', decimalPrecision: precision };
                break;
            case 'string':
            case 'text':
                return 'textfield';
                break;
            case 'boolean':
                return {
                    xtype: 'checkbox',
                    inputValue: 1,
                    uncheckedValue: 0
                };
                break;
            case 'date':
            case 'dateTime':
                return {
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    submitFormat: 'Y-m-d'
                };
                break;
            default:
                return {
                    hidden: true
                };
                break;
        }
    },

    /**
     * @param { boolean } readOnly
     */
    changeFieldReadOnlyMode: function(readOnly) {
        var me = this,
            form = me.formPanel,
            fields = form.getForm().getFields();

        fields.each(function(field) {
            field.setReadOnly(readOnly);
        });
    }
});
//{/block}

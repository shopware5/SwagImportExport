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
                    me.fireEvent('addNewIteration', me.treePanel, me.treeStore, me.selectedNodeId);
                }
            }, {
                text:  '{s name=newNode}New node{/s}',
                iconCls: 'sprite-plus-circle-frame',
                itemId: 'createChild',
                disabled: true,
                handler: function () {
                    me.fireEvent('addNewNode', me.treePanel, me.treeStore, me.selectedNodeId);
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
                    me.fireEvent('deleteNode', me.treeStore, me.selectedNodeId, me.treePanel.getSelectionModel());
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
            viewConfig: {
                plugins: {
                    ptype: 'customtreeviewdragdrop',
                    pluginId: 'customtreeviewdragdrop',
                    enableDrag: !me.readOnly
                },
                listeners: {
                    drop: function (node, data, overModel, dropPosition, eOpts) {
                        if (dropPosition === 'append') {
                            if (overModel.get('type') !== 'iteration') {
                                overModel.set('type', '');
                                overModel.set('iconCls', '');
                            }
                        }

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
                select: me.onTreeItemSelection,
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
                width: 400,
                labelWidth: 150,
                name: 'nodeName',
                allowBlank: false
            }, {
                itemId: 'defaultValue',
                fieldLabel: '{s name=defaultValue}Default value{/s}',
                hidden: true,
                width: 400,
                labelWidth: 150,
                name: 'defaultValue',
                allowBlank: true
            }, {
                itemId: 'swColumn',
                fieldLabel: '{s name=shopwareColumn}Shopware column{/s}',
                hidden: true,
                xtype: 'combobox',
                editable: false,
                emptyText: 'Select Column',
                queryMode: 'local',
                store: me.columnStore,
                valueField: 'id',
                displayField: 'name',
                width: 400,
                labelWidth: 150,
                name: 'swColumn',
                allowBlank: false,
                listeners: {
                    change: function (field, newValue) {
                        var defaultValue = me.treeStore.getById(me.selectedNodeId).get('defaultValue');

                        me.fireEvent('changeColumn', me.columnStore, newValue, defaultValue);
                    }
                }
            }, {
                itemId: 'adapter',
                fieldLabel: '{s name=adapter}Adapter{/s}',
                hidden: true,
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
                        me.formPanel.child('#parentKey').getStore().load({
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

    onTreeItemSelection: function (view, record) {
        var me = this;

        me.selectedNodeId = record.getId();
        me.composeFormFields();

        if (!me.readOnly) {
            var toolbar = me.toolbar;

            if (record.get('type') === 'attribute') {
                toolbar.items.get('createIteration').setDisabled(true);
                toolbar.items.get('createAttribute').setDisabled(true);
                toolbar.items.get('createChild').setDisabled(true);
                toolbar.items.get('deleteSelected').setDisabled(false);
            } else if (record.get('type') === 'leaf') {
                toolbar.items.get('createIteration').setDisabled(false);
                toolbar.items.get('createAttribute').setDisabled(false);
                toolbar.items.get('createChild').setDisabled(false);
                toolbar.items.get('deleteSelected').setDisabled(false);
            } else if (record.get('type') === 'iteration') {
                toolbar.items.get('createIteration').setDisabled(false);
                toolbar.items.get('createAttribute').setDisabled(false);
                toolbar.items.get('createChild').setDisabled(false);
                toolbar.items.get('deleteSelected').setDisabled(false);
            } else {
                if (record.get('inIteration') === true) {
                    toolbar.items.get('createAttribute').setDisabled(false);
                } else {
                    toolbar.items.get('createAttribute').setDisabled(true);
                }
                toolbar.items.get('createIteration').setDisabled(false);
                toolbar.items.get('createChild').setDisabled(false);
                if (record.getId() === 'root') {
                    toolbar.items.get('deleteSelected').setDisabled(true);
                } else {
                    toolbar.items.get('deleteSelected').setDisabled(false);
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
                me.fireEvent('addNewIteration', me.treePanel, me.treeStore, record.get('id'));
            }
        }, {
            text:  '{s name=newNode}New entry{/s}',
            iconCls: 'sprite-plus-circle-frame',
            handler: function () {
                me.fireEvent('addNewNode', me.treePanel, me.treeStore, record.get('id'));
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
        form.child('#swColumn').hide();
    },

    composeFormFields: function() {
        var me = this,
            form = me.formPanel,
            node = me.treeStore.getById(me.selectedNodeId);

        form.child('#nodeName').show();
        form.child('#nodeName').setValue(node.get('text'));
        form.child('#swColumn').setValue(node.get('swColumn'));

        if (node.get('type') === 'attribute') {
            form.child('#swColumn').getStore().load({
                params: {
                    profileId: me.getProfileId(),
                    adapter: node.get('adapter')
                }
            });
            form.child('#swColumn').show();
            form.child('#adapter').hide();
            form.child('#parentKey').hide();
        } else if (node.get('type') === 'leaf') {
            form.child('#swColumn').getStore().load({
                params: {
                    profileId: me.getProfileId(),
                    adapter: node.get('adapter')
                }
            });
            form.child('#swColumn').show();
            form.child('#adapter').hide();
            form.child('#parentKey').hide();
        } else if (node.get('type') === 'iteration') {
            form.child('#swColumn').hide();
            form.child('#adapter').show();
            form.child('#adapter').setValue(node.get('adapter'));

            // check if it's the first iteration node
            var parentNode = node.parentNode;
            var isFirst = true;
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
                form.child('#parentKey').setValue(node.get('parentKey'));
                form.child('#parentKey').show();
            } else {
                form.child('#parentKey').hide();
                form.child('#parentKey').setValue('');
            }
        } else {
            form.child('#swColumn').hide();
            form.child('#adapter').hide();
            form.child('#parentKey').hide();
            form.child('#defaultValue').hide();
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

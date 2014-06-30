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

//{namespace name="backend/swag_import_export/controller"}
//{block name="backend/swag_import_export/controller/profile"}
Ext.define('Shopware.apps.SwagImportExport.controller.Profile', {
    extend: 'Ext.app.Controller',
    
    snippets: {
        addChild: {
            failureTitle: '{s name=swag_import_export/profile/add_child/failure_title}Create Child Node Failed{/s}'
        },
        save: {
            title: '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
            success: '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}',
            failureTitle: '{s name=swag_import_export/profile/save/failure_title}Save Failed{/s}'
        },
        'delete': {
            title: '{s name=swag_import_export/profile/delete/title}Delete Node?{/s}',
            msg: '{s name=swag_import_export/profile/delete/msg}Are you sure you want to permanently delete the node?{/s}',
            failed: '{s name=swag_import_export/profile/delete/failed}Delete List Failed{/s}'
        },
        addAttribute: {
            failureTitle: '{s name=swag_import_export/profile/add_attribute/failure_title}Create Attribute Failed{/s}',
        },
        categories: '{s name=swag_import_export/profile/type/categories}Categories{/s}',
        articles: '{s name=swag_import_export/profile/type/articles}Articles{/s}',
        articlesInStock: '{s name=swag_import_export/profile/type/articlesInStock}Articles in stock{/s}',
        customers: '{s name=swag_import_export/profile/type/customers}Customers{/s}',
        newsletter: '{s name=swag_import_export/profile/type/newsletter}Newsletter receiver{/s}'
    },
    
    /**
     * This method creates listener for events fired from the export 
     */
    init: function() {
        var me = this;

        me.control({
            'swag-import-export-profile-profile': {
                createOwnProfile: me.createOwnProfile,
                deleteSelectedProfile: me.deleteSelectedProfile,
                showMappings: me.showMappings,
                addNewNode: me.addNewNode,
                saveNode: me.saveNode,
                deleteNode: me.deleteNode,
                addNewAttribute: me.addNewAttribute
            },
            'swag-import-export-window': {
                addConversion: me.addConversion,
                updateConversion: me.updateConversion,
                deleteConversion: me.deleteConversion,
                deleteMultipleConversions: me.deleteMultipleConversions
            }
        });

        me.callParent(arguments);
    },
    
    addConversion: function(grid, editor) {
        var me = this;

        editor.cancelEdit();
        var conversion = Ext.create('Shopware.apps.SwagImportExport.model.Conversion', {
            profileId: 1,
            variable: '',
            exportConversion: '',
            importConversion: ''
        });

        grid.getStore().add(conversion);
        editor.startEdit(conversion, 0);
    },
    
    updateConversion: function(store) {
        store.sync();
    },
    
    deleteConversion: function(store, index) {
        store.removeAt(index);
        store.sync();
    },
    
    deleteMultipleConversions: function(store, selectionModel) {
        store.remove(selectionModel.getSelection());
        store.sync();
    },
    
    /**
     * Shows window with fields for the new profile and adds it
     */
    createOwnProfile: function(store, combo) {
        var me = this,
            profileTypeStore = new Ext.data.SimpleStore({
            fields: ['type', 'label'],
            data: [
                ['categories', me.snippets.categories],
                ['articles', me.snippets.articles],
                ['articlesInStock', me.snippets.articlesInStock],
                ['customers', me.snippets.customers],
                ['newsletter', me.snippets.newsletter]
            ]
        });
        
        var myForm = Ext.create('Ext.form.Panel', {
            width: 500,
            height: 150,
            bodyPadding: 12,
            title: 'New Profile',
            border: false,
            bodyStyle: {
                border: '0 !important'
            },
            floating: true,
            closable: true,
            modal: true,
            items: [{
                    xtype: 'textfield',
                    itemId: 'profileName',
                    fieldLabel: 'Profile Name',
                    name: 'profileName',
                    allowBlank: false
                }, {
                    xtype: 'combobox',
                    itemId: 'type',
                    fieldLabel: 'Type',
                    emptyText: 'Select Type',
                    store: profileTypeStore,
                    name: 'type',
                    valueField: 'type',
                    displayField: 'label',
                    allowBlank: false
                }],
            dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    style: {
                        backgroundColor: '#F0F2F4'
                    },
                    items: ['->', {
                            text: 'Save',
                            cls: 'primary',
                            action: 'swag-import-export-manager-profile-save',
                            handler: function() {
                                var model = combo.store.add({ type: myForm.child('#type').getValue(), name: myForm.child('#profileName').getValue(), tree: "" });
                                myForm.setLoading(true);
                                combo.store.sync({
                                    success: function() {
                                        combo.setValue(model[0].get('id'));
                                        myForm.setLoading(false);
                                        myForm.close();
                                    },
                                    failure: function() {
                                        myForm.setLoading(false);
                                        myForm.close();
                                    }
                                });

                            }
                        }]
                }]
        });
        myForm.show();
    },
    
    /**
     * Deletes the selected profile
     */
    deleteSelectedProfile: function(combobox, store, id) {
        combobox.reset();
        store.remove(store.getById(id));
        store.sync();
    },
    
    /**
     * Shows the window with the conversions for the current profile
     * 
     * @param { Ext.tree.Panel } treeStore
     */
    showMappings: function(profileId) {
        var me = this;

        me.mainWindow = me.getView('profile.window.Mappings').create({ profileId: profileId }).show();
    },

    /**
     * Adds new node to the tree as a child of the selected node
     * 
     * @param { Ext.tree.Panel } treePanel
     * @param { Ext.data.TreeStore } treeStore
     * @param { int } selectedNodeId
     */
    addNewNode: function(treePanel, treeStore, selectedNodeId) {
        var me = this;
        
        var node = treeStore.getById(selectedNodeId);
        node.set('leaf', false);
        node.set('expanded', true);
        if (node.get('type') !== 'iteration') {
            node.set('type', '');
            node.set('iconCls', '');
        }

        var data = { };
        if (node.data.inIteration === true) {
            data = { text: "New Node", leaf: true, type: 'node', iconCls: 'sprite-icon_taskbar_top_inhalte_active', inIteration: true };
        } else {
            data = { text: "New Node", expanded: true };
        }
        var newNode = node.appendChild(data);
        treeStore.sync({
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                        msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: me.snippets.addChild.failureTitle,
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
        treePanel.expand();
        treePanel.getSelectionModel().select(treeStore.getById(newNode.data.id));
    },
    
    /**
     * Saves the changes of the currently selected node 
     * 
     * @param { Ext.data.TreeStore } treeStore
     * @param { int } selectedNodeId
     * @param { string } nodeName
     * @param { string } swColumn
     */
    saveNode: function(treeStore, selectedNodeId, nodeName, swColumn, adapter, parentKey) {
        var me = this;
        
        var node = treeStore.getById(selectedNodeId);
        node.set('text', nodeName);
        node.set('swColumn', swColumn);
        node.set('adapter', adapter);
        node.set('parentKey', parentKey);
        treeStore.sync({
            success: function() {
                Shopware.Notification.createGrowlMessage(
                        me.snippets.save.title,
                        me.snippets.save.success
                        );
            },
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                        msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: me.snippets.save.failureTitle,
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
    },
    
    /**
     * Deletes the selected node
     */
    deleteNode: function(treeStore, selectedNodeId, selModel) {
        var me = this;
        Ext.Msg.show({
            title: me.snippets.delete.title,
            msg: me.snippets.delete.msg,
            buttons: Ext.Msg.YESNO,
            fn: function(response) {
                if (response === 'yes') {
                    var node = treeStore.getById(selectedNodeId);
                    node.parentNode.removeChild(node);
                    treeStore.sync({
                        failure: function(batch, options) {
                            var error = batch.exceptions[0].getError(),
                                    msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                            Ext.MessageBox.show({
                                title: me.snippets.delete.failed,
                                msg: msg,
                                icon: Ext.Msg.ERROR,
                                buttons: Ext.Msg.OK
                            });
                        }
                    });

                    // If there is no selection, or the selection no longer exists in the store (it was part of the deleted node(s))
                    // then select the "All Lists" root
                    if (!selModel.hasSelection() || !treeStore.getNodeById(selModel.getSelection()[0].getId())) {
                        selModel.select(0);
                    }
                }
            }
        });
    },
    
    /**
     * Adds new attribute for the selected node
     * 
     * @param { Ext.tree.Panel } treePanel
     * @param { Ext.data.TreeStore } treeStore
     * @param { int } selectedNodeId
     */
    addNewAttribute: function(treePanel, treeStore, selectedNodeId) {
        var me = this;
        var node = treeStore.getById(selectedNodeId);
        node.set('leaf', false);
        node.set('expanded', true);
        
        var children = node.childNodes;
        var data = { text: "New Attribute", leaf: true, type: 'attribute', iconCls: 'sprite-sticky-notes-pin', inIteration: true };
        var newNode;

        if (children.length > 0) {
            for (var i = 0; i < children.length; i++) {
                if (children[i].get('type') !== 'attribute') {
                    break;
                }
            }
            newNode = node.insertChild(i, data);
        } else {
            newNode = node.appendChild(data);
        }

        treeStore.sync({
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                        msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: me.snippets.addAtrribute.failureTitle,
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
        treePanel.expand();
        treePanel.getSelectionModel().select(treeStore.getById(newNode.data.id));
    }
});
//{/block}

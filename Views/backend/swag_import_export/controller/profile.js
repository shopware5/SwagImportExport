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
        'deleteProfile': {
            title: '{s name=swag_import_export/profile/deleteProfile/title}Delete Profile?{/s}',
            msg: '{s name=swag_import_export/profile/deleteProfile/msg}Are you sure you want to permanently delete the profile?{/s}'
        },
        addAttribute: {
            failureTitle: '{s name=swag_import_export/profile/add_attribute/failure_title}Create Attribute Failed{/s}'
        },
        conversion: {
            title: '{s name="swag_import_export/profile/conversion/title"}Import/Export conversion{/s}',
            successMsg: '{s name="swag_import_export/profile/conversion/success_msg"}Conversion was save successfully{/s}',
            failureMsg: '{s name="swag_import_export/profile/conversion/failure_msg"}Conversion saving failed{/s}'
        },
        duplicate: '{s name=swag_import_export/profile/duplicater}Profile was duplicate successfully{/s}'
    },

    refs: [
        {
            ref: 'profileForm',
            selector: 'swag-import-export-profile-profile'
        }
    ],

    /**
     * This method creates listener for events fired from the export
     */
    init: function() {
        var me = this;

        me.control({
            'swag-import-export-profile-profile': {
                createOwnProfile: me.createOwnProfile,
                deleteSelectedProfile: me.deleteSelectedProfile,
                renameSelectedProfile: me.renameSelectedProfile,
                duplicateSelectedProfile: me.duplicateSelectedProfile,
                showMappings: me.showMappings,
                addNewIteration: me.addNewIteration,
                addNewNode: me.addNewNode,
                saveNode: me.saveNode,
                deleteNode: me.deleteNode,
                addNewAttribute: me.addNewAttribute,
                changeColumn: me.changeColumn
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

    updateConversion: function(store, flag) {
        var me = this;

        if (flag === true){
            store.sync({
                success: function(){
                    Shopware.Notification.createGrowlMessage(
                        me.snippets.conversion.title,
                        me.snippets.conversion.successMsg
                    );
                },
                failure: function() {
                    Shopware.Notification.createGrowlMessage(
                        me.snippets.conversion.title,
                        me.snippets.conversion.failureMsg
                    );
                }
            });
        } else {
            store.sync();
        }
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
        this.getView('profile.window.NewProfile').create({ combo: combo }).show();
    },

    /**
     * Renames the selected profile
     */
    renameSelectedProfile: function(store, id, combo) {
        this.getView('profile.window.RenameProfile').create({ store: store, profileId: id, combo: combo }).show();
    },

    /**
     * Duplicate the selected profile
     */
    duplicateSelectedProfile: function(combobox, store, id) {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="SwagImportExport" action="duplicateProfile"}',
            method: 'POST',
            params: { profileId: id },
            success: function(response) {
                var result = Ext.decode(response.responseText);
                store.reload();
                Shopware.Notification.createGrowlMessage(
                    me.snippets.save.title,
                    me.snippets.duplicate
                );
            },
            failure: function(response) {
                Shopware.Msg.createStickyGrowlMessage({
                    title: 'An error occured',
                    text: "Profile was not created"
                });
            }
        });
    },

    /**
     * Deletes the selected profile
     */
    deleteSelectedProfile: function(combobox, store, id) {
        var me = this;
        Ext.Msg.show({
            title: me.snippets.deleteProfile.title,
            msg: me.snippets.deleteProfile.msg,
            buttons: Ext.Msg.YESNO,
            fn: function(response) {
                if (response === 'yes') {
                    combobox.reset();
                    store.remove(store.getById(id));
                    store.sync();
                }
            }
        });
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
    addNewIteration: function(treePanel, treeStore, selectedNodeId) {
        var me = this;

        var node = treeStore.getById(selectedNodeId);
        if (node.get('type') !== 'iteration') {
            node.set('type', '');
            node.set('iconCls', '');
        }

        var data = { text: "New Iteration Node", adapter:'none', expanded: true, type: 'iteration', iconCls: 'sprite-blue-folders-stack', inIteration: true };

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
            },
            success: function() {
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(newNode.data.id));
            }
        });
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
        if (node.get('type') !== 'iteration') {
            node.set('type', '');
            node.set('iconCls', '');
        }

        var data = { };
        if (node.get('inIteration') === true) {
            data = { text: "New Node", expanded: true, type: 'leaf', iconCls: 'sprite-blue-document-text', inIteration: true, adapter: node.get('adapter') };
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
            },
            success: function() {
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(newNode.data.id));
            }
        });
    },

    /**
     * Saves the changes of the currently selected node
     *
     * @param { Ext.data.TreeStore } treeStore
     * @param { int } selectedNodeId
     * @param { string } nodeName
     * @param { string } swColumn
     */
    saveNode: function(treePanel, treeStore, selectedNodeId, nodeName, swColumn, defaultValue, adapter, parentKey) {
        var me = this;

        var node = treeStore.getById(selectedNodeId);

        if(!node) {
            return;
        }

        node.set('text', nodeName);
        node.set('swColumn', swColumn);
        node.set('defaultValue', defaultValue);

        // change only when in iteration (because otherwise adapter will be empty)
        if (node.get('type') === 'iteration') {
            node.set('adapter', adapter);
            node.set('parentKey', parentKey);
        }

        treeStore.sync({
            success: function() {
                Shopware.Notification.createGrowlMessage(
                    me.snippets.save.title,
                    me.snippets.save.success
                );
                treePanel.getSelectionModel().deselectAll(true);
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(node.get('id')));
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
                    var parentNode = node.parentNode;
                    parentNode.removeChild(node);

                    if (parentNode.get('type') !== 'iteration' && parentNode.get('inIteration') === true) {
                        var bChildNodes = false;

                        // check if there is at least one leaf, iteration or node
                        for (var i = 0; i < parentNode.childNodes.length; i++) {
                            if (parentNode.childNodes[i].get('type') !== 'attribute') {
                                bChildNodes = true;
                                break;
                            }
                        }

                        if (!bChildNodes) {
                            parentNode.set('type', 'leaf');
                            parentNode.set('iconCls', 'sprite-icon_taskbar_top_inhalte_active');
                        }
                    }

                    treeStore.sync({
                        success: function() {
                            selModel.deselectAll();
                            selModel.select(parentNode);
                        },
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
        var data = { text: "New Attribute", leaf: true, type: 'attribute', iconCls: 'sprite-sticky-notes-pin', inIteration: true, adapter: node.get('adapter') };
        var newNode = node.appendChild(data);

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
            },
            success: function() {
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(newNode.getId()));
            }
        });
    },

    /**
     * Helper function which add and remove default value field
     * depending on selected shopware column
     *
     * @param [Ext.data.Store] store - Column store
     * @param [integer] value - id of selected shopware column
     */
    changeColumn: function(store, value) {
        var me = this,
            profileForm = me.getProfileForm(),
            formPanel = profileForm.formPanel;

        formPanel.remove('defaultValue');

        //Create default value field
        var fieldType = 'hidden';
        var settings = {
            id: 'defaultValue',
            itemId: 'defaultValue',
            fieldLabel: '{s namespace=backend/swag_import_export/view/profile name=defaultValue}Default value{/s}',
            width: 400,
            labelWidth: 150,
            name: 'defaultValue',
            allowBlank: true
        };

        //Set new field type if selected column have default flag
        var record = store.getById(value);
        if (record) {
            if (record.get('default')) {
                fieldType = record.get('type');
            }
        }

        //Merge component settings depending on field type
        settings = Ext.apply({ }, settings, me.getDefaultValueType(fieldType));

        //Add default field to grid
        formPanel.insert(1, settings);
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

    }
});
//{/block}

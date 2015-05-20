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

    okIcon: 'sprite-tick',

    failIcon: 'sprite-cross',

    validationUrl: '{url controller="SwagImportExport" action="validateProfile"}',

    currentProfile: {
        value: null,
        panel: null,
        firstLabel: null,
        secondLabel: null
    },
    
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
                profileSelectChange: me.onProfileSelectChange
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

    onProfileSelectChange: function (value, panel, firstLabel, secondLabel) {
        var me = this;
        panel.setLoading(true);

        me.currentProfile.value = value;
        me.currentProfile.panel = panel;
        me.currentProfile.firstLabel = firstLabel;
        me.currentProfile.secondLabel = secondLabel;

        Ext.Ajax.request({
            url: me.validationUrl,
            params: {
                profileId: value.value
            },
            success: function(response)
            {
                var returnedData = Ext.decode(response.responseText);
                if(returnedData.hidePanel){
                    panel.setLoading(false);
                    panel.hide();
                    return;
                }
                var additionalText = '';
                if(returnedData.update && returnedData.update.length > 0) {
                    additionalText = me.createAdditionalText(returnedData.update);
                    me.updateLabel(firstLabel, me.failIcon, me.snippets.profileValidation.notWorkingUpdate + additionalText);
                } else {
                    me.updateLabel(firstLabel,me.okIcon, me.snippets.profileValidation.workingUpdate);
                }

                if(returnedData.create && returnedData.create.length > 0) {
                    additionalText = me.createAdditionalText(returnedData.create);
                    me.updateLabel(secondLabel, me.failIcon, me.snippets.profileValidation.notWorkingCreates + additionalText);
                } else {
                    me.updateLabel(secondLabel, me.okIcon, me.snippets.profileValidation.workingCreate);
                }

                panel.setLoading(false);
            }
        });
        panel.show();
    },
    
    updateLabel: function (label, icon, text) {
        var me = this;
        me.setButtonIcon(label, icon);
        label.setText(text);
    },

    createAdditionalText: function (missingFields) {
        var additionalText = '',
            length = missingFields.length,
            currentCounter = 1;

        Ext.each(missingFields, function (value) {
            if(currentCounter == length) {
                additionalText = additionalText + value;
            } else {
                additionalText = additionalText + value + ', ';
            }
            currentCounter++;
        });
        return additionalText;
    },
    
    setButtonIcon: function (button, icon) {
        button.setIconCls(icon);
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
                me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
            },
            success: function() {
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(newNode.data.id));
                me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
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
    saveNode: function(treePanel, treeStore, selectedNodeId, nodeName, swColumn, adapter, parentKey) {
        var me = this;
        
        var node = treeStore.getById(selectedNodeId);

        if(!node) {
            return;
        }

        node.set('text', nodeName);
        node.set('swColumn', swColumn);
        
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
                me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
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
                me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
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
                            me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
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
                            me.onProfileSelectChange(me.currentProfile.value, me.currentProfile.panel, me.currentProfile.firstLabel, me.currentProfile.secondLabel);
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
    }
});
//{/block}

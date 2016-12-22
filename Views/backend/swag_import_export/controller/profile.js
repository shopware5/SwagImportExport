//{namespace name="backend/swag_import_export/controller"}
//{block name="backend/swag_import_export/controller/profile"}
Ext.define('Shopware.apps.SwagImportExport.controller.Profile', {
    extend: 'Ext.app.Controller',

    refs: [
        {
            ref: 'profileForm',
            selector: 'swag-import-export-profile-profile'
        }, {
            ref: 'importForm',
            selector: 'swag-import-export-manager-import'
        }, {
            ref: 'exportForm',
            selector: 'swag-import-export-manager-export'
        }, {
            ref: 'grid',
            selector: 'swag-import-export-profile-grid{ isVisible(true) }'
        }
    ],

    /**
     * This method creates listener for events fired from the export
     */
    init: function() {
        var me = this;

        me.control({
            'swag-import-export-profile-grid{ isVisible(true) }': {
                addProfile: me.onAddProfile,
                showProfile: me.onShowProfile,
                editProfile: me.onEditProfile,
                deleteProfile: me.onDeleteProfile,
                duplicateProfile: me.onDuplicateProfile,
                exportProfile: me.onExportProfile,
                onImportFileSelected: me.startImport,
                checkboxfilterchange: me.onFilterDefaultProfiles,
                searchfilterchange: me.onSearchProfile
            },
            'swag-import-export-profile-window{ isVisible(true) }': {
                baseprofileselected: me.onBaseProfileSelected,
                saveProfile: me.onSaveProfile,
                saveNode: me.saveNode
            },
            'swag-import-export-profile-profile{ isVisible(true) }': {
                showMappings: me.showMappings,
                addNewIteration: me.addNewIteration,
                addNewNode: me.addNewNode,
                deleteNode: me.deleteNode,
                addNewAttribute: me.addNewAttribute
            },
            'swag-import-export-iterator-window{ isVisible(true) }': {
                addIterator: me.addIterator
            },
            'swag-import-export-column-window{ isVisible(true) }': {
                addNode: me.addNode
            },
            'swag-import-export-mapping-window{ isVisible(true) }': {
                addConversion: me.addConversion,
                updateConversion: me.updateConversion,
                deleteConversion: me.deleteConversion,
                deleteMultipleConversions: me.deleteMultipleConversions
            }
        });

        // set base url for profile export download
        me.baseUrl = '{url controller="SwagImportExportProfile" action="exportProfile"}';
        me.importUrl = '{url controller="SwagImportExportProfile" action="importProfile"}';

        me.callParent(arguments);
    },

    onSearchProfile: function(field, newValue) {
        var searchString = Ext.String.trim(newValue),
            store = field.up('grid').getStore();

        //scroll the store to first page
        store.currentPage = 1;

        //If the search-value is empty, reset the filter
        if ( searchString.length === 0 ) {
            store.filters.removeAtKey('search');
            store.load();
        } else {
            //Loads the store with a special filter
            store.filter([
                { id: 'search', property: 'name', value: '%' + searchString + '%', expression: 'LIKE' }
            ]);
        }
    },

    onFilterDefaultProfiles: function(checkbox, value) {
        var searchfilter = checkbox.nextSibling('#searchfield'),
            store = checkbox.up('grid').getStore();

        //scroll the store to first page
        store.currentPage = 1;

        if (value) {
            store.filter([
                { id: 'default', property: 'default', value: 0 }
            ]);
        } else {
            store.filters.removeAtKey('default');
            store.load();
        }
    },

    onAddProfile: function() {
        var record = Ext.create('Shopware.apps.SwagImportExport.model.ProfileList');

        Ext.create('Shopware.apps.SwagImportExport.view.profile.Window').show(null, function() {
            this.down('#profilebaseform').loadRecord(record);
        });
    },

    onEditProfile: function(grid, record) {
        Ext.create('Shopware.apps.SwagImportExport.view.profile.Window').show(null, function() {
            this.down('#profilebaseform').loadRecord(record);
            this.setProfileId(record.get('id'));
        });
    },

    onShowProfile: function(grid, record) {
        Ext.create('Shopware.apps.SwagImportExport.view.profile.Window', {
            readOnly: true
        }).show(null, function() {
            this.down('#profilebaseform').loadRecord(record);
            this.setProfileId(record.get('id'));
        });
    },

    /**
     * @param { Ext.Window } window
     * @param { Ext.data.Model } selectedProfile
     */
    onBaseProfileSelected: function(window, selectedProfile) {
        var configurator = window.profileConfigurator,
            treeStore = configurator.treeStore;

        configurator.hideFormFields();

        // use standard load with params here because
        // we dont want proxy set to existing profile id
        // and just load data for preview
        treeStore.load({
            params: {
                profileId: selectedProfile.get('id')
            }
        });

        configurator.enable();
        configurator.changeFieldReadOnlyMode(true);
        configurator.down('toolbar[dock=top]').disable();
        configurator.treePanel.getView().getPlugin('customtreeviewdragdrop').dragZone.lock();
    },

    onSaveProfile: function(window) {
        var me = this,
            store = window.profileStore,
            form = window.down('#profilebaseform'),
            record = form.getRecord();

        if (!form.getForm().isDirty()) {
            return;
        }

        if (form.getForm().isValid()) {
            form.getForm().updateRecord(record);
            record.join(store);

            window.setLoading(true);

            record.save({
                callback: function(record, operation, success) {
                    var result = operation.request.scope.reader.jsonData;
                    window.setLoading(false);
                    if (result.success) {
                        window.down('#profilebaseform').loadRecord(record);
                        window.setProfileId(record.get('id'));
                        me.getGrid().getStore().load();
                        Shopware.Notification.createGrowlMessage(
                            '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                            '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                        );
                    } else {
                        Shopware.Notification.createGrowlMessage(
                            '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                            result.message
                        );
                        me.getGrid().store.reload();
                    }
                }
            });
        } else {
            Ext.MessageBox.show({
                title: '{s name="swag_import_export/profile/new_profile/failure_title"}Create New Profile Failed{/s}',
                msg:  '{s name="swag_import_export/profile/new_profile/not_all_fields_filled_error"}Not all fields are filled!{/s}',
                icon: Ext.Msg.ERROR,
                buttons: Ext.Msg.OK
            });
        }
    },

    onDeleteProfile: function(grid, selection) {
        var me = this,
            record = selection[0];

        if (record.get('default') === true) {
            Shopware.Notification.createGrowlMessage(
                '{s name=swag_import_export/profile/delete_profile}Delete profile{/s}',
                '{s name=swag_import_export/profile/delete_default_msg}Default profiles can not be removed.{/s}'
            );
            return;
        }
        Ext.Msg.show({
            title: '{s name=swag_import_export/profile/deleteProfile/title}Delete Profile?{/s}',
            msg: '{s name=swag_import_export/profile/deleteProfile/msg}Are you sure you want to permanently delete the profile?{/s}',
            buttons: Ext.Msg.YESNO,
            fn: function(response) {
                if (response === 'yes') {
                    record.destroy({
                        callback: function(records, operation, success) {
                            if (operation.success) {
                                Shopware.Notification.createGrowlMessage(
                                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                                    '{s name=swag_import_export/profile/save/delete_success}Successfully deleted.{/s}'
                                );
                                grid.getStore().loadPage(1);
                            } else {
                                Shopware.Notification.createGrowlMessage(
                                    '{s name=swag_import_export/profile/save/failure}Failure{/s}',
                                    '{s name=swag_import_export/profile/deletion_error_msg}Unexpected error while deleting profile.{/s}'
                                );
                            }
                        }
                    });
                }
            }
        });
    },

    onDuplicateProfile: function(grid, record) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="SwagImportExportProfile" action="duplicateProfile"}',
            method: 'POST',
            params: { profileId: record.get('id') },
            success: function(response) {
                var result = Ext.JSON.decode(response.responseText);
                if (result.success) {
                    grid.getStore().load();
                    Shopware.Notification.createGrowlMessage(
                        '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                        '{s name=swag_import_export/profile/duplicater}Profile was duplicate successfully{/s}'
                    );
                } else {
                    Shopware.Notification.createGrowlMessage(
                        '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                        result.message
                    );
                }
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
     * @param { Ext.grid.Panel } grid
     * @param { Ext.data.Model } record
     */
    onExportProfile: function(grid, record) {
        var me = this,
            exportUrl = Ext.String.format('[0]?profileId=[1]', me.baseUrl, record.get('id'));

        window.open(exportUrl, 'Download');
    },

    /**
     * @param { Ext.grid.Panel } grid
     * @param { Ext.form.field.File } uploadfield
     * @param { string } newValue
     */
    startImport: function(grid, uploadfield, newValue) {
        var me = this,
            form = uploadfield.up('form');

        form.submit({
            url: me.importUrl,
            success: function() {
                Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                );
                grid.getStore().load();
            },
            failure: function(form, action) {
                Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    action.result.message
                );
            }
        });
    },

    /**
     * Profile configuration handling
     */

    /**
     * Shows the window with the conversions for the current profile
     *
     * @param { Ext.tree.Panel } treeStore
     */
    showMappings: function(profileId) {
        var me = this;

        Ext.create('Shopware.apps.SwagImportExport.view.profile.window.Mappings', { profileId: profileId }).show();
    },

    /**
     * Adds new node to the tree as a child of the selected node
     *
     * @param { Ext.tree.Panel } treePanel
     * @param { int } profileId
     */
    addNewIteration: function(treePanel, profileId) {
        Ext.create('Shopware.apps.SwagImportExport.view.profile.window.Iterator', {
            treePanel: treePanel,
            profileId: profileId,
            autoShow: true
        });
    },

    /**
     * @param { Shopware.apps.SwagImportExport.view.profile.window.Iterator } win
     */
    addIterator: function(win) {
        var me = this,
            form = win.formPanel,
            treePanel = win.treePanel,
            treeStore = treePanel.getStore(),
            store = win.columnGrid.getStore(),
            formValues = form.getForm().getValues(),
            iteratorNode,
            columns,
            mainNode = treeStore.getRootNode().findChildBy(function(node) {
                return node.get('type') === 'iteration' && node.get('inIteration');
            }, me, true);

        iteratorNode = treeStore.model.create({
            text: formValues.nodeName,
            adapter: formValues.adapter,
            expanded: true,
            type: 'iteration',
            iconCls: 'sprite-blue-folders-stack',
            inIteration: true,
            parentKey: formValues.parentKey || ''
        });

        columns = store.getRange();

        mainNode.appendChild(iteratorNode);

        me.doSync(treeStore, function() {
            for (var i = 0, count = columns.length; i < count; i++) {
                var column = columns[i],
                    columnNode;
                if (!column.get('select')) {
                    continue;
                }
                columnNode = treeStore.model.create({
                    text: column.get('nodeName'),
                    adapter: formValues.adapter,
                    inIteration: true,
                    type: 'leaf',
                    iconCls: 'sprite-blue-document-text',
                    swColumn: column.get('swColumn')
                });
                iteratorNode.appendChild(columnNode);
            }
            me.doSync(treeStore, function() {
                Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                );
                treePanel.expand();
                treePanel.getSelectionModel().select(iteratorNode);
                win.close();
            });
        }, me);
    },

    /**
     *
     * @param { Ext.data.TreeStore } treeStore
     * @param callback
     * @param scope
     */
    doSync: function(treeStore, callback, scope) {
        treeStore.sync({
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                    msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: '{s name=swag_import_export/profile/add_child/failure_title}Create Child Node Failed{/s}',
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            },
            success: function() {
                Ext.callback(callback, scope);
            }
        });
    },

    /**
     * Adds new node to the tree as a child of the selected node
     *
     * @param { Ext.tree.Panel } treePanel
     * @param { int } profileId
     * @param { string } adapter
     */
    addNewNode: function(treePanel, profileId, adapter) {
        Ext.create('Shopware.apps.SwagImportExport.view.profile.window.Column', {
            treePanel: treePanel,
            profileId: profileId,
            adapter: adapter,
            autoShow: true
        });
    },

    /**
     * @param { Shopware.apps.SwagImportExport.view.profile.window.Column } win
     */
    addNode: function(win) {
        var me = this,
            treePanel = win.treePanel,
            form = win.formPanel,
            formValues = form.getForm().getValues(),
            store = treePanel.getStore(),
            parentNode = treePanel.getSelectionModel().getSelection()[0],
            newNode;

        if (parentNode.get('type') === 'leaf') {
            parentNode = parentNode.parentNode;
        }

        newNode = store.model.create({
            text: formValues.nodeName,
            expanded: true,
            type: 'leaf',
            iconCls: 'sprite-blue-document-text',
            inIteration: true,
            swColumn: formValues.swColumn,
            defaultValue: formValues.defaultValue || '',
            adapter: win.getAdapter()
        });

        parentNode.appendChild(newNode);

        store.sync({
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                    msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: '{s name=swag_import_export/profile/add_child/failure_title}Create Child Node Failed{/s}',
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            },
            success: function() {
                Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                );
                win.close();
                treePanel.expand();
                treePanel.getSelectionModel().select(newNode);
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
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                );
                treePanel.getSelectionModel().deselectAll(true);
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(node.get('id')));
            },
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                    msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: '{s name=swag_import_export/profile/save/failure_title}Save Failed{/s}',
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
    deleteNode: function(treePanel) {
        Ext.Msg.show({
            title: '{s name=swag_import_export/profile/delete/title}Delete Node?{/s}',
            msg: '{s name=swag_import_export/profile/delete/msg}Are you sure you want to permanently delete the node?{/s}',
            buttons: Ext.Msg.YESNO,
            fn: function(btn) {
                if (btn === 'yes') {
                    var selModel = treePanel.getSelectionModel(),
                        store = treePanel.getStore(),
                        selection = selModel.getSelection();

                    for (var i = 0, count = selection.length; i < count; i++) {
                        var node = selection[i],
                            parentNode = node.parentNode,
                            selectNode = node.previousSibling;

                        if (!selectNode) {
                            selectNode = node.nextSibling;
                        }
                        if (!selectNode) {
                            selectNode = node.parentNode;
                        }

                        parentNode.removeChild(node);

                        if (parentNode.get('type') !== 'iteration' && parentNode.get('inIteration') === true) {
                            var bChildNodes = false;

                            // check if there is at least one leaf, iteration or node
                            for (var j = 0; j < parentNode.childNodes.length; j++) {
                                if (parentNode.childNodes[j].get('type') !== 'attribute') {
                                    bChildNodes = true;
                                    break;
                                }
                            }

                            if (!bChildNodes) {
                                parentNode.set('type', 'leaf');
                                parentNode.set('iconCls', 'sprite-blue-document-text');
                            }
                        }
                    }

                    store.sync({
                        success: function() {
                            Shopware.Notification.createGrowlMessage(
                                '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                                '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                            );
                            selModel.deselectAll();
                            if (selectNode) {
                                selModel.select(selectNode);
                            }
                        },
                        failure: function(batch, options) {
                            var error = batch.exceptions[0].getError(),
                                msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                            Ext.MessageBox.show({
                                title: '{s name=swag_import_export/profile/delete/failed}Delete List Failed{/s}',
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
        var node = treeStore.getById(selectedNodeId),
            data,
            newNode;

        node.set('leaf', false);
        node.set('expanded', true);

        data = { text: "New Attribute", leaf: true, type: 'attribute', iconCls: 'sprite-sticky-notes-pin', inIteration: true, adapter: node.get('adapter') };
        newNode = node.appendChild(data);

        treeStore.sync({
            failure: function(batch, options) {
                var error = batch.exceptions[0].getError(),
                    msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

                Ext.MessageBox.show({
                    title: '{s name=swag_import_export/profile/add_attribute/failure_title}Create Attribute Failed{/s}',
                    msg: msg,
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            },
            success: function() {
                Shopware.Notification.createGrowlMessage(
                    '{s name=swag_import_export/profile/save/title}Swag import export{/s}',
                    '{s name=swag_import_export/profile/save/success}Successfully updated.{/s}'
                );
                treePanel.expand();
                treePanel.getSelectionModel().select(treeStore.getById(newNode.getId()));
            }
        });
    },

    /**
     * Conversion handling
     */

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
                        '{s name="swag_import_export/profile/conversion/title"}Import/Export conversion{/s}',
                        '{s name="swag_import_export/profile/conversion/success_msg"}Conversion was save successfully{/s}'
                    );
                },
                failure: function() {
                    Shopware.Notification.createGrowlMessage(
                        '{s name="swag_import_export/profile/conversion/title"}Import/Export conversion{/s}',
                        '{s name="swag_import_export/profile/conversion/failure_msg"}Conversion saving failed{/s}'
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
    }
});
//{/block}

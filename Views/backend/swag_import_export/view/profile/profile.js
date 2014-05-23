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

//{namespace name=backend/swag_import_export/view/profile}
//{block name="backend/swag_import_export/view/profile/profile"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.Profile', {
	extend: 'Ext.container.Container',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-profile-profile',
		
    title: '{s name=swag_import_export/profile/profile/title}Profile{/s}',

	layout: 'border',
	
	style: {
		background: '#fff'
	},
		
	autoScroll: false,
	
	loadNew: function(profileId) {
        console.log(profileId);
		var me = this;
        if (profileId !== null) {
            me.profileId = profileId;
            me.treeStore.getProxy().setExtraParam('profileId', profileId);
            me.treeStore.load({ params: { profileId: profileId } });
            me.formPanel.hideFields();
            me.treePanel.getView().setDisabled(false);
        } else {
            me.treePanel.getView().setDisabled(true);
            me.formPanel.hideFields();
            me.treePanel.collapseAll();
        }
	},
	
	initComponent: function() {
		var me = this;
		
		me.profilesStore = Ext.create('Shopware.apps.SwagImportExport.store.ProfileList').load();
		me.treeStore = Ext.create('Shopware.apps.SwagImportExport.store.Profile');		
		me.selectedNodeId = 0;

        me.items = [me.createToolbar(), me.createTreeItem(), me.createFormPanel()];
		
		me.treePanel.getView().setDisabled(true);
		me.callParent(arguments);
	},

    createToolbar: function() {
        var me = this;
        
        me.toolbar = Ext.create('Ext.toolbar.Toolbar', {
            region: 'north',
            items: [{
                    xtype: 'combobox',
                    itemId: 'profilesCombo',
                    allowBlank: false,
                    store: me.profilesStore,
                    labelStyle: 'font-weight: 700; text-align: left;',
                    width: 150,
                    valueField: 'id',
                    displayField: 'name',
                    editable: false,
                    name: 'profile',
                    emptyText: 'Select Profile...',
                    listeners: {
                        scope: me,
                        change: function(value) {
                            me.loadNew(value.getValue());
                        }
                    }
                },
                '-', {
                    text: 'Create Own Profile',
                    handler: function() {
                        me.fireEvent('createOwnProfile', me.profilesStore);
                    }
                }, {
                    text: 'Delete Selected Profile',
//                    disabled: true,
                    handler: function() {
                        me.fireEvent('deleteSelectedProfile', me.toolbar.items.get('profilesCombo'), me.profilesStore, me.profileId);
                    }
                }, {
                    text: 'Show Conversions',
                    id: 'show-mappings',
                    handler: function() {
                        me.fireEvent('showMappings', me.profilesStore);
                    }
                }
            ],
            style: {
                borderRight: '1px solid #A4B5C0',
                borderLeft: '1px solid #A4B5C0',
                borderTop: '1px solid #A4B5C0',
                borderBottom: '1px solid #A4B5C0'
            }
        });
        
        return me.toolbar;
    },
	
	createTreeItem: function() {
		var me = this;
		
		me.treePanel = Ext.create('Ext.tree.Panel', {
			region: 'west',
			store: me.treeStore,
			viewConfig: {
				plugins: {
					ptype: 'treeviewdragdrop'
				}
			},
			title: 'Profile',
			width: 300,
			useArrows: true,
			expandChildren: true,
			dockedItems: [{
					itemId: 'toolbar',
					xtype: 'toolbar',
					style: {
						borderRight: '1px solid #A4B5C0',
						borderLeft: '1px solid #A4B5C0',
						borderTop: '1px solid #A4B5C0'
					},
					items: [{
							itemId: 'createChild',
							text: 'Create Child Node',
                            disabled: true,
							handler: function() {
                                me.fireEvent('addNewNode', me.treePanel, me.treeStore, me.selectedNodeId);
							}
						}, {
							itemId: 'createAttribute',
							text: 'Create Attribute',
                            disabled: true,
							handler: function() {
                                me.fireEvent('addNewAttribute', me.treePanel, me.treeStore, me.selectedNodeId);
							}
						}, {
							itemId: 'deleteSelected',
							text: 'Delete Selected',
                            disabled: true,
							handler: function() {
								me.fireEvent('deleteNode', me.treeStore, me.selectedNodeId, me.treePanel.getSelectionModel());
							}
						}]
				}],
			listeners: {
				itemclick: {
					fn: function(view, record, item, index, event) {
						me.selectedNodeId = record.data.id;
						me.formPanel.fillForm();
						
						var toolbar = this.dockedItems.get('toolbar');
						
						if (record.data.type === 'attribute') {
							toolbar.items.get('createAttribute').setDisabled(true);
							toolbar.items.get('createChild').setDisabled(true);
							toolbar.items.get('deleteSelected').setDisabled(false);
						} else if (record.data.type === 'node') {
							toolbar.items.get('createAttribute').setDisabled(false);
							toolbar.items.get('createChild').setDisabled(false);
							toolbar.items.get('deleteSelected').setDisabled(false);
						} else {
							if (record.data.inIteration === true) {
								toolbar.items.get('createAttribute').setDisabled(false);
							} else {
								toolbar.items.get('createAttribute').setDisabled(true);
							}
							toolbar.items.get('createChild').setDisabled(false);
							if (record.data.id === 'root') {
								toolbar.items.get('deleteSelected').setDisabled(true);
							} else {
								toolbar.items.get('deleteSelected').setDisabled(false);
							}
						}
					}
				}

			}
		});
		
		return me.treePanel;
	},
	
	createFormPanel: function() {
		var me = this;
		
		me.formPanel = Ext.create('Ext.form.Panel', {
			region: 'center',
			bodyPadding: 12,
			defaultType: 'textfield',
			border: false,
			bodyStyle: {
				border: '0 !important'
			},
			hideFields: function() {
				this.child('#nodeName').hide();
				this.child('#swColumn').hide();
				this.child('#iteration').hide();
			},
			fillForm: function() {
				var node = me.treeStore.getById(me.selectedNodeId);
				this.child('#nodeName').show();
				this.child('#nodeName').setValue(node.data.text);
				this.child('#swColumn').setValue(node.data.swColumn);
				this.child('#iteration').setValue(node.data.type === 'iteration');
				
				if (node.data.type === 'attribute') {
					this.child('#swColumn').show();
					this.child('#iteration').hide();
				} else if (node.data.type === 'node') {
					this.child('#swColumn').show();
					this.child('#iteration').hide();
				} else {
					this.child('#swColumn').hide();
					this.child('#iteration').show();
				}
			},
			items: [{
					itemId: 'nodeName',
					fieldLabel: 'Node Name',
					hidden: true,
					width: 400,
					labelWidth: 150,
					name: 'nodeName',
					allowBlank: false
				}, {
					itemId: 'swColumn',
					fieldLabel: 'Shopware Column',
					hidden: true,
					xtype: 'combobox',
					emptyText: 'Select Column',
					store: ['id', 'description', 'parent', 'active'],
					width: 400,
					labelWidth: 150,
					name: 'swColumn',
					allowBlank: false
				}, {
					itemId: 'iteration',
					fieldLabel: 'Iteration Node',
					hidden: true,
					disabled: true,
					xtype: 'checkbox',
					emptyText: 'Select Column',
					width: 400,
					labelWidth: 150,
					name: 'iteration',
					trueText: 'T',
					falseText: 'N'
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
								me.fireEvent('saveNode', me.treeStore, me.selectedNodeId, me.formPanel.child('#nodeName').getValue(), me.formPanel.child('#swColumn').getValue());
							}
						}]
				}]
		});
		
		return me.formPanel;
	}
});
//{/block}

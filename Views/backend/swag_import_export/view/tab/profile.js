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
 * Shopware SwagGiftPackaging Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagGiftPackaging
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/swag_gift_packaging/view/main}
//{block name="backend/swag_gift_packaging/view/main/window"}
Ext.define('Shopware.apps.SwagImportExport.view.tab.Profile', {
	extend: 'Ext.container.Container',
	
	/**
	 * List of short aliases for class names. Most useful for defining xtypes for widgets.
	 * @string
	 */
	alias: 'widget.swag-import-export-tab-profile',
		
    title: '{s name=swag_import_export/tab/profile/title}Profile{/s}',

	layout: 'border',
	
	style: {
		background: '#fff'
	},
		
	autoScroll: false,
	
	loadNew: function(profileId) {
		var me = this;
		
		me.profileId = profileId;
		me.treeStore.getProxy().setExtraParam('profileId',profileId);
		me.treeStore.load({ params: { profileId: profileId } });
		me.formPanel.hideFields();
		me.treePanel.getView().setDisabled(false);
	},
	
	initComponent: function() {
		var me = this;
		
		me.profilesStore = Ext.create('Shopware.apps.SwagImportExport.store.ProfileList').load();
		
		me.treeStore = Ext.create('Shopware.apps.SwagImportExport.store.Profile', {	});
		
		me.selectedNodeId = 0;

		me.items = [{
				xtype: 'toolbar',
				region: 'north',
				items: me.getToolbarItems(me),
				style: {
					borderRight: '1px solid #A4B5C0',
					borderLeft: '1px solid #A4B5C0',
					borderTop: '1px solid #A4B5C0',
					borderBottom: '1px solid #A4B5C0',
				}
			}, me.createTreeItem(me), me.createFormPanel(me)
		];
		
		me.treePanel.getView().setDisabled(true);
		me.callParent(arguments);
	},
	
	getToolbarItems: function(me) {
		return [{
				xtype: 'combobox',
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
//						var record = me.profilesStore.getById(value.getValue());
						me.loadNew(value.getValue());
					}
				}
			}, {
				xtype: 'tbseparator'
			}, {
				text: 'Create Own Profile',
				handler: function() {
					var callback = function(btn, text) {
						me.profilesStore.add({ type: 'categories', name: text, tree: "" });
						me.profilesStore.sync();
					}
					Ext.MessageBox.prompt('Name', 'Please enter the profile name:', callback);
				}
			}, {
				text: 'Delete Selected Profile',
				disabled: true
			}, {
				text: 'Show Conversions',
				id: 'show-mappings'
			}
		]
	},
	
	createTreeItem: function() {
		var me = this;
		
		me.treePanel = Ext.create('Ext.tree.Panel', {
			region: 'west',
			store: me.treeStore,
//			viewConfig: {
//				plugins: {
//					ptype: 'treeviewdragdrop'
//				}
//			},
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
						borderTop: '1px solid #A4B5C0',
					},
					items: [{
							itemId: 'createChild',
							text: 'Create Child Node',
							handler: function() {
								var node = me.treeStore.getById(me.selectedNodeId);
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
								me.treeStore.sync({
									failure: function(batch, options) {
										var error = batch.exceptions[0].getError(),
												msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

										Ext.MessageBox.show({
											title: 'Create Child Failed',
											msg: msg,
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								});
								me.treePanel.expand();
								me.treePanel.getSelectionModel().select(me.treeStore.getById(newNode.data.id));
							}
						}, {
							itemId: 'createAttribute',
							text: 'Create Attribute',
							handler: function() {
								var node = me.treeStore.getById(me.selectedNodeId);
								node.set('leaf', false);
								node.set('expanded', true);

								var data = { text: "New Attribute", leaf: true, type: 'attribute', iconCls: 'sprite-sticky-notes-pin', inIteration: true };
								var newNode = node.appendChild(data);
								me.treeStore.sync({
									failure: function(batch, options) {
										var error = batch.exceptions[0].getError(),
												msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

										Ext.MessageBox.show({
											title: 'Create Attribute Failed',
											msg: msg,
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								});
								me.treePanel.expand();
								me.treePanel.getSelectionModel().select(me.treeStore.getById(newNode.data.id));
							}
						}, {
							itemId: 'deleteSelected',
							text: 'Delete Selected',
							handler: function() {
								Ext.Msg.show({
									title: 'Delete Node?',
									msg: 'Are you sure you want to permanently delete the node?',
									buttons: Ext.Msg.YESNO,
									fn: function(response) {
										if (response === 'yes') {
											var node = me.treeStore.getById(me.selectedNodeId);
											node.parentNode.removeChild(node);
											me.treeStore.sync({
												failure: function(batch, options) {
													var error = batch.exceptions[0].getError(),
															msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

													Ext.MessageBox.show({
														title: 'Delete List Failed',
														msg: msg,
														icon: Ext.Msg.ERROR,
														buttons: Ext.Msg.OK
													});
												}
											});
											
											var selModel = me.treePanel.getSelectionModel();

											// If there is no selection, or the selection no longer exists in the store (it was part of the deleted node(s))
											// then select the "All Lists" root
											if (!selModel.hasSelection() || !me.treeStore.getNodeById(selModel.getSelection()[0].getId())) {
												selModel.select(0);
											}
										}
									}
								});
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
		
		$tree = me.treePanel;
		
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
								var node = me.treeStore.getById(me.selectedNodeId);
								node.set('text', me.formPanel.child('#nodeName').getValue());
								node.set('swColumn', me.formPanel.child('#swColumn').getValue());
								me.treeStore.sync({
									failure: function(batch, options) {
										var error = batch.exceptions[0].getError(),
												msg = Ext.isObject(error) ? error.status + ' ' + error.statusText : error;

										Ext.MessageBox.show({
											title: 'Save Failed',
											msg: msg,
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								});
							}
						}]
				}]
		});
		
		return me.formPanel;
	}
});
//{/block}

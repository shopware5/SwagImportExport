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
	alias: 'widget.swag-import-export-window',
	
    height: 600,
    
    layout: 'fit',
	
    title: 'Conversions',

    initComponent:function () {
        var me = this;

        //add the order list grid panel and set the store
        me.items = [ me.createGridPanel() ];
        me.callParent(arguments);
    },
	
    createGridPanel: function() {
		var me = this;

		// sample static data for the store
		var myData = [
			['id', /*{literal}*/'{if $article.active} "false" {else} "true" {/if}'/*{/literal}*/, /*{literal}*/'{if $article.active == "false"} 1 {else} 0 {/if}'/*{/literal}*/],
			['name', /*{literal}*/'{if $article.active} "false" {else} "true" {/if}'/*{/literal}*/, /*{literal}*/'{if $article.active == "false"} 1 {else} 0 {/if}'/*{/literal}*/],
			['description', /*{literal}*/'{if $article.active} "false" {else} "true" {/if}'/*{/literal}*/, /*{literal}*/'{if $article.active == "false"} 1 {else} 0 {/if}'/*{/literal}*/],
			['parentid', /*{literal}*/'{if $article.active} "false" {else} "true" {/if}'/*{/literal}*/, /*{literal}*/'{if $article.active == "false"} 1 {else} 0 {/if}'/*{/literal}*/]
		];

		// create the data store
		var store = Ext.create('Ext.data.ArrayStore', {
			fields: [
				{ name: 'swField' },
				{ name: 'export' },
				{ name: 'import' }
			],
			data: myData
		});
		
		
		me.rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 2,
            autoCancel: true
        });

		// create the Grid
		return Ext.create('Ext.grid.Panel', {
			store: store,
			plugins: [ me.rowEditor ],
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
					text: 'Shopware Field',
					flex: 1,
					sortable: true,
					dataIndex: 'swField',
					editor: {
						xtype: 'combobox',
						editable: false,
						queryMode: 'local',
						allowBlank: false,
						store: [{ id: 1, name: 'id' }],
						displayField: 'name',
						valueField: 'id'
					}
				}, {
					text: 'Export Conversion',
					flex: 2,
					sortable: false,
					dataIndex: 'export',
					editor: {
						xtype: 'textarea',
						allowBlank: false
					}
				}, {
					text: 'Import Conversion',
					flex: 2,
					sortable: false,
					dataIndex: 'import',
					editor: {
						xtype: 'textarea',
						allowBlank: false
					}
				}, {
					xtype: 'actioncolumn',
					width: 90,
					items: [
						{
							iconCls: 'sprite-minus-circle-frame',
							action: 'deletePosition',
							tooltip: 'Delete Mapping',
							handler: function(view, rowIndex, colIndex, item) {
							}
						}]
				}],
			height: 350,
			width: 600
		});
	},
	
	/**
     * Creates the grid selection model for checkboxes
     *
     * @return [Ext.selection.CheckboxModel] grid selection model
     */
    getGridSelModel:function () {
        var me = this;

        var selModel = Ext.create('Ext.selection.CheckboxModel', {
            listeners:{
                // Unlocks the save button if the user has checked at least one checkbox
                selectionchange:function (sm, selections) {
                    me.deletePositionsButton.setDisabled(selections.length === 0);
                }
            }
        });
        return selModel;
    },
	
	/**
     * Creates the toolbar for the position grid.
     * @return Ext.toolbar.Toolbar
     */
    createGridToolbar: function() {
        var me = this;

        me.deletePositionsButton = Ext.create('Ext.button.Button', {
            iconCls:'sprite-minus-circle-frame',
            text: 'Delete Selected',
            disabled:true,
            action:'deletePosition',
            handler: function() {
                me.fireEvent('deleteMultiplePositions', me.record, me.orderPositionGrid, {
                    callback: function(order) {
                        me.fireEvent('updateForms', order, me.up('window'));
                    }
                });
            }
        });

        me.addPositionButton = Ext.create('Ext.button.Button', {
            iconCls:'sprite-plus-circle-frame',
            text: 'Add New',
            action:'addPosition',
            handler: function() {
                me.fireEvent('addPosition', me.record, me.orderPositionGrid, me.rowEditor)
            }
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock:'top',
            ui: 'shopware-ui',
            items:[
                me.addPositionButton,
                me.deletePositionsButton
            ]
        });
    },
});
//{/block}

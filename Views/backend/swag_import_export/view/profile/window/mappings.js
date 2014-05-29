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

        var store = Ext.create('Shopware.apps.SwagImportExport.store.Conversion');
        store.load({
            params: {
                profileId: me.profileId
            }
        });
        
        store.getProxy().setExtraParam('profileId', me.profileId);

        me.rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 2,
            autoCancel: true
        });

        // create the Grid
        me.conversionsGrid = Ext.create('Ext.grid.Panel', {
            store: store,
            plugins: [me.rowEditor],
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
                    text: 'Shopware Field',
                    flex: 1,
                    sortable: true,
                    dataIndex: 'variable',
                    editor: {
                        xtype: 'textarea',
                        allowBlank: false
                    }
                }, {
                    text: 'Export Conversion',
                    flex: 2,
                    sortable: false,
                    dataIndex: 'exportConversion',
                    editor: {
                        xtype: 'textarea',
                        allowBlank: true
                    }
                }, {
                    text: 'Import Conversion',
                    flex: 2,
                    sortable: false,
                    dataIndex: 'importConversion',
                    editor: {
                        xtype: 'textarea',
                        allowBlank: true
                    }
                }, {
                    xtype: 'actioncolumn',
                    width: 90,
                    items: [
                        {
                            iconCls: 'sprite-minus-circle-frame',
                            action: 'deleteConversion',
                            tooltip: 'Delete Mapping',
                            handler: function(view, rowIndex, colIndex, item) {
                                me.fireEvent("deleteConversion", me.conversionsGrid.getStore(), rowIndex);
                            }
                        }]
                }],
            height: 350,
            width: 600
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
            text: 'Delete Selected',
            disabled: true,
            action: 'deleteConversion',
            handler: function() {
                me.fireEvent('deleteMultipleConversions', me.conversionsGrid.getStore(), me.conversionsGrid.getSelectionModel());
            }
        });

        me.addConversionButton = Ext.create('Ext.button.Button', {
            iconCls:'sprite-plus-circle-frame',
            text: 'Add New',
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

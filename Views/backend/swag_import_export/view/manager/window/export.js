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
//{namespace name=backend/swag_import_export/view/main}
//{block name="backend/swag_import_export/view/manager/window/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.window.Export', {
    /**
     * Define that the order main window is an extension of the enlight application window
     * @string
     */
    extend: 'Enlight.app.SubWindow',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-window-export',
    /**
     * Define window width
     * @integer
     */
    width: 360,
    /**
     * Define window height
     * @integer
     */
    height: 130,
    /**
     * Display no footer button for the detail window
     * @boolean
     */
    footerButton: false,
    /**
     * Set vbox layout and stretch align to display the toolbar on top and the button container
     * under the toolbar.
     * @object
     */
    layout: {
        align: 'stretch',
        type: 'vbox'
    },
    /**
     * If the modal property is set to true, the user can't change the window focus to another window.
     * @boolean
     */
    modal: true,
    /**
     * The body padding is used in order to have a smooth side clearance.
     * @integer
     */
    bodyPadding: 10,
    /**
     * Disable the close icon in the window header
     * @boolean
     */
    closable: false,
    /**
     * Disable window resize
     * @boolean
     */
    resizable: false,
    /**
     * Disables the maximize button in the window header
     * @boolean
     */
    maximizable: false,
    /**
     * Disables the minimize button in the window header
     * @boolean
     */
    minimizable: false,
    /**
     * The title shown in the window header
     */
    title: '{s name=swag_import_export/manager/window/export/title}Export window{/s}',
    /**
     * Constructor for the generation window
     * Registers events and adds all needed content items to the window
     */
    initComponent: function() {
        var me = this;
        me.registerEvents();
        me.items = me.createItems();
        me.callParent(arguments);
    },
    /**
     * Helper function to create the window items.
     */
    createItems: function() {
        var me = this;
        
        me.exportProgress = me.createProgressBar('exporting', me.batchConfig.snippet);

        return [
            me.exportProgress,
            me.createButtons()
        ];
    },
    /**
     * Registers events in the event bus for firing events when needed
     */
    registerEvents: function() {
        this.addEvents('startProcess', 'cancelProcess');
    },
    /**
     * Returns a new progress bar for a detailed view of the exporting progress status
     *
     * @param name
     * @param text
     * @returns [object]
     */
    createProgressBar: function(name, text) {
        return Ext.create('Ext.ProgressBar', {
            animate: true,
            name: name,
            text: text,
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align'
        });
    },
    /**
     * Returns a container with all exporting buttons
     * The cancel button is hidden by default
     *
     * @returns [object]
     */
    createButtons: function() {
        var me = this;

        me.startButton = me.createStartButton();
        me.closeButton = me.createCloseButton();
        me.cancelButton = me.createCancelButton();

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            items: [
                me.startButton,
                me.cancelButton,
                me.closeButton
            ]
        });
    },
    /**
     * Returns a new start button for the exporting process
     *
     * @returns [object]
     */
    createStartButton: function() {
        var me = this;

        return Ext.create('Ext.button.Button', {
            text: 'Start exporting',
            cls: 'primary',
            action: 'start',
            handler: function() {
                me.fireEvent('startProcess', me, this);
            }
        });
    },
    /**
     * Returns a new cancel button for the exporting process
     *
     * @returns [object]
     */
    createCancelButton: function() {
        var me = this;

        return Ext.create('Ext.button.Button', {
            text: 'Cancel',
            cls: 'primary',
            action: 'cancel',
            disabled: false,
            hidden: true,
            width: 160,
            handler: function() {
                me.fireEvent('cancelProcess', this);
            }
        });
    },
    /**
     * Returns a new close button for the exporting process window
     *
     * @returns [object]
     */
    createCloseButton: function() {
        var me = this;

        return Ext.create('Ext.button.Button', {
            text: 'Close',
            flex: 1,
            action: 'closeWindow',
            cls: 'secondary',
            handler: function() {
                me.destroy();
            }
        });
    }
});
//{/block}
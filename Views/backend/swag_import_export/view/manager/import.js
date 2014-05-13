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
//{block name="backend/swag_import_export/view/manager/export"}
Ext.define('Shopware.apps.SwagImportExport.view.manager.Import', {
    extend: 'Ext.container.Container',
    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias: 'widget.swag-import-export-manager-import',
    title: '{s name=swag_import_export/manager/import/title}Import{/s}',
    layout: 'fit',
    style: {
        background: '#fff'
    },
    bodyPadding: 10,
    autoScroll: true,
    snippets: {
        configText: '{s name=configImportText}New imports should always be tested in advance in a test environment. Before importing any file in your \n\
                    productive environment, carry out a complete backup of your datebase, so that in the event of a failing import, it can be reinstalled easily. \n\
                    Depending on you server system and the size of the file, the import could take quite a while.{/s}',
    },
    /*
     * profile store
     */
    profilesStore: Ext.create('Shopware.apps.SwagImportExport.store.ProfileList').load(),
    initComponent: function() {
        var me = this;

        me.items = [me.createFormPanel()];

        me.callParent(arguments);
    },
    /**
     * Creates the main form panel for the component which
     * features all neccessary form elements
     *
     * @return [object] me.formPnl - generated Ext.form.Panel
     */
    createFormPanel: function() {
        var me = this;

        // Form panel which holds off all options
        me.formPanel = Ext.create('Ext.form.Panel', {
            bodyPadding: 20,
            border: 0,
            autoScroll: true,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [
                me.createDropZone(), me.createConfigurationFieldset()
            ],
            dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'shopware-ui',
                    cls: 'shopware-toolbar',
                    items: ['->', {
                            text: 'Import',
                            cls: 'primary',
                            action: 'swag-import-export-manager-import-button'
                        }]
                }]
        });

        return me.formPanel;
    },
    /**
     * Creates a new upload drop zone which uploads the dropped files
     * to the server and adds them to the active album
     *
     * @return [object] this.dropZone - created Shopware.app.FileUpload
     */
    createDropZone: function() {
        var me = this;

        me.dropZone = Ext.create('Shopware.app.FileUpload', {
            requestURL: '{url controller="mediaManager" action="upload"}',
            hideOnLegacy: true,
            showInput: false,
            checkType: false,
            checkAmount: false,
            enablePreviewImage: false,
            dropZoneText: 'UPLOAD FILES USING DRAG + DROP',
            height: 100
        });

        return Ext.create('Ext.form.FieldSet', {
            title: "Drag'n'Drop import",
            padding: 12,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            items: [{
                    xtype: 'container',
                    padding: '0 0 8',
                    items: [me.dropZone]
                }]
        });

    },
    createConfigurationFieldset: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            title: "Import configuration",
            padding: 12,
            defaults: {
                labelStyle: 'font-weight: 700; text-align: right;'
            },
            html: '<i style="color: grey" >' + me.snippets.configText + '</i>',
            items: [{
                    xtype: 'container',
                    padding: '0 0 8',
                    items: [me.createUploadField(), me.createProfileCombo()]
                }]
        });
    },
    createSelectFile: function() {

    },
    createProfileCombo: function() {
        var me = this;

        return Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: 'Select profile',
            store: me.profilesStore,
            labelStyle: 'font-weight: 700; text-align: left;',
            width: 400,
            labelWidth: 150,
            valueField: 'id',
            displayField: 'name',
            editable: false,
            name: 'profile'
        });
    },
    /**
     * Creates the container with the both upload fields.
     * @return Ext.container.Container
     */
    createUploadField: function() {
        var me = this;

        // Media selection field
        me.mediaSelection = Ext.create('Shopware.MediaManager.MediaSelection', {
            name: 'importFile',
            fieldLabel: 'Select file',
            multiSelect: false,
            anchor: '100%',
            validTypes: me.getImportAllowedExtensions(),
            allowBlank: false,
            buttonOnly: false,
            width: 515,
            labelWidth: 150
        });

        return Ext.create('Ext.container.Container', {
            margin: '0 0 5 0',
            layout: 'column',
            items: [
                me.mediaSelection
            ]
        });
    },
    /**
     * Method to set the allowed file extension for the import
     * @return Array of strings
     */
    getImportAllowedExtensions: function() {
        return ['csv', 'xml', 'xls'];
    }

});
//{/block}
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
 * Shopware UI - Media Manager Thumbnail Controller
 *
 * The thumbnail controller handles the thumbnail main window,
 * its elements and the batch calls for the thumbnail generation.
 *
 * @category    Shopware
 * @package     MediaManager
 * @copyright   Copyright (c) shopware AG (http://www.shopware.de)
 */

//{namespace name="backend/swag_import_export/view/main"}
//{block name="backend/swag_import_export/controller/export"}
Ext.define('Shopware.apps.SwagImportExport.controller.Export', {
    extend: 'Ext.app.Controller',
    snippets: {
    },
    init: function() {
        var me = this;

        me.control({
            // Export button
            'swag-import-export-manager-export button[action=swag-import-export-manager-export-button]': {
                click: me.onExport
            }
        });

        me.callParent(arguments);
    },
    onExport: function(btn) {
        var me = this,
                form = btn.up('form'),
                values = form.getValues();

        me.onCreateExportWindow(values);
    },
    onCreateExportWindow: function(values) {
        var me = this;

        me.window = me.getView('Shopware.apps.SwagImportExport.view.manager.window.Export').create({}).show();
    }
});
//{/block}

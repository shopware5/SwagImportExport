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
//{namespace name="backend/swag_import_export/view/main"}
//{block name="backend/swag_import_export/controller/operation"}
Ext.define('Shopware.apps.SwagImportExport.controller.Operation', {
	extend: 'Ext.app.Controller',
	
	/**
     * This method creates listener for events fired from the export 
     */
    init: function() {
        var me = this;

        me.control({
            // Export button
            'swag-import-export-manager-operation': {
                deleteSession: me.onDeleteSession,
                downloadFile: me.onDownloadFile
            }
        });

        me.callParent(arguments);
    },
    onDownloadFile: function(record) {
        var url = '{url action="downloadFile"}' + '/fileName/' + record.get('fileName');
        window.open(url, '_blank');        
        
    },
    onDeleteSession: function(record, sessionStore) {
        var me = this,
            message = 'Are you sure, you want to delete ' + ' ' + record.get('fileName'),
            title = 'Delete session';

        // we do not just delete - we are polite and ask the user if he is sure.
        Ext.MessageBox.confirm(title, message, function (response) {
            if ( response !== 'yes' ) {
                return;
            }
            record.destroy({
                callback: function(data, operation) {
                    if ( operation.success === true ) {
//                        Shopware.Notification.createGrowlMessage('Success');
                    } else {
//                        Shopware.Notification.createGrowlMessage('False');
                    }
                    sessionStore.reload();
                }
            });
        });
    }
});
//{/block}

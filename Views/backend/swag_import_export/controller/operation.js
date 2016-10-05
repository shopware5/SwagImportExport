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
	
    snippets: {
        failure: {
            title: '{s name=swag_import_export/operation/failure_title}Import/Export download{/s}',
            msg: '{s name=swag_import_export/operation/failure}File does not exists.{/s}'
        },
        messages: {
            deleteOperationTitle: '{s name=swag_import_export/operation/delete_operations_tittle}Delete selected Operation(s)?{/s}',
            deleteOperation: '{s name=swag_import_export/operation/delete_operations}Are you sure you want to delete the selected Operation(s)?{/s}',
            successTitle: '{s name=swag_import_export/operation/success_title}Success{/s}',
            deleteSuccess: '{s name=swag_import_export/operation/delete_success}The selected operation(s) have been removed{/s}',
            growlMessage: '{s name=swag_import_export/operation/operation}Operation{/s}'
        }

    },
	/**
     * This method creates listener for events fired from the export 
     */
    init: function() {
        var me = this;

        me.control({
            // Export button
            'swag-import-export-manager-operation': {
                deleteSession: me.onDeleteSession,
                deleteMultipleSessions: me.onDeleteMultipleSessions,
                downloadFile: me.onDownloadFile
            }
        });

        me.callParent(arguments);
    },
    onDownloadFile: function(record) {
        var me = this;
        var url = '{url action="downloadFile"}' + '?fileName=' + record.get('fileName');
        var urlExists = me.urlExists(url);
        if (urlExists !== true) {
            Shopware.Msg.createStickyGrowlMessage({
                title: me.snippets.failure.title,
                text: me.snippets.failure.msg
            });
        } else {
            window.open(url, '_blank');
        }
    },
    urlExists: function(url)
    {
        var http = new XMLHttpRequest();
        http.open('HEAD', url, false);
        http.send();
        return http.status!=404;
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
    },
    /**
     * @param records
     */
    onDeleteMultipleSessions: function (records, sessionStore) {
        var me = this;

        if (records.length > 0) {
            //ask the user if he is sure.
            Ext.MessageBox.confirm(
                me.snippets.messages.deleteOperationTitle,
                me.snippets.messages.deleteOperation,
                function (response) {
                    if (response !== 'yes') {
                        return;
                    }

                    sessionStore.remove(records);
                    sessionStore.sync({
                        callback: function () {
                            Shopware.Notification.createGrowlMessage(
                                me.snippets.messages.successTitle,
                                me.snippets.messages.deleteSuccess,
                                me.snippets.growlMessage
                            );
                            //store.currentPage = 1;
                            sessionStore.load();
                        }
                    });
                }
            );
        }
    }
});
//{/block}

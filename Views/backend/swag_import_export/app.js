/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware SwagImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImportExport
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */

//{namespace name="backend/swag_import_export/view/main"}
//{block name="backend/swag_import_export/app"}
Ext.define('Shopware.apps.SwagImportExport', {

    /**
     * Extends from our special controller, which handles the
     * sub-application behavior and the event bus
     * @string
     */
    extend: 'Enlight.app.SubApplication',

    /**
     * The name of the module. Used for internal purpose
     * @string
     */
    name: 'Shopware.apps.SwagImportExport',

    /**
     * Sets the loading path for the sub-application.
     *
     * Note that you'll need a "loadAction" in your
     * controller (server-side)
     * @string
     */
    loadPath: '{url action=load}',

    /**
     * Enables the Shopware bulk loading system.
     * @boolean
     */
    bulkLoad: true,

    /**
     * Requires controllers for sub-application
     * @array
     */
    controllers: [ 'Main', 'Export', 'Import', 'Session', 'Profile' ],

    /**
     * Used views here to improve bulk loading
     */
    views: [
        'Window',
        'profile.window.Mappings',
        'profile.window.Iterator',
        'profile.window.Column',
		'profile.Profile',
        'profile.Grid',
        'profile.Window',
        'profile.tree.DragAndDrop',
		'manager.Manager',
		'manager.Export',
		'manager.Import',
		'manager.Session',
		'manager.window.Export',
		'manager.window.Import',
        'manager.window.Log',
        'log.Log'
    ],

    /**
     * Requires models for sub-application
     * @array
     */
    models: ['Profile', 'ProfileList', 'SessionList', 'Log', 'Conversion'],

    /**
     * Requires stores for sub-application
     * @array
     */
    stores: ['Profile', 'ProfileList', 'SessionList', 'Log', 'Conversion'],

    /**
     * Returns the main application window for this is expected
     * by the Enlight.app.SubApplication class.
     * The class sets a new event listener on the "destroy" event of
     * the main application window to perform the destroying of the
     * whole sub application when the user closes the main application window.
     *
     * This method will be called when all dependencies are solved and
     * all member controllers, models, views and stores are initialized.
     *
     * @private
     * @return [object] mainWindow - the main application window based on Enlight.app.Window
     */
    launch: function () {
        return this.getController('Main').mainWindow;
    }
});
    //{include file="backend/category/model/tree.js"}
    //{include file="backend/category/store/tree.js"}
//{/block}
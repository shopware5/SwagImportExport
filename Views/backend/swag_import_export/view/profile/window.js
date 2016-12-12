//{namespace name=backend/swag_import_export/view/profile/window}
//{block name="backend/swag_import_export/view/profile/window"}
Ext.define('Shopware.apps.SwagImportExport.view.profile.Window', {
    extend: 'Enlight.app.Window',

    alias: 'widget.swag-import-export-profile-window',

    height: '80%',
    width: '80%',

    layout: 'fit',

    title: '{s name=swag_import_export/profile/window/title}Profile settings{/s}',

    config: {
        readOnly: false,
        profileId: null
    },

    /**
     * After saving a new profile, set new profileId
     * change buttons, and active profileconfigurator tree
     *
     * @param newValue
     * @param oldValue
     */
    updateProfileId: function(newValue, oldValue) {
        var me = this;

        if (Ext.isEmpty(oldValue)) {
            var treeStore = me.profileConfigurator.treeStore,
                columnStore = me.profileConfigurator.columnStore,
                selectionStore = me.profileConfigurator.sectionStore;

            me.down('#baseProfile').setReadOnly(true);
            treeStore.getProxy().setExtraParam('profileId', newValue);
            treeStore.load();
            columnStore.getProxy().setExtraParam('profileId', newValue);
            columnStore.load();
            selectionStore.getProxy().setExtraParam('profileId', newValue);
            selectionStore.load();
            if (!me.readOnly) {
                me.down('#savebutton').enable();
                me.profileConfigurator.down('toolbar[dock=top]').enable();
                me.profileConfigurator.changeFieldReadOnlyMode(false);
                me.profileConfigurator.hideFormFields();
                if (Ext.isDefined(me.profileConfigurator.treePanel.getView().getPlugin('customtreeviewdragdrop').dragZone)) {
                    me.profileConfigurator.treePanel.getView().getPlugin('customtreeviewdragdrop').dragZone.unlock();
                }
            }
            me.profileConfigurator.enable();
        }
    },

    initComponent: function() {
        var me = this;

        me.profileStore = Ext.create('Shopware.apps.SwagImportExport.store.ProfileList', {
            pageSize: 200,
            autoLoad: true
        });

        me.items = me.buildItems();
        me.dockedItems = me.buildDockedItems();

        me.callParent(arguments);
    },

    buildItems: function() {
        var me = this;

        me.profileConfigurator = Ext.create('Shopware.apps.SwagImportExport.view.profile.Profile', {
            readOnly: me.readOnly,
            disabled: Ext.isEmpty(me.profileId),
            profileId: me.profileId,
            flex: 1
        });

        return [{
            xtype: 'container',
            style: 'background-color: #F0F2F4;',
            padding: 5,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [{
                xtype: 'form',
                trackResetOnLoad: true,
                itemId: 'profilebaseform',
                padding: 15,
                defaults: {
                    anchor: '50%'
                },
                border: false,
                items: [{
                    xtype: 'hidden',
                    name: 'id'
                }, {
                    xtype: 'textfield',
                    itemId: 'namefield',
                    readOnly: me.readOnly,
                    fieldLabel: '{s name=swag_import_export/profile/window/field_name}Profile name{/s}',
                    name: 'name',
                    allowBlank: false
                }, {
                    xtype: 'hidden',
                    name: 'type'
                }, {
                    xtype: 'combo',
                    name: 'baseProfile',
                    itemId: 'baseProfile',
                    fieldLabel: '{s name=swag_import_export/profile/window/field_based_on}Based on{/s}',
                    allowBlank: false,
                    editable: false,
                    triggerAction: 'all',
                    store: me.profileStore,
                    listConfig: {
                        getInnerTpl: function () {
                            return Ext.XTemplate(
                                '{literal}'  +
                                '<tpl if="translation">{ translation } <i>({ name })</i>' +
                                '<tpl else>{ name }</tpl>' +
                                '{/literal}'
                            );
                        },
                        width: 305
                    },
                    valueField: 'id',
                    displayTpl: new Ext.XTemplate(
                        '<tpl for=".">' +
                        '{literal}'  +
                        '{[typeof values === "string" ? values : this.getFormattedName(values)]}' +
                        '<tpl if="xindex < xcount">' + me.delimiter + '</tpl>' +
                        '{/literal}' +
                        '</tpl>',
                        {
                            getFormattedName: function(values) {
                                if (values.translation) {
                                    return Ext.String.format('[0] ([1])', values.translation, values.name);
                                }
                                return values.name;
                            }
                        }
                    ),
                    helpText: '{s name=swag_import_export/profile/window/profile_helptext}The selected default profile can be individually extended and modified via the configuration tree and be saved as a custom profile.{/s}',
                    listeners: {
                        boxready: function(combo) {
                            combo.relayEvents(combo.getStore(), ['load'], 'store');
                        },
                        storeload: function() {
                            if (!Ext.isEmpty(me.profileId)) {
                                if (Ext.isEmpty(this.getValue())) {
                                    this.disable();
                                    return;
                                }
                                this.validate();
                            }
                        },
                        select: function(combo, selectedRecords) {
                            var selection = selectedRecords[0],
                                typefield = combo.previousSibling('hidden[name=type]');

                            typefield.setValue(selection.get('type'))
                            me.fireEvent('baseprofileselected', me, selection);
                        }
                    }
                }]
            }, me.profileConfigurator]
        }];
    },

    buildDockedItems: function() {
        var me = this;

        me.bottomBar = Ext.create('Ext.toolbar.Toolbar', {
            xtype: 'toolbar',
            dock: 'bottom',
            ui: 'shopware-ui',
            cls: 'shopware-toolbar',
            style: {
                backgroundColor: '#F0F2F4',
                borderRight: '1px solid #A4B5C0',
                borderLeft: '1px solid #A4B5C0',
                borderTop: '1px solid #A4B5C0',
                borderBottom: '1px solid #A4B5C0'
            },
            items: ['->', {
                text: '{s name=swag_import_export/profile/window/close}Close{/s}',
                cls: 'secondary',
                handler: function() {
                    me.close();
                }
            }, {
                text: '{s name=swag_import_export/profile/window/save}Save{/s}',
                cls: 'primary',
                itemId: 'savebutton',
                disabled: me.readOnly,
                handler: function () {
                    me.fireEvent('saveProfile', me);

                    if (me.profileId !== null) {
                        me.fireEvent(
                            'saveNode',
                            me.profileConfigurator.treePanel,
                            me.profileConfigurator.treeStore,
                            me.profileConfigurator.selectedNodeId,
                            me.profileConfigurator.formPanel.child('#nodeName').getValue(),
                            me.profileConfigurator.formPanel.child('#swColumn').getValue(),
                            me.profileConfigurator.formPanel.child('#defaultValue').getValue(),
                            me.profileConfigurator.formPanel.child('#adapter').getValue(),
                            me.profileConfigurator.formPanel.child('#parentKey').getValue()
                        );
                    }
                }
            }]
        });

        return [
            me.bottomBar
        ];
    }
});
//{/block}

Ext.define('Shopware.apps.SwagImportExport.view.profile.tree.DragAndDrop', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',
    
    alias: 'plugin.customtreeviewdragdrop',
    
    onViewRender: function() {
        var me = this;
        
        me.callParent(arguments);
        
        /**
         * @TODO: override onNodeOver, because isValidDropPoint is in private class
         */
        me.dropZone.isValidDropPoint = function(node, position, dragZone, e, data) {
            if (!node || !data.item) {
                return false;
            }

            var view = me.dropZone.view,
                    targetNode = view.getRecord(node),
                    draggedRecords = data.records,
                    dataLength = draggedRecords.length,
                    ln = draggedRecords.length,
                    i, record;
            
            // No drop position, or dragged records: invalid drop point
            if (!(targetNode && position && dataLength)) {
                return false;
            }

            // If the targetNode is within the folder we are dragging
            for (i = 0; i < ln; i++) {
                record = draggedRecords[i];
                if (record.isNode && record.contains(targetNode)) {
                    return false;
                }
            }

            // Respect the allowDrop field on Tree nodes
            if (position === 'append' && targetNode.get('allowDrop') === false) {
                return false;
            }
            else if (position !== 'append' && targetNode.parentNode.get('allowDrop') === false) {
                return false;
            }

            // If the target record is in the dragged dataset, then invalid drop
            if (Ext.Array.contains(draggedRecords, targetNode)) {
                return false;
            }
            
            // Custom checks
            for (i = 0; i < ln; i++) {
                record = draggedRecords[i];
                if (record.get('adapter') !== targetNode.get('adapter')) {
                    return false;
                }
            }

            // @TODO: fire some event to notify that there is a valid drop possible for the node you're dragging
            // Yes: this.fireViewEvent(blah....) fires an event through the owning View.
            return true;
        };
    }
});

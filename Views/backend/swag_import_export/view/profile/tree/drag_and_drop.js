Ext.define('Shopware.apps.SwagImportExport.view.profile.tree.DragAndDrop', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',
    
    alias: 'plugin.customtreeviewdragdrop',
    
    onViewRender: function() {
        var me = this;
        
        me.callParent(arguments);
        
        /**
         * Custom drag&drop validation function for profile editor tree
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

            if (position === 'append') {
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
            if (targetNode.get('allowDrop') === false || targetNode.parentNode.get('allowDrop') === false) {
                return false;
            }

            // If the target record is in the dragged dataset, then invalid drop
            if (Ext.Array.contains(draggedRecords, targetNode)) {
                return false;
            }
            
            // Custom checks
            for (i = 0; i < ln; i++) {
                record = draggedRecords[i];
                // check if the node is in the same iteration
                if (record.parentNode !== targetNode.parentNode
                    && record.get('adapter') !== targetNode.get('adapter')
                ) {
                    return false;
                }
                // special case check: node cannot be inserted in the same level as the iteration node
                if (record.parentNode !== targetNode.parentNode
                    && position === 'before' && record.get('adapter') !== targetNode.parentNode.get('adapter')
                ) {
                    return false;
                }
            }

            return true;
        };
    }
});

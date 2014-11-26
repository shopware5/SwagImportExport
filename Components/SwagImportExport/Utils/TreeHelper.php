<?php

namespace Shopware\Components\SwagImportExport\Utils;

class TreeHelper
{

    /**
     * Converts the JSON tree to ExtJS tree
     * 
     * @param array $node
     * @param boolean $isInIteration
     * @param string $adapter
     * @return array
     */
    static public function convertToExtJSTree(array $node, $isInIteration = false, $adapter = '')
    {
        $parentKey = '';
        $children = array();

        if ($node['type'] == 'iteration') {
            $isInIteration = true;
            $adapter = $node['adapter'];
            $parentKey = $node['parentKey'];

            $icon = 'sprite-blue-folders-stack';
        } else if ($node['type'] == 'leaf') {
            $icon = 'sprite-icon_taskbar_top_inhalte_active';
        } else { // $node['type'] == 'node' 
            $icon = '';
        }

        // Get the attributes
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $attribute) {
                $children[] = array(
                    'id' => $attribute['id'],
                    'text' => $attribute['name'],
                    'type' => $attribute['type'],
                    'index' => $attribute['index'],
                    'adapter' => $adapter,
                    'leaf' => true,
                    'expanded' => false,
                    'iconCls' => 'sprite-sticky-notes-pin',
                    'type' => 'attribute',
                    'swColumn' => $attribute['shopwareField'],
                    'inIteration' => $isInIteration
                );
            }
        }

        // Get the child nodes
        if (isset($node['children']) && count($node['children']) > 0) {
            foreach ($node['children'] as $child) {
                $children[] = static::convertToExtJSTree($child, $isInIteration, $adapter);
            }
        }

        return array(
            'id' => $node['id'],
            'type' => $node['type'],
            'index' => $node['index'],
            'text' => $node['name'],
            'adapter' => $adapter,
            'parentKey' => $parentKey,
            'leaf' => false,
            'expanded' => true,
            'iconCls' => $icon,
            'swColumn' => $node['shopwareField'],
            'inIteration' => $isInIteration,
            'children' => $children
        );
    }

    /**
     * Helper function which appends child node to the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function appendNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] == 'attribute') {
                $node['attributes'][] = array(
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else if ($child['type'] == 'node') {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else if ($child['type'] == 'iteration') {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'name' => $child['text'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'adapter' => $child['adapter'],
                    'parentKey' => $child['parentKey'],
                );
            } else {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                );
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::appendNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds node from the tree
     * 
     * @param string $id
     * @param array $node
     * @param string $parentId
     * @return boolean
     */
    static public function getNodeById($id, array $node, $parentId = 'root')
    {
        if ($node['id'] == $id) { // the node is found
            $node['parentId'] = $parentId;
            return $node;
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $result = static::getNodeById($id, $attribute, $node['id']);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
            if (isset($node['children'])) {
                foreach ($node['children'] as $childNode) {
                    $result = static::getNodeById($id, $childNode, $node['id']);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which appends child node to the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function moveNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] == 'attribute') {
                unset($child['parentId']);
                $node['attributes'][] = $child;
            } else if ($child['type'] == 'node') {
                unset($child['parentId']);
                $node['children'][] = $child;
            } else {
                unset($child['parentId']);
                $node['children'][] = $child;
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::moveNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and changes node from the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function changeNode(array $child, array &$node)
    {
        if ($node['id'] == $child['id']) { // the node is found
            $node['name'] = $child['text'];
            $node['type'] = $child['type'];
            $node['index'] = $child['index'];
            if (isset($child['swColumn'])) {
                $node['shopwareField'] = $child['swColumn'];
            } else {
                unset($node['shopwareField']);
            }

            if ($child['type'] == 'iteration') {
                if (isset($child['adapter'])) {
                    $node['adapter'] = $child['adapter'];
                } else {
                    unset($node['adapter']);
                }
                if (isset($child['parentKey'])) {
                    $node['parentKey'] = $child['parentKey'];
                } else {
                    unset($node['parentKey']);
                }
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as &$childNode) {
                    if (static::changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and deletes node from the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function deleteNode(array $child, array &$node)
    {
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['children'][$key]);
                    if (count($node['children']) == 0) {
                        unset($node['children']);
                    }
                    return true;
                } else if (static::deleteNode($child, $childNode)) {
                    return true;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['attributes'][$key]);
                    if (count($node['attributes']) == 0) {
                        unset($node['attributes']);
                    }
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Sorting tree via index key
     * 
     * @param array $node
     * @return array
     */
    static public function reorderTree($node)
    {
        $reorderdNode = array();
        if (is_array($node) && isset($node['children'])) {
            foreach ($node as $key => $value) {
                if ($key === 'children' || $key === 'attributes') {
                    $count = count($value);
                    foreach ($value as $currentIndex => $innerValue) {
                        $value3 = self::reorderTree($innerValue);
                        
                        // fix for to-be-deleted nodes
                        if (isset($reorderdNode[$key][$innerValue['index']])) {
                            $reorderdNode[$key][$count + $currentIndex] = $reorderdNode[$key][$innerValue['index']];
                        }
                        $reorderdNode[$key][$innerValue['index']] = $value3;
                    }
                    ksort($reorderdNode[$key]);
                    
                } else {
                    $reorderdNode[$key] = $value;
                }
            }
        } else {
            $reorderdNode = $node;
        }
        
        return $reorderdNode;
    }

    /**
     * Returns the default tree for a profile by given profile type. <br/>
     * Note: The id of the root node MUST be 'root', but the name may be different.
     * 
     * @param string $profileType
     * @return string
     * @throws \Exception
     */
    static public function getDefaultTreeByProfileType($profileType)
    {
        switch ($profileType) {
            case 'categories':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryID","shopwareField":"id"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":3,"name":"position","shopwareField":"position"},{"id":"53e0d1414b0d7","type":"leaf","index":4,"name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":5,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":6,"name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":7,"name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":8,"name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":9,"name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":10,"name":"blog","shopwareField":"blog"},{"id":"53e9f61981e83","type":"leaf","index":11,"name":"showfiltergroups","shopwareField":"showFilterGroups"},{"id":"53e9f62a03f55","type":"leaf","index":12,"name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":13,"name":"hidefilter","shopwareField":"hideFilter"},{"id":"541c35c378bc9","type":"leaf","index":14,"name":"attribute_attribute1","shopwareField":"attributeAttribute1"},{"id":"541c36d0bba0f","type":"leaf","index":15,"name":"attribute_attribute2","shopwareField":"attributeAttribute2"},{"id":"541c36d63fac6","type":"leaf","index":16,"name":"attribute_attribute3","shopwareField":"attributeAttribute3"},{"id":"541c36da52222","type":"leaf","index":17,"name":"attribute_attribute4","shopwareField":"attributeAttribute4"},{"id":"541c36dc540e3","type":"leaf","index":18,"name":"attribute_attribute5","shopwareField":"attributeAttribute5"},{"id":"541c36dd9e130","type":"leaf","index":19,"name":"attribute_attribute6","shopwareField":"attributeAttribute6"}]}]}]}';
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"4","name":"articles","index":0,"type":"","children":[{"id":"53e0d3148b0b2","name":"article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":[{"id":"53e0d365881b7","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},{"id":"53e0d329364c4","type":"leaf","index":1,"name":"mainnumber","shopwareField":"mainNumber"},{"id":"53e0d3a201785","type":"leaf","index":2,"name":"name","shopwareField":"name"},{"id":"53fb1c8c99aac","type":"leaf","index":3,"name":"additionalText","shopwareField":"additionalText"},{"id":"53e0d3fea6646","type":"leaf","index":4,"name":"supplier","shopwareField":"supplierName"},{"id":"53e0d4333dca7","type":"leaf","index":5,"name":"tax","shopwareField":"tax"},{"id":"53e0d44938a70","type":"node","index":6,"name":"prices","shopwareField":"","children":[{"id":"53e0d45110b1d","name":"price","index":0,"type":"iteration","adapter":"price","parentKey":"variantId","shopwareField":"","children":[{"id":"53eddba5e3471","type":"leaf","index":0,"name":"group","shopwareField":"priceGroup"},{"id":"53e0d472a0aa8","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"53e0d48a9313a","type":"leaf","index":2,"name":"pseudoprice","shopwareField":"pseudoPrice"},{"id":"541af979237a1","type":"leaf","index":3,"name":"baseprice","shopwareField":"basePrice"}],"attributes":null}]},{"id":"53fb272db680f","type":"leaf","index":7,"name":"active","shopwareField":"active"},{"id":"53eddc83e7a2e","type":"leaf","index":8,"name":"instock","shopwareField":"inStock"},{"id":"541af5febd073","type":"leaf","index":9,"name":"stockmin","shopwareField":"stockMin"},{"id":"53e0d3e46c923","type":"leaf","index":10,"name":"description","shopwareField":"description"},{"id":"541af5dc189bd","type":"leaf","index":11,"name":"description_long","shopwareField":"descriptionLong"},{"id":"541af601a2874","type":"leaf","index":12,"name":"shippingtime","shopwareField":"shippingTime"},{"id":"541af6bac2305","type":"leaf","index":13,"name":"added","shopwareField":"date"},{"id":"541af75d8a839","type":"leaf","index":14,"name":"changed","shopwareField":"changeTime"},{"id":"541af76ed2c28","type":"leaf","index":15,"name":"releasedate","shopwareField":"releaseDate"},{"id":"541af7a98284d","type":"leaf","index":16,"name":"shippingfree","shopwareField":"shippingFree"},{"id":"541af7d1b1c53","type":"leaf","index":17,"name":"topseller","shopwareField":"topSeller"},{"id":"541af887a00ee","type":"leaf","index":18,"name":"metatitle","shopwareField":"metaTitle"},{"id":"541af887a00ed","type":"leaf","index":18,"name":"keywords","shopwareField":"keywords"},{"id":"541af7f35d78a","type":"leaf","index":19,"name":"minpurchase","shopwareField":"minPurchase"},{"id":"541af889cfb71","type":"leaf","index":20,"name":"purchasesteps","shopwareField":"purchaseSteps"},{"id":"541af88c05567","type":"leaf","index":21,"name":"maxpurchase","shopwareField":"maxPurchase"},{"id":"541af88e24a40","type":"leaf","index":22,"name":"purchaseunit","shopwareField":"purchaseUnit"},{"id":"541af8907b3e3","type":"leaf","index":23,"name":"referenceunit","shopwareField":"referenceUnit"},{"id":"541af9dd95d11","type":"leaf","index":24,"name":"packunit","shopwareField":"packUnit"},{"id":"541af9e03ba80","type":"leaf","index":25,"name":"unitID","shopwareField":"unitId"},{"id":"541af9e2939b0","type":"leaf","index":26,"name":"pricegroupID","shopwareField":"priceGroupId"},{"id":"541af9e54b365","type":"leaf","index":27,"name":"pricegroupActive","shopwareField":"priceGroupActive"},{"id":"541afad534551","type":"leaf","index":28,"name":"laststock","shopwareField":"lastStock"},{"id":"541afad754eb9","type":"leaf","index":29,"name":"suppliernumber","shopwareField":"supplierNumber"},{"id":"541afad9b7357","type":"leaf","index":30,"name":"weight","shopwareField":"weight"},{"id":"541afadc6536c","type":"leaf","index":31,"name":"width","shopwareField":"width"},{"id":"541afadfb5179","type":"leaf","index":32,"name":"height","shopwareField":"height"},{"id":"541afae631bc8","type":"leaf","index":33,"name":"length","shopwareField":"length"},{"id":"541afae97c6ec","type":"leaf","index":34,"name":"ean","shopwareField":"ean"},{"id":"541afdba8e926","name":"similars","index":35,"type":"iteration","adapter":"similar","parentKey":"articleId","shopwareField":"","children":[{"id":"541afdc37e956","type":"leaf","index":0,"name":"similar","shopwareField":"ordernumber"}]},{"id":"53e0d5f7d03d4","type":"","index":36,"name":"configurators","shopwareField":"","children":[{"id":"53e0d603db6b9","name":"configurator","index":0,"type":"iteration","adapter":"configurator","parentKey":"variantId","shopwareField":"","children":[{"id":"542119418283a","type":"leaf","index":0,"name":"configuratorsetID","shopwareField":"configSetId"},{"id":"53e0d6142adca","type":"leaf","index":1,"name":"configuratortype","shopwareField":"configSetType"},{"id":"53e0d63477bef","type":"leaf","index":2,"name":"configuratorGroup","shopwareField":"configGroupName"},{"id":"53e0d6446940d","type":"leaf","index":3,"name":"configuratorOptions","shopwareField":"configOptionName"}],"attributes":null}]},{"id":"54211df500e93","name":"category","index":37,"type":"iteration","adapter":"category","parentKey":"articleId","shopwareField":"","children":[{"id":"54211e05ddc3f","type":"leaf","index":0,"name":"categories","shopwareField":"categoryId"}]}],"attributes":null}],"shopwareField":""}]}';
            case 'articlesInStock':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"articlesInStock","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"article","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":null,"children":[{"id":"5373865547d06","name":"ordernumber","index":0,"type":"leaf","shopwareField":"orderNumber"},{"id":"537388742e20e","name":"instock","index":1,"type":"leaf","shopwareField":"inStock"},{"id":"541c4b9ddc00e","type":"leaf","index":2,"name":"_additionaltext","shopwareField":"additionalText"},{"id":"541c4bc6b7e0a","type":"leaf","index":3,"name":"_supplier","shopwareField":"supplier"},{"id":"541c4bd27761c","type":"leaf","index":4,"name":"_price","shopwareField":"price"}]}]}]}';
            case 'articlesPrices':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Prices","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Price","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"540ff6e624be5","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},{"id":"540ffb5b14291","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"540ffb5cea2df","type":"leaf","index":2,"name":"pricegroup","shopwareField":"priceGroup"},{"id":"540ffb5e68fe5","type":"leaf","index":3,"name":"from","shopwareField":"from"},{"id":"540ffb5fd04ba","type":"leaf","index":4,"name":"pseudoprice","shopwareField":"pseudoPrice"},{"id":"540ffb61558eb","type":"leaf","index":5,"name":"baseprice","shopwareField":"basePrice"},{"id":"540ffda5904e5","type":"leaf","index":6,"name":"_name","shopwareField":"name"},{"id":"540ffc1d66042","type":"leaf","index":7,"name":"_additionaltext","shopwareField":"additionalText"},{"id":"540ffcf5089af","type":"leaf","index":8,"name":"_supplier","shopwareField":"supplierName"}]}]}]}';
            case 'articlesImages':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"images","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"image","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ff1e618a9ad","type":"leaf","index":0,"name":"ordernumber","shopwareField":"ordernumber"},{"id":"5373865547d06","name":"image","index":1,"type":"leaf","shopwareField":"image"},{"id":"537388742e20e","name":"main","index":2,"type":"leaf","shopwareField":"main"},{"id":"53e39a5fddf41","type":"leaf","index":3,"name":"description","shopwareField":"description"},{"id":"53e39a698522a","type":"leaf","index":4,"name":"position","shopwareField":"position"},{"id":"53e39a737733d","type":"leaf","index":5,"name":"width","shopwareField":"width"},{"id":"53e39a7c1a52e","type":"leaf","index":6,"name":"height","shopwareField":"height"},{"id":"54004e7bf3a1a","type":"leaf","index":7,"name":"relations","shopwareField":"relations"}]}]}]}';
            case 'articlesTranslations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"5429676d78b28","type":"leaf","index":0,"name":"articlenumber","shopwareField":"articleNumber"},{"id":"543798726b38e","type":"leaf","index":1,"name":"languageId","shopwareField":"languageId"},{"id":"53ce5e8f25a24","name":"name","index":2,"type":"leaf","shopwareField":"name"},{"id":"53ce5f9501db7","name":"description","index":3,"type":"leaf","shopwareField":"description"},{"id":"53ce5fa3bd231","name":"longdescription","index":4,"type":"leaf","shopwareField":"descriptionLong"},{"id":"53ce5fb6d95d8","name":"keywords","index":5,"type":"leaf","shopwareField":"keywords"},{"id":"542a5df925af2","type":"leaf","index":6,"name":"metatitle","shopwareField":"metaTitle"}]}]}]}';
            case 'orders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"orders","index":1,"type":"node","children":[{"id":"537359399c90d","name":"order","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53eca77b49d6d","type":"leaf","index":0,"name":"orderId","shopwareField":"orderId"},{"id":"5373865547d06","name":"number","index":1,"type":"leaf","shopwareField":"number"},{"id":"53ecb1fa09cfd","type":"leaf","index":2,"name":"customerId","shopwareField":"customerId"},{"id":"53ecb3a3e43fb","type":"leaf","index":3,"name":"orderStatusID","shopwareField":"status"},{"id":"53ecb496e80e0","type":"leaf","index":4,"name":"cleared","shopwareField":"cleared"},{"id":"53ecb4e584159","type":"leaf","index":5,"name":"paymentStatusID","shopwareField":"paymentId"},{"id":"53ecb4f9a203b","type":"leaf","index":6,"name":"dispatchId","shopwareField":"dispatchId"},{"id":"53ecb510a3379","type":"leaf","index":7,"name":"partnerId","shopwareField":"partnerId"},{"id":"53ecb51a93f21","type":"leaf","index":8,"name":"shopId","shopwareField":"shopId"},{"id":"53ecb6a059334","type":"leaf","index":9,"name":"invoiceAmount","shopwareField":"invoiceAmount"},{"id":"53ecb6a74e399","type":"leaf","index":10,"name":"invoiceAmountNet","shopwareField":"invoiceAmountNet"},{"id":"53ecb6b4587ba","type":"leaf","index":11,"name":"invoiceShipping","shopwareField":"invoiceShipping"},{"id":"53ecb6be27e2e","type":"leaf","index":12,"name":"invoiceShippingNet","shopwareField":"invoiceShippingNet"},{"id":"53ecb6db22a2e","type":"leaf","index":13,"name":"orderTime","shopwareField":"orderTime"},{"id":"53ecb6ebaf4c5","type":"leaf","index":14,"name":"transactionId","shopwareField":"transactionId"},{"id":"53ecb7014e7ad","type":"leaf","index":15,"name":"comment","shopwareField":"comment"},{"id":"53ecb7f0df5db","type":"leaf","index":16,"name":"customerComment","shopwareField":"customerComment"},{"id":"53ecb7f265873","type":"leaf","index":17,"name":"internalComment","shopwareField":"internalComment"},{"id":"53ecb7f3baed3","type":"leaf","index":18,"name":"net","shopwareField":"net"},{"id":"53ecb7f518b2a","type":"leaf","index":19,"name":"taxFree","shopwareField":"taxFree"},{"id":"53ecb7f778bb0","type":"leaf","index":20,"name":"temporaryId","shopwareField":"temporaryId"},{"id":"53ecb7f995899","type":"leaf","index":21,"name":"referer","shopwareField":"referer"},{"id":"53ecb8ba28544","type":"leaf","index":22,"name":"clearedDate","shopwareField":"clearedDate"},{"id":"53ecb8bd55dda","type":"leaf","index":23,"name":"trackingCode","shopwareField":"trackingCode"},{"id":"53ecb8c076318","type":"leaf","index":24,"name":"languageIso","shopwareField":"languageIso"},{"id":"53ecb8c42923d","type":"leaf","index":25,"name":"currency","shopwareField":"currency"},{"id":"53ecb8c74168b","type":"leaf","index":26,"name":"currencyFactor","shopwareField":"currencyFactor"},{"id":"53ecb9203cb33","type":"leaf","index":27,"name":"remoteAddress","shopwareField":"remoteAddress"},{"id":"53fddf437e561","type":"node","index":28,"name":"details","shopwareField":"","children":[{"id":"53ecb9c7d602d","type":"leaf","index":0,"name":"orderDetailId","shopwareField":"orderDetailId"},{"id":"53ecb9ee6f821","type":"leaf","index":1,"name":"articleId","shopwareField":"articleId"},{"id":"53ecbaa627334","type":"leaf","index":2,"name":"taxId","shopwareField":"taxId"},{"id":"53ecba416356a","type":"leaf","index":3,"name":"taxRate","shopwareField":"taxRate"},{"id":"53ecbaa813093","type":"leaf","index":4,"name":"statusId","shopwareField":"statusId"},{"id":"53ecbb05eccf1","type":"leaf","index":5,"name":"number","shopwareField":"number"},{"id":"53ecbb0411d43","type":"leaf","index":6,"name":"articleNumber","shopwareField":"articleNumber"},{"id":"53ecba19dc9ef","type":"leaf","index":7,"name":"price","shopwareField":"price"},{"id":"53ecba29e1a37","type":"leaf","index":8,"name":"quantity","shopwareField":"quantity"},{"id":"53ecba34bf110","type":"leaf","index":9,"name":"articleName","shopwareField":"articleName"},{"id":"53ecbb07dda54","type":"leaf","index":10,"name":"shipped","shopwareField":"shipped"},{"id":"53ecbb09bb007","type":"leaf","index":11,"name":"shippedGroup","shopwareField":"shippedGroup"},{"id":"53ecbbc15479a","type":"leaf","index":12,"name":"releaseDate","shopwareField":"releasedate"},{"id":"53ecbbc40bcd3","type":"leaf","index":13,"name":"mode","shopwareField":"mode"},{"id":"53ecbbc57169d","type":"leaf","index":14,"name":"esdArticle","shopwareField":"esd"},{"id":"53ecbbc6b6f2c","type":"leaf","index":15,"name":"config","shopwareField":"config"}]}],"shopwareField":"","parentKey":""}],"shopwareField":""}]}';
            case 'customers':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"customers","index":1,"type":"","shopwareField":"","children":[{"id":"53ea047e7dca5","name":"customer","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ea048def53f","type":"leaf","index":0,"name":"customernumber","shopwareField":"customerNumber"},{"id":"53ea052c8f4c9","type":"leaf","index":1,"name":"email","shopwareField":"email"},{"id":"53ea0535e3348","type":"leaf","index":2,"name":"password","shopwareField":"password"},{"id":"53fb366466188","type":"leaf","index":3,"name":"encoder","shopwareField":"encoder"},{"id":"540d9e8c6ab4f","type":"leaf","index":4,"name":"active","shopwareField":"active"},{"id":"53ea054339f8e","type":"leaf","index":5,"name":"billing_company","shopwareField":"billingCompany"},{"id":"53ea057725a7d","type":"leaf","index":6,"name":"billing_department","shopwareField":"billingDepartment"},{"id":"53ea0595b1d31","type":"leaf","index":7,"name":"billing_salutation","shopwareField":"billingSalutation"},{"id":"53ea05dba6a4d","type":"leaf","index":8,"name":"billing_firstname","shopwareField":"billingFirstname"},{"id":"53ea05de1204b","type":"leaf","index":9,"name":"billing_lastname","shopwareField":"billingLastname"},{"id":"53ea05df9caf1","type":"leaf","index":10,"name":"billing_street","shopwareField":"billingStreet"},{"id":"53ea05e10ee03","type":"leaf","index":11,"name":"billing_streetnumber","shopwareField":"billingStreetnumber"},{"id":"53ea05e271edd","type":"leaf","index":12,"name":"billing_zipcode","shopwareField":"billingZipcode"},{"id":"53ea05e417656","type":"leaf","index":13,"name":"billing_city","shopwareField":"billingCity"},{"id":"53ea05e5e2e12","type":"leaf","index":14,"name":"phone","shopwareField":"billingPhone"},{"id":"53ea065093393","type":"leaf","index":15,"name":"fax","shopwareField":"billingFax"},{"id":"53ea0652597f1","type":"leaf","index":16,"name":"billing_countryID","shopwareField":"billingCountryID"},{"id":"53ea0653ddf4a","type":"leaf","index":17,"name":"billing_stateID","shopwareField":"billingStateID"},{"id":"53ea0691b1774","type":"leaf","index":18,"name":"ustid","shopwareField":"ustid"},{"id":"53ea069d37da6","type":"leaf","index":19,"name":"shipping_company","shopwareField":"shippingCompany"},{"id":"53ea069eac2c6","type":"leaf","index":20,"name":"shipping_department","shopwareField":"shippingDepartment"},{"id":"53ea06a0013c7","type":"leaf","index":21,"name":"shipping_salutation","shopwareField":"shippingSalutation"},{"id":"53ea06a23cdc1","type":"leaf","index":22,"name":"shipping_firstname","shopwareField":"shippingFirstname"},{"id":"53ea0e4a3792d","type":"leaf","index":23,"name":"shipping_lastname","shopwareField":"shippingLastname"},{"id":"53ea0e4fda6e7","type":"leaf","index":24,"name":"shipping_street","shopwareField":"shippingStreet"},{"id":"53ea0e52a578a","type":"leaf","index":25,"name":"shipping_streetnumber","shopwareField":"shippingStreetnumber"},{"id":"53ea0e55b2b31","type":"leaf","index":26,"name":"shipping_zipcode","shopwareField":"shippingZipcode"},{"id":"53ea0e57ddba7","type":"leaf","index":27,"name":"shipping_city","shopwareField":"shippingZipcode"},{"id":"53ea0e5a4ee0c","type":"leaf","index":28,"name":"shipping_countryID","shopwareField":"shippingCountryID"},{"id":"53ea0e5c6d67e","type":"leaf","index":29,"name":"paymentID","shopwareField":"paymentID"},{"id":"53ea0e5e88347","type":"leaf","index":30,"name":"newsletter","shopwareField":"newsletter"},{"id":"53ea0e6194ba6","type":"leaf","index":31,"name":"accountmode","shopwareField":"accountMode"},{"id":"53ea118664a90","type":"leaf","index":32,"name":"customergroup","shopwareField":"customergroup"},{"id":"53ea1188ca4ca","type":"leaf","index":33,"name":"language","shopwareField":"language"},{"id":"53ea118b67fe2","type":"leaf","index":34,"name":"subshopID","shopwareField":"subshopID"}]}]}]}';
            case 'newsletter':
                return '{"id":"root","name":"Root","type":"node","children":{"1":{"id":"537359399c8b7","name":"Users","index":0,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"user","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"53e4b0f86aded","type":"leaf","index":0,"name":"email","shopwareField":"email"},{"id":"53e4b103bf001","type":"leaf","index":1,"name":"group","shopwareField":"groupName"},{"id":"53e4b105ea8c2","type":"leaf","index":2,"name":"salutation","shopwareField":"salutation"},{"id":"53e4b107872be","type":"leaf","index":3,"name":"firstname","shopwareField":"firstName"},{"id":"53e4b108d49f9","type":"leaf","index":4,"name":"lastname","shopwareField":"lastName"},{"id":"53e4b10a38e08","type":"leaf","index":5,"name":"street","shopwareField":"street"},{"id":"53e4b10c1d522","type":"leaf","index":6,"name":"streetnumber","shopwareField":"streetNumber"},{"id":"53e4b10d68c09","type":"leaf","index":7,"name":"zipcode","shopwareField":"zipCode"},{"id":"53e4b157416fc","type":"leaf","index":8,"name":"city","shopwareField":"city"},{"id":"53e4b1592dd4b","type":"leaf","index":9,"name":"lastmailing","shopwareField":"lastNewsletter"},{"id":"53e4b15a69651","type":"leaf","index":10,"name":"lastread","shopwareField":"lastRead"},{"id":"53e4b15bde918","type":"leaf","index":11,"name":"userID","shopwareField":"userID"}]}]}}}';
            default :
                throw new \Exception('The profile could not be created.');
        }
    }

    /**
     * @param string $profileType
     * @return string
     * @throws \Exception
     */
    static public function getTreeByHiddenProfileType($profileType)
    {
        switch ($profileType) {
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"4","name":"articles","index":0,"type":"","children":[{"id":"53e0d3148b0b2","name":"article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":[{"id":"53e0d365881b7","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},{"id":"53e0d329364c4","type":"leaf","index":1,"name":"mainnumber","shopwareField":"mainNumber"},{"id":"544f816b5eb00","name":"similars","index":2,"type":"iteration","adapter":"similar","parentKey":"articleId","shopwareField":"","children":[{"id":"544f818e656fd","type":"leaf","index":0,"name":"similar","shopwareField":"ordernumber"}]},{"id":"544f819b59b8b","name":"accessories","index":3,"type":"iteration","adapter":"accessory","parentKey":"articleId","shopwareField":"","children":[{"id":"544f81ac1b04e","type":"leaf","index":0,"name":"accessory","shopwareField":"ordernumber"}]},{"id":"544f83241f649","type":"leaf","index":4,"name":"processed","shopwareField":"processed"}],"attributes":null}],"shopwareField":""}]}';
            default :
                throw new \Exception('The profile type does not exists.');
        }
    }

}
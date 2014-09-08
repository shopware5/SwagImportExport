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
     * @return string|boolean
     * @throws \Exception
     */
    static public function getDefaultTreeByProfileType($profileType)
    {
        switch ($profileType) {
            case 'categories':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryID","shopwareField":"id"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":3,"name":"position","shopwareField":"position"},{"id":"53e0d1414b0d7","type":"leaf","index":4,"name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":5,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":6,"name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":7,"name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":8,"name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":9,"name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":10,"name":"blog","shopwareField":"blog"},{"id":"53e9f61981e83","type":"leaf","index":11,"name":"showfiltergroups","shopwareField":"showFilterGroups"},{"id":"53e9f62a03f55","type":"leaf","index":12,"name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":13,"name":"hidefilter","shopwareField":"hideFilter"}]}]}]}';
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"4","name":"articles","index":0,"type":"","children":[{"id":"53e0d3148b0b2","name":"article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":[{"id":"53e0d329364c4","type":"leaf","index":0,"name":"main_number","shopwareField":"mainNumber"},{"id":"53e0d365881b7","type":"leaf","index":1,"name":"variant_number","shopwareField":"orderNumber"},{"id":"53fb1c8c99aac","type":"leaf","index":2,"name":"active","shopwareField":"active"},{"id":"53e0d3a201785","type":"leaf","index":3,"name":"title","shopwareField":"name"},{"id":"53e0d3e46c923","type":"leaf","index":4,"name":"description","shopwareField":"description"},{"id":"53e0d3fea6646","type":"leaf","index":5,"name":"supplier","shopwareField":"supplierName"},{"id":"53e0d4333dca7","type":"leaf","index":6,"name":"tax","shopwareField":"tax"},{"id":"53eddc83e7a2e","type":"leaf","index":7,"name":"instock","shopwareField":"inStock"},{"id":"53fb272db680f","type":"leaf","index":8,"name":"variantActive","shopwareField":"variantActive"},{"id":"53e0d44938a70","type":"node","index":9,"name":"prices","shopwareField":"","children":[{"id":"53e0d45110b1d","name":"price","index":0,"type":"iteration","adapter":"price","parentKey":"variantId","shopwareField":"","children":[{"id":"53eddba5e3471","type":"leaf","index":0,"name":"group","shopwareField":"priceGroup"},{"id":"53e0d472a0aa8","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"53e0d48a9313a","type":"leaf","index":2,"name":"pseudo_price","shopwareField":"pseudoPrice"}],"attributes":null}]},{"id":"53e0d5f7d03d4","type":"","index":10,"name":"configurators","shopwareField":"","children":[{"id":"53e0d603db6b9","name":"configurator","index":0,"type":"iteration","adapter":"configurator","parentKey":"variantId","shopwareField":"","children":[{"id":"53e0d6142adca","type":"leaf","index":0,"name":"set","shopwareField":"configSetName"},{"id":"53e0d63477bef","type":"leaf","index":1,"name":"group","shopwareField":"configGroupName"},{"id":"53e0d6446940d","type":"leaf","index":2,"name":"option","shopwareField":"configOptionName"}],"attributes":null}]},{"id":"53eddbf2b19cd","type":"","index":11,"name":"images","shopwareField":"","children":[{"id":"53eddc062a7c0","name":"image","index":0,"type":"iteration","adapter":"image","parentKey":"articleId","shopwareField":"","children":[{"id":"53eddcbcf35f0","type":"leaf","index":0,"name":"name","shopwareField":"path"}]}]},{"id":"53fd8b347ec38","type":"","index":12,"name":"categories","shopwareField":"","children":[{"id":"53fd8b4570f9d","name":"category","index":0,"type":"iteration","adapter":"category","parentKey":"articleId","shopwareField":"","children":[{"id":"53fd8b524be38","type":"leaf","index":0,"name":"categoryId","shopwareField":"categoryId"}]}]}],"attributes":null}],"shopwareField":""}]}';
            case 'articlesInStock':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"articlesInStock","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"article","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"5373865547d06","name":"article_number","index":0,"type":"leaf","shopwareField":"orderNumber"},{"id":"537386ac3302b","name":"Description","index":1,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"supplier","index":0,"shopwareField":"supplier"}],"children":[{"id":"5373870d38c80","name":"Value","index":1,"type":"leaf","shopwareField":"additionalText"}]},{"id":"537388742e20e","name":"inStock","index":2,"type":"leaf","shopwareField":"inStock"}]}]}]}';
            case 'articlesPrices':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Prices","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Price","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"articleDetailsId","index":0,"shopwareField":"articleDetailsId"}],"children":[{"id":"5373865547d06","name":"articleId","index":1,"type":"leaf","shopwareField":"articleId"},{"id":"537386ac3302b","name":"Description","index":2,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"from","index":0,"shopwareField":"from"}],"children":[{"id":"5373870d38c80","name":"to","index":1,"type":"leaf","shopwareField":"to"}]},{"id":"537388742e20e","name":"price","index":3,"type":"leaf","shopwareField":"price"}]}]}]}';
            case 'articlesImages':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Images","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Images","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"orderNumber","index":0,"shopwareField":"orderNumber","type":"attribute"}],"children":{"0":{"id":"5373865547d06","name":"image","index":1,"type":"leaf","shopwareField":"image"},"2":{"id":"537388742e20e","name":"main","index":2,"type":"leaf","shopwareField":"main"},"3":{"id":"53e39a5fddf41","type":"leaf","index":3,"name":"description","shopwareField":"description"},"4":{"id":"53e39a698522a","type":"leaf","index":4,"name":"position","shopwareField":"position"},"5":{"id":"53e39a737733d","type":"leaf","index":5,"name":"width","shopwareField":"width"},"6":{"id":"53e39a7c1a52e","type":"leaf","index":6,"name":"height","shopwareField":"height"}}}]}]}';
            case 'articlesTranslations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"article_number","index":0,"shopwareField":"articleNumber"}],"children":{"2":{"id":"53ce5e8f25a24","name":"title","index":1,"type":"leaf","shopwareField":"title"},"3":{"id":"53ce5f9501db7","name":"description","index":2,"type":"leaf","shopwareField":"description"},"4":{"id":"53ce5fa3bd231","name":"long_description","index":3,"type":"leaf","shopwareField":"descriptionLong"},"5":{"id":"53ce5fb6d95d8","name":"keywords","index":4,"type":"leaf","shopwareField":"keywords"}}}]}]}';
            case 'orders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"orders","index":1,"type":"node","children":[{"id":"537359399c90d","name":"order","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53eca77b49d6d","type":"leaf","index":0,"name":"orderId","shopwareField":"orderId"},{"id":"5373865547d06","name":"number","index":1,"type":"leaf","shopwareField":"number"},{"id":"53ecb1fa09cfd","type":"leaf","index":2,"name":"customerId","shopwareField":"customerId"},{"id":"53ecb3a3e43fb","type":"leaf","index":3,"name":"orderStatusID","shopwareField":"status"},{"id":"53ecb496e80e0","type":"leaf","index":4,"name":"cleared","shopwareField":"cleared"},{"id":"53ecb4e584159","type":"leaf","index":5,"name":"paymentStatusID","shopwareField":"paymentId"},{"id":"53ecb4f9a203b","type":"leaf","index":6,"name":"dispatchId","shopwareField":"dispatchId"},{"id":"53ecb510a3379","type":"leaf","index":7,"name":"partnerId","shopwareField":"partnerId"},{"id":"53ecb51a93f21","type":"leaf","index":8,"name":"shopId","shopwareField":"shopId"},{"id":"53ecb6a059334","type":"leaf","index":9,"name":"invoiceAmount","shopwareField":"invoiceAmount"},{"id":"53ecb6a74e399","type":"leaf","index":10,"name":"invoiceAmountNet","shopwareField":"invoiceAmountNet"},{"id":"53ecb6b4587ba","type":"leaf","index":11,"name":"invoiceShipping","shopwareField":"invoiceShipping"},{"id":"53ecb6be27e2e","type":"leaf","index":12,"name":"invoiceShippingNet","shopwareField":"invoiceShippingNet"},{"id":"53ecb6db22a2e","type":"leaf","index":13,"name":"orderTime","shopwareField":"orderTime"},{"id":"53ecb6ebaf4c5","type":"leaf","index":14,"name":"transactionId","shopwareField":"transactionId"},{"id":"53ecb7014e7ad","type":"leaf","index":15,"name":"comment","shopwareField":"comment"},{"id":"53ecb7f0df5db","type":"leaf","index":16,"name":"customerComment","shopwareField":"customerComment"},{"id":"53ecb7f265873","type":"leaf","index":17,"name":"internalComment","shopwareField":"internalComment"},{"id":"53ecb7f3baed3","type":"leaf","index":18,"name":"net","shopwareField":"net"},{"id":"53ecb7f518b2a","type":"leaf","index":19,"name":"taxFree","shopwareField":"taxFree"},{"id":"53ecb7f778bb0","type":"leaf","index":20,"name":"temporaryId","shopwareField":"temporaryId"},{"id":"53ecb7f995899","type":"leaf","index":21,"name":"referer","shopwareField":"referer"},{"id":"53ecb8ba28544","type":"leaf","index":22,"name":"clearedDate","shopwareField":"clearedDate"},{"id":"53ecb8bd55dda","type":"leaf","index":23,"name":"trackingCode","shopwareField":"trackingCode"},{"id":"53ecb8c076318","type":"leaf","index":24,"name":"languageIso","shopwareField":"languageIso"},{"id":"53ecb8c42923d","type":"leaf","index":25,"name":"currency","shopwareField":"currency"},{"id":"53ecb8c74168b","type":"leaf","index":26,"name":"currencyFactor","shopwareField":"currencyFactor"},{"id":"53ecb9203cb33","type":"leaf","index":27,"name":"remoteAddress","shopwareField":"remoteAddress"},{"id":"53fddf437e561","type":"node","index":28,"name":"details","shopwareField":"","children":[{"id":"53ecb9c7d602d","type":"leaf","index":0,"name":"orderDetailId","shopwareField":"orderDetailId"},{"id":"53ecb9ee6f821","type":"leaf","index":1,"name":"articleId","shopwareField":"articleId"},{"id":"53ecbaa627334","type":"leaf","index":2,"name":"taxId","shopwareField":"taxId"},{"id":"53ecba416356a","type":"leaf","index":3,"name":"taxRate","shopwareField":"taxRate"},{"id":"53ecbaa813093","type":"leaf","index":4,"name":"statusId","shopwareField":"statusId"},{"id":"53ecbb05eccf1","type":"leaf","index":5,"name":"number","shopwareField":"number"},{"id":"53ecbb0411d43","type":"leaf","index":6,"name":"articleNumber","shopwareField":"articleNumber"},{"id":"53ecba19dc9ef","type":"leaf","index":7,"name":"price","shopwareField":"price"},{"id":"53ecba29e1a37","type":"leaf","index":8,"name":"quantity","shopwareField":"quantity"},{"id":"53ecba34bf110","type":"leaf","index":9,"name":"articleName","shopwareField":"articleName"},{"id":"53ecbb07dda54","type":"leaf","index":10,"name":"shipped","shopwareField":"shipped"},{"id":"53ecbb09bb007","type":"leaf","index":11,"name":"shippedGroup","shopwareField":"shippedGroup"},{"id":"53ecbbc15479a","type":"leaf","index":12,"name":"releaseDate","shopwareField":"releasedate"},{"id":"53ecbbc40bcd3","type":"leaf","index":13,"name":"mode","shopwareField":"mode"},{"id":"53ecbbc57169d","type":"leaf","index":14,"name":"esdArticle","shopwareField":"esd"},{"id":"53ecbbc6b6f2c","type":"leaf","index":15,"name":"config","shopwareField":"config"}]}],"shopwareField":"","parentKey":""}],"shopwareField":""}]}';
            case 'customers':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"customers","index":1,"type":"","shopwareField":"","children":[{"id":"53ea047e7dca5","name":"customer","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ea048def53f","type":"leaf","index":0,"name":"customernumber","shopwareField":"customerNumber"},{"id":"53ea052c8f4c9","type":"leaf","index":1,"name":"email","shopwareField":"email"},{"id":"53ea0535e3348","type":"leaf","index":2,"name":"password","shopwareField":"password"},{"id":"53fb366466188","type":"leaf","index":3,"name":"encoder","shopwareField":"encoder"},{"id":"540d9e8c6ab4f","type":"leaf","index":4,"name":"active","shopwareField":"active"},{"id":"53ea054339f8e","type":"leaf","index":5,"name":"billing_company","shopwareField":"billingCompany"},{"id":"53ea057725a7d","type":"leaf","index":6,"name":"billing_department","shopwareField":"billingDepartment"},{"id":"53ea0595b1d31","type":"leaf","index":7,"name":"billing_salutation","shopwareField":"billingSalutation"},{"id":"53ea05dba6a4d","type":"leaf","index":8,"name":"billing_firstname","shopwareField":"billingFirstname"},{"id":"53ea05de1204b","type":"leaf","index":9,"name":"billing_lastname","shopwareField":"billingLastname"},{"id":"53ea05df9caf1","type":"leaf","index":10,"name":"billing_street","shopwareField":"billingStreet"},{"id":"53ea05e10ee03","type":"leaf","index":11,"name":"billing_streetnumber","shopwareField":"billingStreetnumber"},{"id":"53ea05e271edd","type":"leaf","index":12,"name":"billing_zipcode","shopwareField":"billingZipcode"},{"id":"53ea05e417656","type":"leaf","index":13,"name":"billing_city","shopwareField":"billingCity"},{"id":"53ea05e5e2e12","type":"leaf","index":14,"name":"phone","shopwareField":"billingPhone"},{"id":"53ea065093393","type":"leaf","index":15,"name":"fax","shopwareField":"billingFax"},{"id":"53ea0652597f1","type":"leaf","index":16,"name":"billing_countryID","shopwareField":"billingCountryID"},{"id":"53ea0653ddf4a","type":"leaf","index":17,"name":"billing_stateID","shopwareField":"billingStateID"},{"id":"53ea0691b1774","type":"leaf","index":18,"name":"ustid","shopwareField":"ustid"},{"id":"53ea069d37da6","type":"leaf","index":19,"name":"shipping_company","shopwareField":"shippingCompany"},{"id":"53ea069eac2c6","type":"leaf","index":20,"name":"shipping_department","shopwareField":"shippingDepartment"},{"id":"53ea06a0013c7","type":"leaf","index":21,"name":"shipping_salutation","shopwareField":"shippingSalutation"},{"id":"53ea06a23cdc1","type":"leaf","index":22,"name":"shipping_firstname","shopwareField":"shippingFirstname"},{"id":"53ea0e4a3792d","type":"leaf","index":23,"name":"shipping_lastname","shopwareField":"shippingLastname"},{"id":"53ea0e4fda6e7","type":"leaf","index":24,"name":"shipping_street","shopwareField":"shippingStreet"},{"id":"53ea0e52a578a","type":"leaf","index":25,"name":"shipping_streetnumber","shopwareField":"shippingStreetnumber"},{"id":"53ea0e55b2b31","type":"leaf","index":26,"name":"shipping_zipcode","shopwareField":"shippingZipcode"},{"id":"53ea0e57ddba7","type":"leaf","index":27,"name":"shipping_city","shopwareField":"shippingZipcode"},{"id":"53ea0e5a4ee0c","type":"leaf","index":28,"name":"shipping_countryID","shopwareField":"shippingCountryID"},{"id":"53ea0e5c6d67e","type":"leaf","index":29,"name":"paymentID","shopwareField":"paymentID"},{"id":"53ea0e5e88347","type":"leaf","index":30,"name":"newsletter","shopwareField":"newsletter"},{"id":"53ea0e6194ba6","type":"leaf","index":31,"name":"accountmode","shopwareField":"accountMode"},{"id":"53ea118664a90","type":"leaf","index":32,"name":"customergroup","shopwareField":"customergroup"},{"id":"53ea1188ca4ca","type":"leaf","index":33,"name":"language","shopwareField":"language"},{"id":"53ea118b67fe2","type":"leaf","index":34,"name":"subshopID","shopwareField":"subshopID"}]}]}]}';
            case 'newsletter':
                return '{"id":"root","name":"Root","type":"node","children":{"1":{"id":"537359399c8b7","name":"Users","index":0,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"user","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"53e4b0f86aded","type":"leaf","index":0,"name":"email","shopwareField":"email"},{"id":"53e4b103bf001","type":"leaf","index":1,"name":"group","shopwareField":"groupName"},{"id":"53e4b105ea8c2","type":"leaf","index":2,"name":"salutation","shopwareField":"salutation"},{"id":"53e4b107872be","type":"leaf","index":3,"name":"firstname","shopwareField":"firstName"},{"id":"53e4b108d49f9","type":"leaf","index":4,"name":"lastname","shopwareField":"lastName"},{"id":"53e4b10a38e08","type":"leaf","index":5,"name":"street","shopwareField":"street"},{"id":"53e4b10c1d522","type":"leaf","index":6,"name":"streetnumber","shopwareField":"streetNumber"},{"id":"53e4b10d68c09","type":"leaf","index":7,"name":"zipcode","shopwareField":"zipCode"},{"id":"53e4b157416fc","type":"leaf","index":8,"name":"city","shopwareField":"city"},{"id":"53e4b1592dd4b","type":"leaf","index":9,"name":"lastmailing","shopwareField":"lastNewsletter"},{"id":"53e4b15a69651","type":"leaf","index":10,"name":"lastread","shopwareField":"lastRead"},{"id":"53e4b15bde918","type":"leaf","index":11,"name":"userID","shopwareField":"userID"}]}]}}}';
            default :
                throw new \Exception('The profile could not be created.');
        }

        return false;
    }

}
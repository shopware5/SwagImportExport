<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

use Shopware\CustomModels\ImportExport\Profile;
use SwagImportExport\Components\DataManagers\DataManager;

class TreeHelper
{
    /**
     * Converts the JSON tree to ExtJS tree
     *
     * @param bool   $isInIteration
     * @param string $adapter
     *
     * @return array
     */
    public static function convertToExtJSTree(array $node, $isInIteration = false, $adapter = '')
    {
        $parentKey = '';
        $children = [];

        if ($node['type'] === 'iteration') {
            $isInIteration = true;
            $adapter = $node['adapter'];
            $parentKey = $node['parentKey'];

            $icon = 'sprite-blue-folders-stack';
        } elseif ($node['type'] === 'leaf') {
            $icon = 'sprite-blue-document-text';
        } else {
            // $node['type'] == 'node'
            $icon = '';
        }

        // Get the attributes
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $attribute) {
                $children[] = [
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
                    'inIteration' => $isInIteration,
                ];
            }
        }

        // Get the child nodes
        if (isset($node['children']) && \count($node['children']) > 0) {
            foreach ($node['children'] as $child) {
                $children[] = static::convertToExtJSTree($child, $isInIteration, $adapter);
            }
        }

        return [
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
            'defaultValue' => $node['defaultValue'],
            'inIteration' => $isInIteration,
            'children' => $children,
        ];
    }

    /**
     * Helper function which appends child node to the tree
     *
     * @return bool
     */
    public static function appendNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] === 'attribute') {
                $node['attributes'][] = [
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                ];
            } elseif ($child['type'] === 'node') {
                $node['children'][] = [
                    'id' => $child['id'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                ];
            } elseif ($child['type'] === 'iteration') {
                $node['children'][] = [
                    'id' => $child['id'],
                    'name' => $child['text'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'adapter' => $child['adapter'],
                    'parentKey' => $child['parentKey'],
                ];
            } else {
                $node['children'][] = [
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                    'defaultValue' => $child['defaultValue'],
                    'shopwareField' => $child['swColumn'],
                ];
            }

            return true;
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                if (static::appendNode($child, $childNode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds node from the tree
     *
     * @param string $id
     * @param string $parentId
     *
     * @return bool|array
     */
    public static function getNodeById($id, array $node, $parentId = 'root')
    {
        if ($node['id'] == $id) { // the node is found
            $node['parentId'] = $parentId;

            return $node;
        }
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

        return false;
    }

    /**
     * Helper function which appends child node to the tree
     *
     * @return bool
     */
    public static function moveNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] === 'attribute') {
                unset($child['parentId']);
                $node['attributes'][] = $child;
            } elseif ($child['type'] === 'node') {
                unset($child['parentId']);
                $node['children'][] = $child;
            } else {
                unset($child['parentId']);
                $node['children'][] = $child;
            }

            return true;
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                if (static::moveNode($child, $childNode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and changes node from the tree
     *
     * @param array $defaultFields
     *
     * @return bool
     */
    public static function changeNode(array $child, array &$node, $defaultFields = [])
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

            if (isset($child['defaultValue'])) {
                $type = DataManager::getFieldType($child['swColumn'], $defaultFields);
                $defaultValue = DataManager::castDefaultValue($child['defaultValue'], $type);

                $node['defaultValue'] = $defaultValue;
            } else {
                unset($node['defaultValue']);
            }

            if ($child['type'] === 'iteration') {
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
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                if (static::changeNode($child, $childNode, $defaultFields)) {
                    return true;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as &$childNode) {
                if (static::changeNode($child, $childNode, $defaultFields)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and deletes node from the tree
     *
     * @return bool
     */
    public static function deleteNode(array $child, array &$node)
    {
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['children'][$key]);
                    if (\count($node['children']) == 0) {
                        unset($node['children']);
                    }

                    return true;
                }

                if (static::deleteNode($child, $childNode)) {
                    return true;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['attributes'][$key]);
                    if (\count($node['attributes']) == 0) {
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
     *
     * @return array
     */
    public static function reorderTree($node)
    {
        $reorderdNode = [];
        if (\is_array($node) && isset($node['children'])) {
            foreach ($node as $key => $value) {
                if ($key === 'children' || $key === 'attributes') {
                    $count = \count($value);
                    foreach ($value as $currentIndex => $innerValue) {
                        $value3 = self::reorderTree($innerValue);

                        // fix for to-be-deleted nodes
                        if (isset($reorderdNode[$key][$innerValue['index']])) {
                            $reorderdNode[$key][$count + $currentIndex] = $reorderdNode[$key][$innerValue['index']];
                        }
                        $reorderdNode[$key][$innerValue['index']] = $value3;
                    }
                    \ksort($reorderdNode[$key]);
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
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function getDefaultTreeByProfileType($profileType)
    {
        switch ($profileType) {
            case 'categories':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryId","shopwareField":"categoryId"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":3,"name":"position","shopwareField":"position"},{"id":"53e0d1414b0ad","type":"leaf","index":4,"name":"metatitle","shopwareField":"metaTitle"},{"id":"53e0d1414b0d7","type":"leaf","index":5,"name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":6,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":7,"name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":8,"name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":9,"name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":10,"name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":11,"name":"blog","shopwareField":"blog"},{"id":"53e9f62a03f55","type":"leaf","index":13,"name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":14,"name":"hidefilter","shopwareField":"hideFilter"},{"id":"541c35c378bc9","type":"leaf","index":15,"name":"attribute_attribute1","shopwareField":"attributeAttribute1"},{"id":"541c36d0bba0f","type":"leaf","index":16,"name":"attribute_attribute2","shopwareField":"attributeAttribute2"},{"id":"541c36d63fac6","type":"leaf","index":17,"name":"attribute_attribute3","shopwareField":"attributeAttribute3"},{"id":"541c36da52222","type":"leaf","index":18,"name":"attribute_attribute4","shopwareField":"attributeAttribute4"},{"id":"541c36dc540e3","type":"leaf","index":19,"name":"attribute_attribute5","shopwareField":"attributeAttribute5"},{"id":"541c36dd9e130","type":"leaf","index":20,"name":"attribute_attribute6","shopwareField":"attributeAttribute6"},{"id":"54dc86ff4bee5","name":"CustomerGroups","index":21,"type":"iteration","adapter":"customerGroups","parentKey":"categoryId","shopwareField":"","children":[{"id":"54dc87118ad11","type":"leaf","index":0,"name":"CustomerGroup","shopwareField":"customerGroupId"}]}]}]}]}';
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"4","name":"articles","index":0,"type":"","children":[{"id":"53e0d3148b0b2","name":"article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":{"0":{"id":"53e0d365881b7","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},"1":{"id":"53e0d329364c4","type":"leaf","index":1,"name":"mainnumber","shopwareField":"mainNumber"},"2":{"id":"53e0d3a201785","type":"leaf","index":2,"name":"name","shopwareField":"name"},"3":{"id":"53fb1c8c99aac","type":"leaf","index":3,"name":"additionalText","shopwareField":"additionalText"},"4":{"id":"53e0d3fea6646","type":"leaf","index":4,"name":"supplier","shopwareField":"supplierName"},"5":{"id":"53e0d4333dca7","type":"leaf","index":5,"name":"tax","shopwareField":"tax"},"6":{"id":"53e0d44938a70","type":"node","index":6,"name":"prices","shopwareField":"","children":[{"id":"53e0d45110b1d","name":"price","index":0,"type":"iteration","adapter":"price","parentKey":"variantId","shopwareField":"","children":[{"id":"53eddba5e3471","type":"leaf","index":0,"name":"group","shopwareField":"priceGroup"},{"id":"53e0d472a0aa8","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"53e0d48a9313a","type":"leaf","index":2,"name":"pseudoprice","shopwareField":"pseudoPrice"}],"attributes":null}]},"7":{"id":"53fb272db680f","type":"leaf","index":7,"name":"active","shopwareField":"active"},"8":{"id":"53eddc83e7a2e","type":"leaf","index":8,"name":"instock","shopwareField":"inStock"},"9":{"id":"541af5febd073","type":"leaf","index":9,"name":"stockmin","shopwareField":"stockMin"},"10":{"id":"53e0d3e46c923","type":"leaf","index":10,"name":"description","shopwareField":"description"},"11":{"id":"541af5dc189bd","type":"leaf","index":11,"name":"description_long","shopwareField":"descriptionLong"},"12":{"id":"541af601a2874","type":"leaf","index":12,"name":"shippingtime","shopwareField":"shippingTime"},"13":{"id":"541af6bac2305","type":"leaf","index":13,"name":"added","shopwareField":"date"},"14":{"id":"541af75d8a839","type":"leaf","index":14,"name":"changed","shopwareField":"changeTime"},"15":{"id":"541af76ed2c28","type":"leaf","index":15,"name":"releasedate","shopwareField":"releaseDate"},"16":{"id":"541af7a98284d","type":"leaf","index":16,"name":"shippingfree","shopwareField":"shippingFree"},"17":{"id":"541af7d1b1c53","type":"leaf","index":17,"name":"topseller","shopwareField":"topSeller"},"18":{"id":"541af887a00ee","type":"leaf","index":18,"name":"metatitle","shopwareField":"metaTitle"},"19":{"id":"541af887a00ed","type":"leaf","index":19,"name":"keywords","shopwareField":"keywords"},"20":{"id":"541af7f35d78a","type":"leaf","index":20,"name":"minpurchase","shopwareField":"minPurchase"},"21":{"id":"541af889cfb71","type":"leaf","index":21,"name":"purchasesteps","shopwareField":"purchaseSteps"},"22":{"id":"541af88c05567","type":"leaf","index":22,"name":"maxpurchase","shopwareField":"maxPurchase"},"23":{"id":"541af88e24a40","type":"leaf","index":23,"name":"purchaseunit","shopwareField":"purchaseUnit"},"24":{"id":"541af8907b3e3","type":"leaf","index":24,"name":"referenceunit","shopwareField":"referenceUnit"},"25":{"id":"541af9dd95d11","type":"leaf","index":25,"name":"packunit","shopwareField":"packUnit"},"26":{"id":"541af9e03ba80","type":"leaf","index":26,"name":"unitID","shopwareField":"unitId"},"27":{"id":"541af9e2939b0","type":"leaf","index":27,"name":"pricegroupID","shopwareField":"priceGroupId"},"28":{"id":"541af9e54b365","type":"leaf","index":28,"name":"pricegroupActive","shopwareField":"priceGroupActive"},"29":{"id":"541afad534551","type":"leaf","index":29,"name":"laststock","shopwareField":"lastStock"},"30":{"id":"541afad754eb9","type":"leaf","index":30,"name":"suppliernumber","shopwareField":"supplierNumber"},"31":{"id":"540efb5f704bc","type":"leaf","index":31,"name":"purchaseprice","shopwareField":"purchasePrice"},"32":{"id":"541afad9b7357","type":"leaf","index":32,"name":"weight","shopwareField":"weight"},"33":{"id":"541afadc6536c","type":"leaf","index":33,"name":"width","shopwareField":"width"},"34":{"id":"541afadfb5179","type":"leaf","index":34,"name":"height","shopwareField":"height"},"35":{"id":"541afae631bc8","type":"leaf","index":35,"name":"length","shopwareField":"length"},"36":{"id":"541afae97c6ec","type":"leaf","index":36,"name":"ean","shopwareField":"ean"},"37":{"id":"53e0d5f7d03d4","type":"","index":37,"name":"configurators","shopwareField":"","children":[{"id":"53e0d603db6b9","name":"configurator","index":0,"type":"iteration","adapter":"configurator","parentKey":"variantId","shopwareField":"","children":[{"id":"542119418283a","type":"leaf","index":0,"name":"configuratorsetID","shopwareField":"configSetId"},{"id":"53e0d6142adca","type":"leaf","index":1,"name":"configuratortype","shopwareField":"configSetType"},{"id":"53e0d63477bef","type":"leaf","index":2,"name":"configuratorGroup","shopwareField":"configGroupName"},{"id":"53e0d6446940d","type":"leaf","index":3,"name":"configuratorOptions","shopwareField":"configOptionName"},{"id":"57c93872e082d","type":"leaf","index":4,"name":"configSetName","shopwareField":"configSetName","defaultValue":""}],"attributes":null}]},"38":{"id":"54211df500e93","name":"category","index":38,"type":"iteration","adapter":"category","parentKey":"articleId","shopwareField":"","children":[{"id":"54211e05ddc3f","type":"leaf","index":0,"name":"categories","shopwareField":"categoryId"}]},"78":{"id":"541afdba8e926","name":"similars","index":37,"type":"iteration","adapter":"similar","parentKey":"articleId","shopwareField":"","children":[{"id":"541afdc37e956","type":"leaf","index":0,"name":"similar","shopwareField":"ordernumber"}]}},"attributes":null}],"shopwareField":""}]}';
            case 'articlesInStock':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"articlesInStock","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"article","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":null,"children":[{"id":"5373865547d06","name":"ordernumber","index":0,"type":"leaf","shopwareField":"orderNumber"},{"id":"537388742e20e","name":"instock","index":1,"type":"leaf","shopwareField":"inStock"},{"id":"541c4b9ddc00e","type":"leaf","index":2,"name":"_additionaltext","shopwareField":"additionalText"},{"id":"541c4bc6b7e0a","type":"leaf","index":3,"name":"_supplier","shopwareField":"supplier"},{"id":"541c4bd27761c","type":"leaf","index":4,"name":"_price","shopwareField":"price"}]}]}]}';
            case 'articlesPrices':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Prices","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Price","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"540ff6e624be5","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},{"id":"540ffb5b14291","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"540ffb5cea2df","type":"leaf","index":2,"name":"pricegroup","shopwareField":"priceGroup"},{"id":"540ffb5e68fe5","type":"leaf","index":3,"name":"from","shopwareField":"from"},{"id":"540ffb5fd04ba","type":"leaf","index":4,"name":"pseudoprice","shopwareField":"pseudoPrice"},{"id":"540ffda5904e5","type":"leaf","index":6,"name":"_name","shopwareField":"name"},{"id":"540ffc1d66042","type":"leaf","index":7,"name":"_additionaltext","shopwareField":"additionalText"},{"id":"540ffcf5089af","type":"leaf","index":8,"name":"_supplier","shopwareField":"supplierName"},{"id":"540efb5f704bc","type":"leaf","index":9,"name":"purchaseprice","shopwareField":"purchasePrice"}]}]}]}';
            case 'articlesImages':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"images","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"image","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ff1e618a9ad","type":"leaf","index":0,"name":"ordernumber","shopwareField":"ordernumber"},{"id":"5373865547d06","name":"image","index":1,"type":"leaf","shopwareField":"image"},{"id":"537388742e20e","name":"main","index":2,"type":"leaf","shopwareField":"main"},{"id":"53e39a5fddf41","type":"leaf","index":3,"name":"description","shopwareField":"description"},{"id":"53e39a698522a","type":"leaf","index":4,"name":"position","shopwareField":"position"},{"id":"53e39a737733d","type":"leaf","index":5,"name":"width","shopwareField":"width"},{"id":"53e39a7c1a52e","type":"leaf","index":6,"name":"height","shopwareField":"height"},{"id":"54004e7bf3a1a","type":"leaf","index":7,"name":"relations","shopwareField":"relations"}]}]}]}';
            case 'articlesTranslations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"5429676d78b28","type":"leaf","index":0,"name":"articlenumber","shopwareField":"articleNumber"},{"id":"543798726b38e","type":"leaf","index":1,"name":"languageId","shopwareField":"languageId"},{"id":"53ce5e8f25a24","name":"name","index":2,"type":"leaf","shopwareField":"name"},{"id":"53ce5f9501db7","name":"description","index":3,"type":"leaf","shopwareField":"description"},{"id":"53ce5fa3bd231","name":"longdescription","index":4,"type":"leaf","shopwareField":"descriptionLong"},{"id":"53ce5fb6d95d8","name":"keywords","index":5,"type":"leaf","shopwareField":"keywords"},{"id":"542a5df925af2","type":"leaf","index":6,"name":"metatitle","shopwareField":"metaTitle"}]}]}]}';
            case 'orders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"orders","index":1,"type":"node","children":[{"id":"537359399c90d","name":"order","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53eca77b49d6d","type":"leaf","index":0,"name":"orderId","shopwareField":"orderId"},{"id":"5373865547d06","name":"number","index":1,"type":"leaf","shopwareField":"number"},{"id":"53ecb1fa09cfd","type":"leaf","index":2,"name":"customerId","shopwareField":"customerId"},{"id":"53ecb3a3e43fb","type":"leaf","index":3,"name":"orderStatusID","shopwareField":"status"},{"id":"53ecb496e80e0","type":"leaf","index":4,"name":"cleared","shopwareField":"cleared"},{"id":"53ecb4e584159","type":"leaf","index":5,"name":"paymentID","shopwareField":"paymentId"},{"id":"53ecb4f9a203b","type":"leaf","index":6,"name":"dispatchId","shopwareField":"dispatchId"},{"id":"53ecb510a3379","type":"leaf","index":7,"name":"partnerId","shopwareField":"partnerId"},{"id":"53ecb51a93f21","type":"leaf","index":8,"name":"shopId","shopwareField":"shopId"},{"id":"53ecb6a059334","type":"leaf","index":9,"name":"invoiceAmount","shopwareField":"invoiceAmount"},{"id":"53ecb6a74e399","type":"leaf","index":10,"name":"invoiceAmountNet","shopwareField":"invoiceAmountNet"},{"id":"53ecb6b4587ba","type":"leaf","index":11,"name":"invoiceShipping","shopwareField":"invoiceShipping"},{"id":"53ecb6be27e2e","type":"leaf","index":12,"name":"invoiceShippingNet","shopwareField":"invoiceShippingNet"},{"id":"53ecb6db22a2e","type":"leaf","index":13,"name":"orderTime","shopwareField":"orderTime"},{"id":"53ecb6ebaf4c5","type":"leaf","index":14,"name":"transactionId","shopwareField":"transactionId"},{"id":"53ecb7014e7ad","type":"leaf","index":15,"name":"comment","shopwareField":"comment"},{"id":"53ecb7f0df5db","type":"leaf","index":16,"name":"customerComment","shopwareField":"customerComment"},{"id":"53ecb7f265873","type":"leaf","index":17,"name":"internalComment","shopwareField":"internalComment"},{"id":"53ecb7f3baed3","type":"leaf","index":18,"name":"net","shopwareField":"net"},{"id":"53ecb7f518b2a","type":"leaf","index":19,"name":"taxFree","shopwareField":"taxFree"},{"id":"53ecb7f778bb0","type":"leaf","index":20,"name":"temporaryId","shopwareField":"temporaryId"},{"id":"53ecb7f995899","type":"leaf","index":21,"name":"referer","shopwareField":"referer"},{"id":"53ecb8ba28544","type":"leaf","index":22,"name":"clearedDate","shopwareField":"clearedDate"},{"id":"53ecb8bd55dda","type":"leaf","index":23,"name":"trackingCode","shopwareField":"trackingCode"},{"id":"53ecb8c076318","type":"leaf","index":24,"name":"languageIso","shopwareField":"languageIso"},{"id":"53ecb8c42923d","type":"leaf","index":25,"name":"currency","shopwareField":"currency"},{"id":"53ecb8c74168b","type":"leaf","index":26,"name":"currencyFactor","shopwareField":"currencyFactor"},{"id":"53ecb9203cb33","type":"leaf","index":27,"name":"remoteAddress","shopwareField":"remoteAddress"},{"id":"53fddf437e561","type":"node","index":28,"name":"details","shopwareField":"","children":[{"id":"53ecb9c7d602d","type":"leaf","index":0,"name":"orderDetailId","shopwareField":"orderDetailId"},{"id":"53ecb9ee6f821","type":"leaf","index":1,"name":"articleId","shopwareField":"articleId"},{"id":"53ecbaa627334","type":"leaf","index":2,"name":"taxId","shopwareField":"taxId"},{"id":"53ecba416356a","type":"leaf","index":3,"name":"taxRate","shopwareField":"taxRate"},{"id":"53ecbaa813093","type":"leaf","index":4,"name":"statusId","shopwareField":"statusId"},{"id":"53ecbb05eccf1","type":"leaf","index":5,"name":"number","shopwareField":"number"},{"id":"53ecbb0411d43","type":"leaf","index":6,"name":"articleNumber","shopwareField":"articleNumber"},{"id":"53ecba19dc9ef","type":"leaf","index":7,"name":"price","shopwareField":"price"},{"id":"53ecba29e1a37","type":"leaf","index":8,"name":"quantity","shopwareField":"quantity"},{"id":"53ecba34bf110","type":"leaf","index":9,"name":"articleName","shopwareField":"articleName"},{"id":"53ecbb07dda54","type":"leaf","index":10,"name":"shipped","shopwareField":"shipped"},{"id":"53ecbb09bb007","type":"leaf","index":11,"name":"shippedGroup","shopwareField":"shippedGroup"},{"id":"53ecbbc15479a","type":"leaf","index":12,"name":"releaseDate","shopwareField":"releasedate"},{"id":"53ecbbc40bcd3","type":"leaf","index":13,"name":"mode","shopwareField":"mode"},{"id":"53ecbbc57169d","type":"leaf","index":14,"name":"esdArticle","shopwareField":"esd"},{"id":"53ecbbc6b6f2c","type":"leaf","index":15,"name":"config","shopwareField":"config"}]}],"shopwareField":"","parentKey":""}],"shopwareField":""}]}';
            case 'mainOrders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c8b7","name":"mainOrders","index":0,"type":"","children":[{"id":"537359399c90d","name":"mainOrder","index":0,"type":"iteration","adapter":"order","parentKey":"","shopwareField":"","children":[{"id":"53eca77b49d6d","type":"leaf","index":0,"name":"orderId","shopwareField":"orderId"},{"id":"5373865547d06","type":"leaf","index":1,"name":"orderNumber","shopwareField":"orderNumber"},{"id":"53ecb9c7d60ss","type":"leaf","index":2,"name":"invoiceNumber","shopwareField":"invoiceNumber"},{"id":"53ecb6a059334","type":"leaf","index":3,"name":"invoiceAmount","shopwareField":"invoiceAmount"},{"id":"53ecb6a74e399","type":"leaf","index":4,"name":"invoiceAmountNet","shopwareField":"invoiceAmountNet"},{"id":"53ecb6b4587ba","type":"leaf","index":5,"name":"invoiceShipping","shopwareField":"invoiceShipping"},{"id":"53ecb6be27e2e","type":"leaf","index":6,"name":"invoiceShippingNet","shopwareField":"invoiceShippingNet"},{"id":"53fddf437e58c","type":"node","index":7,"name":"taxRateSums","shopwareField":"","children":[{"id":"63e0d494n0b1d","name":"taxRateSum","index":0,"type":"iteration","adapter":"taxRateSum","parentKey":"orderId","shopwareField":"","children":[{"id":"83eab6be27a1a","type":"leaf","index":0,"name":"taxRateSums","shopwareField":"taxRateSums"},{"id":"83eah9bi27a1a","type":"leaf","index":1,"name":"taxRate","shopwareField":"taxRate"}]}]},{"id":"53ecb7dd88b2a","type":"leaf","index":9,"name":"net","shopwareField":"net"},{"id":"53ecb7f518b2a","type":"leaf","index":10,"name":"taxFree","shopwareField":"taxFree"},{"id":"53ecb9c7d602d","type":"leaf","index":11,"name":"paymentName","shopwareField":"paymentName"},{"id":"53ecb9c7d60aa","type":"leaf","index":12,"name":"paymentStatus","shopwareField":"paymentState"},{"id":"53ecb9c7d60bb","type":"leaf","index":13,"name":"orderStatus","shopwareField":"orderState"},{"id":"53ecb8c42923d","type":"leaf","index":14,"name":"currency","shopwareField":"currency"},{"id":"53ecb8c74168b","type":"leaf","index":15,"name":"currencyFactor","shopwareField":"currencyFactor"},{"id":"53ecb6ebaf4c5","type":"leaf","index":16,"name":"transactionId","shopwareField":"transactionId"},{"id":"53ecb8bd55dda","type":"leaf","index":17,"name":"trackingCode","shopwareField":"trackingCode"},{"id":"53ecb6db22a2e","type":"leaf","index":18,"name":"orderTime","shopwareField":"orderTime"},{"id":"53ecb9c7d602e","type":"leaf","index":19,"name":"email","shopwareField":"email"},{"id":"53ecb9c7d602a","type":"leaf","index":20,"name":"customerNumber","shopwareField":"customerNumber"},{"id":"53ecb9c7d60cc","type":"leaf","index":21,"name":"customerGroup","shopwareField":"customerGroupName"},{"id":"53ecb9c7d6s12","type":"leaf","index":22,"name":"billingSalutation","shopwareField":"billingSalutation"},{"id":"53ecb9c7d602b","type":"leaf","index":23,"name":"billingFirstName","shopwareField":"billingFirstName"},{"id":"53ecb9c7d602c","type":"leaf","index":24,"name":"billingLastName","shopwareField":"billingLastName"},{"id":"53ecb9cab1623","type":"leaf","index":25,"name":"billingCompany","shopwareField":"billingCompany"},{"id":"53ecb9cab162a","type":"leaf","index":26,"name":"billingDepartment","shopwareField":"billingDepartment"},{"id":"53ecb9cab162b","type":"leaf","index":27,"name":"billingStreet","shopwareField":"billingStreet"},{"id":"53ecb9cab162c","type":"leaf","index":28,"name":"billingZipCode","shopwareField":"billingZipCode"},{"id":"53ecb9cab162d","type":"leaf","index":29,"name":"billingCity","shopwareField":"billingCity"},{"id":"53ecb9cab162e","type":"leaf","index":30,"name":"billingPhone","shopwareField":"billingPhone"},{"id":"53ecb9cab16d3","type":"leaf","index":32,"name":"billingAdditionalAddressLine1","shopwareField":"billingAdditionalAddressLine1"},{"id":"53ecb9cab16q2","type":"leaf","index":33,"name":"billingAdditionalAddressLine2","shopwareField":"billingAdditionalAddressLine2"},{"id":"52ecb9cab16q2","type":"leaf","index":34,"name":"billingState","shopwareField":"billingState"},{"id":"52ecb9cab16qd","type":"leaf","index":35,"name":"billingCountry","shopwareField":"billingCountry"},{"id":"53ecb9cjd602a","type":"leaf","index":36,"name":"shippingSalutation","shopwareField":"shippingSalutation"},{"id":"53ecb9cld6s12","type":"leaf","index":37,"name":"shippingFirstName","shopwareField":"shippingFirstName"},{"id":"53ecb9cmd602b","type":"leaf","index":38,"name":"shippingLastName","shopwareField":"shippingLastName"},{"id":"53ecb9ctd602c","type":"leaf","index":39,"name":"shippingCompany","shopwareField":"shippingCompany"},{"id":"53ecb9ceb1623","type":"leaf","index":40,"name":"shippingDepartment","shopwareField":"shippingDepartment"},{"id":"53ecb9cyb162a","type":"leaf","index":41,"name":"shippingStreet","shopwareField":"shippingStreet"},{"id":"53ecb9ck2162b","type":"leaf","index":42,"name":"shippingZipCode","shopwareField":"shippingZipCode"},{"id":"53ecb9ca5162c","type":"leaf","index":43,"name":"shippingCity","shopwareField":"shippingCity"},{"id":"53ecb9caw16d3","type":"leaf","index":44,"name":"shippingAdditionalAddressLine1","shopwareField":"shippingAdditionalAddressLine1"},{"id":"53ecb9ca616q2","type":"leaf","index":45,"name":"shippingAdditionalAddressLine1","shopwareField":"shippingAdditionalAddressLine1"},{"id":"52eax9cab16q2","type":"leaf","index":46,"name":"shippingState","shopwareField":"shippingState"},{"id":"53ecb9c7d6020","type":"leaf","index":47,"name":"shippingCountry","shopwareField":"shippingCountry"}]}],"shopwareField":""}]}';
            case 'customers':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"customers","index":1,"type":"","shopwareField":"","children":[{"id":"53ea047e7dca5","name":"customer","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ea048def53f","type":"leaf","index":0,"name":"customernumber","shopwareField":"customerNumber"},{"id":"53ea052c8f4c9","type":"leaf","index":1,"name":"email","shopwareField":"email"},{"id":"53ea0535e3348","type":"leaf","index":2,"name":"password","shopwareField":"password"},{"id":"53fb366466188","type":"leaf","index":3,"name":"encoder","shopwareField":"encoder"},{"id":"540d9e8c6ab4f","type":"leaf","index":4,"name":"active","shopwareField":"active"},{"id":"53ea054339f8e","type":"leaf","index":5,"name":"billing_company","shopwareField":"billingCompany"},{"id":"53ea057725a7d","type":"leaf","index":6,"name":"billing_department","shopwareField":"billingDepartment"},{"id":"53ea0595b1d31","type":"leaf","index":7,"name":"billing_salutation","shopwareField":"billingSalutation"},{"id":"53ea05dba6a4d","type":"leaf","index":8,"name":"billing_firstname","shopwareField":"billingFirstname"},{"id":"53ea05de1204b","type":"leaf","index":9,"name":"billing_lastname","shopwareField":"billingLastname"},{"id":"53ea05df9caf1","type":"leaf","index":10,"name":"billing_street","shopwareField":"billingStreet"},{"id":"53ea05e271edd","type":"leaf","index":12,"name":"billing_zipcode","shopwareField":"billingZipcode"},{"id":"53ea05e417656","type":"leaf","index":13,"name":"billing_city","shopwareField":"billingCity"},{"id":"53ea05e5e2e12","type":"leaf","index":14,"name":"phone","shopwareField":"billingPhone"},{"id":"53ea0652597f1","type":"leaf","index":16,"name":"billing_countryID","shopwareField":"billingCountryID"},{"id":"53ea0653ddf4a","type":"leaf","index":17,"name":"billing_stateID","shopwareField":"billingStateID"},{"id":"53ea0691b1774","type":"leaf","index":18,"name":"ustid","shopwareField":"ustid"},{"id":"53ea069d37da6","type":"leaf","index":19,"name":"shipping_company","shopwareField":"shippingCompany"},{"id":"53ea069eac2c6","type":"leaf","index":20,"name":"shipping_department","shopwareField":"shippingDepartment"},{"id":"53ea06a0013c7","type":"leaf","index":21,"name":"shipping_salutation","shopwareField":"shippingSalutation"},{"id":"53ea06a23cdc1","type":"leaf","index":22,"name":"shipping_firstname","shopwareField":"shippingFirstname"},{"id":"53ea0e4a3792d","type":"leaf","index":23,"name":"shipping_lastname","shopwareField":"shippingLastname"},{"id":"53ea0e4fda6e7","type":"leaf","index":24,"name":"shipping_street","shopwareField":"shippingStreet"},{"id":"53ea0e55b2b31","type":"leaf","index":26,"name":"shipping_zipcode","shopwareField":"shippingZipcode"},{"id":"53ea0e57ddba7","type":"leaf","index":27,"name":"shipping_city","shopwareField":"shippingCity"},{"id":"53ea0e5a4ee0c","type":"leaf","index":28,"name":"shipping_countryID","shopwareField":"shippingCountryID"},{"id":"53ea0e5c6d67e","type":"leaf","index":29,"name":"paymentID","shopwareField":"paymentID"},{"id":"53ea0e5e88347","type":"leaf","index":30,"name":"newsletter","shopwareField":"newsletter"},{"id":"53ea0e6194ba6","type":"leaf","index":31,"name":"accountmode","shopwareField":"accountMode"},{"id":"53ea118664a90","type":"leaf","index":32,"name":"customergroup","shopwareField":"customergroup"},{"id":"53ea1188ca4ca","type":"leaf","index":33,"name":"language","shopwareField":"language"},{"id":"53ea118b67fe2","type":"leaf","index":34,"name":"subshopID","shopwareField":"subshopID"}]}]}]}';
            case 'newsletter':
                return '{"id":"root","name":"Root","type":"node","children":{"1":{"id":"537359399c8b7","name":"Users","index":0,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"user","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"53e4b0f86aded","type":"leaf","index":0,"name":"email","shopwareField":"email"},{"id":"53e4b103bf001","type":"leaf","index":1,"name":"group","shopwareField":"groupName"},{"id":"53e4b105ea8c2","type":"leaf","index":2,"name":"salutation","shopwareField":"salutation"},{"id":"53e4b107872be","type":"leaf","index":3,"name":"firstname","shopwareField":"firstName"},{"id":"53e4b108d49f9","type":"leaf","index":4,"name":"lastname","shopwareField":"lastName"},{"id":"53e4b10a38e08","type":"leaf","index":5,"name":"street","shopwareField":"street"},{"id":"53e4b10d68c09","type":"leaf","index":7,"name":"zipcode","shopwareField":"zipCode"},{"id":"53e4b157416fc","type":"leaf","index":8,"name":"city","shopwareField":"city"},{"id":"53e4b1592dd4b","type":"leaf","index":9,"name":"lastmailing","shopwareField":"lastNewsletter"},{"id":"53e4b15a69651","type":"leaf","index":10,"name":"lastread","shopwareField":"lastRead"},{"id":"53e4b15bde918","type":"leaf","index":11,"name":"userID","shopwareField":"userID"}]}]}}}';
            case 'translations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"552fbf10a3912","type":"leaf","index":0,"name":"objectKey","shopwareField":"objectKey"},{"id":"53ce5e8f25a24","name":"objectType","index":1,"type":"leaf","shopwareField":"objectType"},{"id":"53ce5f9501db7","name":"baseName","index":2,"type":"leaf","shopwareField":"baseName"},{"id":"552fbde3dcb30","type":"leaf","index":3,"name":"name","shopwareField":"name"},{"id":"53ce5fa3bd231","name":"description","index":4,"type":"leaf","shopwareField":"description"},{"id":"543798726b38e","type":"leaf","index":5,"name":"languageId","shopwareField":"languageId"}]}]}]}';
            default:
                throw new \Exception('The profile could not be created.');
        }
    }

    /**
     * @param int $baseProfileId
     *
     * @return string
     */
    public static function getDefaultTreeByBaseProfile($baseProfileId)
    {
        return Shopware()->Container()->get('models')
            ->getRepository(Profile::class)
            ->createQueryBuilder('p')
            ->select('p.tree')
            ->where('p.id = :baseProfileId')
            ->setParameter('baseProfileId', $baseProfileId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $profileType
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function getTreeByHiddenProfileType($profileType)
    {
        switch ($profileType) {
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"4","name":"articles","index":0,"type":"","children":[{"id":"53e0d3148b0b2","name":"article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":[{"id":"53e0d365881b7","type":"leaf","index":0,"name":"ordernumber","shopwareField":"orderNumber"},{"id":"53e0d329364c4","type":"leaf","index":1,"name":"mainnumber","shopwareField":"mainNumber"},{"id":"544f816b5eb00","name":"similars","index":2,"type":"iteration","adapter":"similar","parentKey":"articleId","shopwareField":"","children":[{"id":"544f818e656fd","type":"leaf","index":0,"name":"similar","shopwareField":"ordernumber"}]},{"id":"544f819b59b8b","name":"accessories","index":3,"type":"iteration","adapter":"accessory","parentKey":"articleId","shopwareField":"","children":[{"id":"544f81ac1b04e","type":"leaf","index":0,"name":"accessory","shopwareField":"ordernumber"}]},{"id":"544f83241f649","type":"leaf","index":4,"name":"processed","shopwareField":"processed"}],"attributes":null}],"shopwareField":""}]}';
            case 'articlesImages':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"images","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"image","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ff1e618a9ad","type":"leaf","index":0,"name":"ordernumber","shopwareField":"ordernumber"},{"id":"5373865547d06","name":"image","index":1,"type":"leaf","shopwareField":"image"},{"id":"55152b922111d","type":"leaf","index":2,"name":"thumbnail","shopwareField":"thumbnail"}]}]}]}';
            default:
                throw new \Exception('The profile type does not exists.');
        }
    }
}

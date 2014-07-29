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
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the default tree for a profile by given profile type
     * 
     * @param string $profileType
     * @return string|boolean
     * @throws \Exception
     */
    static public function getDefaultTreeByProfileType($profileType)
    {
        switch ($profileType) {
            case 'categories':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"Category","index":0,"type":"iteration","adapter":"default","attributes":[{"id":"53738653da10f","name":"show_filter_groups","index":0,"shopwareField":"showFilterGroups"}],"children":[{"id":"5373865547d06","name":"Id","index":1,"type":"leaf","shopwareField":"id"},{"id":"537386ac3302b","name":"Description","index":2,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"Active","index":0,"shopwareField":"active"}],"children":[{"id":"5373870d38c80","name":"Value","index":1,"type":"leaf","shopwareField":"name"}]},{"id":"537388742e20e","name":"Title","index":3,"type":"leaf","shopwareField":"name"}]}]}]}';
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"2","name":"Header","index":0,"type":"node","children":[{"id":"3","name":"HeaderChild","index":0,"type":"node"}]},{"id":"4","name":"Articles","index":1,"type":"node","children":[{"id":"5","name":"Article","index":0,"type":"iteration","adapter":"article","attributes":[{"id":"6","name":"variantId","index":0,"shopwareField":"variantId"},{"id":"7","name":"orderNumber","index":1,"shopwareField":"orderNumber"}],"children":[{"id":"8","name":"mainNumber","index":2,"type":"leaf","shopwareField":"mainNumber"},{"id":"9","name":"name","index":3,"type":"leaf","shopwareField":"name"},{"id":"10","name":"tax","index":4,"type":"leaf","shopwareField":"tax"},{"id":"11","name":"supplierName","index":5,"type":"leaf","shopwareField":"supplierName"},{"id":"12","name":"additionalText","index":6,"type":"leaf","shopwareField":"additionalText","attributes":[{"id":"13a","name":"inStock","index":0,"shopwareField":"inStock"}]},{"id":"13","name":"Prices","index":7,"type":"node","children":[{"id":"14","name":"Price","index":0,"type":"iteration","adapter":"price","parentKey":"variantId","attributes":[{"id":"15","name":"group","index":0,"shopwareField":"priceGroup"}],"children":[{"id":"16","name":"pricegroup","index":1,"type":"leaf","shopwareField":"priceGroup"},{"id":"17","name":"price","index":2,"type":"leaf","shopwareField":"price"}]}]},{"id":"53ccd2dbcd345","name":"Similars","index":8,"type":"node","shopwareField":"","children":[{"id":"53ccd3b232713","name":"similar","index":0,"type":"iteration","adapter":"similar","parentKey":"articleId","shopwareField":"","children":[{"id":"53ccd4586f580","name":"similarId","index":0,"type":"leaf","shopwareField":"similarId"}]}]},{"id":"53ccd51b807bc","name":"Images","index":9,"type":"node","shopwareField":"","children":[{"id":"53ccd529c4019","name":"image","index":0,"type":"iteration","adapter":"image","parentKey":"articleId","shopwareField":"","children":[{"id":"53ccd58e8bb25","name":"image_name","index":0,"type":"leaf","shopwareField":"path"}]}]}]}]}]}';
            case 'articlesInStock':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"articlesInStock","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"article","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"5373865547d06","name":"article_number","index":0,"type":"leaf","shopwareField":"orderNumber"},{"id":"537386ac3302b","name":"Description","index":1,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"supplier","index":0,"shopwareField":"supplier"}],"children":[{"id":"5373870d38c80","name":"Value","index":1,"type":"leaf","shopwareField":"additionalText"}]},{"id":"537388742e20e","name":"inStock","index":2,"type":"leaf","shopwareField":"inStock"}]}]}]}';
            case 'articlesPrices':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Prices","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Price","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"articleDetailsId","index":0,"shopwareField":"articleDetailsId"}],"children":[{"id":"5373865547d06","name":"articleId","index":1,"type":"leaf","shopwareField":"articleId"},{"id":"537386ac3302b","name":"Description","index":2,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"from","index":0,"shopwareField":"from"}],"children":[{"id":"5373870d38c80","name":"to","index":1,"type":"leaf","shopwareField":"to"}]},{"id":"537388742e20e","name":"price","index":3,"type":"leaf","shopwareField":"price"}]}]}]}';
            case 'articlesTranslations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"article_number","index":0,"shopwareField":"articleNumber"}],"children":{"2":{"id":"53ce5e8f25a24","name":"title","index":1,"type":"leaf","shopwareField":"title"},"3":{"id":"53ce5f9501db7","name":"description","index":2,"type":"leaf","shopwareField":"description"},"4":{"id":"53ce5fa3bd231","name":"long_description","index":3,"type":"leaf","shopwareField":"descriptionLong"},"5":{"id":"53ce5fb6d95d8","name":"keywords","index":4,"type":"leaf","shopwareField":"keywords"}}}]}]}';
            case 'orders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Orders","index":1,"type":"node","children":[{"id":"537359399c90d","name":"Order","index":0,"type":"iteration","adapter":"order","attributes":[{"id":"53738653da10f","name":"Attribute1","index":0,"shopwareField":"parent"}],"children":[{"id":"5373865547d06","name":"Id","index":1,"type":"leaf","shopwareField":"id"},{"id":"537386ac3302b","name":"Description","index":2,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"Attribute2","index":0,"shopwareField":"active"}],"children":[{"id":"5373870d38c80","name":"Value","index":1,"type":"leaf","shopwareField":"description"}]},{"id":"537388742e20e","name":"Title","index":3,"type":"leaf","shopwareField":"description"}]}]}]}';
            case 'customers':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"customers","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"customer","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"password","index":0,"shopwareField":"password"}],"children":[{"id":"5373865547d06","name":"Id","index":1,"type":"leaf","shopwareField":"id"},{"id":"537386ac3302b","name":"billing_info","index":2,"type":"node","shopwareField":"description","attributes":[],"children":{"1":{"id":"53cd02b45a066","name":"first_name","index":0,"type":"leaf","shopwareField":"billingFirstname"},"2":{"id":"53cd0343c19c2","name":"last_name","index":1,"type":"leaf","shopwareField":"shippingLastname"}}},{"id":"537388742e20e","name":"shipping_info","index":3,"type":"node","shopwareField":"encoder","children":[{"id":"53cd02fa7025a","name":"first_name","index":0,"type":"leaf","shopwareField":"shippingFirstname"},{"id":"53cd031bb402c","name":"last_name","index":1,"type":"leaf","shopwareField":"shippingLastname"}]},{"id":"53cd036e8d9f3","name":"encoder","index":4,"type":"leaf","shopwareField":"encoder"}]}]}]}';
            case 'newsletter':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Users","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"user","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"userID","index":0,"shopwareField":"userID"}],"children":[{"id":"5373865547d06","name":"email","index":1,"type":"leaf","shopwareField":"email"},{"id":"537386ac3302b","name":"Information","index":2,"type":"node","shopwareField":"description","attributes":[],"children":[{"id":"5373870d38c80","name":"salutation","index":0,"type":"leaf","shopwareField":"salutation"},{"id":"53cd096c0e116","name":"first_name","index":1,"type":"leaf","shopwareField":"firstName"},{"id":"53cd098005374","name":"last_name","index":2,"type":"leaf","shopwareField":"lastName"},{"id":"53cd09a440859","name":"street","index":3,"type":"leaf","shopwareField":"street"},{"id":"53cd09b26e7dc","name":"street_number","index":4,"type":"leaf","shopwareField":"streetNumber"},{"id":"53cd09c6c183e","name":"city","index":5,"type":"leaf","shopwareField":"city"},{"id":"53cd09d35b7c5","name":"zip_code","index":6,"type":"leaf","shopwareField":"zipCode"}]},{"id":"537388742e20e","name":"group","index":3,"type":"leaf","shopwareField":"groupName"},{"id":"53cd09ff37910","name":"last_read","index":4,"type":"leaf","shopwareField":"lastRead"}]}]}]}';
            default :
                throw new \Exception('The profile could not be created.');
        }

        return false;
    }

}

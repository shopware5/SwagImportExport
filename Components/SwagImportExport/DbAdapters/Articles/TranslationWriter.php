<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class TranslationWriter
{
    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->writer = new \Shopware_Components_Translation();
        $this->shops = $this->getShops();
    }

    public function write($articleId, $articleDetailId, $mainDetailId, $translations)
    {
        $whiteList = array(
            'name',
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
        );

        $variantWhiteList = array(
            'additionalText',
            'packUnit',
        );

        $whiteList = array_merge($whiteList, $variantWhiteList);

        //attributes
        $attributes =  $this->getTranslationAttr();

        if ($attributes){
            foreach ($attributes as $attr){
                $whiteList[] = $attr['name'];
                $variantWhiteList[] = $attr['name'];
            }
        }

        foreach ($translations as $translation){

            if(!$this->isValid($translation)){
                continue;
            }

            $languageId = $translation['languageId'];

            if (!$this->getShop($languageId)) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
                throw new AdapterException(sprintf($message, $languageId));
            }

            if ($articleDetailId === $mainDetailId){
                $data = array_intersect_key($translation, array_flip($whiteList));
                $this->writer->write($languageId, 'article', $articleId, $data);
            } else {
                $data = array_intersect_key($translation, array_flip($variantWhiteList));

                //checks for empty translations
                if (!empty($data)){
                    foreach($data as $index => $rows){
                        //removes empty rows
                        if(empty($rows)){
                            unset($data[$index]);
                        }
                    }
                }

                //saves if there is available data
                if (!empty($data)){
                    $this->writer->write($languageId, 'variant', $articleDetailId, $data);
                }
            }
        }
    }

    /**
     * @param $translation
     * @return bool
     */
    private function isValid($translation)
    {
        if (!isset($translation['languageId']) || empty($translation['languageId'])){
            return false;
        }

        return true;
    }

    /**
     * Returns all shops
     * @return array
     */
    public function getShops()
    {
        $shops = array();
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_core_shops');

        foreach ($result as $row) {
            $shops[$row['id']] = $row['name'];
        }

        return $shops;
    }

    /**
     * @param $shopId
     */
    public function getShop($shopId)
    {
        return $this->shops[$shopId];
    }

    /**
     * Returns translation attributes
     * @return mixed
     */
    public function getTranslationAttr()
    {
        return $this->connection->fetchAll('SELECT `name` FROM s_core_engine_elements WHERE `translatable` = 1');
    }
}
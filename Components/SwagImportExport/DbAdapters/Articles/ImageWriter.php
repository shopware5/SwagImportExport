<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class ImageWriter
{
    /** @var ArticlesDbAdapter */
    protected $articlesDbAdapter = null;

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct(ArticlesDbAdapter $articlesDbAdapter)
    {
        $this->articlesDbAdapter = $articlesDbAdapter;
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function getArticlesDbAdapter()
    {
        return $this->articlesDbAdapter;
    }

    public function write($articleId, $mainDetailOrderNumber, $images)
    {
        $newImages = array();
        foreach ($images as $image) {

            //if image data has only 'parentIndexElement' element
            if (count($image) < 2 ) {
                break;
            }

            if (empty($image['mediaId']) && empty($image['path']) && empty($image['imageUrl'])) {
                continue;
            }

            if (isset($image['mediaId']) && !empty($image['mediaId'])) {
                $media = $this->getMediaById($image['mediaId']);
                $image['path'] = $media['name'];
            } elseif (isset($image['path']) && !empty($image['path'])) {
                $media = $this->getMediaByName($image['path']);
            } elseif (isset($image['imageUrl']) && !empty($image['imageUrl'])) {
                $name = pathinfo($image['imageUrl'], PATHINFO_FILENAME);
                $media = $this->getMediaByName($name);
                $image['path'] = $name;

                if (!$media) {
                    $thumbnail = isset($image['thumbnail']) && $image['thumbnail'] == 0 ? 0 : 1;
                    $data = array(
                        'ordernumber' => $mainDetailOrderNumber,
                        'image' => $image['imageUrl'],
                        'thumbnail' => $thumbnail
                    );

                    $this->getArticlesDbAdapter()->setUnprocessedData('articlesImages', 'default', $data);
                }
            }

            if (!$media) {
                continue;
            }

            $image['mediaId'] = $media['id'];

            if (!$this->isImageExists($articleId, $image['mediaId'])) {
                $newImages['medias'][] = $media;
                $newImages['images'][] = $image;

                $isImageNameCorrect = $this->isImageNameCorrect($image['mediaId'], $image['path']);
                if (!$isImageNameCorrect) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/image_not_found', 'Image with name %s could not be found');
                    throw new AdapterException(sprintf($message, $image['path']));
                }
            }
        }

        if ($newImages) {
            $this->insertImages($newImages, $articleId); //insert only new images
        }
    }

    protected function getMediaById($mediaId)
    {
        $media = $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media WHERE id = ?',
            array($mediaId)
        );

        return $media;
    }

    protected function getMediaByName($name)
    {
        $media = $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media media WHERE media.name = ?',
            array($name)
        );

        return $media;
    }

    protected function isImageExists($articleId, $mediaId)
    {
        $isImageExists = $this->db->fetchOne(
            'SELECT id FROM s_articles_img WHERE articleID = ? AND media_id = ?',
            array($articleId, $mediaId)
        );

        return is_numeric($isImageExists);
    }

    protected function isImageNameCorrect($mediaId, $imageName)
    {
        $isImageNameCorrect = $this->db->fetchOne(
            'SELECT media.id FROM s_media media WHERE media.id = ? AND media.name = ?',
            array($mediaId, $imageName)
        );

        return is_numeric($isImageNameCorrect);
    }

    protected function insertImages($data, $articleId)
    {
        $medias = $data['medias'];
        $images = $data['images'];

        $imageData = $this->prepareImageData($medias, $images);

        $values = implode(
            ', ',
            array_map(
                function ($image) use ($articleId) {
                    if ($image['variantId']) {
                        return "({$articleId}, '{$image['name']}', '{$image['main']}', '{$image['description']}', '{$image['extension']}', '{$image['variantId']}', {$image['id']})";
                    } else {
                        return "({$articleId}, '{$image['name']}', '{$image['main']}', '{$image['description']}', '{$image['extension']}', NULL, {$image['id']})";
                    }
                },
                $imageData
            )
        );

        $insert = "INSERT INTO s_articles_img (articleID, img, main, description, extension, article_detail_id, media_id) VALUES {$values}";
        $this->connection->exec($insert);
    }

    protected function prepareImageData($medias, $images)
    {
        $imageData = array();
        foreach($images as $key => $image) {
            $imageData[$key]['name'] = $image['path'];
            $imageData[$key]['main'] = $image['main'] ? : 2;
            $imageData[$key]['description'] = $medias[$key]['description'];
            $imageData[$key]['extension'] = $medias[$key]['extension'];
            $imageData[$key]['variantId'] = $image[$key]['variantId'];
            $imageData[$key]['id'] = $medias[$key]['id'];
        }

        return $imageData;
    }
}
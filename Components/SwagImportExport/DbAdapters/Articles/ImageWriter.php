<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class ImageWriter
{
    /**
     * @var ArticlesDbAdapter
     */
    protected $articlesDbAdapter;

    /**
     * @var PDOConnection
     */
    protected $db;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(ArticlesDbAdapter $articlesDbAdapter)
    {
        $this->articlesDbAdapter = $articlesDbAdapter;
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
    }

    /**
     * @return ArticlesDbAdapter
     */
    public function getArticlesDbAdapter()
    {
        return $this->articlesDbAdapter;
    }

    /**
     * @param $articleId
     * @param $mainDetailOrderNumber
     * @param $images
     *
     * @throws AdapterException
     */
    public function write($articleId, $mainDetailOrderNumber, $images)
    {
        $newImages = [];
        foreach ($images as $image) {
            //if image data has only 'parentIndexElement' element
            if (count($image) < 2) {
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
                // if data comes from article adapter prepare data for hidden profile
                if (!$media) {
                    $thumbnail = isset($image['thumbnail']) && $image['thumbnail'] == 0 ? 0 : 1;
                    $data = [
                        'ordernumber' => $mainDetailOrderNumber,
                        'image' => $image['imageUrl'],
                        'thumbnail' => $thumbnail,
                    ];
                    // set unprocessed data to use hidden profile for articleImages
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

    /**
     * @param $mediaId
     *
     * @return mixed
     */
    protected function getMediaById($mediaId)
    {
        $media = $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media WHERE id = ?',
            [$mediaId]
        );

        return $media;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function getMediaByName($name)
    {
        $media = $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media media WHERE media.name = ?',
            [$name]
        );

        return $media;
    }

    /**
     * @param $articleId
     * @param $mediaId
     *
     * @return bool
     */
    protected function isImageExists($articleId, $mediaId)
    {
        $isImageExists = $this->db->fetchOne(
            'SELECT id FROM s_articles_img WHERE articleID = ? AND media_id = ?',
            [$articleId, $mediaId]
        );

        return is_numeric($isImageExists);
    }

    /**
     * @param $mediaId
     * @param $imageName
     *
     * @return bool
     */
    protected function isImageNameCorrect($mediaId, $imageName)
    {
        $isImageNameCorrect = $this->db->fetchOne(
            'SELECT media.id FROM s_media media WHERE media.id = ? AND media.name = ?',
            [$mediaId, $imageName]
        );

        return is_numeric($isImageNameCorrect);
    }

    /**
     * @param $data
     * @param $articleId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function insertImages($data, $articleId)
    {
        $medias = $data['medias'];
        $images = $data['images'];

        list($imageData, $mediaId) = $this->prepareImageData($medias, $images);

        $values = implode(
            ', ',
            array_map(
                function ($image) use ($articleId) {
                    if ($image['variantId']) {
                        return "({$articleId}, '{$image['name']}', '{$image['main']}', '{$image['description']}', '{$image['extension']}', '{$image['variantId']}', {$image['id']})";
                    }

                    return "({$articleId}, '{$image['name']}', '{$image['main']}', '{$image['description']}', '{$image['extension']}', NULL, {$image['id']})";
                },
                $imageData
            )
        );
        $insert = "INSERT INTO s_articles_img (articleID, img, main, description, extension, article_detail_id, media_id) VALUES {$values}";
        $this->connection->exec($insert);

        $this->setMainImage($articleId, $mediaId);
    }

    /**
     * @param $medias
     * @param $images
     *
     * @return array
     */
    protected function prepareImageData($medias, $images)
    {
        $mediaId = null;
        $imageData = [];
        foreach ($images as $key => $image) {
            $imageData[$key]['name'] = $image['path'];
            $imageData[$key]['main'] = $image['main'] ?: 2;
            $imageData[$key]['description'] = !empty($image['description']) ? $image['description'] : $medias[$key]['description'];
            $imageData[$key]['extension'] = $medias[$key]['extension'];
            $imageData[$key]['variantId'] = $image[$key]['variantId'];
            $imageData[$key]['id'] = $medias[$key]['id'];

            if ($imageData[$key]['main'] == 1) {
                $mediaId = $medias[$key]['id'];
            }
        }

        return [$imageData, $mediaId];
    }

    /**
     * @param $articleId
     * @param $mediaId
     */
    protected function setMainImage($articleId, $mediaId)
    {
        $count = $this->countOfMainImages($articleId);
        if ($count == 1) {
            return;
        }

        if (!$count) {
            $this->setFirstImageAsMain($articleId);
        } elseif ($mediaId !== null) {
            $this->updateMain($articleId, $mediaId);
        }
    }

    /**
     * @param $articleId
     *
     * @return string
     */
    protected function countOfMainImages($articleId)
    {
        $count = $this->db->fetchOne(
            'SELECT COUNT(main)
             FROM s_articles_img
             WHERE main = 1 AND articleID = ?',
            [$articleId]
        );

        return $count;
    }

    /**
     * @param $articleId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setFirstImageAsMain($articleId)
    {
        $update = "UPDATE s_articles_img SET main = 1 WHERE articleID = {$articleId} ORDER BY id ASC LIMIT 1";
        $this->connection->exec($update);
    }

    /**
     * @param $articleId
     * @param $mediaId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateMain($articleId, $mediaId)
    {
        $update = "UPDATE s_articles_img SET main = 2 WHERE articleID = {$articleId} AND media_id != {$mediaId}";
        $this->connection->exec($update);
    }
}

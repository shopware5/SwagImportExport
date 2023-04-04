<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsDbAdapter;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ImageWriter
{
    private ProductsDbAdapter $productsDbAdapter;

    private PDOConnection $db;

    private Connection $connection;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(
        PDOConnection $db,
        Connection $connection,
        UploadPathProvider $uploadPathProvider
    ) {
        $this->db = $db;
        $this->connection = $connection;
        $this->uploadPathProvider = $uploadPathProvider;
    }

    public function setProductDBAdapter(ProductsDbAdapter $productsDbAdapter): void
    {
        $this->productsDbAdapter = $productsDbAdapter;
    }

    /**
     * @param array<int, array<string, mixed>> $images
     *
     * @throws AdapterException
     */
    public function write(int $productId, string $mainDetailOrderNumber, array $images): void
    {
        $newImages = [];
        foreach ($images as $image) {
            // if image data has only 'parentIndexElement' element
            if (\count($image) < 2) {
                break;
            }

            $media = null;

            if (empty($image['mediaId']) && empty($image['path']) && empty($image['imageUrl'])) {
                continue;
            }

            if (!empty($image['mediaId'])) {
                $media = $this->getMediaById((int) $image['mediaId']);
                $image['path'] = $media['name'];
            } elseif (!empty($image['path'])) {
                $media = $this->getMediaByName($image['path']);
            } elseif (!empty($image['imageUrl'])) {
                $name = $this->uploadPathProvider->getFileNameWithoutExtensionFromPath($image['imageUrl']);
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
                    $this->productsDbAdapter->setUnprocessedData(
                        DataDbAdapter::PRODUCT_IMAGE_ADAPTER,
                        'default',
                        $data
                    );
                }
            }

            if (!$media) {
                continue;
            }

            $image['mediaId'] = $media['id'];

            if (!$this->isImageExists($productId, (int) $image['mediaId'])) {
                $newImages['medias'][] = $media;
                $newImages['images'][] = $image;

                $isImageNameCorrect = $this->isImageNameCorrect((int) $image['mediaId'], $image['path']);
                if (!$isImageNameCorrect) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/image_not_found', 'Image with name %s could not be found');
                    throw new AdapterException(\sprintf($message, $image['path']));
                }
            }
        }

        if ($newImages) {
            $this->insertImages($newImages, $productId); // insert only new images
        }
    }

    /**
     * @return array<string, string>
     */
    private function getMediaById(int $mediaId): array
    {
        return $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media WHERE id = ?',
            [$mediaId]
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMediaByName(string $name): ?array
    {
        $media = $this->db->fetchRow(
            'SELECT id, name, description, extension FROM s_media media WHERE media.name = ?',
            [$name]
        );

        if (\is_bool($media)) {
            return null;
        }

        return $media;
    }

    private function isImageExists(int $productId, int $mediaId): bool
    {
        $isImageExists = $this->db->fetchOne(
            'SELECT id FROM s_articles_img WHERE articleID = ? AND media_id = ?',
            [$productId, $mediaId]
        );

        return \is_numeric($isImageExists);
    }

    private function isImageNameCorrect(int $mediaId, string $imageName): bool
    {
        $isImageNameCorrect = $this->db->fetchOne(
            'SELECT media.id FROM s_media media WHERE media.id = ? AND media.name = ?',
            [$mediaId, $imageName]
        );

        return \is_numeric($isImageNameCorrect);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws DBALException
     */
    private function insertImages(array $data, int $productId): void
    {
        $medias = $data['medias'];
        $images = $data['images'];

        [$imageData, $mediaId] = $this->prepareImageData($medias, $images);

        $values = \implode(
            ', ',
            \array_map(
                function ($image) use ($productId) {
                    if ($image['variantId']) {
                        return sprintf("(%s, '%s', '%s', '%s', '%s', '%s', %s)", $productId, $image['name'], $image['main'], $image['description'], $image['extension'], $image['variantId'], $image['id']);
                    }

                    return sprintf("(%s, '%s', '%s', '%s', '%s', NULL, %s)", $productId, $image['name'], $image['main'], $image['description'], $image['extension'], $image['id']);
                },
                $imageData
            )
        );
        $insert = sprintf('INSERT INTO s_articles_img (articleID, img, main, description, extension, article_detail_id, media_id) VALUES %s', $values);
        $this->connection->executeStatement($insert);

        $this->setMainImage($productId, $mediaId);
    }

    /**
     * @param array<string, mixed> $medias
     * @param array<string, mixed> $images
     */
    private function prepareImageData(array $medias, array $images): array
    {
        $mediaId = null;
        $imageData = [];
        foreach ($images as $key => $image) {
            $imageData[$key]['name'] = $image['path'];
            $imageData[$key]['main'] = $image['main'] ?? 2;
            $imageData[$key]['description'] = !empty($image['description']) ? $image['description'] : $medias[$key]['description'];
            $imageData[$key]['extension'] = $medias[$key]['extension'];
            $imageData[$key]['variantId'] = $medias[$key]['variantId'];
            $imageData[$key]['id'] = $medias[$key]['id'];

            if ((int) $imageData[$key]['main'] === 1) {
                $mediaId = (int) $medias[$key]['id'];
            }
        }

        return [$imageData, $mediaId];
    }

    private function setMainImage(int $productId, ?int $mediaId): void
    {
        $count = $this->countOfMainImages($productId);
        if ($count == 1) {
            return;
        }

        if (!$count) {
            $this->setFirstImageAsMain($productId);
        } elseif ($mediaId !== null) {
            $this->updateMain($productId, $mediaId);
        }
    }

    private function countOfMainImages(int $productId): int
    {
        $count = $this->db->fetchOne(
            'SELECT COUNT(main)
             FROM s_articles_img
             WHERE main = 1 AND articleID = ?',
            [$productId]
        );

        return (int) $count;
    }

    /**
     * @throws DBALException
     */
    private function setFirstImageAsMain(int $productId): void
    {
        $update = 'UPDATE s_articles_img SET main = 1 WHERE articleID = :productId ORDER BY id ASC LIMIT 1';
        $this->connection->executeStatement($update, ['productId' => $productId]);
    }

    /**
     * @throws DBALException
     */
    private function updateMain(int $productId, int $mediaId): void
    {
        $update = 'UPDATE s_articles_img SET main = 2 WHERE articleID = :productId AND media_id != :mediaId';
        $this->connection->executeStatement($update, ['productId' => $productId, 'mediaId' => $mediaId]);
    }
}

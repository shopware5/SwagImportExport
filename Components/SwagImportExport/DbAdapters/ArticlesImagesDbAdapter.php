<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DataManagers\ArticleImageDataManager;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Service\UnderscoreToCamelCaseServiceInterface;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\ArticleImageValidator;
use Shopware\Components\Thumbnail\Manager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Repository;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use Symfony\Component\HttpFoundation\File\File;

class ArticlesImagesDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var MediaService
     */
    protected $mediaService;

    /**
     * @var \Enlight_Controller_Request_Request
     */
    protected $request;

    /**
     * @var \Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @var ArticleImageValidator
     */
    protected $validator;

    /**
     * @var ArticleImageDataManager
     */
    protected $dataManager;

    /**
     * @var int
     */
    protected $imageImportMode;

    /**
     * @var bool
     */
    protected $importExportErrorMode;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * @var string
     */
    protected $logState;

    /**
     * @var Manager
     */
    protected $thumbnailManager;

    /**
     * @var string
     */
    protected $docPath;

    /**
     * @var UnderscoreToCamelCaseServiceInterface
     */
    protected $underscoreToCamelCaseService;

    /**
     * @var DbalHelper
     */
    private $dbalHelper;

    public function __construct()
    {
        $this->manager = Shopware()->Models();
        $this->db = Shopware()->Db();
        $this->mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $this->request = Shopware()->Front()->Request();
        $this->eventManager = Shopware()->Events();
        $this->validator = new ArticleImageValidator();
        $this->dataManager = new ArticleImageDataManager();
        $this->imageImportMode = (int) Shopware()->Config()->get('SwagImportExportImageMode');
        $this->importExportErrorMode = (bool) Shopware()->Config()->get('SwagImportExportErrorMode');
        $this->thumbnailManager = Shopware()->Container()->get('thumbnail_manager');
        $this->docPath = Shopware()->DocPath('media_' . 'temp');
        $this->underscoreToCamelCaseService = Shopware()->Container()->get('swag_import_export.underscore_camelcase_service');
        $this->dbalHelper = DbalHelper::create();
    }

    /**
     * Returns record ids
     *
     * @param int $start
     * @param int $limit
     * @param $filter
     *
     * @return array
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('image.id');

        $builder->from(Image::class, 'image')
                ->where('image.articleDetailId IS NULL')
                ->andWhere('image.parentId IS NULL');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();
        $result = array_column($records, 'id');

        return $result;
    }

    /**
     * Returns article images
     *
     * @param array $ids
     * @param array $columns
     *
     * @throws
     *
     * @return array
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/no_article_images_ids', 'Can not read article images without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/no_article_images_column', 'Can not read article images without column names.');
            throw new \Exception($message);
        }

        $builder = $this->getBuilder($columns, $ids);
        $result['default'] = $builder->getQuery()->getArrayResult();

        foreach ($result['default'] as &$image) {
            $image['image'] = $this->mediaService->getUrl($image['image']);

            if (empty($image['relations'])) {
                continue;
            }

            $relations = explode(';', $image['relations']);
            $relations = array_unique($relations);

            $out = [];
            foreach ($relations as $rule) {
                $split = explode('|', $rule);
                $ruleId = $split[0];
                $name = $split[2];
                $groupName = $split[3];
                if ($groupName && $name) {
                    $out[$ruleId][] = "$groupName:$name";
                }
            }

            $temp = [];
            foreach ($out as $group) {
                $temp[] = '{' . implode('|', $group) . '}';
            }

            $image['relations'] = implode('&', $temp);
        }

        return $result;
    }

    /**
     * Returns default image columns name
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        $path = $this->request->getScheme() . '://' . $this->request->getHttpHost() . $this->request->getBasePath() . '/media/image/';

        $columns = [
            'mv.number as ordernumber',
            "CONCAT('$path', aimage.path, '.', aimage.extension) as image",
            'aimage.main as main',
            'aimage.description as description',
            'aimage.position as position',
            'aimage.width as width',
            'aimage.height as height',
            "GroupConcat( im.id, '|', mr.optionId, '|' , co.name, '|', cg.name
            ORDER by im.id
            SEPARATOR ';' ) as relations",
            ' \'1\' as thumbnail',
        ];

        $columns = array_merge($columns, $this->getAttributesColumns());

        return $columns;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * Insert/Update data into db
     *
     * @param array $records
     *
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articlesImages/no_records',
                'No new article image records were found.'
            );
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesImagesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        foreach ($records['default'] as $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);

                /** @var \Shopware\Models\Article\Detail $articleDetailModel */
                $articleDetailModel = $this->manager->getRepository(Detail::class)->findOneBy(['number' => $record['ordernumber']]);
                if (!$articleDetailModel) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists');
                    throw new AdapterException(sprintf($message, $record['ordernumber']));
                }

                $record = $this->dataManager->setDefaultFields($record, $articleDetailModel->getArticle()->getId());
                $this->validator->validate($record, ArticleImageValidator::$mapper);

                $relations = [];
                if (isset($record['relations'])) {
                    $importedRelations = explode('&', $record['relations']);

                    foreach ($importedRelations as $key => $relation) {
                        if ($relation === '') {
                            continue;
                        }

                        $variantConfig = explode('|', preg_replace('/{|}/', '', $relation));
                        foreach ($variantConfig as $config) {
                            list($group, $option) = explode(':', $config);

                            //Get configurator group
                            $cGroupModel = $this->manager->getRepository(Group::class)->findOneBy(['name' => $group]);
                            if ($cGroupModel === null) {
                                continue;
                            }

                            //Get configurator option
                            $cOptionModel = $this->manager->getRepository(Option::class)->findOneBy(['name' => $option, 'groupId' => $cGroupModel->getId()]);
                            if ($cOptionModel === null) {
                                continue;
                            }

                            $relations[$key][] = ['group' => $cGroupModel, 'option' => $cOptionModel];
                        }
                    }
                }

                /** @var \Shopware\Models\Article\Article $article */
                $article = $articleDetailModel->getArticle();

                $name = pathinfo($record['image'], PATHINFO_FILENAME);

                $media = false;
                if ($this->imageImportMode === 1) {
                    $mediaRepository = $this->manager->getRepository(Media::class);
                    $media = $mediaRepository->findOneBy(['name' => $name]);
                }

                //create new media
                if ($this->imageImportMode === 2 || empty($media)) {
                    $path = $this->load($record['image'], $name);
                    $file = new File($path);

                    $media = new Media();
                    $media->setAlbumId(-1);
                    $media->setAlbum($this->manager->getRepository(Album::class)->find(-1));

                    $media->setFile($file);
                    $media->setName(pathinfo($record['image'], PATHINFO_FILENAME));
                    $media->setDescription('');
                    $media->setCreated(new \DateTime());
                    $media->setUserId(0);

                    $this->manager->persist($media);
                    $this->manager->flush();

                    //thumbnail flag
                    //TODO: validate thumbnail
                    $thumbnail = (bool) $record['thumbnail'];

                    //generate thumbnails
                    if ($media->getType() === Media::TYPE_IMAGE && $thumbnail) {
                        $this->thumbnailManager->createMediaThumbnail($media, [], true);
                    }
                }

                $image = new Image();
                $image->setArticle($article);
                $image->setPosition($record['position']);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());
                $image->setMedia($media);
                $image->setMain($record['main']);
                $image->setDescription($record['description']);
                $this->manager->persist($image);
                $this->manager->flush();

                if ($relations && !empty($relations)) {
                    $this->setImageMappings($relations, $image->getId());
                }

                $this->createAttribute($record, $image);

                // Prevent multiple images from being a preview
                if ((int) $record['main'] === 1) {
                    $this->db->update(
                        's_articles_img',
                        ['main' => 2],
                        [
                            'articleID = ?' => $article->getId(),
                            'id <> ?' => $image->getId(),
                        ]
                    );
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return [
            ['id' => 'default', 'name' => 'default'],
        ];
    }

    /**
     * @param string $section
     *
     * @return bool|mixed
     */
    public function getColumns($section)
    {
        $method = 'get' . ucfirst($section) . 'Columns';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * @param $message
     *
     * @throws \RuntimeException
     */
    public function saveMessage($message)
    {
        if ($this->importExportErrorMode === false) {
            throw new \RuntimeException($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * @param $logMessages
     */
    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * @return string
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @param $logState
     */
    public function setLogState($logState)
    {
        $this->logState = $logState;
    }

    /**
     * @param array $columns
     * @param array $ids
     *
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from(Image::class, 'aimage')
            ->innerJoin('aimage.article', 'article')
            ->leftJoin(Detail::class, 'mv', Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
            ->leftJoin('aimage.mappings', 'im')
            ->leftJoin('im.rules', 'mr')
            ->leftJoin('mr.option', 'co')
            ->leftJoin('co.group', 'cg')
            ->leftJoin('aimage.attribute', 'attribute')
            ->where('aimage.id IN (:ids)')
            ->groupBy('aimage.id')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @return array
     */
    protected function getAttributesColumns()
    {
        $stmt = $this->db->query('SHOW COLUMNS FROM `s_articles_img_attributes`');
        $columns = $stmt->fetchAll();

        $attributes = [];
        foreach ($columns as $column) {
            if (in_array($column['Field'], ['id', 'imageID'])) {
                continue;
            }
            $attributes[] = $column['Field'];
        }

        $attributesSelect = [];
        if ($attributes) {
            $prefix = 'attribute';
            foreach ($attributes as $attribute) {
                $attr = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $attr, ucwords($attr));
            }
        }

        $attributesSelect = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesImagesDbAdapter_GetArticleImagesAttributes',
            $attributesSelect,
            ['subject' => $this]
        );

        return $attributesSelect;
    }

    /**
     * @param array $image
     *
     * @return array
     */
    protected function mapAttributes($image)
    {
        $attributes = [];
        foreach ($image as $key => $value) {
            $position = strpos($key, 'attribute');
            if ($position === false || $position !== 0) {
                continue;
            }

            $attrKey = lcfirst(str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    /**
     * Sets image mapping for variants
     *
     * @param array $relationGroups
     * @param int   $imageId
     */
    protected function setImageMappings($relationGroups, $imageId)
    {
        /** @var Repository $articleRepository */
        $articleRepository = $this->manager->getRepository(Article::class);
        $query = $articleRepository->getArticleImageDataQuery($imageId);
        $image = $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        $imageData = $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        foreach ($relationGroups as $relationGroup) {
            $optionCollection = [];
            $mapping = new Image\Mapping();

            foreach ($relationGroup as $relation) {
                $optionModel = $relation['option'];
                $optionCollection[] = $optionModel;

                $rule = new Image\Rule();
                $rule->setMapping($mapping);
                $rule->setOption($optionModel);

                $mapping->getRules()->add($rule);
                $mapping->setImage($image);

                $this->manager->persist($mapping);
            }

            $this->createImagesForOptions($optionCollection, $imageData, $image);
            $this->manager->flush();
        }
    }

    /**
     * @param Option[] $options
     * @param $imageData
     * @param $parent Image
     */
    protected function createImagesForOptions($options, $imageData, $parent)
    {
        $articleId = $parent->getArticle()->getId();
        $imageData['path'] = null;
        $imageData['parent'] = $parent;

        $join = '';
        foreach ($options as $option) {
            $alias = 'alias' . $option->getId();
            $join = $join . ' INNER JOIN s_article_configurator_option_relations alias' . $option->getId() .
                    ' ON ' . $alias . '.option_id = ' . $option->getId() .
                    ' AND ' . $alias . '.article_id = d.id ';
        }
        $sql = 'SELECT d.id
                FROM s_articles_details d
        ' . $join . '
        WHERE d.articleID = ' . (int) $articleId;

        $details = $this->db->fetchCol($sql);

        foreach ($details as $detailId) {
            $detail = $this->manager->getReference(Detail::class, $detailId);
            $image = new Image();
            $image->fromArray($imageData);
            $image->setArticleDetail($detail);
            $this->manager->persist($image);
        }
    }

    /**
     * @param string $url          URL of the resource that should be loaded (ftp, http, file)
     * @param string $baseFilename Optional: Instead of creating a hash, create a filename based on the given one
     *
     * @throws \Exception
     *
     * @return bool|string returns the absolute path of the downloaded file
     */
    protected function load($url, $baseFilename = null)
    {
        if (!is_dir($this->docPath)) {
            mkdir($this->docPath, 0777, true);
        }

        $destPath = realpath($this->docPath);

        if (!file_exists($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_not_found', 'Destination directory %s does not exist.');
            throw new \Exception(sprintf($message, $destPath));
        }

        if (!is_writable($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_permissions', 'Destination directory %s does not have write permissions.');
            throw new \Exception(sprintf($message, $destPath));
        }

        $urlArray = parse_url($url);
        $urlArray['path'] = explode('/', $urlArray['path']);
        switch ($urlArray['scheme']) {
            case 'ftp':
            case 'http':
            case 'https':
            case 'file':
                if ($baseFilename === null) {
                    $filename = md5(uniqid(mt_rand(), true));
                } else {
                    $filename = $baseFilename;
                }

                if (!$put_handle = fopen("$destPath/$filename", 'wb+')) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/could_open_dir_file', 'Could not open %s/%s for writing');
                    throw new AdapterException(sprintf($message), $destPath, $filename);
                }

                //replace empty spaces
                $url = str_replace(' ', '%20', $url);

                if (!$get_handle = fopen($url, 'rb')) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/could_not_open_url', 'Could not open %s for reading');
                    throw new AdapterException(sprintf($message, $url));
                }
                while (!feof($get_handle)) {
                    fwrite($put_handle, fgets($get_handle, 4096));
                }
                fclose($get_handle);
                fclose($put_handle);

                return "$destPath/$filename";
        }
        $message = SnippetsHelper::getNamespace()
            ->get('adapters/articlesImages/unsupported_schema', 'Unsupported schema %s.');
        throw new AdapterException(sprintf($message, $urlArray['scheme']));
    }

    private function createAttribute(array $record, Image $image)
    {
        $attributes = $this->mapAttributes($record);

        $attributesId = false;
        if ($image->getAttribute()) {
            $attributesId = $image->getAttribute()->getId();
        }

        if (!empty($attributes)) {
            $attributes['articleImageId'] = $image->getId();
            $queryBuilder = $this->dbalHelper->getQueryBuilderForEntity(
                $attributes,
                \Shopware\Models\Attribute\ArticleImage::class,
                $attributesId
            );

            $queryBuilder->execute();
        }
    }
}

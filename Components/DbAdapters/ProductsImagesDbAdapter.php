<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use GuzzleHttp\ClientInterface;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Thumbnail\Manager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Repository;
use Shopware\Models\Attribute\ArticleImage as ProductImageAttribute;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use SwagImportExport\Components\DataManagers\ProductImageDataManager;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseService;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\ProductImageValidator;
use Symfony\Component\HttpFoundation\File\File;

class ProductsImagesDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    private const PROTOCOL_FTP = 'ftp';
    private const PROTOCOL_HTTP = 'http';
    private const PROTOCOL_HTTPS = 'https';
    private const PROTOCOL_FILE = 'file';

    private const ALLOWED_PROTOCOLS = [
        self::PROTOCOL_FTP,
        self::PROTOCOL_HTTP,
        self::PROTOCOL_HTTPS,
        self::PROTOCOL_FILE,
    ];

    protected ModelManager $manager;

    protected MediaService $mediaService;

    protected ?\Enlight_Controller_Request_Request $request;

    protected \Enlight_Event_EventManager $eventManager;

    protected ProductImageValidator $validator;

    protected ProductImageDataManager $dataManager;

    protected int $imageImportMode;

    protected bool $importExportErrorMode;

    protected \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    protected array $logMessages = [];

    protected ?string $logState = null;

    protected Manager $thumbnailManager;

    protected string $docPath;

    protected UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService;

    private DbalHelper $dbalHelper;

    private ClientInterface $httpClient;

    public function __construct(
        ModelManager $manager,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        MediaService $mediaService,
        ContainerAwareEventManager $eventManager,
        ProductImageDataManager $dataManager,
        Manager $thumbnailManager,
        UnderscoreToCamelCaseService $underscoreToCamelCaseService,
        DbalHelper $dbalHelper,
        GuzzleFactory $guzzleFactory,
        \Shopware_Components_Config $config,
        \Enlight_Controller_Front $front,
        string $path
    ) {
        $this->manager = $manager;
        $this->db = $db;
        $this->mediaService = $mediaService;
        $this->eventManager = $eventManager;
        $this->dataManager = $dataManager;
        $this->thumbnailManager = $thumbnailManager;
        $this->underscoreToCamelCaseService = $underscoreToCamelCaseService;
        $this->dbalHelper = $dbalHelper;
        $this->httpClient = $guzzleFactory->createClient();

        $this->imageImportMode = (int) $config->get('SwagImportExportImageMode');
        $this->importExportErrorMode = (bool) $config->get('SwagImportExportErrorMode');
        $this->docPath = $path . \DIRECTORY_SEPARATOR . 'media_temp';
        $this->validator = new ProductImageValidator();
        $this->request = $front->Request();
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::PRODUCT_IMAGE_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(int $start = null, int $limit = null, array $filter = null): array
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

        return \array_column($records, 'id');
    }

    /**
     * Returns article images
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/no_article_images_ids', 'Can not read article images without ids.');
            throw new \Exception($message);
        }

        if (empty($columns)) {
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

            $relations = \explode(';', $image['relations']);
            $relations = \array_unique($relations);

            $out = [];
            foreach ($relations as $rule) {
                $split = \explode('|', $rule);
                $ruleId = $split[0];
                $name = $split[2];
                $groupName = $split[3];
                if ($groupName && $name) {
                    $out[$ruleId][] = "$groupName:$name";
                }
            }

            $temp = [];
            foreach ($out as $group) {
                $temp[] = '{' . \implode('|', $group) . '}';
            }

            $image['relations'] = \implode('&', $temp);
        }

        return $result;
    }

    /**
     * Returns default image columns name
     */
    public function getDefaultColumns(): array
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

        return \array_merge($columns, $this->getAttributesColumns());
    }

    /**
     * Insert/Update data into db
     *
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write(array $records): void
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articlesImages/no_records',
                'No new article image records were found.'
            );
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ProductsImagesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        foreach ($records['default'] as $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);

                /** @var Detail $productDetailModel */
                $productDetailModel = $this->manager->getRepository(Detail::class)->findOneBy(['number' => $record['ordernumber']]);
                if (!$productDetailModel) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists');
                    throw new AdapterException(\sprintf($message, $record['ordernumber']));
                }

                $record = $this->dataManager->setDefaultFields($record, $productDetailModel->getArticle()->getId());
                $this->validator->validate($record, ProductImageValidator::$mapper);

                $relations = [];
                if (isset($record['relations'])) {
                    $importedRelations = \explode('&', $record['relations']);

                    foreach ($importedRelations as $key => $relation) {
                        if ($relation === '') {
                            continue;
                        }

                        $variantConfig = \explode('|', \preg_replace('/{|}/', '', $relation));
                        foreach ($variantConfig as $config) {
                            [$group, $option] = \explode(':', $config);

                            // Get configurator group
                            $cGroupModel = $this->manager->getRepository(Group::class)->findOneBy(['name' => $group]);
                            if ($cGroupModel === null) {
                                continue;
                            }

                            // Get configurator option
                            $cOptionModel = $this->manager->getRepository(Option::class)->findOneBy(['name' => $option, 'groupId' => $cGroupModel->getId()]);
                            if ($cOptionModel === null) {
                                continue;
                            }

                            $relations[$key][] = ['group' => $cGroupModel, 'option' => $cOptionModel];
                        }
                    }
                }

                $product = $productDetailModel->getArticle();

                $name = \pathinfo($record['image'], \PATHINFO_FILENAME);

                $media = false;
                if ($this->imageImportMode === 1) {
                    $mediaRepository = $this->manager->getRepository(Media::class);
                    $media = $mediaRepository->findOneBy(['name' => $name]);
                }

                // create new media
                if ($this->imageImportMode === 2 || empty($media)) {
                    $path = $this->load($record['image'], $name);
                    $file = new File($path);

                    $media = new Media();
                    $media->setAlbumId(-1);
                    $media->setAlbum($this->manager->getRepository(Album::class)->find(-1));

                    $media->setFile($file);
                    $media->setName(\pathinfo($record['image'], \PATHINFO_FILENAME));
                    $media->setDescription('');
                    $media->setCreated(new \DateTime());
                    $media->setUserId(0);

                    $this->manager->persist($media);
                    $this->manager->flush();

                    $thumbnail = (bool) $record['thumbnail'];

                    // generate thumbnails
                    if ($media->getType() === Media::TYPE_IMAGE && $thumbnail) {
                        $this->thumbnailManager->createMediaThumbnail($media, [], true);
                    }
                }

                $image = new Image();
                $image->setArticle($product);
                $image->setPosition($record['position']);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());
                $image->setMedia($media);
                $image->setMain($record['main']);
                $image->setDescription($record['description']);
                $this->manager->persist($image);
                $this->manager->flush();

                if (!empty($relations)) {
                    $this->setImageMappings($relations, $image->getId());
                }

                $this->createAttribute($record, $image);

                // Prevent multiple images from being a preview
                if ((int) $record['main'] === 1) {
                    $this->db->update(
                        's_articles_img',
                        ['main' => 2],
                        [
                            'articleID = ?' => $product->getId(),
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

    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default'],
        ];
    }

    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @throws \RuntimeException
     */
    public function saveMessage(string $message): void
    {
        if ($this->importExportErrorMode === false) {
            throw new \RuntimeException($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    public function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    /**
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    public function getBuilder(array $columns, array $ids): QueryBuilder
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
     * @return array<string>
     */
    protected function getAttributesColumns(): array
    {
        $stmt = $this->db->query('SHOW COLUMNS FROM `s_articles_img_attributes`');
        $columns = $stmt->fetchAll();

        $attributes = [];
        foreach ($columns as $column) {
            if (\in_array($column['Field'], ['id', 'imageID'])) {
                continue;
            }
            $attributes[] = $column['Field'];
        }

        $attributesSelect = [];
        if ($attributes) {
            $prefix = 'attribute';
            foreach ($attributes as $attribute) {
                $attr = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);

                if (empty($attr)) {
                    continue;
                }

                $attributesSelect[] = \sprintf('%s.%s as attribute%s', $prefix, $attr, \ucwords($attr));
            }
        }

        return $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesImagesDbAdapter_GetArticleImagesAttributes',
            $attributesSelect,
            ['subject' => $this]
        );
    }

    /**
     * @param array<string, mixed> $image
     *
     * @return array<string, mixed>
     */
    protected function mapAttributes(array $image): array
    {
        $attributes = [];
        foreach ($image as $key => $value) {
            $position = \strpos($key, 'attribute');
            if ($position === false || $position !== 0) {
                continue;
            }

            $attrKey = \lcfirst(\str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    /**
     * Sets image mapping for variants
     *
     * @param array<int, array<int, array{option: Option}>> $relationGroups
     */
    protected function setImageMappings(array $relationGroups, int $imageId): void
    {
        /** @var Repository $articleRepository */
        $articleRepository = $this->manager->getRepository(Article::class);
        $query = $articleRepository->getProductImageDataQuery($imageId);
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
     * @param Option[]             $options
     * @param array<string, mixed> $imageData
     */
    protected function createImagesForOptions(array $options, array $imageData, Image $parent): void
    {
        if (!$parent->getArticle() instanceof Article) {
            throw new \Exception('Article must be set');
        }

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

    protected function load(string $url, ?string $baseFilename = null): string
    {
        if (!\is_dir($this->docPath)) {
            \mkdir($this->docPath, 0777, true);
        }

        $destPath = \realpath($this->docPath);

        if (!\file_exists($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_not_found', 'Destination directory %s does not exist.');
            throw new \Exception(\sprintf($message, $destPath));
        }

        if (!\is_writable($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_permissions', 'Destination directory %s does not have write permissions.');
            throw new \Exception(\sprintf($message, $destPath));
        }

        $urlScheme = \parse_url($url, \PHP_URL_SCHEME);

        if (!\in_array($urlScheme, self::ALLOWED_PROTOCOLS, true)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/unsupported_schema', 'Unsupported schema %s.');
            throw new AdapterException(\sprintf($message, $urlScheme ?? '"No URL scheme given"'));
        }

        $filename = $baseFilename ?? \md5(\uniqid(\mt_rand(), true));

        $put_handle = \fopen(sprintf('%s/%s', $destPath, $filename), 'wb+');
        if (!$put_handle) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/could_open_dir_file', 'Could not open %s/%s for writing');
            throw new AdapterException(\sprintf($message, $destPath, $filename));
        }

        // replace empty spaces
        $url = \str_replace(' ', '%20', $url);

        if ($urlScheme === self::PROTOCOL_FILE) {
            $get_handle = \fopen($url, 'rb');
            if (!$get_handle) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesImages/could_not_open_url', 'Could not open %s for reading');
                throw new AdapterException(\sprintf($message, $url));
            }
            while (!\feof($get_handle)) {
                $data = \fgets($get_handle, 4096);
                if (!\is_string($data)) {
                    continue;
                }
                \fwrite($put_handle, $data);
            }
            \fclose($get_handle);
        } else {
            try {
                $contents = $this->httpClient->get($url)->getBody()->getContents();
            } catch (\Throwable $exception) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesImages/could_not_open_url', 'Could not open %s for reading');
                throw new AdapterException(\sprintf($message, $url));
            }

            fwrite($put_handle, $contents);
        }

        fclose($put_handle);

        return sprintf('%s/%s', $destPath, $filename);
    }

    private function createAttribute(array $record, Image $image): void
    {
        $attributes = $this->mapAttributes($record);

        $attributesId = false;
        if ($image->getAttribute() instanceof ProductImageAttribute) {
            $attributesId = $image->getAttribute()->getId();
        }

        if (!empty($attributes)) {
            $attributes['articleImageId'] = $image->getId();
            $queryBuilder = $this->dbalHelper->getQueryBuilderForEntity(
                $attributes,
                ProductImageAttribute::class,
                $attributesId
            );

            $queryBuilder->execute();
        }
    }
}

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
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\ArticleImageValidator;
use Shopware\Components\SwagImportExport\DataManagers\ArticleImageDataManager;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Media;
use Symfony\Component\HttpFoundation\File\File;

class ArticlesImagesDbAdapter implements DataDbAdapter
{
    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $articleRepository;
    protected $articleDetailRepository;
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

    /** @var ArticleImageValidator */
    protected $validator;

    /** @var ArticleImageDataManager */
    protected $dataManager;

    /**
     * Returns record ids
     *
     * @param int $start
     * @param int $limit
     * @param $filter
     * @return array
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('image.id');

        $builder->from('Shopware\Models\Article\Image', 'image')
                ->where('image.articleDetailId IS NULL')
                ->andWhere('image.parentId IS NULL');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * Returns article images
     *
     * @param array $ids
     * @param array $columns
     * @return array
     * @throws
     */
    public function read($ids, $columns)
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');

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

        $result['default'] = $builder->getQuery()->getResult();

        foreach ($result['default'] as &$image) {
            $image['image'] = $mediaService->getUrl($image['image']);

            if (empty($image['relations'])) {
                continue;
            }

            $relations = explode(';', $image['relations']);
            $relations = array_unique($relations);

            $out = array();
            foreach ($relations as $rule) {
                $split = explode('|', $rule);
                $ruleId = $split[0];
                $optionId = $split[1];
                $name = $split[2];
                $groupName = $split[3];
                if ($groupName && $name) {
                    $out[$ruleId][] = "$groupName:$name";
                }
            }

            $temp = array();
            foreach ($out as $group) {
                $name = $group['name'];
                $temp [] = "{" . implode('|', $group) . "}";
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
        $request = Shopware()->Front()->Request();
        $path = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/media/image/';

        $columns = array(
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
            ' \'1\' as thumbnail'
        );

        return $columns;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * Insert/Update data into db
     *
     * @param array $records
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articlesImages/no_records',
                'No article image records were found.'
            );
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesImagesDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $validator = $this->getValidator();
        $dataManager = $this->getDataManager();
        $imageImportMode = Shopware()->Config()->get('SwagImportExportImageMode');
        $configuratorGroupRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Group');
        $configuratorOptionRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Option');

        foreach ($records['default'] as $record) {
            try {
                $record = $validator->filterEmptyString($record);
                $validator->checkRequiredFields($record);

                /** @var \Shopware\Models\Article\Detail $articleDetailModel */
                $articleDetailModel = $this->getArticleDetailRepository()->findOneBy(array('number' => $record['ordernumber']));
                if (!$articleDetailModel) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists');
                    throw new AdapterException(sprintf($message, $record['ordernumber']));
                }

                $record = $dataManager->setDefaultFields($record, $articleDetailModel->getArticle()->getId());
                $validator->validate($record, ArticleImageValidator::$mapper);

                $relations = array();
                if (isset($record['relations'])) {
                    $oldRelations = explode("&", $record['relations']);

                    foreach ($oldRelations as $key => $relation) {
                        if ($relation === "") {
                            continue;
                        }

                        $variantConfiguration = explode('|', preg_replace('/{|}/', '', $relation));
                        foreach ($variantConfiguration as $configuration) {
                            list($group, $option) = explode(":", $configuration);

                            //Get configurator group
                            $cGroupModel = $configuratorGroupRepository->findOneBy(array('name' => $group));
                            if ($cGroupModel === null) {
                                continue;
                            }

                            //Get configurator option
                            $cOptionModel = $configuratorOptionRepository->findOneBy(array('name' => $option, 'groupId' => $cGroupModel->getId()));
                            if ($cOptionModel === null) {
                                continue;
                            }

                            $relations[$key][] = array("group" => $cGroupModel, "option" => $cOptionModel);
                            unset($cGroupModel);
                            unset($cOptionModel);
                        }
                    }
                }

                /** @var \Shopware\Models\Article\Article $article */
                $article = $articleDetailModel->getArticle();

                $name = pathinfo($record['image'], PATHINFO_FILENAME);
                $mediaExists = false;

                if ($imageImportMode == 1) {
                    $mediaRepo = $this->getManager()->getRepository('Shopware\Models\Media\Media');
                    $media = $mediaRepo->findOneBy(array('name' => $name));
                    if ($media) {
                        $mediaExists = true;
                    }
                }

                //create new media
                if ($imageImportMode == 2 || $mediaExists == false) {
                    $path = $this->load($record['image'], $name);
                    $file = new File($path);

                    $media = new Media();
                    $media->setAlbumId(-1);
                    $media->setAlbum($this->getManager()->find('Shopware\Models\Media\Album', -1));

                    $media->setFile($file);
                    $media->setName(pathinfo($record['image'], PATHINFO_FILENAME));
                    $media->setDescription('');
                    $media->setCreated(new \DateTime());
                    $media->setUserId(0);

                    $this->getManager()->persist($media);
                    $this->getManager()->flush();

                    //thumbnail flag
                    //TODO: validate thumbnail
                    $thumbnail = (bool) $record['thumbnail'];

                    //generate thumbnails
                    if ($media->getType() == Media::TYPE_IMAGE && $thumbnail) {
                        /** @var \Shopware\Components\Thumbnail\Manager $manager */
                        $manager = Shopware()->Container()->get('thumbnail_manager');
                        $manager->createMediaThumbnail($media, array(), true);
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
                $this->getManager()->persist($image);
                $this->getManager()->flush($image);

                if ($relations && !empty($relations)) {
                    $this->setImageMappings($relations, $image->getId());
                }

                // Prevent multiple images from being a preview
                if ((int) $record['main'] === 1) {
                    $this->getDb()->update(
                        's_articles_img',
                        array('main' => 2),
                        array(
                            'articleID = ?' => $article->getId(),
                            'id <> ?' => $image->getId()
                        )
                    );
                }

                unset($media);
                unset($image);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * Sets image mapping for variants
     *
     * @param array $relationGroups
     * @param int $imageId
     */
    protected function setImageMappings($relationGroups, $imageId)
    {
        $query = $this->getArticleRepository()->getArticleImageDataQuery($imageId);
        $image = $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        $imageData = $query->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        foreach ($relationGroups as $relationGroup) {
            $optionCollection = array();

            foreach ($relationGroup as $relation) {
                $optionModel = $relation['option'];
                $optionCollection[] = $optionModel;
                $mapping = new Image\Mapping();

                $rule = new Image\Rule();
                $rule->setMapping($mapping);
                $rule->setOption($optionModel);

                $rules = $mapping->getRules()->add($rule);
                $mapping->setRules($rules);
                $mapping->setImage($image);

                $this->getManager()->persist($mapping);
            }

            $this->createImagesForOptions($optionCollection, $imageData, $image);

            unset($mapping);
        }
    }

    /**
     * @param $options
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
        $sql = "SELECT d.id
                FROM s_articles_details d
        " . $join . "
        WHERE d.articleID = " . (int) $articleId;

        $details = Shopware()->Db()->fetchCol($sql);

        foreach ($details as $detailId) {
            $detail = $this->getManager()->getReference('Shopware\Models\Article\Detail', $detailId);
            $image = new Image();
            $image->fromArray($imageData);
            $image->setArticleDetail($detail);
            $this->getManager()->persist($image);
        }

        $this->getManager()->flush();
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'default', 'name' => 'default')
        );
    }

    /**
     * @param string $section
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
     * Returns entity manager
     *
     * @return ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    /**
     * Helper function to get access to the articleDetail repository.
     *
     * @return \Shopware\Components\Model\ModelRepository
     */
    public function getArticleDetailRepository()
    {
        if ($this->articleDetailRepository === null) {
            $this->articleDetailRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->articleDetailRepository;
    }

    /**
     * Helper function to get access to the article repository.
     *
     * @return \Shopware\Models\Article\Repository
     */
    public function getArticleRepository()
    {
        if ($this->articleRepository === null) {
            $this->articleRepository = $this->getManager()->getRepository('Shopware\Models\Article\Article');
        }

        return $this->articleRepository;
    }

    /**
     * Helper function to get access to the database.
     */
    public function getDb()
    {
        if ($this->db === null) {
            $this->db = Shopware()->Db();
        }

        return $this->db;
    }

    /**
     * @param string $url URL of the resource that should be loaded (ftp, http, file)
     * @param string $baseFilename Optional: Instead of creating a hash, create a filename based on the given one
     * @return bool|string returns the absolute path of the downloaded file
     * @throws \Exception
     */
    protected function load($url, $baseFilename = null)
    {
        $destPath = Shopware()->DocPath('media_' . 'temp');
        if (!is_dir($destPath)) {
            mkdir($destPath, 0777, true);
        }

        $destPath = realpath($destPath);

        if (!file_exists($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_not_found', 'Destination directory %s does not exist.');
            throw new \Exception(sprintf($message, $destPath));
        } elseif (!is_writable($destPath)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesImages/directory_permissions', 'Destination directory %s does not have write permissions.');
            throw new \Exception(sprintf($message, $destPath));
        }

        $urlArray = parse_url($url);
        $urlArray['path'] = explode("/", $urlArray['path']);
        switch ($urlArray['scheme']) {
            case "ftp":
            case "http":
            case "https":
            case "file":
                if ($baseFilename === null) {
                    $filename = md5(uniqid(rand(), true));
                } else {
                    $filename = $baseFilename;
                }

                if (!$put_handle = fopen("$destPath/$filename", "w+")) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/could_open_dir_file', 'Could not open %s/%s for writing');
                    throw new AdapterException(sprintf($message), $destPath, $filename);
                }

                //replace empty spaces
                $url = str_replace(' ', '%20', $url);

                if (!$get_handle = fopen($url, "r")) {
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

    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    public function getLogMessages()
    {
        return $this->logMessages;
    }

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

    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Article\Image', 'aimage')
            ->innerJoin('aimage.article', 'article')
            ->leftJoin('Shopware\Models\Article\Detail', 'mv', Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
            ->leftJoin('aimage.mappings', 'im')
            ->leftJoin('im.rules', 'mr')
            ->leftJoin('mr.option', 'co')
            ->leftJoin('co.group', 'cg')
            ->where('aimage.id IN (:ids)')
            ->groupBy('aimage.id')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @return ArticleImageValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new ArticleImageValidator();
        }

        return $this->validator;
    }

    /**
     * @return ArticleImageDataManager
     */
    public function getDataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = new ArticleImageDataManager();
        }

        return $this->dataManager;
    }
}

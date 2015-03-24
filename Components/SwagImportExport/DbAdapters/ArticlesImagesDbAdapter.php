<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

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
     * Returns record ids
     * 
     * @param int $start
     * @param int $limit
     * @param type $filter
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

        $builder->setFirstResult($start)
                ->setMaxResults($limit);

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

        $result['default'] = $builder->getQuery()->getResult();

        foreach ($result['default'] as &$image) {
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
            SEPARATOR ';' ) as relations"
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
     */
    public function write($records)
    {
        $records = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_DbAdapters_ArticlesImagesDbAdapter_Write',
                $records,
                array('subject' => $this)
        );

        $imageImportMode = Shopware()->Config()->get('SwagImportExportImageMode');

        $configuratorGroupRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Group');
        $configuratorOptionRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Option');

        foreach ($records['default'] as $record) {
            try {
                if (empty($record['ordernumber']) || empty($record['image'])) {
                    $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articlesImages/ordernumber_image_required', 'Ordernumber and image are required');
                    throw new AdapterException($message);
                }

                /** @var \Shopware\Models\Article\Detail $articleDetailModel */
                $articleDetailModel = $this->getArticleDetailRepository()->findOneBy(array('number' => $record['ordernumber']));
                if (!$articleDetailModel) {
                    $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists');
                    throw new AdapterException(sprintf($message, $record['ordernumber']));
                }

                if (isset($record['relations']) && !empty($record['relations'])) {
                    $relations = array();
                    $results = explode("&", $record['relations']);

                    $i = 0;

                    foreach ($results as $result) {
                        if ($result !== "") {
                            $result = preg_replace('/{|}/', '', $result);

                            foreach (explode('|', $result) as $value) {
                                list($group, $option) = explode(":", $value);

                                // Try to get given configurator group/option. Continue, if they don't exist
                                $cGroupModel = $configuratorGroupRepository->findOneBy(array('name' => $group));
                                if ($cGroupModel === null) {
                                    continue;
                                }
                                $cOptionModel = $configuratorOptionRepository->findOneBy(
                                        array('name' => $option,
                                            'groupId' => $cGroupModel->getId()
                                        )
                                );
                                if ($cOptionModel === null) {
                                    continue;
                                }
                                $relations[$i][] = array("group" => $cGroupModel, "option" => $cOptionModel);
                                unset($cGroupModel);
                                unset($cOptionModel);
                            }
                            $i++;
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
                       $path = $media->getPath();
                       $mediaExists = true;
                    }
                }

	            if($imageImportMode == 2 || $mediaExists == false) {
                    $path = $this->load($record['image'], $name);

                    $file = new \Symfony\Component\HttpFoundation\File\File($path);

                    $media = new \Shopware\Models\Media\Media();
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
                    $thumbnail = $record['thumbnail'] == 0 ? false : true;

                    if (empty($record['main'])) {
                        $record['main'] = 1;
                    }

                    //generate thumbnails
                    if ($media->getType() == \Shopware\Models\Media\Media::TYPE_IMAGE && $thumbnail) {
                        /*                 * @var $manager \Shopware\Components\Thumbnail\Manager */
                        $manager = Shopware()->Container()->get('thumbnail_manager');
                        $manager->createMediaThumbnail($media, array(), true);
                    }
                }

                $image = new \Shopware\Models\Article\Image();
                $image->setArticle($article);

	            $description = isset($record["description"]) ? $record["description"] : "";
	            $imagePosition = isset($record['position']) ? $record['position'] : $this->getDefaultImagePosition($image->getArticle()->getId());

	            $image->setPosition($imagePosition);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());
                $image->setMedia($media);
                $image->setMain($record['main']);
	            $image->setDescription($description);
                $this->getManager()->persist($image);
                $this->getManager()->flush($image);

                if ($relations && !empty($relations)) {
                    $this->setImageMappings($relations, $image->getId());
                }

                // Prevent multiple images from being a preview
                if ((int) $record['main'] === 1) {
                    $this->getDb()->update('s_articles_img', array('main' => 2), array(
                        'articleID = ?' => $article->getId(),
                        'id <> ?' => $image->getId()
                            )
                    );
                }
                $this->getManager()->clear();
                unset($media);
                unset($image);

            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

	/**
	 * Gets the latest image position from a specific article.
	 * @param $articleId
	 * @return int
	 */
	private function getDefaultImagePosition($articleId){
		$sql = "SELECT MAX(position) FROM s_articles_img WHERE articleID=?;";
		$result = Shopware()->Db()->fetchOne($sql, $articleId);

		return isset($result) ? ((int)$result +1) : 0;
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
        $image = $query->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
        $imageData = $query->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        foreach ($relationGroups as $relationGroup) {
            $optionCollection = array();

            foreach ($relationGroup as $relation) {
                $optionModel = $relation['option'];
                $optionCollection[] = $optionModel;

                if (!$mapping) {
                    $mapping = new \Shopware\Models\Article\Image\Mapping();
                }

                $rule = new \Shopware\Models\Article\Image\Rule();
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
     * @param $parent \Shopware\Models\Article\Image
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
            $image = new \Shopware\Models\Article\Image();
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
     * @return mix
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
     * @return Shopware\Components\Model\ModelManager
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
     * @return Shopware\Models\Article\Repository
     */
    public function getArticleRepository()
    {
        if ($this->articleRepository === null) {
            $this->articleRepository = $this->getManager()->getRepository('Shopware\Models\Article\Article');
        }
        return $this->articleRepository;
    }

    /**
     * Helper function to get access to the datebase.
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
    }

    public function getLogMessages()
    {
        return $this->logMessages;
    }

    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Article\Image', 'aimage')
                ->innerJoin('aimage.article', 'article')
                ->leftJoin('Shopware\Models\Article\Detail', 'mv', \Doctrine\ORM\Query\Expr\Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
                ->leftJoin('aimage.mappings', 'im')
                ->leftJoin('im.rules', 'mr')
                ->leftJoin('mr.option', 'co')
                ->leftJoin('co.group', 'cg')
                ->where('aimage.id IN (:ids)')
                ->groupBy('aimage.id')
                ->setParameter('ids', $ids);

        return $builder;
    }

}

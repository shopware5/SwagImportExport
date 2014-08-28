<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesImagesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $articleDetailRepository;

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
            throw new \Exception('Can not read article images without ids.');
        }

        if (!$columns && empty($columns)) {
            throw new \Exception('Can not read article images without column names.');
        }

        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Article\Image', 'aimage')
                ->join('aimage.article', 'article')
                ->join('article.details', 'articleDetail')
                ->where('aimage.id IN (:ids)')
                ->andWhere('articleDetail.kind = 1')
//                ->groupBy('aimage.id')
                ->setParameter('ids', $ids);

        $result['default'] = $builder->getQuery()->getResult();

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
            'articleDetail.number as ordernumber',
            "CONCAT('$path', aimage.path, '.', aimage.extension) as image",
            'aimage.main as main',
            'aimage.description as description',
            'aimage.position as position',
            'aimage.width as width',
            'aimage.height as height',
        );

        return $columns;
    }

    /**
     * Insert/Update data into db
     * 
     * @param array $records
     */
    public function write($records)
    {
        $db = Shopware()->Db();

        foreach ($records['default'] as $record) {
            if (empty($record['ordernumber']) || empty($record['image'])) {
                throw new \Exception('Ordernumber and image are required');
            }

            /** @var \Shopware\Models\Article\Detail $articleDetailModel */
            $articleDetailModel = $this->getArticleDetailRepository()->findOneBy(array('number' => $record['ordernumber']));
            if (!$articleDetailModel) {
                throw new \Exception(sprintf('Article with number %s does not exists', $record['ordernumber']));
            }

            /** @var \Shopware\Models\Article\Article $article */
            $article = $articleDetailModel->getArticle();

            $name = pathinfo($record['image'], PATHINFO_FILENAME);
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

            if (empty($record['main'])) {
                $record['main'] = 1;
            }

            //generate thumbnails
            if ($media->getType() == \Shopware\Models\Media\Media::TYPE_IMAGE) {
                /*                 * @var $manager \Shopware\Components\Thumbnail\Manager */
                $manager = Shopware()->Container()->get('thumbnail_manager');
                $manager->createMediaThumbnail($media, array(), true);
            }

            $image = new \Shopware\Models\Article\Image();
            $image->setArticle($article);
            $image->setDescription($record['description']);
            $image->setPosition($record['position']);
            $image->setPath($media->getName());
            $image->setExtension($media->getExtension());
            $image->setMedia($media);
            $image->setMain($record['main']);

            $this->getManager()->persist($image);
            $this->getManager()->flush($image);

            // Prevent multiple images from being a preview
            if ((int) $record['main'] === 1) {
                $db->update('s_articles_img', 
                        array('main' => 2), 
                        array(
                            'articleID = ?' => $article->getId(),
                            'id <> ?' => $image->getId()
                        )
                );
            }
        }
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
     * @param string $url URL of the resource that should be loaded (ftp, http, file)
     * @param string $baseFilename Optional: Instead of creating a hash, create a filename based on the given one
     * @return bool|string returns the absolute path of the downloaded file
     * @throws \InvalidArgumentException
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
            throw new \InvalidArgumentException(
            sprintf("Destination directory '%s' does not exist.", $destPath)
            );
        } elseif (!is_writable($destPath)) {
            throw new \InvalidArgumentException(
            sprintf("Destination directory '%s' does not have write permissions.", $destPath)
            );
        }

        $urlArray = parse_url($url);
        $urlArray['path'] = explode("/", $urlArray['path']);
        switch ($urlArray['scheme']) {
            case "ftp":
            case "http":
            case "https":
            case "file":
                $counter = 1;
                if ($baseFilename === null) {
                    $filename = md5(uniqid(rand(), true));
                } else {
                    $filename = $baseFilename;
                }

                while (file_exists("$destPath/$filename")) {
                    if ($baseFilename) {
                        $filename = "$counter-$baseFilename";
                        $counter++;
                    } else {
                        $filename = md5(uniqid(rand(), true));
                    }
                }

                if (!$put_handle = fopen("$destPath/$filename", "w+")) {
                    throw new \Exception("Could not open $destPath/$filename for writing");
                }

                if (!$get_handle = fopen($url, "r")) {
                    throw new \Exception("Could not open $url for reading");
                }
                while (!feof($get_handle)) {
                    fwrite($put_handle, fgets($get_handle, 4096));
                }
                fclose($get_handle);
                fclose($put_handle);

                return "$destPath/$filename";
        }
        throw new \InvalidArgumentException(
        sprintf("Unsupported schema '%s'.", $urlArray['scheme'])
        );
    }

}

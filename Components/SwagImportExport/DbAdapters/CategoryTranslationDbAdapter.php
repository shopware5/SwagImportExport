<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\CategoryTranslationValidator;
use Shopware\Models\Translation\Translation;

class CategoryTranslationDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var \Shopware_Components_Translation
     */
    protected $translationComponent;

    /**
     * @var bool
     */
    protected $importExportErrorMode;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * @var string
     */
    protected $logState;

    /**
     * @var CategoryTranslationValidator
     */
    protected $validator;

    /**
     * @var \Enlight_Event_EventManager
     */
    protected $eventManager;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        $container = Shopware()->Container();

        $this->logMessages = [];
        $this->translationComponent = $container->get('translation');
        $this->validator = new CategoryTranslationValidator();
        $this->manager = $container->get('models');
        $this->connection = $container->get('dbal_connection');
        $this->eventManager = $container->get('events');
        $this->importExportErrorMode = (bool) $container->get('config')->get('SwagImportExportErrorMode');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultColumns()
    {
        return [
            'c.categoryId as categoryId',
            't.languageId as languageId',
            't.description as description',
            't.external as external',
            't.externalTarget as externalTarget',
            't.imagePath as imagePath',
            't.cmsheadline as cmsheadline',
            't.cmstext as cmstext',
            't.metatitle as metatitle',
            't.metadescription as metadescription',
            't.metakeywords as metakeywords',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_ids', 'Can not read translations without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_column_names', 'Can not read translations without column names.');
            throw new \Exception($message);
        }

        $translationRows = $this->getTranslations($ids);

        if (!$translationRows) {
            return;
        }

        foreach ($translationRows as &$translationRow) {
            $translation = unserialize($translationRow['objectdata']);

            if (!$translation) {
                continue;
            }

            $translationRow = array_merge($translationRow, $translation);
        }

        return ['default' => $translationRows];
    }

    /**
     * {@inheritdoc}
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('t.id')
            ->from(Translation::class, 't')
            ->where('t.type = :type')
            ->setParameter('type', 'category');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getArrayResult();

        return array_column($records, 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function getSections()
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function write($records)
    {
        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoryTranslationsDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        try {
            foreach ($records['default'] as $record) {
                $this->validator->checkRequiredFields($record);

                $objectKey = (int) $record['categoryId'];
                $objectLanguage = (int) $record['languageId'];
                unset($record['categoryId']);
                unset($record['languageId']);

                $this->checkIfShopExist($objectLanguage, $objectKey);
                $this->checkIfCategoryExist($objectKey);

                $this->translationComponent->write(
                    $objectLanguage,
                    'category',
                    $objectKey,
                    $record
                );
            }
        } catch (\Exception $exception) {
            if ($this->importExportErrorMode === false) {
                throw new \Exception($exception->getMessage());
            }

            $this->logMessages[] = $exception->getMessage();
            $this->logState = 'true';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUnprocessedData()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @param $ids
     *
     * @return array
     */
    private function getTranslations($ids)
    {
        return $this->connection->createQueryBuilder()
            ->select([
                'category.id AS categoryId',
                'translation.id',
                'translation.objecttype',
                'translation.objectdata',
                'translation.objectkey',
                'translation.objectlanguage AS languageId',
                'translation.dirty',
            ])
            ->from('s_core_translations', 'translation')
            ->leftJoin('translation', 's_categories', 'category', 'translation.objectkey = category.id')
            ->where('translation.id IN (:translationIds)')
            ->andWhere('translation.objecttype = "category"')
            ->orderBy('translation.objectlanguage', 'ASC')
            ->setParameter('translationIds', $ids, Connection::PARAM_INT_ARRAY)
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $objectLanguage
     * @param int $objectKey
     *
     * @throws AdapterException
     */
    private function checkIfShopExist($objectLanguage, $objectKey)
    {
        $shopExists = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_core_shops')
            ->where('id = :id')
            ->setParameter('id', $objectLanguage)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);

        if (!$shopExists) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/lang_id_not_found', 'Language with id %s does not exists');

            throw new AdapterException(sprintf($message, $objectLanguage));
        }
    }

    /**
     * @param int $objectKey
     *
     * @throws AdapterException
     */
    private function checkIfCategoryExist($objectKey)
    {
        $categoryExists = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_categories')
            ->where('id = :id')
            ->setParameter('id', $objectKey)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);

        if (!$categoryExists) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/category_id_not_found', 'Category with Id: %s does not exists');

            throw new AdapterException(sprintf($message, $objectKey));
        }
    }
}

<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\TranslationValidator;
use Shopware\Models\Article\Configurator\Group as ConfiguratorGroup;
use Shopware\Models\Article\Configurator\Option as ConfiguratorOption;
use Shopware\Models\Property\Group as PropertyGroup;
use Shopware\Models\Property\Option as PropertyOption;
use Shopware\Models\Property\Value as PropertyValue;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Translation\Translation;

class TranslationsDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager|null
     */
    protected $manager;

    /**
     * @var ModelRepository<ConfiguratorGroup>|null
     */
    protected $configuratorGroupRepo;

    /**
     * @var ModelRepository<ConfiguratorOption>|null
     */
    protected $configuratorOptionRepo;

    /**
     * @var ModelRepository<PropertyGroup>|null
     */
    protected $propertyGroupRepo;

    /**
     * @var ModelRepository<PropertyOption>|null
     */
    protected $propertyOptionRepo;

    /**
     * @var ModelRepository<PropertyValue>|null
     */
    protected $propertyValueRepo;

    /**
     * @var array<string>
     */
    protected $logMessages = [];

    /**
     * @var string
     */
    protected $logState;

    /**
     * @var TranslationValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('t.id');
        $builder->from(Translation::class, 't')
            ->where("t.type = 'propertyvalue'")
            ->orWhere("t.type = 'propertyvalue'")
            ->orWhere("t.type = 'propertyoption'")
            ->orWhere("t.type = 'propertygroup'")
            ->orWhere("t.type = 'configuratoroption'")
            ->orWhere("t.type = 'configuratorgroup'");

        if ($start) {
            $builder->setFirstResult($start);
        }
        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = \array_map(
            function ($item) {
                return $item['id'];
            },
            $records
        );

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_ids', 'Can not read translations without ids.');
            throw new \Exception($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_column_names', 'Can not read translations without column names.');
            throw new \Exception($message);
        }

        $translations = $this->getTranslations($ids);

        $result['default'] = $this->prepareTranslations($translations);

        return $result;
    }

    /**
     * @return array
     */
    public function prepareTranslations($translations)
    {
        $mapper = $this->getElementMapper();

        $result = [];
        foreach ($translations as $index => $translation) {
            $data = \unserialize($translation['objectdata']);

            // key for different translation types
            $key = $mapper[$translation['objecttype']];

            $result[] = [
                'objectKey' => $translation['objectkey'],
                'objectType' => $translation['objecttype'],
                'baseName' => $translation['baseName'],
                'name' => $data[$key],
                'description' => $data['description'],
                'languageId' => $translation['languageId'],
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $translation = [
            'objectKey',
            'objectType',
            'baseName',
            'name',
            'description',
            'languageId',
        ];

        return $translation;
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * @param string $section
     *
     * @return bool|mixed
     */
    public function getColumns($section)
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     *
     * @return void
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_records', 'No translation records were found.');
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_TranslationsDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $validator = $this->getValidator();
        $importMapper = $this->getElementMapper();
        $translationWriter = Shopware()->Container()->get('translation');

        foreach ($records['default'] as $index => $record) {
            try {
                $record = $validator->filterEmptyString($record);
                $validator->checkRequiredFields($record);
                $validator->validate($record, TranslationValidator::$mapper);

                $shop = null;
                if (isset($record['languageId'])) {
                    $shop = $this->getManager()->find(Shop::class, $record['languageId']);
                }

                if (!$shop instanceof Shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/lang_id_not_found', 'Language with id %s does not exists');
                    throw new AdapterException(\sprintf($message, $record['languageId']));
                }

                $repository = $this->getRepository($record['objectType']);

                $element = null;
                if (isset($record['objectKey'])) {
                    $element = $repository->findOneBy(['id' => (int) $record['objectKey']]);

                    if (!$element) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/translations/element_id_not_found', '%s element not found with ID %s');
                        throw new AdapterException(\sprintf($message, $record['objectType'], $record['objectKey']));
                    }
                } elseif (isset($record['baseName'])) {
                    $findKey = $record['objectType'] === 'propertyvalue' ? 'value' : 'name';
                    $element = $repository->findOneBy([$findKey => $record['baseName']]);

                    if (!$element) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/translations/element_baseName_not_found', '%s element not found with name %s');
                        throw new AdapterException(\sprintf($message, $record['objectType'], $record['baseName']));
                    }
                }

                if (!$element) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/element_objectKey_baseName_not_found', 'Please provide objectKey or baseName');
                    throw new AdapterException(\sprintf($message));
                }

                $key = $importMapper[$record['objectType']];
                $data[$key] = $record['name'];

                if ($record['objectType'] === 'configuratorgroup') {
                    $data['description'] = $record['description'];
                }

                $translationWriter->write($shop->getId(), $record['objectType'], $element->getId(), $data);

                unset($shop);
                unset($element);
                unset($data);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    public function getUnprocessedData()
    {
        return [];
    }

    /**
     * @param string $message
     *
     * @throws \Exception
     *
     * @return void
     */
    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
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
     * @param string $logMessages
     *
     * @return void
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
     * @param string $logState
     *
     * @return void
     */
    public function setLogState($logState)
    {
        $this->logState = $logState;
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
     * @return QueryBuilder
     */
    public function getBuilder($ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select('translation')
            ->from(Translation::class, 'translation')
            ->where('translation.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * Returns configurator group repository
     *
     * @return ModelRepository<ConfiguratorGroup>
     */
    public function getConfiguratorGroupRepository()
    {
        if ($this->configuratorGroupRepo === null) {
            $this->configuratorGroupRepo = $this->getManager()->getRepository(ConfiguratorGroup::class);
        }

        return $this->configuratorGroupRepo;
    }

    /**
     * Returns configurator option repository
     *
     * @return ModelRepository<ConfiguratorOption>
     */
    public function getConfiguratorOptionRepository()
    {
        if ($this->configuratorOptionRepo === null) {
            $this->configuratorOptionRepo = $this->getManager()->getRepository(ConfiguratorOption::class);
        }

        return $this->configuratorOptionRepo;
    }

    /**
     * Returns property group repository
     *
     * @return ModelRepository<PropertyGroup>
     */
    public function getPropertyGroupRepository()
    {
        if ($this->propertyGroupRepo === null) {
            $this->propertyGroupRepo = $this->getManager()->getRepository(PropertyGroup::class);
        }

        return $this->propertyGroupRepo;
    }

    /**
     * Returns property option repository
     *
     * @return ModelRepository<PropertyOption>
     */
    public function getPropertyOptionRepository()
    {
        if ($this->propertyOptionRepo === null) {
            $this->propertyOptionRepo = $this->getManager()->getRepository(PropertyOption::class);
        }

        return $this->propertyOptionRepo;
    }

    /**
     * Returns property value repository
     *
     * @return ModelRepository<PropertyValue>
     */
    public function getPropertyValueRepository()
    {
        if ($this->propertyValueRepo === null) {
            $this->propertyValueRepo = $this->getManager()->getRepository(PropertyValue::class);
        }

        return $this->propertyValueRepo;
    }

    /**
     * @return TranslationValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new TranslationValidator();
        }

        return $this->validator;
    }

    /**
     * @return array
     */
    protected function getElementMapper()
    {
        return [
            'configuratorgroup' => 'name',
            'configuratoroption' => 'name',
            'propertygroup' => 'groupName',
            'propertyoption' => 'optionName',
            'propertyvalue' => 'optionValue',
        ];
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array
     */
    protected function getTranslations($ids)
    {
        $articleDetailIds = \implode(',', $ids);

        $translationColumns = 'ct.objecttype, ct.objectkey, ct.objectdata as objectdata, ct.objectlanguage as languageId';

        $sql = "(SELECT cgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_groups AS cgroup
                ON cgroup.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'configuratorgroup')

                UNION

                (SELECT coptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_options AS coptions
                ON coptions.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'configuratoroption')

                UNION

                (SELECT pgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter AS pgroup
                ON pgroup.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertygroup')

                UNION

                (SELECT poptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_options AS poptions
                ON poptions.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertyoption')

                UNION

                (SELECT pvalues.value as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_values AS pvalues
                ON pvalues.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertyvalue')
                ";

        return Shopware()->Db()->query($sql)->fetchAll();
    }

    /**
     * @param string $type
     *
     * @throws AdapterException
     *
     * @return ModelRepository
     */
    protected function getRepository($type)
    {
        switch ($type) {
            case 'configuratorgroup':
                return $this->getConfiguratorGroupRepository();
            case 'configuratoroption':
                return $this->getConfiguratorOptionRepository();
            case 'propertygroup':
                return $this->getPropertyGroupRepository();
            case 'propertyoption':
                return $this->getPropertyOptionRepository();
            case 'propertyvalue':
                return $this->getPropertyValueRepository();
            default:
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/translations/object_type_not_existing', 'Object type %s not existing.');
                throw new AdapterException(\sprintf($message, $type));
        }
    }
}

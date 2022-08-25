<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Article\Configurator\Group as ConfiguratorGroup;
use Shopware\Models\Article\Configurator\Option as ConfiguratorOption;
use Shopware\Models\Property\Group as PropertyGroup;
use Shopware\Models\Property\Option as PropertyOption;
use Shopware\Models\Property\Value as PropertyValue;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Translation\Translation;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\TranslationValidator;

class TranslationsDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected ModelManager $manager;

    /**
     * @var ModelRepository<ConfiguratorGroup>
     */
    protected ModelRepository $configuratorGroupRepo;

    /**
     * @var ModelRepository<ConfiguratorOption>
     */
    protected ModelRepository $configuratorOptionRepo;

    /**
     * @var ModelRepository<PropertyGroup>
     */
    protected ModelRepository $propertyGroupRepo;

    /**
     * @var ModelRepository<PropertyOption>
     */
    protected ModelRepository $propertyOptionRepo;

    /**
     * @var ModelRepository<PropertyValue>
     */
    protected ModelRepository $propertyValueRepo;

    /**
     * @var array<string>
     */
    protected array $logMessages = [];

    protected ?string $logState = null;

    protected TranslationValidator $validator;

    private \Enlight_Event_EventManager $eventManager;

    private \Shopware_Components_Translation $translation;

    private \Shopware_Components_Config $config;

    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    public function __construct(
        ModelManager $manager,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Translation $translation,
        \Shopware_Components_Config $config,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db
    ) {
        $this->manager = $manager;
        $this->eventManager = $eventManager;
        $this->translation = $translation;
        $this->config = $config;
        $this->db = $db;
        $this->propertyGroupRepo = $this->manager->getRepository(PropertyGroup::class);
        $this->propertyOptionRepo = $this->manager->getRepository(PropertyOption::class);
        $this->configuratorGroupRepo = $this->manager->getRepository(ConfiguratorGroup::class);
        $this->configuratorOptionRepo = $this->manager->getRepository(ConfiguratorOption::class);
        $this->propertyValueRepo = $this->manager->getRepository(PropertyValue::class);
        $this->validator = new TranslationValidator();
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::TRANSLATION_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $builder = $this->manager->createQueryBuilder();

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

        return \array_column($records, 'id');
    }

    /**
     * @throws \Exception
     */
    public function read(array $ids, array $columns): array
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
     * @param array<array<string, mixed>> $translations
     *
     * @return array<array<string, string>>
     */
    public function prepareTranslations(array $translations): array
    {
        $mapper = $this->getElementMapper();

        $result = [];
        foreach ($translations as $translation) {
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

    public function getDefaultColumns(): array
    {
        return [
            'objectKey',
            'objectType',
            'baseName',
            'name',
            'description',
            'languageId',
        ];
    }

    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * @return array<string>
     */
    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @param array<string, mixed> $records
     */
    public function write(array $records): void
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_records', 'No translation records were found.');
            throw new \RuntimeException($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_TranslationsDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $importMapper = $this->getElementMapper();

        foreach ($records['default'] as $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, TranslationValidator::$mapper);

                $shop = null;
                if (isset($record['languageId'])) {
                    $shop = $this->manager->find(Shop::class, $record['languageId']);
                }

                if (!$shop instanceof Shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/lang_id_not_found', 'Language with id %s does not exist');
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
                    throw new AdapterException($message);
                }

                $key = $importMapper[$record['objectType']];
                $data[$key] = $record['name'];

                if ($record['objectType'] === 'configuratorgroup') {
                    $data['description'] = $record['description'];
                }

                if (!method_exists($element, 'getId')) {
                    throw new \RuntimeException(sprintf('%s has no getter for ID', \get_class($element)));
                }

                $this->translation->write($shop->getId(), $record['objectType'], $element->getId(), $data);

                unset($shop, $element, $data);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
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
     * @param array<int> $ids
     */
    public function getBuilder(array $ids): QueryBuilder
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select('translation')
            ->from(Translation::class, 'translation')
            ->where('translation.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @return array<string>
     */
    protected function getElementMapper(): array
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
     * @param array<int> $ids
     *
     * @return array<array<string, mixed>>
     */
    protected function getTranslations(array $ids): array
    {
        $productDetailIds = \implode(',', $ids);

        $translationColumns = 'ct.objecttype, ct.objectkey, ct.objectdata as objectdata, ct.objectlanguage as languageId';

        $sql = "(SELECT cgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_groups AS cgroup
                ON cgroup.id = ct.objectkey

                WHERE ct.id IN ($productDetailIds) AND ct.objecttype = 'configuratorgroup')

                UNION

                (SELECT coptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_options AS coptions
                ON coptions.id = ct.objectkey

                WHERE ct.id IN ($productDetailIds) AND ct.objecttype = 'configuratoroption')

                UNION

                (SELECT pgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter AS pgroup
                ON pgroup.id = ct.objectkey

                WHERE ct.id IN ($productDetailIds) AND ct.objecttype = 'propertygroup')

                UNION

                (SELECT poptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_options AS poptions
                ON poptions.id = ct.objectkey

                WHERE ct.id IN ($productDetailIds) AND ct.objecttype = 'propertyoption')

                UNION

                (SELECT pvalues.value as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_values AS pvalues
                ON pvalues.id = ct.objectkey

                WHERE ct.id IN ($productDetailIds) AND ct.objecttype = 'propertyvalue')
                ";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @throws AdapterException
     */
    protected function getRepository(string $type): ModelRepository
    {
        switch ($type) {
            case 'configuratorgroup':
                return $this->configuratorGroupRepo;
            case 'configuratoroption':
                return $this->configuratorOptionRepo;
            case 'propertygroup':
                return $this->propertyGroupRepo;
            case 'propertyoption':
                return $this->propertyOptionRepo;
            case 'propertyvalue':
                return $this->propertyValueRepo;
            default:
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/translations/object_type_not_existing', 'Object type %s not existing.');
                throw new AdapterException(\sprintf($message, $type));
        }
    }
}

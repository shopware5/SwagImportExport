<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Category\Category;
use Shopware\Models\Customer\Group;
use SwagImportExport\Components\DataManagers\CategoriesDataManager;
use SwagImportExport\Components\DataType\CategoryDataType;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseService;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\DbAdapterHelper;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\CategoryValidator;

class CategoriesDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     * @var EntityRepository
     */
    protected $repository;

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
     * @var CategoryValidator
     */
    protected $validator;

    /**
     * @var CategoriesDataManager
     */
    protected $dataManager;

    /**
     * @var array
     */
    protected $defaultValues;

    private $categoryAvoidCustomerGroups;

    /**
     * @var UnderscoreToCamelCaseServiceInterface
     */
    private $underscoreToCamelCaseService;

    private \Enlight_Event_EventManager $eventManager;

    private \Shopware_Components_Config $config;

    public function __construct(
        ModelManager $modelManager,
        CategoriesDataManager $dataManager,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        UnderscoreToCamelCaseService $underscoreToCamelCase,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->modelManager = $modelManager;
        $this->repository = $this->modelManager->getRepository(Category::class);
        $this->dataManager = $dataManager;
        $this->validator = new CategoryValidator();
        $this->db = $db;
        $this->underscoreToCamelCaseService = $underscoreToCamelCase;
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $builder = $this->modelManager->createQueryBuilder();

        $builder->select('c.id');

        $builder->from(Category::class, 'c')
            ->where('c.id != 1')
            ->orderBy('c.parentId', 'ASC');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = [];
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * Returns categories
     *
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/categories/no_ids', 'Can not read categories without ids.');
            throw new \Exception($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/categories/no_column_names', 'Can not read categories without column names.');
            throw new \Exception($message);
        }

        $builder = $this->getBuilder($columns['default'], $ids);

        $categories = $builder->getQuery()->getArrayResult();

        $result = [];
        foreach ($categories as $category) {
            $key = (int) $category['categoryId'] . $category['parentId'];
            $result[$key] = $category;
        }
        \ksort($result);

        $result['default'] = DbAdapterHelper::decodeHtmlEntities(\array_values($result));
        $result['customerGroups'] = $this->getBuilder($this->getCustomerGroupsColumns(), $ids)->getQuery()->getResult();

        return $result;
    }

    /**
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select($columns)
            ->from(Category::class, 'c')
            ->leftJoin('c.attribute', 'attr')
            ->leftJoin('c.customerGroups', 'customerGroups')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->distinct();

        return $builder;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array|string
     */
    public function getAttributes()
    {
        $stmt = $this->db->query('SHOW COLUMNS FROM s_categories_attributes');
        $columns = $stmt->fetchAll();
        $attributes = $this->getFieldNames($columns);

        $attributesSelect = '';
        if ($attributes) {
            $prefix = 'attr';
            $attributesSelect = [];
            foreach ($attributes as $attribute) {
                $catAttr = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);

                $attributesSelect[] = \sprintf('%s.%s as attribute%s', $prefix, $catAttr, \ucwords($catAttr));
            }
        }

        return $attributesSelect;
    }

    /**
     * Insert/Update data into db
     *
     * @param array $records
     */
    public function write($records)
    {
        $this->unprocessedData = [];

        $records = $records['default'];
        $this->validateRecordsShouldNotBeEmpty($records);

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoriesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        foreach ($records as $index => $record) {
            try {
                $record = $this->validator->filterEmptyString($record);

                $category = $this->findCategoryById($record['categoryId']);
                if (!$category instanceof Category) {
                    $record = $this->dataManager->setDefaultFieldsForCreate($record, $this->defaultValues);
                    $category = $this->createCategoryAndSetId($record['categoryId']);
                }

                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, CategoryDataType::$mapper);

                $record['parent'] = $this->repository->find($record['parentId']);
                $this->validateParentCategory($record);

                $record = $this->prepareData($record, $index, $category->getId(), $records['customerGroups']);
                $category->fromArray($record);

                $this->validateCategoryModel($category);

                $metaData = $this->modelManager->getClassMetadata(Category::class);
                $metaData->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

                $this->modelManager->persist($category);
                $this->modelManager->flush($category);
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
            ['id' => 'customerGroups', 'name' => 'CustomerGroups'],
        ];
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function getParentKeys($section)
    {
        switch ($section) {
            case 'customerGroups':
                return [
                    'c.id as categoryId',
                ];
        }

        throw new \RuntimeException(sprintf('No case found for section "%s"', $section));
    }

    /**
     * @param string $section
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
     * @return array
     */
    public function getCustomerGroupsColumns()
    {
        return [
            'c.id as categoryId',
            'customerGroups.id as customerGroupId',
        ];
    }

    /**
     * Returns default categories columns name
     * and category attributes
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns['default'] = [
            'c.id as categoryId',
            'c.parentId as parentId',
            'c.name as name',
            'c.position as position',
            'c.metaTitle as metaTitle',
            'c.metaKeywords as metaKeywords',
            'c.metaDescription as metaDescription',
            'c.cmsHeadline as cmsHeadline',
            'c.cmsText as cmsText',
            'c.template as template',
            'c.active as active',
            'c.blog as blog',
            'c.external as external',
            'c.hideFilter as hideFilter',
        ];

        // Attributes
        $attributesSelect = $this->getAttributes();

        if (!empty($attributesSelect)) {
            $columns['default'] = \array_merge($columns['default'], $attributesSelect);
        }

        return $columns;
    }

    /**
     * Set default values for fields which are empty or don't exists
     *
     * @param array $values default values for nodes
     */
    public function setDefaultValues($values)
    {
        $this->defaultValues = $values;
    }

    /**
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

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

    public function setLogState($logState)
    {
        $this->logState = $logState;
    }

    /**
     * @return array
     */
    protected function prepareData(array $data, $index, $categoryId, $groups)
    {
        //prepares attribute associated data
        foreach ($data as $column => $value) {
            if (strpos($column, 'attribute') === 0) {
                $newKey = \lcfirst(\preg_replace('/^attribute/', '', $column));
                $data['attribute'][$newKey] = $value;
                unset($data[$column]);
            }
        }

        //prepares customer groups associated data
        $customerGroups = [];
        $customerGroupIds = $this->getCustomerGroupIdsFromIndex($groups, $index);
        foreach ($customerGroupIds as $customerGroupID) {
            $customerGroup = $this->getCustomerGroupById($customerGroupID);
            if ($customerGroup && !$this->checkIfRelationExists($categoryId, $customerGroup->getId())) {
                $customerGroups[] = $customerGroup;
            }
        }
        $data['customerGroups'] = $customerGroups;

        unset($data['parentId']);

        return $data;
    }

    /**
     * Helper method: Filtered the field names and return them
     *
     * @return array
     */
    private function getFieldNames($columns)
    {
        $attributes = [];
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] !== 'categoryID') {
                $attributes[] = $column['Field'];
            }
        }

        return $attributes;
    }

    /**
     * @param array $array
     * @param int   $currentIndex
     *
     * @return array
     */
    private function getCustomerGroupIdsFromIndex($array, $currentIndex)
    {
        $returnArray = [];
        foreach ($array as $customerGroupEntry) {
            if ($customerGroupEntry['parentIndexElement'] == $currentIndex) {
                $returnArray[] = $customerGroupEntry['customerGroupId'];
            }
        }

        return $returnArray;
    }

    /**
     * Create the Category by hand. The method ->fromArray do not work
     *
     * @param int $id
     *
     * @return Category|null
     */
    private function findCategoryById($id)
    {
        if ($id === null) {
            return null;
        }

        return $this->repository->find($id);
    }

    /**
     * @return bool
     */
    private function checkIfRelationExists($categoryId, $customerGroupId)
    {
        if ($this->categoryAvoidCustomerGroups === null) {
            $this->setCategoryAvoidCustomerGroups();
        }

        foreach ($this->categoryAvoidCustomerGroups as $relation) {
            if ($relation['categoryID'] == $categoryId && $relation['customergroupID'] == $customerGroupId) {
                return true;
            }
        }

        return false;
    }

    private function setCategoryAvoidCustomerGroups()
    {
        $sql = 'SELECT categoryID, customergroupID FROM s_categories_avoid_customergroups';
        $this->categoryAvoidCustomerGroups = $this->db->fetchAll($sql);
    }

    /**
     * @param int $id
     *
     * @return Group|null
     */
    private function getCustomerGroupById($id)
    {
        /* @var Group $group */
        return $this->modelManager->getRepository(Group::class)->find($id);
    }

    /**
     * @param int|null $categoryId
     *
     * @return Category
     */
    private function createCategoryAndSetId($categoryId)
    {
        $category = new Category();
        if ($categoryId) {
            $category->setId($categoryId);
        }

        return $category;
    }

    /**
     * @param Category $category
     *
     * @throws AdapterException
     */
    private function validateCategoryModel($category)
    {
        $violations = $this->modelManager->validate($category);
        if ($violations->count() > 0) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/category/no_valid_category_entity', 'No valid category entity for category %s');
            throw new AdapterException(\sprintf($message, $category->getName()));
        }
    }

    /**
     * @param array $record
     *
     * @throws AdapterException
     */
    private function validateParentCategory($record)
    {
        if (!$record['parent'] instanceof Category) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/categories/parent_not_exists', 'Parent category does not exists for category %s');
            throw new AdapterException(\sprintf($message, $record['name']));
        }
    }

    /**
     * @param array $records
     *
     * @throws \Exception
     */
    private function validateRecordsShouldNotBeEmpty($records)
    {
        if (empty($records)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/categories/no_records', 'No category records were found.');
            throw new \Exception($message);
        }
    }
}

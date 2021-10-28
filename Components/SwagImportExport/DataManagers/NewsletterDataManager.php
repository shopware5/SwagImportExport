<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataManagers;

use Shopware\Components\SwagImportExport\DataType\NewsletterDataType;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Models\Newsletter\Group;
use Shopware\Models\Newsletter\Repository;

class NewsletterDataManager extends DataManager
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var Repository
     */
    private $groupRepository;

    /**
     * initialises the class properties
     */
    public function __construct()
    {
        $this->config = Shopware()->Config();
        $this->groupRepository = Shopware()->Models()->getRepository(Group::class);
    }

    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return NewsletterDataType::$defaultFieldsForCreate;
    }

    /**
     * Return fields which should be set by default
     *
     * @return array
     */
    public function getDefaultFieldsName()
    {
        $defaultFieldsForCreate = $this->getDefaultFields();
        $defaultFields = $this->getFields($defaultFieldsForCreate);

        return $defaultFields;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     * @param array $defaultValues
     *
     * @throws AdapterException
     */
    public function setDefaultFieldsForCreate($record, $defaultValues)
    {
        $getDefaultFields = $this->getDefaultFieldsName();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }

            if ($key === 'groupName') {
                $record[$key] = $this->getGroupName($record['email'], $record[$key]);
            }
        }

        return $record;
    }

    /**
     * Returns newsletter default group name.
     *
     * @param string $email
     * @param string $groupName
     *
     * @throws AdapterException
     *
     * @return string
     */
    private function getGroupName($email, $groupName)
    {
        $group = $this->groupRepository->findOneBy(['name' => $groupName]);
        if ($group instanceof Group) {
            return $group->getName();
        }

        $groupId = $this->config->get('sNEWSLETTERDEFAULTGROUP');
        $group = $this->groupRepository->find($groupId);

        if (!$group instanceof Group) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/newsletter/group_required', 'Group is required for email %s');
            throw new AdapterException(\sprintf($message, $email));
        }

        return $group->getName();
    }
}

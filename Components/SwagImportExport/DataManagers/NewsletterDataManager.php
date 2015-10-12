<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

use Shopware\Models\Newsletter\Group;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class NewsletterDataManager
{
    /** @var \Shopware_Components_Config */
    private $config = null;

    private $groupRepository = null;

    /** Define which field should be set by default */
    private static $defaultFields = array(
        'groupName',
    );

    public function __construct()
    {
        $this->config = Shopware()->Config();
        $this->groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Newsletter\Group');
    }

    /**
     * Return fields which should be set by default
     *
     * @return array
     */
    public function getDefaultFields()
    {
        return self::$defaultFields;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     * @return mixed
     * @throws AdapterException
     */
    public function setDefaultFields($record, $defaultValues)
    {
        $getDefaultFields = $this->getDefaultFields();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }

            switch ($key) {
                case 'groupName':
                    $record[$key] = $this->getGroupName($record['email']);
                    break;
            }
        }

        return $record;
    }

    /**
     * Returns newsletter default group name.
     *
     * @param string $email
     * @return string
     * @throws AdapterException
     */
    private function getGroupName($email)
    {
        $groupId = $this->config->get("sNEWSLETTERDEFAULTGROUP");
        $group = $this->groupRepository->findOneBy($groupId);

        if (!$group instanceof Group) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/newsletter/group_required', 'Group is required for email %s');
            throw new AdapterException(sprintf($message, $email));
        }

        return $group->getName();
    }
}
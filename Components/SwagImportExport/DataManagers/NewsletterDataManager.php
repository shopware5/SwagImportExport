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
    private $defaultFields = array(
        'groupName',
    );

    public function __construct()
    {
        $this->config = Shopware()->Config();
        $this->groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Newsletter\Group');
    }

    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'groupName':
                    $record['groupName'] = $this->getGroupName($record['email']);
                    break;
            }
        }

        return $record;
    }

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
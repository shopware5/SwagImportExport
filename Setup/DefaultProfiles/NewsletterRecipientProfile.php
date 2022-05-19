<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class NewsletterRecipientProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_newsletter_recipient';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_newsletter_recipient_description';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => [
                    1 => [
                            'id' => '537359399c8b7',
                            'name' => 'Users',
                            'index' => 0,
                            'type' => 'node',
                            'shopwareField' => '',
                            'children' => [
                                    0 => [
                                            'id' => '537359399c90d',
                                            'name' => 'user',
                                            'index' => 0,
                                            'type' => 'iteration',
                                            'adapter' => 'default',
                                            'parentKey' => '',
                                            'shopwareField' => '',
                                            'attributes' => [
                                                ],
                                            'children' => $this->getNewsletterFields(),
                                        ],
                                ],
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getNewsletterFields()
    {
        return [
            0 => [
                    'id' => '53e4b0f86aded',
                    'type' => 'leaf',
                    'index' => 0,
                    'name' => 'email',
                    'shopwareField' => 'email',
                ],
            1 => [
                    'id' => '53e4b103bf001',
                    'type' => 'leaf',
                    'index' => 1,
                    'name' => 'group',
                    'shopwareField' => 'groupName',
                ],
            2 => [
                    'id' => '53e4b105ea8c2',
                    'type' => 'leaf',
                    'index' => 2,
                    'name' => 'salutation',
                    'shopwareField' => 'salutation',
                ],
            3 => [
                    'id' => '53e4b107872be',
                    'type' => 'leaf',
                    'index' => 3,
                    'name' => 'firstname',
                    'shopwareField' => 'firstName',
                ],
            4 => [
                    'id' => '53e4b108d49f9',
                    'type' => 'leaf',
                    'index' => 4,
                    'name' => 'lastname',
                    'shopwareField' => 'lastName',
                ],
            5 => [
                    'id' => '53e4b10a38e08',
                    'type' => 'leaf',
                    'index' => 5,
                    'name' => 'street',
                    'shopwareField' => 'street',
                ],
            6 => [
                    'id' => '53e4b10d68c09',
                    'type' => 'leaf',
                    'index' => 7,
                    'name' => 'zipcode',
                    'shopwareField' => 'zipCode',
                ],
            7 => [
                    'id' => '53e4b157416fc',
                    'type' => 'leaf',
                    'index' => 8,
                    'name' => 'city',
                    'shopwareField' => 'city',
                ],
            8 => [
                    'id' => '53e4b1592dd4b',
                    'type' => 'leaf',
                    'index' => 9,
                    'name' => 'lastmailing',
                    'shopwareField' => 'lastNewsletter',
                ],
            9 => [
                    'id' => '53e4b15a69651',
                    'type' => 'leaf',
                    'index' => 10,
                    'name' => 'lastread',
                    'shopwareField' => 'lastRead',
                ],
            10 => [
                    'id' => '53e4b15bde918',
                    'type' => 'leaf',
                    'index' => 11,
                    'name' => 'userID',
                    'shopwareField' => 'userID',
                ],
            11 => [
                    'id' => '53e4b15bde9ff',
                    'type' => 'leaf',
                    'index' => 12,
                    'name' => 'added',
                    'shopwareField' => 'added',
                ],
            12 => [
                    'id' => '53e4b15bde9fe',
                    'type' => 'leaf',
                    'index' => 13,
                    'name' => 'doubleOptinConfirmed',
                    'shopwareField' => 'doubleOptinConfirmed',
                ],
        ];
    }
}

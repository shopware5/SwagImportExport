<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class NewsletterDefaultProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testNewsletterRecipientShouldBeImported(): void
    {
        $filePath = __DIR__ . '/_fixtures/newsletter_profile.csv';
        $expectedRecipientEmail = 'email_should_be_created@example.org';

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='{$expectedRecipientEmail}'");
        $assignedGroup = $this->executeQuery("SELECT * FROM s_campaigns_groups WHERE id={$importedRecipient[0]['groupID']}");

        static::assertEquals($expectedRecipientEmail, $importedRecipient[0]['email']);
        static::assertEquals('Newsletter-Empfänger', $assignedGroup[0]['name']);
    }

    public function testCustomNewsletterGroupImportAndAssignedRecipient(): void
    {
        $filePath = __DIR__ . '/_fixtures/newsletter_profile.csv';
        $expectedAssignedRecipientEmail = 'custom_group_should_be_created@example.org';
        $expectedGroupName = 'Custom_Group';

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedNewsletterGroup = $this->executeQuery("SELECT * FROM s_campaigns_groups WHERE name='{$expectedGroupName}'");
        $assignedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE groupID='{$importedNewsletterGroup[0]['id']}'");

        static::assertEquals($expectedGroupName, $importedNewsletterGroup[0]['name']);
        static::assertEquals($expectedAssignedRecipientEmail, $assignedRecipient[0]['email']);
    }

    public function testImportedRecipientAssignedToExistingShopCustomer(): void
    {
        $filePath = __DIR__ . '/_fixtures/newsletter_profile.csv';
        $exceptedRecipientCustomerEmail = 'mustermann@b2b.de';

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='{$exceptedRecipientCustomerEmail}'");
        $assignedShopCustomer = $this->executeQuery("SELECT * FROM s_user WHERE email='{$importedRecipient[0]['email']}'");

        static::assertEquals($exceptedRecipientCustomerEmail, $assignedShopCustomer[0]['email']);
    }
}

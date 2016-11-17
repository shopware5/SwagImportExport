<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class NewsletterDefaultProfileTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_newsletter_recipient_should_be_imported()
    {
        $filePath = __DIR__ . '/_fixtures/newsletter_profile.csv';
        $expectedRecipientEmail = 'email_should_be_created@example.org';

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='{$expectedRecipientEmail}'");
        $assignedGroup = $this->executeQuery("SELECT * FROM s_campaigns_groups WHERE id={$importedRecipient[0]['groupID']}");

        $this->assertEquals($expectedRecipientEmail, $importedRecipient[0]['email']);
        $this->assertEquals('Newsletter-Empfänger', $assignedGroup[0]['name']);
    }

    public function test_custom_newsletter_group_import_and_assigned_recipient()
    {
        $filePath = __DIR__ . '/_fixtures/newsletter_profile.csv';
        $expectedAssignedRecipientEmail = "custom_group_should_be_created@example.org";
        $expectedGroupName = "Custom_Group";

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedNewsletterGroup = $this->executeQuery("SELECT * FROM s_campaigns_groups WHERE name='{$expectedGroupName}'");
        $assignedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE groupID='{$importedNewsletterGroup[0]['id']}'");

        $this->assertEquals($expectedGroupName, $importedNewsletterGroup[0]['name']);
        $this->assertEquals($expectedAssignedRecipientEmail, $assignedRecipient[0]['email']);
    }

    public function test_imported_recipient_assigned_to_existing_shop_customer()
    {
        $filePath = __DIR__ . "/_fixtures/newsletter_profile.csv";
        $exceptedRecipientCustomerEmail = "mustermann@b2b.de";

        $this->runCommand("sw:import:import -p default_newsletter_recipient {$filePath}");

        $importedRecipient = $this->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='{$exceptedRecipientCustomerEmail}'");
        $assignedShopCustomer = $this->executeQuery("SELECT * FROM s_user WHERE email='{$importedRecipient[0]['email']}'");

        $this->assertEquals($exceptedRecipientCustomerEmail, $assignedShopCustomer[0]["email"]);
    }
}

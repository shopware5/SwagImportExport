<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

interface ProfileMetaData
{
    /**
     * Returns the adapter of the profile.
     */
    public function getAdapter(): string;

    /**
     * Returns the name of a default profile. The default profile name will be used for the cli commands and the cronjob.
     * Further it represents the snippet name.
     */
    public function getName(): string;

    /**
     * Returns a snippet key which is used to deliver a profile explaining
     * descriptional text to the backend user.
     */
    public function getDescription(): string;
}

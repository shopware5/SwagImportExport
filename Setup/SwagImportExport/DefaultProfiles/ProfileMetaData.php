<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

interface ProfileMetaData
{
    /**
     * Returns the adapter of the profile.
     */
    public function getAdapter();

    /**
     * Returns the name of a default profile. The default profile name will be used for the cli commands and the cronjob.
     * Further it represents the snippet name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns a snippet key which is used to deliver a profile explaining
     * descriptional text to the backend user.
     *
     * @return string
     */
    public function getDescription();
}

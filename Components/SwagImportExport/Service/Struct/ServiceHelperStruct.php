<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service\Struct;

use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\FileIO\FileReader;
use Shopware\Components\SwagImportExport\FileIO\FileWriter;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Session\Session;

class ServiceHelperStruct
{
    /** @var Profile */
    private $profile;

    /** @var Session */
    private $session;

    /** @var DataDbAdapter */
    private $dbAdapter;

    /** @var FileReader */
    private $fileReader;

    /** @var FileWriter */
    private $fileWriter;

    /** @var DataIO */
    private $dataIO;

    /**
     * @param Profile       $profile
     * @param Session       $session
     * @param DataDbAdapter $dbAdapter
     * @param FileReader    $fileReader
     * @param FileWriter    $fileWriter
     * @param DataIO        $dataIO
     */
    public function __construct(
        Profile $profile,
        Session $session,
        DataDbAdapter $dbAdapter,
        FileReader $fileReader,
        FileWriter $fileWriter,
        DataIO $dataIO
    ) {
        $this->profile = $profile;
        $this->session = $session;
        $this->dbAdapter = $dbAdapter;
        $this->fileReader = $fileReader;
        $this->fileWriter = $fileWriter;
        $this->dataIO = $dataIO;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return DataDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * @return FileReader
     */
    public function getFileReader()
    {
        return $this->fileReader;
    }

    /**
     * @return FileWriter
     */
    public function getFileWriter()
    {
        return $this->fileWriter;
    }

    /**
     * @return DataIO
     */
    public function getDataIO()
    {
        return $this->dataIO;
    }
}

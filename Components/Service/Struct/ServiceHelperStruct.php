<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service\Struct;

use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\FileIO\FileWriter;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Session\Session;

class ServiceHelperStruct
{
    private Profile $profile;

    private Session $session;

    private DataDbAdapter $dbAdapter;

    private FileReader $fileReader;

    private FileWriter $fileWriter;

    private DataIO $dataIO;

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

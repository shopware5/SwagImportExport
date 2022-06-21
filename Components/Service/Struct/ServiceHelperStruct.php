<?php
declare(strict_types=1);
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

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getDbAdapter(): DataDbAdapter
    {
        return $this->dbAdapter;
    }

    public function getFileReader(): FileReader
    {
        return $this->fileReader;
    }

    public function getFileWriter(): FileWriter
    {
        return $this->fileWriter;
    }

    public function getDataIO(): DataIO
    {
        return $this->dataIO;
    }
}

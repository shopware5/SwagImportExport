<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Logger;

class LogDataStruct
{
    private string $date;

    private string $fileName;

    private string $profileName;

    private string $messages;

    private string $status;

    public function __construct(string $date, string $fileName, string $profileName, string $messages, string $status)
    {
        $this->date = $date;
        $this->fileName = $fileName;
        $this->profileName = $profileName;
        $this->messages = $messages;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    public function setDate(string $date)
    {
        if ($date instanceof \DateTime) {
            throw new \InvalidArgumentException('Got ' . \DateTime::class . ', expected string.');
        }
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getProfileName()
    {
        return $this->profileName;
    }

    public function setProfileName(string $profileName)
    {
        $this->profileName = $profileName;
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function setMessages(string $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return \get_object_vars($this);
    }
}

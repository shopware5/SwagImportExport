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

    /**
     * @param string $date
     * @param string $fileName
     * @param string $profileName
     * @param string $messages
     * @param string $status
     */
    public function __construct($date, $fileName, $profileName, $messages, $status)
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

    /**
     * @param string $date
     */
    public function setDate($date)
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

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
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

    /**
     * @param string $profileName
     */
    public function setProfileName($profileName)
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

    /**
     * @param string $messages
     */
    public function setMessages($messages)
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

    /**
     * @param string $status
     */
    public function setStatus($status)
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

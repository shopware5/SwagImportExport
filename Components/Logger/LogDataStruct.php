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

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        if ($date instanceof \DateTime) {
            throw new \InvalidArgumentException('Got ' . \DateTime::class . ', expected string.');
        }
        $this->date = $date;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function setProfileName(string $profileName): void
    {
        $this->profileName = $profileName;
    }

    public function getMessages(): string
    {
        return $this->messages;
    }

    public function setMessages(string $messages): void
    {
        $this->messages = $messages;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}

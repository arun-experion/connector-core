<?php

namespace Connector\Integrations\Storage;

use Connector\Record\RecordLocator;

/**
 *
 */
class StorageRecordLocator extends RecordLocator
{
    /**
     * @var string $recordType
     * Folder identifier. Implementation-specific.
     */
    public string $recordType  = '';

    /**
     * @var string|null Sub folder
     */
    public ?string $folder  = '';

    public function getPath(): string
    {
        return $this->recordType . ($this->folder?'/'.$this->escapeFolderName(ltrim($this->folder,"/")):"");
    }

    private function escapeFolderName(string $folderName): string {
        $escapeMapping = [
            '/'  => '%2F',
            '\\' => '%5C',
            '?'  => '%3F',
            '*'  => '%2A',
            ':'  => '%3A',
            '|'  => '%7C',
            '"'  => '%22',
            '<'  => '%3C',
            '>'  => '%3E',
            '#'  => '%23'
        ];

        foreach ($escapeMapping as $char => $escape) {
            $folderName = str_replace($char, $escape, $folderName);
        }

        return $folderName;
    }
}

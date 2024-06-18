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
        return $this->recordType . ($this->folder?'/'.ltrim($this->folder,"/"):"");
    }
}

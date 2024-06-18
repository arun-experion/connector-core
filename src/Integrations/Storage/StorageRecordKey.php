<?php

namespace Connector\Integrations\Storage;

use Connector\Record\RecordKey;

/**
  * The RecordKey uniquely identifies a single file in the integrated system.
 */
class StorageRecordKey extends RecordKey
{
    public string $url;

    public function __construct(mixed $fileId, string $folder = '', string $url = "")
    {
        parent::__construct($fileId, $folder);
        $this->url = $url;
    }
}

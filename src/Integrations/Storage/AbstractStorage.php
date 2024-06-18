<?php

namespace Connector\Integrations\Storage;

use Connector\Exceptions\EmptyRecordException;
use Connector\Exceptions\InvalidMappingException;
use Connector\Exceptions\NotImplemented;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Record;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use Connector\Schema\Builder\RecordProperty;
use Connector\Schema\Builder\RecordType;
use Connector\Schema\Builder;
use Connector\Schema\IntegrationSchema;
use Connector\Type\JsonSchemaFormats;
use Connector\Type\JsonSchemaTypes;

/**
 * Abstract class for storage-like integrations (Google Drive, Box, OneDrive, etc.)
 * Redefines load() as a folder and file creation.
 *
 * Expects RecordLocator to have the following property:
 *  `path`      - the location where to upload the file, using "/" as a separator.
 *
 * Expects Mapping to have the following keys:
 *  `url`       - the URL where the file to load() can be found.
 *  `fileName`  - the name of the file
 *  `mimeType`  = the mime type of the file
 *  `size`      - the size of the file, in bytes.
 *
 */
abstract class AbstractStorage extends AbstractIntegration
{

    /**
     * @return string[] A list of readable & writable folders.
     */
    abstract protected function findAllFolders(): array;

    /**
     * @param string $path The path to the folder
     *
     * @return mixed The folder found or created, as an implementation-specific resource.
     */
    abstract protected function findOrCreateFolder(string $path = ''): mixed;

    /**
     * @param mixed       $destinationFolder The folder where the file is to be uploaded, as an implementation-specific resource.
     * @param string      $fileName          The name of the file
     * @param string      $fileBase64Content The content (base64 encoded) of the file to create/update.
     * @param string|null $mimeType          The mime-type of the file
     *
     * @return StorageRecordKey  Contains an implementation-specific key identifying the file created or updated.
     */
    abstract protected function createOrReplaceFile(mixed $destinationFolder, string $fileName, string $fileBase64Content, string $mimeType = null): StorageRecordKey;

    public function discover(): IntegrationSchema
    {
        $folders = $this->findAllFolders();

        $builder  = new Builder("http://formassembly.com/schema/storage.schema.json", "Storage Schema");

        foreach($folders as $folder) {
            $recordType = new RecordType($folder);
            $recordType->title = $folder;
            $recordType->setTags(['folder']);
            $recordType->addProperty( new RecordProperty( "name", ['$ref' => '#/$defs/name'] ));
            $recordType->addProperty( new RecordProperty( "size", ['$ref' => '#/$defs/size'] ));
            $recordType->addProperty( new RecordProperty( "url",  ['$ref' => '#/$defs/url'] ));
            $recordType->addProperty( new RecordProperty( "mimetype", ['$ref' => '#/$defs/mimetype'] ));
            $recordType->addProperty( new RecordProperty( "content", ['$ref' => '#/$defs/content'] ));
            $builder->addRecordType($recordType);

        }

        $builder->addDefinition(new RecordProperty("name", ["title" => "File Name", "type" => JsonSchemaTypes::String]));
        $builder->addDefinition(new RecordProperty("url", ["title" => "File URL", "type" => JsonSchemaTypes::String]));
        $builder->addDefinition(new RecordProperty("size", ["title" => "File Size", "type" => JsonSchemaTypes::Integer]));
        $builder->addDefinition(new RecordProperty("mimetype", ["title" => "Mime Type", "type" => JsonSchemaTypes::String]));
        $builder->addDefinition(new RecordProperty("content", ["title" => "Content", "type" => JsonSchemaTypes::String]));

        // Result record
        $recordType = new RecordType( "result");
        $recordType->title = "Operation Result";
        $recordType->setTags(['result']);
        $recordType->setDescription("Available data after a file is uploaded.");
        $recordType->addProperty("id", ["title" => "ID" , "type"=> JsonSchemaTypes::String, "description" => "ID of uploaded file"]);
        $recordType->addProperty("url", ["title" => "URL" , "type"=> JsonSchemaTypes::String, "format" => JsonSchemaFormats::Uri, "description" => "Link to uploaded file"]);

        $builder->addRecordType($recordType);

        return $builder->toSchema();
    }

    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response {
        throw new NotImplemented();
    }

    /**
     * @param \Connector\Record\RecordLocator  $recordLocator
     * @param \Connector\Mapping               $mapping
     * @param \Connector\Record\RecordKey|null $scope
     *
     * @return \Connector\Integrations\Response
     * @throws \Connector\Exceptions\InvalidMappingException|\Connector\Exceptions\EmptyRecordException
     */
    final public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response {

        $recordLocator = new StorageRecordLocator($recordLocator);

        $contentKey  = $this->schema->fullyQualifyName($recordLocator->recordType, 'content');
        $nameKey     = $this->schema->fullyQualifyName($recordLocator->recordType, 'name');
        $mimeTypeKey = $this->schema->fullyQualifyName($recordLocator->recordType, 'mimetype');

        if(!$mapping->hasItem($nameKey)) {
            throw new InvalidMappingException('File name is missing from mapping, expecting key '.$nameKey);
        }
        if(!$mapping->hasItem($contentKey)) {
            throw new InvalidMappingException('File content is missing from mapping, expecting key '.$nameKey);
        }

        $fileContent = $mapping->getValuesByKey($contentKey)[0];
        $fileName    = $mapping->getValuesByKey($nameKey)[0];
        $mimeType    = $mapping->hasItem($mimeTypeKey) ? $mapping->getValuesByKey($mimeTypeKey)[0] : null;

        if(!$fileContent) {
            throw new EmptyRecordException('No file to process (key: '.$contentKey.')');
        }

        $targetPath   = $recordLocator->getPath();
        $targetFolder = $this->findOrCreateFolder($targetPath);
        $key          = $this->createOrReplaceFile($targetFolder, $fileName, $fileContent, $mimeType);
        $recordset    = new Recordset();
        $recordset[]  = new Record($key, ['result:id' => $key->recordId, 'result:url' => $key->url]);

        $this->log(sprintf('Created file "%s" in "%s",', $fileName, $targetPath));

        return (new Response())->setRecordKey($key)->setRecordset($recordset);
    }

}

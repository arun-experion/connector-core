<?php

namespace Connector\Integrations\Document;

use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Execution;
use Connector\Integrations\AbstractIntegration;
use Connector\Mapping;

/**
 * Plain text document generation
 */
class PlainText extends AbstractDocument
{

    public function createDocumentFragment(RecordLocator $config, Mapping $mapping, ?RecordKey $scope): mixed
    {
        if($scope) {
            $template = $this->extractRecordTemplate($this->document, $scope->recordType . " " . $scope->recordId);
            $start    = $template['innerStart'];
            $template = $template['template'];
        } else {
            $template = $this->document;
            $start    = 0;
        }
        $fragment = $this->extractRecordTemplate($template, $config->recordType);

        foreach($mapping as $item) {
            $fragment['template'] = str_replace("%%" . $item->key . "%%", $item->value, $fragment['template']);
        }

        $fragment['start'] = $start + $fragment['outerStart'];
        return $fragment;
    }

    /**
     * Assumes no duplicate begin/end tags for a given fragment.
     *
     *
     * @param mixed $document
     * @param mixed $documentFragment
     * @param int   $fragmentId *
     *
* @return mixed
     */
    public function composeDocument(mixed $document, mixed $documentFragment, int $fragmentId): mixed
    {
        $name  = $documentFragment['recordType'];
        $start = $documentFragment['start'];
        $block = "{% begin $name $fragmentId %}" . $documentFragment['template'] . "{% end $name $fragmentId %}";

        return substr($document, 0, $start) . $block . substr($this->document, $start);
    }

    public function getDocument(): mixed
    {
        return $this->cleanDocument($this->document);
    }

    public function buildPlan(AbstractIntegration $source): Execution
    {
        $this->buildPlan = new Execution("", $source, $this);
        $this->buildPlanFromTemplate($this->template, 0);
        return $this->buildPlan;
    }

    private function buildPlanFromTemplate(string $template, int $parentOperationId): void
    {
        $t = $this->extractRecordTemplate($template);
        if ($t) {
            $newOperation =& $this->buildPlan->addNodeAfter($parentOperationId);
            $newOperation['recordLocators'] =
                [ "source" => [ "recordType"       => $t['recordType']],
                  "target" => [ "recordType"       => $t['recordType'],
                                "fragmentBeginTag" => $this->getBeginTag($t['recordType']),
                                "fragmentEndTag"   => $this->getEndTag($t['recordType'])]
                ];
            $newOperation['mapping'] = array_map( function($alias) {
                return [ "source" => ["id" => $alias, "label" => $alias],
                         "target" => ["id" => $alias, "label" => $alias]];
            }, $this->extractAliasesFromTemplate($t['template']));

            // process nested template
            $this->buildPlanFromTemplate($t['template'], $newOperation['id']);

            // process remaining template
            $remove   = $this->addTags($t['template'], $t['recordType']);
            $template = str_replace($remove,'', $template);
            $this->buildPlanFromTemplate($template, $parentOperationId);
        }
    }

    private function addTags(string $template, string $recordType): string
    {
        return $this->getBeginTag($recordType) . $template . $this->getEndTag($recordType);
    }

    private function getBeginTag(string $recordType): string
    {
        return '{% begin ' . $recordType . ' %}';
    }

    private function getEndTag(string $recordType): string
    {
        return '{% end ' . $recordType . ' %}';
    }

    private function cleanDocument(string $template): string
    {
        $template = preg_replace("/{% begin [^%]+ \d+ %}/", "", $template);
        $template = preg_replace("/{% end [^%]+ \d+ %}/", "", $template);
        $template = preg_replace("/{% begin ([^%]+) %}.*?{% end \\1 %}/s", "", $template);

        return $template;
    }

    private function extractRecordTemplate(string $template, string $recordName = null, int $fragmentId = null): ?array
    {
        $acc   = '';
        $rec   = '';
        $depth = 1;
        $innerStart = 0;
        $outerStart = 0;

        for($i=0;$i < strlen($template); $i++) {

            $acc  .= $template[$i];
            $found = preg_match("/{% begin ([^%]+) %}$/", $acc, $matches);

            if($found===1 && (!$recordName || $recordName === $matches[1])) {
                if($rec === $matches[1]) {
                    $depth++;
                } else {
                    if($rec==='') {
                        $rec = $matches[1];
                        $acc = '';
                        $outerStart = $i - strlen($matches[0]) + 1;
                        $innerStart = $i + 1;
                    }
                }
            } else {

                $found = preg_match("/{% end ([^%]+) %}$/", $acc, $matches);

                if($found === 1 && $rec === $matches[1] ) {
                    $depth--;
                    if($depth===0) {
                        return [
                            "innerStart" => $innerStart,
                            "outerStart" => $outerStart,
                            "recordType" => $rec,
                            "template"   => substr($acc, 0, strlen($acc) - strlen($matches[0]))
                        ];
                    }
                }
            }
        }
        return null;
    }

    private function removeNestedRecordTemplate(string $template): string {
        $t = $this->extractRecordTemplate($template);
        if($t) {
            $remove   = $this->addTags($t['template'], $t['recordType']);
            $template = str_replace($remove, '', $template);
            $template = $this->removeNestedRecordTemplate($template);
        }
        return $template;
    }

    private function extractAliasesFromTemplate(string $template): array {
        $aliases = [];
        $template = $this->removeNestedRecordTemplate($template);

        preg_match_all("/%%([^%]+)%%/", $template, $matches, PREG_SET_ORDER);

        foreach($matches as $match) {
            $aliases[] = $match[1];
        }
        return $aliases;
    }
}

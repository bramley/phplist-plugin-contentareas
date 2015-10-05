<?php

namespace phpList\plugin\ContentAreas;

use DOMNode;
use DOMNodeList;
use phpList\plugin\Common\Logger;

/**
 * Class to control the merging of content areas.
 */
class Merger
{
    /** @var DOMXPath xpath object */
    private $xpath;

    /** @var int The number of parent repeatable or hideable elements */
    private $depth = 0;

    /** @var phpList\plugin\Common\Logger Instance of logger */
    private $logger;

    public function __construct($xpath)
    {
        $this->xpath = $xpath;
        $this->logger = Logger::instance();
    }

    /**
     * Merge all the content areas at a specific level.
     *
     * @param DOMNodeList $nodes          content area nodes
     * @param array       $areas          the content area values for the current level
     * @param bool        $edit           whether the merge should include edit buttons
     * @param string|null $repeatName     name of repeatable or hideable area
     * @param int|null    $repeatInstance the repeat or hideable instance
     */
    private function mergeNodesAtLevel(DOMNodeList $nodes, array $areas, $edit, $repeatName = null, $repeatInstance = null)
    {
        ++$this->depth;

        foreach ($nodes as $node) {
            $area = ContentAreaBase::createContentArea($node);
            $area->edit = $edit;

            if ($repeatName) {
                $area->reference = new Reference($repeatName, $repeatInstance, $area->name);
            }

            if (isset($areas[$area->name])) {
                $area->merge($areas[$area->name], $this);
            } else {
                $area->merge(null, $this);
            }
        }
        --$this->depth;
    }

    /**
     * Merge all the content areas at one level
     * Repeatable and hideable areas will recursively call this method.
     * At present allow only one level of repeatable and hideable.
     *
     * @param DOMNode     $contextNode    the current node
     * @param array       $areas          the content area values for the current level
     * @param bool        $edit           whether the merge should include edit buttons
     * @param string|null $repeatName     name of repeatable or hideable area
     * @param int|null    $repeatInstance the repeat or hideable instance
     */
    public function mergeOneLevel(DOMNode $contextNode, array $areas, $edit, $repeatName = null, $repeatInstance = null)
    {
        $expression = $this->depth == 0
            ? sprintf(TemplateModel::XPATH_ALL_AT_DEPTH, $this->depth)
            : TemplateModel::XPATH_CHILD_EDIT;
        $nodes = $this->xpath->query($expression, $contextNode);
        $this->mergeNodesAtLevel($nodes, $areas, $edit, $repeatName, $repeatInstance);
    }
}

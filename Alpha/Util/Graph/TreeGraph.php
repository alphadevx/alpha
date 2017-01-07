<?php

namespace Alpha\Util\Graph;

use Alpha\Util\Logging\Logger;

/**
 * Maintains the geometry for a tree graph.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 */
class TreeGraph
{
    /**
     * An array of nodes on the previous level.
     *
     * @var array
     *
     * @since 1.0
     */
    private $previousLevelNodes = array();

    /**
     * An array of nodes in this graph.
     *
     * @var array
     *
     * @since 1.0
     */
    private $nodes = array();

    /**
     * The root node of the graph.
     *
     * @var \Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    private $root;

    /**
     * The amount of space between graph rows.
     *
     * @var int
     *
     * @since 1.0
     */
    private $rowSpace;

    /**
     * The amount of space between graph columns.
     *
     * @var int
     *
     * @since 1.0
     */
    private $colSpace;

    /**
     * The amount of space between graph branches.
     *
     * @var int
     *
     * @since 1.0
     */
    private $branchSpace;

    /**
     * Flag to track whether the chart is rendered or not.
     *
     * @var bool
     *
     * @since 1.0
     */
    private $isRendered = false;

    /**
     * The index of the current node in the graph we are inspecting.
     *
     * @var int
     *
     * @since 1.0
     */
    private $position = 0;

    /**
     * The height of the graph.
     *
     * @var int
     *
     * @since 1.0
     */
    private $height = 0;

    /**
     * The width of the graph.
     *
     * @var int
     *
     * @since 1.0
     */
    private $width = 0;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @param int $rowSpace
     * @param int $colSpace
     * @param int $branchSpace
     *
     * @since 1.0
     */
    public function __construct($rowSpace = 40, $colSpace = 40, $branchSpace = 80)
    {
        self::$logger = new Logger('TreeGraph');

        $this->root = new GraphNode(0, 0, 0);
        $this->rowSpace = $rowSpace;
        $this->colSpace = $colSpace;
        $this->branchSpace = $branchSpace;
    }

    /**
     * Add a new node to the graph.
     *
     * @param int    $id
     * @param int    $pid
     * @param string $message
     * @param int    $w
     * @param int    $h
     * @param array  $nodeColour
     * @param string $URL
     *
     * @since 1.0
     */
    public function add($id, $pid, $message = '', $w = 0, $h = 0, $nodeColour, $URL)
    {
        $node = new GraphNode($id, $w, $h, $message, $nodeColour, $URL);

        if (isset($this->nodes[$pid])) {
            $pnode = $this->nodes[$pid];
            $node->setParentNode($pnode);
            $pnode->addChild($node);
        } else {
            $pnode = $this->root;
            $node->setParentNode($pnode);
            $this->root->addChild($node);
        }

        $this->nodes[$id] = $node;
    }

    /**
     * Get the specified node from the graph.
     *
     * @param int    $id
     *
     * @return \Alpha\Util\Graph\GraphNode
     *
     * @since 2.0.1
     */
    public function get($id)
    {
        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        } else {
            return null;
        }
    }

    /**
     * The first pass of the graph.
     *
     * @param \Alpha\Util\Graph\GraphNode $node
     * @param int                        $level
     *
     * @since 1.0
     */
    private function firstPass($node, $level)
    {
        $this->setNeighbours($node, $level);

        if ($node->childCount() == 0) {
            $leftSibling = $node->getLeftSibling();

            if (isset($leftSibling)) {
                $node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
            } else {
                $node->setOffset(0);
            }
        } else {
            $childCount = $node->childCount();

            for ($i = 0; $i < $childCount; ++$i) {
                $this->firstPass($node->getChildAt($i), $level + 1);
            }

            $midPoint = $node->getChildrenCenter();
            $midPoint -= $node->getWidth() / 2;
            $leftSibling = $node->getLeftSibling();

            if (isset($leftSibling)) {
                $node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
                $node->setModifier($node->getOffset() - $midPoint);

                $this->layout($node, $level);
            } else {
                $node->setOffset($midPoint);
            }
        }

        self::$logger->debug('Memory usage at first scan ['.((memory_get_usage(true) / 1024) / 1024).' MB]');
    }

    /**
     * The second pass of the graph.
     *
     * @param \Alpha\Util\Graph\GraphNode $node
     * @param int                        $level
     * @param int                        $x
     * @param int                        $y
     *
     * @since 1.0
     */
    private function secondPass($node, $level, $x = 0, $y = 0)
    {
        $nodeX = $node->getOffset() + $x;
        $nodeY = $y;

        $node->setX($nodeX);
        $node->setY($nodeY);

        $this->height = ($this->height > $node->getY() + $node->getWidth()) ? $this->height : $node->getY() + $node->getWidth();
        $this->width = ($this->width > $nodeX + $node->getWidth()) ? $this->width : $nodeX + $node->getWidth() + 10;

        if ($node->childCount() > 0) {
            $this->secondPass($node->getChildAt(0), $level + 1, $x + $node->getModifier(), $y + $node->getHeight() + $this->rowSpace);
        }

        $rightSibling = $node->getRightSibling();

        if (isset($rightSibling)) {
            $this->secondPass($rightSibling, $level, $x, $y);
        }

        self::$logger->debug('Memory usage at second scan ['.((memory_get_usage(true) / 1024) / 1024).' MB]');
    }

    /**
     * Handles the laying out of multi-branch trees.
     *
     * @param \Alpha\Util\Graph\GraphNode $node
     * @param int                        $level
     *
     * @since 1.0
     */
    private function layout($node, $level)
    {
        $firstChild = $node->getChildAt(0);
        $firstChildLeftNeighbour = $firstChild->getLeftSibling();

        for ($j = 1; $j <= $level; ++$j) {
            $modifierSumRight = 0;
            $modifierSumLeft = 0;
            $rightAncestor = $firstChild;
            $leftAncestor = $firstChildLeftNeighbour;

            for ($l = 0; $l < $j; ++$l) {
                $rightAncestor = $rightAncestor->getParentNode();
                $leftAncestor = $leftAncestor->getParentNode();
                $modifierSumRight += $rightAncestor->getModifier();
                $modifierSumLeft += $leftAncestor->getModifier();
            }

            $totalGap = ($firstChildLeftNeighbour->getOffset() + $modifierSumLeft + $firstChildLeftNeighbour->getWidth() + $this->branchSpace) - ($firstChild->getOffset() + $modifierSumRight);

            if ($totalGap > 0) {
                $subTree = $node;
                $subTreesCount = 0;

                while (isset($subTree) && $subTree !== $leftAncestor) {
                    $subTree = $subTree->getLeftSibling();
                    ++$subTreesCount;
                }

                $subTreeMove = $node;
                $singleGap = $totalGap / $subTreesCount;

                while (isset($subTreeMove) && $subTreeMove !== $leftAncestor) {
                    $subTreeMove = $subTreeMove->getLeftSibling();

                    if (isset($subTreeMove)) {
                        $subTreeMove->setOffset($subTreeMove->getOffset() + $totalGap);
                        $subTreeMove->setModifier($subTreeMove->getModifier() + $totalGap);
                        $totalGap -= $singleGap;
                    }
                }
            }

            if ($firstChild->childCount() == 0) {
                $firstChild = $this->getLeftmost($node, 0, $j);
            } else {
                $firstChild = $firstChild->getChildAt(0);
            }

            if (isset($firstChild)) {
                $firstChildLeftNeighbour = $firstChild->getLeftSibling();
            }
        }
    }

    /**
     * Setup neighbour nodes.
     *
     * @param \Alpha\Util\Graph\GraphNode $node
     * @param int                        $level
     *
     * @since 1.0
     */
    private function setNeighbours($node, $level)
    {
        if (isset($this->previousLevelNodes[$level])) {
            $node->setLeftSibling($this->previousLevelNodes[$level]);
        }

        if ($node->getLeftSibling()) {
            $node->getLeftSibling()->setRightSibling($node);
        }
        $this->previousLevelNodes[$level] = $node;
    }

    /**
     * Get left most node in the branch.
     *
     * @param \Alpha\Util\Graph\GraphNode $node
     * @param int                        $level
     * @param int                        $maxlevel
     *
     * @return \Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    private function getLeftmost($node, $level, $maxlevel)
    {
        if ($level >= $maxlevel) {
            return $node;
        }

        $childCount = $node->childCount();

        if ($childCount == 0) {
            return;
        }

        for ($i = 0; $i < $childCount; ++$i) {
            $child = $node->getChildAt($i);

            $leftmostDescendant = $this->getLeftmost($child, $level + 1, $maxlevel);

            if (isset($leftmostDescendant)) {
                return $leftmostDescendant;
            }
        }

        return;
    }

    /**
     * Render the chart in memory.
     *
     * @since 1.0
     */
    protected function render()
    {
        $this->firstPass($this->root, 0);
        $this->secondPass($this->root, 0);

        foreach ($this->nodes as $node) {
            $node->setUpLinks();
        }

        $this->isRendered = true;
    }

    /**
     * Get the width of the graph, will invoke render() if not already rendered.
     *
     * @since 1.0
     */
    public function getWidth()
    {
        if (!$this->isRendered) {
            $this->render();
        }

        return $this->width;
    }

    /**
     * Get the heith of the graph, will invoke render() if not already rendered.
     *
     * @since 1.0
     */
    public function getHeight()
    {
        if (!$this->isRendered) {
            $this->render();
        }

        return $this->height;
    }

    /**
     * Get the next GraphNode instance in the graph, will invoke render() if not already rendered.
     *
     * @return \Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    public function next()
    {
        if (!$this->isRendered) {
            $this->render();
        }

        if (isset($this->nodes[$this->position + 1])) {
            ++$this->position;

            return $this->nodes[$this->position];
        } else {
            return;
        }
    }

    /**
     * Check to see if another GraphNode instance in the graph is available.
     *
     * @return bool
     *
     * @since 1.0
     */
    public function hasNext()
    {
        if (isset($this->nodes[$this->position + 1])) {
            return true;
        } else {
            return false;
        }
    }
}

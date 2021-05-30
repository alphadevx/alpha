<?php

namespace Alpha\Util\Graph;

use Alpha\Exception\IllegalArguementException;

/**
 * Maintains the geometry for a tree node.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class GraphNode
{
    /**
     * The id of the node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $id = 0;

    /**
     * The height of the node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $height = 0;

    /**
     * The width of the node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $width = 0;

    /**
     * The x position of the node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $x = 0;

    /**
     * The y position of the node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $y = 0;

    /**
     * The node to the left of this one.
     *
     * @var Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    private $leftNode;

    /**
     * The node to the right of this one.
     *
     * @var Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    private $rightNode;

    /**
     * An array of child nodes of this node.
     *
     * @var array
     *
     * @since 1.0
     */
    private $children = array();

    /**
     * The margin offset of the current node.
     *
     * @var int
     *
     * @since 1.0
     */
    private $offset = 0;

    /**
     * Optional positional modifier.
     *
     * @var int
     *
     * @since 1.0
     */
    private $modifier = 0;

    /**
     * Parent node of this node (if any).
     *
     * @var Alpha\Util\Graph\GraphNode
     *
     * @since 1.0
     */
    private $parentNode;

    /**
     * The text message to display on the node.
     *
     * @var string
     *
     * @since 1.0
     */
    private $message;

    /**
     * A 2D array of the coordinates of the endpoints for connectots on this node.
     *
     * @var array
     *
     * @since 1.0
     */
    private $links = array();

    /**
     * An array containing the R,G,B values for the colour of this node.
     *
     * @var array
     *
     * @since 1.0
     */
    private $nodeColour = array();

    /**
     * If the node is clickable in an image map, use this property to hold the target URL.
     *
     * @var string
     *
     * @since 1.0
     */
    private $URL;

    /**
     * Constructor.
     *
     * @param int    $id
     * @param int    $width
     * @param int    $height
     * @param string $message
     * @param array  $nodeColour
     * @param string $URL
     */
    public function __construct($id, $width, $height, $message = '', $nodeColour = array(), $URL = null)
    {
        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
        $this->message = $message;
        $this->nodeColour = $nodeColour;
        $this->URL = $URL;
    }

    /**
     * Get the node colour array.
     *
     * @since 1.0
     */
    public function getNodeColour(): array
    {
        return $this->nodeColour;
    }

    /**
     * Set the node colour array.
     *
     * @param array $nodeColour
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setNodeColour($nodeColour): void
    {
        if (is_array($nodeColour) && count($nodeColour) == 3) {
            $this->nodeColour = $nodeColour;
        } else {
            throw new IllegalArguementException('The nodeColour value passed ['.$nodeColour.'] is not a valid array!');
        }
    }

    /**
     * Get the node URL.
     *
     * @since 1.0
     */
    public function getURL(): string
    {
        return $this->URL;
    }

    /**
     * Set the node URL.
     *
     * @param string $URL
     *
     * @since 1.0
     */
    public function setURL($URL): void
    {
        $this->URL = $URL;
    }

    /**
     * Get the node text message.
     *
     * @since 1.0
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the node text message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * Get the node offset.
     *
     * @since 1.0
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set the node offset.
     *
     * @param int $offset
     *
     * @since 1.0
     */
    public function setOffset($offset): void
    {
        $this->offset = $offset;
    }

    /**
     * Get the node modifier.
     *
     * @since 1.0
     */
    public function getModifier(): int
    {
        return $this->modifier;
    }

    /**
     * Set the node modifier.
     *
     * @param int $modifier
     *
     * @since 1.0
     */
    public function setModifier($modifier): void
    {
        $this->modifier = $modifier;
    }

    /**
     * Get the number of child nodes attached to this node.
     *
     * @since 1.0
     */
    public function childCount(): int
    {
        return count($this->children);
    }

    /**
     * Get the parent node of this node (if any).
     *
     * @since 1.0
     */
    public function getParentNode(): \Alpha\Util\Graph\GraphNode
    {
        return $this->parentNode;
    }

    /**
     * Set the parent node.
     *
     * @param Alpha\Util\Graph\GraphNode $node
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setParentNode($node): void
    {
        if ($node instanceof self) {
            $this->parentNode = $node;
        } else {
            throw new IllegalArguementException('The node object passed to setParentNode is not a valid GraphNode instance!');
        }
    }

    /**
     * Get the node to the left of this one (if any).
     *
     * @since 1.0
     */
    public function getLeftSibling(): \Alpha\Util\Graph\GraphNode|null
    {
        if ($this->leftNode) {
            return $this->leftNode;
        } else {
            return null;
        }
    }

    /**
     * Sets the node to the left of this node.
     *
     * @param Alpha\Util\Graph\GraphNode $node
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setLeftSibling($node): void
    {
        if ($node instanceof self) {
            $this->leftNode = $node;
        } else {
            throw new IllegalArguementException('The node object passed to setLeftSibling is not a valid GraphNode instance!');
        }
    }

    /**
     * Get the node to the right of this one (if any).
     *
     * @since 1.0
     */
    public function getRightSibling(): \Alpha\Util\Graph\GraphNode|null
    {
        if ($this->rightNode) {
            return $this->rightNode;
        } else {
            return null;
        }
    }

    /**
     * Sets the node to the right of this node.
     *
     * @param Alpha\Util\Graph\GraphNode $node
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setRightSibling($node): void
    {
        if ($node instanceof self) {
            $this->rightNode = $node;
        } else {
            throw new IllegalArguementException('The node object passed to setRightSibling is not a valid GraphNode instance!');
        }
    }

    /**
     * Gets the child node at the index provided, or returns false if none is found.
     *
     * @param int $i
     *
     * @since 1.0
     */
    public function getChildAt($i): mixed
    {
        if (isset($this->children[$i])) {
            return $this->children[$i];
        } else {
            return false;
        }
    }

    /**
     * Calculates and returns the midpoint X coordinate of the children of this node.
     *
     * @since 1.0
     */
    public function getChildrenCenter(): float
    {
        $node = $this->getChildAt(0);
        $node1 = $this->getChildAt(count($this->children)-1);

        return $node->getOffset()+(($node1->getOffset()-$node->getOffset())+$node1->getWidth())/2;
    }

    /**
     * Returns the array of child GraphNode objects.
     *
     * @since 1.0
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Add a new node to the children array of this node.
     *
     * @param GraphNode $node
     *
     * @throws ALpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function addChild($node): void
    {
        if ($node instanceof self) {
            array_push($this->children, $node);
        } else {
            throw new IllegalArguementException('The node object passed to addChild is not a valid GraphNode instance!');
        }
    }

    /**
     * Returns the links array.
     *
     * @since 1.0
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Sets up the array of connector endpoints.
     *
     * @since 1.0
     */
    public function setUpLinks(): void
    {
        $xa = $this->x+($this->width/2);
        $ya = $this->y+$this->height;

        foreach ($this->children as $child) {
            $xd = $xc = $child->getX()+($child->getWidth()/2);
            $yd = $child->getY();
            $xb = $xa;
            $yb = $yc = $ya+($yd-$ya)/2;
            $this->links[$child->id]['xa'] = $xa;
            $this->links[$child->id]['ya'] = $ya;
            $this->links[$child->id]['xb'] = $xb;
            $this->links[$child->id]['yb'] = $yb;
            $this->links[$child->id]['xc'] = $xc;
            $this->links[$child->id]['yc'] = $yc;
            $this->links[$child->id]['xd'] = $xd;
            $this->links[$child->id]['yd'] = $yd;
        }
    }

    /**
     * Returns the node height.
     *
     * @since 1.0
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Returns the node width.
     *
     * @since 1.0
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Returns the node X-coordinate.
     *
     * @since 1.0
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Returns the node Y-coordinate.
     *
     * @since 1.0
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * Sets the node X-coordinate.
     *
     * @param int $x
     *
     * @since 1.0
     */
    public function setX($x): void
    {
        $this->x = $x;
    }

    /**
     * Sets the node Y-coordinate.
     *
     * @param int $y
     *
     * @since 1.0
     */
    public function setY($y): void
    {
        $this->y = $y;
    }

    /**
     * Returns the node ID.
     *
     * @since 3.1
     */
    public function getID(): int
    {
        return $this->id;
    }
}

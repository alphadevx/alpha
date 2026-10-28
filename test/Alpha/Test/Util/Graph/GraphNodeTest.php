<?php

/**
 * Test cases for GraphNodeTest.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2026, John Collins (founder of Alpha Framework).
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

namespace Alpha\Test\Util\Graph;

use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Graph\GraphNode;
use PHPUnit\Framework\TestCase;

class GraphNodeTest extends TestCase
{
    /**
     * @since 4.1
     */
    public function testConstructorWithDefaults(): void
    {
        $node = new GraphNode(1, 10, 20);

        $this->assertSame(1, $node->getID());
        $this->assertSame(10, $node->getWidth());
        $this->assertSame(20, $node->getHeight());
        $this->assertSame('', $node->getMessage());
        $this->assertSame(array(), $node->getNodeColour());
        $this->assertSame(0, $node->getOffset());
        $this->assertSame(0, $node->getModifier());
        $this->assertSame(0, $node->getX());
        $this->assertSame(0, $node->getY());
        $this->assertSame(array(), $node->getChildren());
        $this->assertSame(array(), $node->getLinks());
        $this->assertSame(0, $node->childCount());
    }

    /**
     * @since 4.1
     */
    public function testConstructorWithAllArguments(): void
    {
        $colour = array(1, 2, 3);
        $node = new GraphNode(99, 30, 40, 'hello', $colour, 'https://example.com');

        $this->assertSame(99, $node->getID());
        $this->assertSame(30, $node->getWidth());
        $this->assertSame(40, $node->getHeight());
        $this->assertSame('hello', $node->getMessage());
        $this->assertSame($colour, $node->getNodeColour());
        $this->assertSame('https://example.com', $node->getURL());
    }

    /**
     * @since 4.1
     */
    public function testGetNodeColour(): void
    {
        $colour = array(10, 20, 30);
        $node = new GraphNode(1, 1, 1, '', $colour);

        $this->assertSame($colour, $node->getNodeColour());
    }

    /**
     * @since 4.1
     */
    public function testSetNodeColourValid(): void
    {
        $node = new GraphNode(1, 1, 1);
        $colour = array(100, 150, 200);

        $node->setNodeColour($colour);

        $this->assertSame($colour, $node->getNodeColour());
    }

    /**
     * @since 4.1
     */
    public function testSetNodeColourThrowsWhenCountIsNotThree(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->expectException(IllegalArguementException::class);

        @$node->setNodeColour(array(1, 2));
    }

    /**
     * @since 4.1
     */
    public function testSetNodeColourThrowsWhenCountIsMoreThanThree(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->expectException(IllegalArguementException::class);

        @$node->setNodeColour(array(1, 2, 3, 4));
    }

    /**
     * @since 4.1
     */
    public function testGetURL(): void
    {
        $node = new GraphNode(1, 1, 1, '', array(), 'https://alphaframework.org');

        $this->assertSame('https://alphaframework.org', $node->getURL());
    }

    /**
     * @since 4.1
     */
    public function testSetURL(): void
    {
        $node = new GraphNode(1, 1, 1);
        $node->setURL('https://example.org');

        $this->assertSame('https://example.org', $node->getURL());
    }

    /**
     * @since 4.1
     */
    public function testGetMessage(): void
    {
        $node = new GraphNode(1, 1, 1, 'message text');

        $this->assertSame('message text', $node->getMessage());
    }

    /**
     * @since 4.1
     */
    public function testSetMessage(): void
    {
        $node = new GraphNode(1, 1, 1);
        $node->setMessage('updated');

        $this->assertSame('updated', $node->getMessage());
    }

    /**
     * @since 4.1
     */
    public function testGetOffset(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->assertSame(0, $node->getOffset());
    }

    /**
     * @since 4.1
     */
    public function testSetOffset(): void
    {
        $node = new GraphNode(1, 1, 1);
        $node->setOffset(42);

        $this->assertSame(42, $node->getOffset());
    }

    /**
     * @since 4.1
     */
    public function testGetModifier(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->assertSame(0, $node->getModifier());
    }

    /**
     * @since 4.1
     */
    public function testSetModifier(): void
    {
        $node = new GraphNode(1, 1, 1);
        $node->setModifier(7);

        $this->assertSame(7, $node->getModifier());
    }

    /**
     * @since 4.1
     */
    public function testChildCount(): void
    {
        $parent = new GraphNode(1, 10, 10);
        $child1 = new GraphNode(2, 5, 5);
        $child2 = new GraphNode(3, 5, 5);

        $this->assertSame(0, $parent->childCount());

        $parent->addChild($child1);
        $this->assertSame(1, $parent->childCount());

        $parent->addChild($child2);
        $this->assertSame(2, $parent->childCount());
    }

    /**
     * @since 4.1
     */
    public function testSetParentNodeValid(): void
    {
        $parent = new GraphNode(1, 1, 1);
        $child = new GraphNode(2, 1, 1);

        $child->setParentNode($parent);

        $this->assertSame($parent, $child->getParentNode());
    }

    /**
     * @since 4.1
     */
    public function testSetParentNodeInvalidThrows(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->expectException(\TypeError::class);

        $node->setParentNode(new \stdClass());
    }

    /**
     * @since 4.1
     */
    public function testGetLeftSiblingNull(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->assertNull($node->getLeftSibling());
    }

    /**
     * @since 4.1
     */
    public function testSetLeftSiblingValid(): void
    {
        $node = new GraphNode(1, 1, 1);
        $left = new GraphNode(2, 1, 1);

        $node->setLeftSibling($left);

        $this->assertSame($left, $node->getLeftSibling());
    }

    /**
     * @since 4.1
     */
    public function testSetLeftSiblingInvalidThrows(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->expectException(\TypeError::class);

        $node->setLeftSibling(new \stdClass());
    }

    /**
     * @since 4.1
     */
    public function testGetRightSiblingNull(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->assertNull($node->getRightSibling());
    }

    /**
     * @since 4.1
     */
    public function testSetRightSiblingValid(): void
    {
        $node = new GraphNode(1, 1, 1);
        $right = new GraphNode(2, 1, 1);

        $node->setRightSibling($right);

        $this->assertSame($right, $node->getRightSibling());
    }

    /**
     * @since 4.1
     */
    public function testSetRightSiblingInvalidThrows(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->expectException(\TypeError::class);

        $node->setRightSibling(new \stdClass());
    }

    /**
     * @since 4.1
     */
    public function testGetChildAtFound(): void
    {
        $parent = new GraphNode(1, 10, 10);
        $child = new GraphNode(2, 5, 5);
        $parent->addChild($child);

        $this->assertSame($child, $parent->getChildAt(0));
    }

    /**
     * @since 4.1
     */
    public function testGetChildAtNotFound(): void
    {
        $parent = new GraphNode(1, 10, 10);

        $this->assertFalse($parent->getChildAt(0));
    }

    /**
     * @since 4.1
     */
    public function testGetChildren(): void
    {
        $parent = new GraphNode(1, 10, 10);
        $child1 = new GraphNode(2, 5, 5);
        $child2 = new GraphNode(3, 5, 5);
        $parent->addChild($child1);
        $parent->addChild($child2);

        $children = $parent->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame($child1, $children[0]);
        $this->assertSame($child2, $children[1]);
    }

    /**
     * @since 4.1
     */
    public function testAddChildValid(): void
    {
        $parent = new GraphNode(1, 10, 10);
        $child = new GraphNode(2, 5, 5);

        $parent->addChild($child);

        $this->assertSame(1, $parent->childCount());
        $this->assertSame($child, $parent->getChildAt(0));
    }

    /**
     * @since 4.1
     */
    public function testAddChildInvalidThrows(): void
    {
        $parent = new GraphNode(1, 10, 10);

        $this->expectException(\TypeError::class);

        $parent->addChild(new \stdClass());
    }

    /**
     * @since 4.1
     */
    public function testGetChildrenCenter(): void
    {
        $parent = new GraphNode(1, 10, 10);
        $child1 = new GraphNode(2, 10, 10);
        $child1->setOffset(0);
        $child2 = new GraphNode(3, 10, 10);
        $child2->setOffset(20);

        $parent->addChild($child1);
        $parent->addChild($child2);

        $this->assertSame(15.0, $parent->getChildrenCenter());
    }

    /**
     * @since 4.1
     */
    public function testGetLinksEmpty(): void
    {
        $node = new GraphNode(1, 1, 1);

        $this->assertSame(array(), $node->getLinks());
    }

    /**
     * @since 4.1
     */
    public function testSetUpLinks(): void
    {
        $parent = new GraphNode(1, 20, 10);
        $parent->setX(0);
        $parent->setY(0);

        $child = new GraphNode(2, 10, 10);
        $child->setX(5);
        $child->setY(30);

        $parent->addChild($child);
        $parent->setUpLinks();

        $links = $parent->getLinks();

        $this->assertArrayHasKey(2, $links);
        $this->assertSame(10, $links[2]['xa']);
        $this->assertSame(10, $links[2]['ya']);
        $this->assertSame(10, $links[2]['xd']);
        $this->assertSame(10, $links[2]['xc']);
        $this->assertSame(30, $links[2]['yd']);
        $this->assertSame(20, $links[2]['yb']);
        $this->assertSame(20, $links[2]['yc']);
        $this->assertSame(10, $links[2]['xb']);
    }

    /**
     * @since 4.1
     */
    public function testSetUpLinksWithNoChildren(): void
    {
        $parent = new GraphNode(1, 20, 10);
        $parent->setUpLinks();

        $this->assertSame(array(), $parent->getLinks());
    }

    /**
     * @since 4.1
     */
    public function testGetHeight(): void
    {
        $node = new GraphNode(1, 10, 25);

        $this->assertSame(25, $node->getHeight());
    }

    /**
     * @since 4.1
     */
    public function testGetWidth(): void
    {
        $node = new GraphNode(1, 35, 10);

        $this->assertSame(35, $node->getWidth());
    }

    /**
     * @since 4.1
     */
    public function testGetXAndSetX(): void
    {
        $node = new GraphNode(1, 1, 1);
        $this->assertSame(0, $node->getX());
        $node->setX(123);
        $this->assertSame(123, $node->getX());
    }

    /**
     * @since 4.1
     */
    public function testGetYAndSetY(): void
    {
        $node = new GraphNode(1, 1, 1);
        $this->assertSame(0, $node->getY());
        $node->setY(456);
        $this->assertSame(456, $node->getY());
    }

    /**
     * @since 4.1
     */
    public function testGetID(): void
    {
        $node = new GraphNode(777, 1, 1);

        $this->assertSame(777, $node->getID());
    }
}

<?php

namespace Alpha\Test\Util\Graph;

use Alpha\Util\Graph\TreeGraph;
use Alpha\Util\Graph\GraphNode;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the TreeGraph class.
 *
 * @since 2.0.1
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
class TreeGraphTest extends TestCase
{
    public function testAdd()
    {
        $graph = new TreeGraph();
        $graph->add(1, 0, 'First child', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');

        $this->assertEquals('First child', $graph->get(1)->getMessage(), 'Testing the add method');
    }

    public function testNext()
    {
        $graph = new TreeGraph();
        $graph->add(1, 0, 'First child', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');
        $graph->add(2, 0, 'Second child', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');

        $this->assertTrue($graph->hasNext(), 'Testing the hasNext method');
        $this->assertEquals('First child', $graph->next()->getMessage(), 'Testing the next method');
        $this->assertTrue($graph->hasNext(), 'Testing the hasNext method');
        $this->assertEquals('Second child', $graph->next()->getMessage(), 'Testing the next method');
        $this->assertFalse($graph->hasNext(), 'Testing the hasNext method');
    }

    public function testWithLeftSibling()
    {
        $graph = new TreeGraph();
        $graph->add(1, 0, 'First child', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');
        $graph->add(2, 0, 'Second child', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');
        $graph->add(3, 1, 'First grandchild', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');
        $graph->add(4, 2, 'Second grandchild', 10, 10, array(0, 0, 0), 'http://www.alphaframework.org/');

        $node = new GraphNode(5, 0, 10, 'Left node', array(0, 0, 0), 'http://www.alphaframework.org/');

        $graph->get(0)->setLeftSibling($node);

        $this->assertTrue($graph->hasNext(), 'Testing the hasNext method');
        $this->assertTrue($graph->next() instanceof GraphNode, 'Testing the next method');
        $this->assertEquals(100, $graph->getWidth(), 'Testing the getWidth method');
        $this->assertEquals(100, $graph->getHeight(), 'Testing the getHeight method');
    }
}

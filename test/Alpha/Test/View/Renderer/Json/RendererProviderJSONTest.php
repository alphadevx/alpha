<?php

namespace Alpha\Test\View\Renderer\Json;

use Alpha\Model\ActiveRecord;
use Alpha\View\Renderer\Json\RendererProviderJSON;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the RendererProviderJSON class.
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
class RendererProviderJSONTest extends TestCase
{
    /**
     * The renderer under test.
     *
     * @var \Alpha\View\Renderer\Json\RendererProviderJSON
     *
     * @since 4.1
     */
    private RendererProviderJSON $renderer;

    /**
     * Called before the test functions will be executed.
     *
     * @since 4.1
     */
    protected function setUp(): void
    {
        $this->renderer = new RendererProviderJSON();

        $record = $this->createMock(ActiveRecord::class);
        $record->method('toArray')->willReturn(array('ID' => '00000000011', 'summary' => 'A test summary'));

        $this->renderer->setRecord($record);
    }

    /**
     * Testing that the create view is not rendered as JSON.
     *
     * @since 4.1
     */
    public function testCreateView(): void
    {
        $this->assertEquals('', $this->renderer->createView(), 'Testing that the create view is not rendered as JSON');
    }

    /**
     * Testing that the edit view is not rendered as JSON.
     *
     * @since 4.1
     */
    public function testEditView(): void
    {
        $this->assertEquals('', $this->renderer->editView(), 'Testing that the edit view is not rendered as JSON');
    }

    /**
     * Testing that the list view renders the record as JSON with a trailing comma.
     *
     * @since 4.1
     */
    public function testListView(): void
    {
        $json = $this->renderer->listView();

        $this->assertTrue(str_ends_with($json, ','), 'Testing that the list view ends with a comma');

        $decoded = json_decode(substr($json, 0, -1), true);

        $this->assertEquals('00000000011', $decoded['ID'], 'Testing that the list view renders the record ID as JSON');
        $this->assertEquals('A test summary', $decoded['summary'], 'Testing that the list view renders the record summary as JSON');
    }

    /**
     * Testing that the detailed view renders the record as JSON.
     *
     * @since 4.1
     */
    public function testDetailedView(): void
    {
        $decoded = json_decode($this->renderer->detailedView(), true);

        $this->assertEquals('00000000011', $decoded['ID'], 'Testing that the detailed view renders the record ID as JSON');
        $this->assertEquals('A test summary', $decoded['summary'], 'Testing that the detailed view renders the record summary as JSON');
    }

    /**
     * Testing that the admin view is not rendered as JSON.
     *
     * @since 4.1
     */
    public function testAdminView(): void
    {
        $this->assertEquals('', $this->renderer->adminView(), 'Testing that the admin view is not rendered as JSON');
    }

    /**
     * Testing that the page head is not rendered as JSON.
     *
     * @since 4.1
     */
    public function testDisplayPageHead(): void
    {
        $this->assertEquals('', RendererProviderJSON::displayPageHead(null), 'Testing that the page head is not rendered as JSON');
    }

    /**
     * Testing that the page foot is not rendered as JSON.
     *
     * @since 4.1
     */
    public function testDisplayPageFoot(): void
    {
        $this->assertEquals('', RendererProviderJSON::displayPageFoot(null), 'Testing that the page foot is not rendered as JSON');
    }

    /**
     * Testing that update messages are rendered as JSON.
     *
     * @since 4.1
     */
    public function testDisplayUpdateMessage(): void
    {
        $decoded = json_decode(RendererProviderJSON::displayUpdateMessage('All good!'), true);

        $this->assertEquals('All good!', $decoded['message'], 'Testing that update messages are rendered as JSON');
    }

    /**
     * Testing that error messages are rendered as JSON.
     *
     * @since 4.1
     */
    public function testDisplayErrorMessage(): void
    {
        $decoded = json_decode(RendererProviderJSON::displayErrorMessage('All bad!', true), true);

        $this->assertEquals('All bad!', $decoded['message'], 'Testing that error messages are rendered as JSON');
    }

    /**
     * Testing that error pages are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderErrorPage(): void
    {
        $this->assertEquals('', RendererProviderJSON::renderErrorPage(404, 'Not found!'), 'Testing that error pages are not rendered as JSON');
    }

    /**
     * Testing that delete forms are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderDeleteForm(): void
    {
        $this->assertEquals('', RendererProviderJSON::renderDeleteForm('/delete'), 'Testing that delete forms are not rendered as JSON');
    }

    /**
     * Testing that security fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderSecurityFields(): void
    {
        $this->assertEquals('', RendererProviderJSON::renderSecurityFields(), 'Testing that security fields are not rendered as JSON');
    }

    /**
     * Testing that integer fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderIntegerField(): void
    {
        $this->assertEquals('', $this->renderer->renderIntegerField('age', 'Age', 'edit', '42'), 'Testing that integer fields are not rendered as JSON');
    }

    /**
     * Testing that double fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderDoubleField(): void
    {
        $this->assertEquals('', $this->renderer->renderDoubleField('price', 'Price', 'edit', '4.2'), 'Testing that double fields are not rendered as JSON');
    }

    /**
     * Testing that boolean fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderBooleanField(): void
    {
        $this->assertEquals('', $this->renderer->renderBooleanField('active', 'Active', 'edit', '1'), 'Testing that boolean fields are not rendered as JSON');
    }

    /**
     * Testing that enum fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderEnumField(): void
    {
        $this->assertEquals('', $this->renderer->renderEnumField('size', 'Size', 'edit', array('S', 'M'), 'M'), 'Testing that enum fields are not rendered as JSON');
    }

    /**
     * Testing that DEnum fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderDEnumField(): void
    {
        $this->assertEquals('', $this->renderer->renderDEnumField('size', 'Size', 'edit', array('S', 'M'), 'M'), 'Testing that DEnum fields are not rendered as JSON');
    }

    /**
     * Testing that default fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderDefaultField(): void
    {
        $this->assertEquals('', $this->renderer->renderDefaultField('thing', 'Thing', 'edit', 'value'), 'Testing that default fields are not rendered as JSON');
    }

    /**
     * Testing that text fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderTextField(): void
    {
        $this->assertEquals('', $this->renderer->renderTextField('body', 'Body', 'edit', 'text'), 'Testing that text fields are not rendered as JSON');
    }

    /**
     * Testing that string fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderStringField(): void
    {
        $this->assertEquals('', $this->renderer->renderStringField('title', 'Title', 'edit', 'string'), 'Testing that string fields are not rendered as JSON');
    }

    /**
     * Testing that relation fields are not rendered as JSON.
     *
     * @since 4.1
     */
    public function testRenderRelationField(): void
    {
        $this->assertEquals('', $this->renderer->renderRelationField('author', 'Author', 'edit', '1'), 'Testing that relation fields are not rendered as JSON');
    }

    /**
     * Testing that all fields render as JSON with the filter fields removed.
     *
     * @since 4.1
     */
    public function testRenderAllFields(): void
    {
        $decoded = json_decode($this->renderer->renderAllFields('edit', array('A test summary')), true);

        $this->assertEquals('00000000011', $decoded['ID'], 'Testing that all fields render as JSON');
        $this->assertArrayNotHasKey('summary', $decoded, 'Testing that filtered fields are removed from the rendered JSON');
    }
}

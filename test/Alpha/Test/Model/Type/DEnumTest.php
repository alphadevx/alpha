<?php

namespace Alpha\Test\Model\Type;

use Alpha\Test\Model\ModelTestCase;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Exception\AlphaException;
use Alpha\Util\Config\Configprovider;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the DEnum data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2024, John Collins (founder of Alpha Framework).
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
class DEnumTest extends ModelTestCase
{
    /**
     * A DEnum for testing.
     *
     * @var DEnum
     *
     * @since 1.0
     */
    protected $denum1;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);

            $denum = new DEnum();
            $denum->rebuildTable();
            $item = new DEnumItem();
            $item->rebuildTable();

            $this->denum1 = new DEnum('Alpha\Model\Article::section');
            $item->set('DEnumID', $this->denum1->getID());
            $item->set('value', 'Test');
            $item->save();
        }
    }

    /**
     * Test to check that the denum options loaded from the database.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDEnumLoadedOptionsFromDB(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertGreaterThan(0, count($this->denum1->getOptions()), 'test to check that the denum options loaded from the database');
    }

    /**
     * Testing the setValue method with a bad options array index value.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSetValueInvalid(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $this->denum1->setValue('blah');
            $this->fail('testing the setValue method with a bad options array index value');
        } catch (AlphaException $e) {
            $this->assertEquals('Not a valid denum option!', $e->getMessage(), 'testing the setValue method with a bad options array index value');
        }
    }

    /**
     * Testing the setValue method with a good options index array value.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSetValueValid(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $options = $this->denum1->getOptions();
            $optionIDs = array_keys($options);
            $this->denum1->setValue($optionIDs[0]);
            $this->assertEquals('00000000001', $this->denum1->getValue());
        } catch (AlphaException $e) {
            $this->fail('testing the setValue method with a good options index array value, exception: '.$e->getMessage());
        }
    }

    /**
     * Testing the getDisplayValue method.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetDisplayValue(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $options = $this->denum1->getOptions();
            $optionIDs = array_keys($options);
            $this->denum1->setValue($optionIDs[0]);

            $this->assertEquals($options[$optionIDs[0]], $this->denum1->getDisplayValue(), 'testing the getDisplayValue method');
        } catch (AlphaException $e) {
            $this->fail('testing the getDisplayValue method, exception: '.$e->getMessage());
        }
    }

    /**
     * Testing the getOptionID method.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetOptionID(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $options = $this->denum1->getOptions();
            $optionIDs = array_keys($options);

            $this->assertEquals($optionIDs[0], $this->denum1->getOptionID($options[$optionIDs[0]]), 'testing the getOptionID method');
        } catch (AlphaException $e) {
            $this->fail('testing the getOptionID method, exception: '.$e->getMessage());
        }
    }

    /**
     * Testing the getItemCount method.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetItemCount(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $options = $this->denum1->getOptions();

        $this->assertEquals(count($options), $this->denum1->getItemCount(), 'testing the getItemCount method');
    }

    /**
     * Testing the DEnumItem::loadItems method directly.
     *
     * @since 1.2.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDEnumItemLoadItems(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $DEnumID = $this->denum1->getID();
        $item = new DEnumItem();
        $items = $item->loadItems($DEnumID);

        $this->assertGreaterThan(0, count($items), 'testing the DEnumItem::loadItems method directly');
    }
}

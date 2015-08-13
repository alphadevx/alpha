<?php

namespace Alpha\Test\Util\File;

use Alpha\Util\File\FileUtils;

/**
 *
 * Test cases for the FileUtils class
 *
 * @since 2.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
 *
 */
class FileUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the getMIMETypeByExtension() method
     *
     * @since 2.0
     */
    public function testGetMIMETypeByExtension()
    {
        $this->assertEquals('text/html', FileUtils::getMIMETypeByExtension('htm'), 'Testing the getMIMETypeByExtension() method');
        $this->assertEquals('image/png', FileUtils::getMIMETypeByExtension('png'), 'Testing the getMIMETypeByExtension() method');
        $this->assertEquals('application/zip', FileUtils::getMIMETypeByExtension('zip'), 'Testing the getMIMETypeByExtension() method');
    }

    /**
     * Testing the listDirectoryContents() method
     *
     * @since 2.0
     */
    public function testListDirectoryContents()
    {
        $fileList = '';
        $this->assertTrue(FileUtils::listDirectoryContents('.', $fileList, 0, array()) > 0, 'Testing the listDirectoryContents() method');
        $this->assertTrue(strpos($fileList,'</em><br>') !== false, 'Testing the listDirectoryContents() method');
    }

    /**
     * Testing the deleteDirectoryContents() method
     *
     * @since 2.0
     */
    public function testDeleteDirectoryContents()
    {
        if (!file_exists('/tmp/alphatestdir')) {
            mkdir('/tmp/alphatestdir');
        }
        mkdir('/tmp/alphatestdir/subdir');

        $this->assertTrue(file_exists('/tmp/alphatestdir/subdir'), 'Testing the deleteDirectoryContents() method');

        FileUtils::deleteDirectoryContents('/tmp/alphatestdir');

        $this->assertFalse(file_exists('/tmp/alphatestdir/subdir'), 'Testing the deleteDirectoryContents() method');
    }

    /**
     * Testing the copy() method
     *
     * @since 2.0
     */
    public function testCopy()
    {
        FileUtils::copy('./public/images/logo-small.png', '/tmp/logo-small.png');

        $this->assertTrue(file_exists('/tmp/logo-small.png'), 'Testing the copy() method');
    }

    /**
     * Testing the zip() method
     *
     * @since 2.0
     */
    public function testZip()
    {
        FileUtils::zip('./public/images/logo-small.png', '/tmp/logo-small.zip');

        $this->assertTrue(file_exists('/tmp/logo-small.zip'), 'Testing the zip() method');
    }
}

?>
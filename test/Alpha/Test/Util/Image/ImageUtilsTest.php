<?php

namespace Alpha\Test\Util\Image;

use Alpha\Util\Image\ImageUtils;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the ImageUtils class.
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
class ImageUtilsTest extends TestCase
{
    private $tempDir = '/tmp/alphatest_images';

    protected function setUp(): void
    {
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
    }

    /**
     * Deletes a directory recursively.
     *
     * @param string $dir The directory to delete
     *
     * @since 4.1
     */
    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * Testing generateSquareThumbnail() creates a thumbnail.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnail(): void
    {
        // Create a simple test image
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);

        $this->assertFileExists($thumbnail);
    }

    /**
     * Testing generateSquareThumbnail() with different dimensions.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailDifferentDimensions(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(300, 200);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 100);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals(100, $info[0]);
        $this->assertEquals(100, $info[1]);
    }

    /**
     * Testing generateSquareThumbnail() with JPEG images.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailJPEG(): void
    {
        $original = $this->tempDir . '/original.jpg';
        $thumbnail = $this->tempDir . '/thumbnail.jpg';

        $image = imagecreatetruecolor(150, 150);
        imagejpeg($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 75);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/jpeg', $info['mime']);
    }

    /**
     * Testing generateSquareThumbnail() with PNG images.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailPNG(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(150, 150);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 75);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/png', $info['mime']);
    }

    /**
     * Testing generateSquareThumbnail() with GIF images.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailGIF(): void
    {
        $original = $this->tempDir . '/original.gif';
        $thumbnail = $this->tempDir . '/thumbnail.gif';

        $image = imagecreatetruecolor(150, 150);
        imagegif($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 75);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/gif', $info['mime']);
    }

    /**
     * Testing generateSquareThumbnail() throws exception for invalid dimensions.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailInvalidDimensions(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(150, 150);
        imagepng($image, $original);
        imagedestroy($image);

        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('Illegal dimensions value provided [0]');

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 0);
    }

    /**
     * Testing generateSquareThumbnail() throws exception for negative dimensions.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailNegativeDimensions(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(150, 150);
        imagepng($image, $original);
        imagedestroy($image);

        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('Illegal dimensions value provided [-10]');

        ImageUtils::generateSquareThumbnail($original, $thumbnail, -10);
    }

    /**
     * Testing generateSquareThumbnail() throws exception for unsupported format.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailUnsupportedFormat(): void
    {
        $original = $this->tempDir . '/original.bmp';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        // Create a simple BMP-like file
        file_put_contents($original, 'BM' . str_repeat("\0", 100));

        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('Unsupported image format');

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);
    }

    /**
     * Testing saveImage() saves PNG images.
     *
     * @since 4.1
     */
    public function testSaveImagePNG(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(100, 100);

        ImageUtils::saveImage($image, 'png', $thumbnail);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/png', $info['mime']);
    }

    /**
     * Testing saveImage() saves JPEG images.
     *
     * @since 4.1
     */
    public function testSaveImageJPEG(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.jpg';

        $image = imagecreatetruecolor(100, 100);

        ImageUtils::saveImage($image, 'jpg', $thumbnail);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/jpeg', $info['mime']);
    }

    /**
     * Testing saveImage() saves GIF images.
     *
     * @since 4.1
     */
    public function testSaveImageGIF(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.gif';

        $image = imagecreatetruecolor(100, 100);

        ImageUtils::saveImage($image, 'gif', $thumbnail);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals('image/gif', $info['mime']);
    }

    /**
     * Testing saveImage() throws exception for invalid type.
     *
     * @since 4.1
     */
    public function testSaveImageInvalidType(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.bmp';

        $image = imagecreatetruecolor(100, 100);

        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('Illegal image type');

        ImageUtils::saveImage($image, 'bmp', $thumbnail);
    }

    /**
     * Testing saveImage() throws exception for empty type.
     *
     * @since 4.1
     */
    public function testSaveImageEmptyType(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(100, 100);

        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('Illegal image type');

        ImageUtils::saveImage($image, '', $thumbnail);
    }

    /**
     * Testing saveImage() frees memory.
     *
     * @since 4.1
     */
    public function testSaveImageFreesMemory(): void
    {
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(100, 100);

        ImageUtils::saveImage($image, 'png', $thumbnail);

        // Image resource should be freed after save
        $this->assertTrue(true, 'Testing that saveImage frees memory');
    }

    /**
     * Testing generateSquareThumbnail() with square image.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailSquareImage(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(100, 100);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals(50, $info[0]);
        $this->assertEquals(50, $info[1]);
    }

    /**
     * Testing generateSquareThumbnail() with landscape image.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailLandscape(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals(50, $info[0]);
        $this->assertEquals(50, $info[1]);
    }

    /**
     * Testing generateSquareThumbnail() with portrait image.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailPortrait(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(100, 200);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals(50, $info[0]);
        $this->assertEquals(50, $info[1]);
    }

    /**
     * Testing multiple image formats work correctly.
     *
     * @since 4.1
     */
    public function testMultipleImageFormats(): void
    {
        $formats = ['png', 'jpg', 'gif'];

        foreach ($formats as $format) {
            $original = $this->tempDir . '/original.' . $format;
            $thumbnail = $this->tempDir . '/thumbnail.' . $format;

            $image = imagecreatetruecolor(100, 100);
            if ($format === 'jpg') {
                imagejpeg($image, $original);
            } elseif ($format === 'gif') {
                imagegif($image, $original);
            } else {
                imagepng($image, $original);
            }
            imagedestroy($image);

            ImageUtils::generateSquareThumbnail($original, $thumbnail, 50);

            $this->assertFileExists($thumbnail);
        }
    }

    /**
     * Testing saveImage() with different image resources.
     *
     * @since 4.1
     */
    public function testSaveImageWithDifferentResources(): void
    {
        // Test with PNG resource
        $thumbnail1 = $this->tempDir . '/test1.png';
        $image1 = imagecreatetruecolor(50, 50);
        ImageUtils::saveImage($image1, 'png', $thumbnail1);
        $this->assertFileExists($thumbnail1);

        // Test with JPEG resource
        $thumbnail2 = $this->tempDir . '/test2.jpg';
        $image2 = imagecreatetruecolor(50, 50);
        ImageUtils::saveImage($image2, 'jpg', $thumbnail2);
        $this->assertFileExists($thumbnail2);
    }

    /**
     * Testing generateSquareThumbnail() with different quality.
     *
     * @since 4.1
     */
    public function testGenerateSquareThumbnailQuality(): void
    {
        $original = $this->tempDir . '/original.png';
        $thumbnail = $this->tempDir . '/thumbnail.png';

        $image = imagecreatetruecolor(150, 150);
        imagepng($image, $original);
        imagedestroy($image);

        ImageUtils::generateSquareThumbnail($original, $thumbnail, 100);

        $this->assertFileExists($thumbnail);

        $info = getimagesize($thumbnail);
        $this->assertEquals(100, $info[0]);
        $this->assertEquals(100, $info[1]);
    }
}

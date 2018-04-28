<?php

namespace Alpha\Util\Code\Metric;

use \Alpha\Util\File\FileUtils;

/**
 * Utility class for calculating some software metrics related to a project.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
class Inspector
{
    /**
     * The directory to begin the calculations from.
     *
     * @var string
     *
     * @since 1.0
     */
    private $rootDir;

    /**
     * The file extensions of the file types to include in the calculations.
     *
     * @var array
     *
     * @since 1.0
     */
    private $includeFileTypes = array('.php', '.html', '.phtml', '.inc', '.js', '.css', '.xml');

    /**
     * Any sub-directories which you might want to exclude from the calculations.
     *
     * @var array
     *
     * @since 1.0
     */
    private $excludeSubDirectories = array('cache', 'lib', 'docs', 'attachments', 'dist', 'vendor');

    /**
     * The Total Lines of Code (TLOC) for the project.
     *
     * @var int
     *
     * @since 1.0
     */
    private $TLOC = 0;

    /**
     * The Total Lines of Code (TLOC) for the project, less comments defined in $comments.
     *
     * @var int
     *
     * @since 1.0
     */
    private $TLOCLessComments = 0;

    /**
     * The count of the source code files in the project.
     *
     * @var int
     *
     * @since 1.0
     */
    private $fileCount = 0;

    /**
     * An array of fileName => lines of code to be populated by this class.
     *
     * @var array
     *
     * @since 1.0
     */
    private $filesLOCResult = array();

    /**
     * An array of fileName => lines of code to be populated by this class,
     * excluding comment lines defined in the $comments array.
     *
     * @var array
     *
     * @since 1.0
     */
    private $filesLOCNoCommentsResult = array();

    /**
     * An array of the source code file names in the project.
     *
     * @var array
     *
     * @since 1.0
     */
    private $files = array();

    /**
     * An array of the first characters of a comment line in source code.
     *
     * @var array
     *
     * @since 1.0
     */
    private $comments = array('/', '*', '#');

    /**
     * Constructor, default $rootDir is .
     *
     * @param string $rootDir
     *
     * @since 1.0
     */
    public function __construct($rootDir = '.')
    {
        $this->rootDir = $rootDir;
        $this->files = FileUtils::getDirectoryContents($rootDir, $this->files);
    }

    /**
     * Calculates the Lines of Code (LOC).
     *
     * @since 1.0
     */
    public function calculateLOC()
    {
        foreach ($this->files as $dir => $fileArray) {
            foreach ($fileArray as $file) {
                $file = $dir.'/'.$file;
                $fileType = mb_substr($file, mb_strrpos($file, '.'));
                if (in_array($fileType, $this->includeFileTypes)) {
                    $exclude = false;
                    foreach ($this->excludeSubDirectories as $excludedDir) {
                        if (preg_match('/'.$excludedDir.'/i', $file)) {
                            $exclude = true;
                        }
                    }

                    if (!$exclude) {
                        $currentFile = file($file);

                        $LOC = count($currentFile);
                        $this->filesLOCResult[$file] = $LOC;
                        $LOCLessComments = $this->disregardCommentsLOC($file);
                        $this->filesLOCNoCommentsResult[$file] = $LOCLessComments;

                        $this->TLOC += $LOC;
                        $this->TLOCLessComments += $LOCLessComments;
                        ++$this->fileCount;
                    }
                }
            }
        }
    }

    /**
     * Generates a HTML table containing the metrics results.
     *
     * @return string
     *
     * @since 1.0
     */
    public function resultsToHTML()
    {
        $count = 1;

        $html = '<table class="table table-striped table-hover"><tr>';
        $html .= '<th style="width:10%;">File #:</th>';
        $html .= '<th style="width:50%;">File name:</th>';
        $html .= '<th style="width:20%;">Lines of Code (LOC):</th>';
        $html .= '<th style="width:20%;">Lines of Code (less comments):</th>';
        $html .= '</tr>';
        foreach (array_keys($this->filesLOCResult) as $result) {
            $html .= "<tr><td>$count</td><td>$result</td><td>".$this->filesLOCResult[$result].'</td><td>'.$this->filesLOCNoCommentsResult[$result].'</td></tr>';
            ++$count;
        }
        $html .= '</table>';

        $html .= '<p>Total files: '.number_format(count($this->files)).'</p>';
        $html .= '<p>Total source code files: '.number_format($this->fileCount).'</p>';
        $html .= '<p>Total Lines of Code (TLOC): '.number_format($this->TLOC).'</p>';
        $html .= '<p>Total Lines of Code (TLOC), less comments: '.number_format($this->TLOCLessComments).'</p>';

        return $html;
    }

    /**
     * Filters comments from LOC metric.
     *
     * @param string $sourceFile
     *
     * @return int
     *
     * @since 1.0
     */
    private function disregardCommentsLOC($sourceFile)
    {
        $file = file($sourceFile);

        $LOC = 0;

        foreach ($file as $line) {
            $exclude = false;
            $line = ltrim($line);

            if (empty($line)) {
                $exclude = true;
            } else {
                foreach ($this->comments as $comment) {
                    if (mb_substr($line, 0, 1) == $comment) {
                        $exclude = true;
                    }
                }
            }

            if (!$exclude) {
                ++$LOC;
            }
        }

        return $LOC;
    }
}

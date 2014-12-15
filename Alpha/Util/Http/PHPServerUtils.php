<?php

namespace Alpha\Util\Http;

use Alpha\Exception\AlphaException;

/**
 * A utility class controlling the build-in HTTP server in PHP
 *
 * @since 1.2.2
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
class PHPServerUtils
{
    /**
     * Starts a new HTTP server at the hostname and port provided, and returns the process ID (PID) of
     * the service if it was started successfully.
     *
     * @param string $host The hostname or IP address
     * @param int $port The port number to use
     * @param string $docRoot The file path to directory containing the documents we want to serve
     * @return int The PID of the new server
     * @throws AlphaException
     */
    public static function start($host, $port, $docRoot)
    {
        // we are on Windows
        if (mb_strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
            // Command that starts the built-in web server
            $command = sprintf(
                'php -S %s:%d -t %s',
                $host,
                $port,
                $docRoot
            );

            $descriptorspec = array (
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
            );

            // Execute the command and store the process ID of the parent
            $prog = proc_open($command, $descriptorspec, $pipes, '.', NULL);
            $ppid = proc_get_status($prog)['pid'];

            // this gets us the process ID of the child (i.e. the server we just started)
            $output = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"$ppid\"")));
            array_pop($output);
            $pid = end($output);
        } else { // we are on Linux
            // Command that starts the built-in web server
            $command = sprintf(
                'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
                $host,
                $port,
                $docRoot
            );

            // Execute the command and store the process ID
            $output = array();
            exec($command, $output);
            $pid = (int) $output[0];
        }

        if (!isset($pid))
            throw new AlphaException("Could not start the build-in PHP server [$host:$port] using the doc root [$docRoot]");
        else
            return $pid;

    }

    /**
     * Stops the server running locally under the process ID (PID) provided.
     *
     * @param int $PID The PID of the running server we want to stop
     */
    public static function stop($PID)
    {
        // we are on Windows
        if (mb_strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN')
            exec("taskkill /F /pid $PID");
        else // we are on Linux
            exec("kill -9 $PID");
    }

    /**
     * Checks to see if there is a server running locally under the process ID (PID) provided.
     *
     * @param int $PID The PID of the running server we want to check
     * @return bool True if there is a server process running under the PID, false otherwise
     */
    public static function status($PID)
    {
        $output = array();
        // we are on Windows
        if (mb_strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("tasklist /fi \"PID eq $PID\"", $output);

            if (isset($output[0]) && $output[0] == 'INFO: No tasks are running which match the specified criteria.')
                return false;
            else
                return true;
        } else { // we are on Linux
            exec("ps -ef | grep $PID | grep -v grep", $output);

            if(count($output) == 0)
                return false;
            else
                return true;
        }
    }

}

?>
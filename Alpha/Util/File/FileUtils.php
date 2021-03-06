<?php

namespace Alpha\Util\File;

use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;
use DirectoryIterator;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * A utility class for carrying out various file system tasks.
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
class FileUtils
{
    /**
     * A hash array for mapping file extensions to MIME types.
     *
     * @var array
     *
     * @since 1.0
     */
    private static $extensionToMIMEMappings = array(
        '3dm' => 'x-world/x-3dmf',
        '3dmf' => 'x-world/x-3dmf',
        'a' => 'application/octet-stream',
        'aab' => 'application/x-authorware-bin',
        'aam' => 'application/x-authorware-map',
        'aas' => 'application/x-authorware-seg',
        'abc' => 'text/vnd.abc',
        'acgi' => 'text/html',
        'afl' => 'video/animaflex',
        'ai' => 'application/postscript',
        'aif' => 'audio/aiff',
        'aifc' => 'audio/aiff',
        'aiff' => 'audio/aiff',
        'aim' => 'application/x-aim',
        'aip' => 'text/x-audiosoft-intra',
        'ani' => 'application/x-navi-animation',
        'aos' => 'application/x-nokia-9000-communicator-add-on-software',
        'aps' => 'application/mime',
        'arc' => 'application/octet-stream',
        'arj' => 'application/arj',
        'art' => 'image/x-jg',
        'asf' => 'video/x-ms-asf',
        'asm' => 'text/x-asm',
        'asp' => 'text/asp',
        'asx' => 'application/x-mplayer2',
        'au' => 'audio/basic',
        'avi' => 'application/x-troff-msvideo',
        'avs' => 'video/avs-video',
        'bcpio' => 'application/x-bcpio',
        'bin' => 'application/octet-stream',
        'bm' => 'image/bmp',
        'bmp' => 'image/bmp',
        'boo' => 'application/book',
        'book' => 'application/book',
        'boz' => 'application/x-bzip2',
        'bsh' => 'application/x-bsh',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'c' => 'text/plain',
        'c++' => 'text/plain',
        'cat' => 'application/vnd.ms-pki.seccat',
        'cc' => 'text/plain',
        'ccad' => 'application/clariscad',
        'cco' => 'application/x-cocoa',
        'cdf' => 'application/cdf',
        'cer' => 'application/pkix-cert',
        'cha' => 'application/x-chat',
        'chat' => 'application/x-chat',
        'class' => 'application/java',
        'com' => 'application/octet-stream',
        'conf' => 'text/plain',
        'cpio' => 'application/x-cpio',
        'cpp' => 'text/x-c',
        'cpt' => 'application/mac-compactpro',
        'crl' => 'application/pkcs-crl',
        'crt' => 'application/pkix-cert',
        'csh' => 'application/x-csh',
        'css' => 'application/x-pointplus',
        'cxx' => 'text/plain',
        'dcr' => 'application/x-director',
        'deepv' => 'application/x-deepv',
        'def' => 'text/plain',
        'der' => 'application/x-x509-ca-cert',
        'dif' => 'video/x-dv',
        'dir' => 'application/x-director',
        'dl' => 'video/dl',
        'doc' => 'application/msword',
        'dot' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dp' => 'application/commonground',
        'drw' => 'application/drafting',
        'dump' => 'application/octet-stream',
        'dv' => 'video/x-dv',
        'dvi' => 'application/x-dvi',
        'dwf' => 'drawing/x-dwf (old)',
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'dxr' => 'application/x-director',
        'el' => 'text/x-script.elisp',
        'elc' => 'application/x-bytecode.elisp (compiled elisp)',
        'env' => 'application/x-envoy',
        'eps' => 'application/postscript',
        'es' => 'application/x-esrehber',
        'etx' => 'text/x-setext',
        'evy' => 'application/envoy',
        'exe' => 'application/octet-stream',
        'f' => 'text/plain',
        'f77' => 'text/x-fortran',
        'f90' => 'text/plain',
        'fdf' => 'application/vnd.fdf',
        'fif' => 'application/fractals',
        'fli' => 'video/fli',
        'flo' => 'image/florian',
        'flx' => 'text/vnd.fmi.flexstor',
        'fmf' => 'video/x-atomic3d-feature',
        'for' => 'text/plain',
        'fpx' => 'image/vnd.fpx',
        'frl' => 'application/freeloader',
        'funk' => 'audio/make',
        'g' => 'text/plain',
        'g3' => 'image/g3fax',
        'gif' => 'image/gif',
        'gl' => 'video/gl',
        'gsd' => 'audio/x-gsm',
        'gsm' => 'audio/x-gsm',
        'gsp' => 'application/x-gsp',
        'gss' => 'application/x-gss',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-compressed',
        'gzip' => 'application/x-gzip',
        'h' => 'text/plain',
        'hdf' => 'application/x-hdf',
        'help' => 'application/x-helpfile',
        'hgl' => 'application/vnd.hp-hpgl',
        'hh' => 'text/plain',
        'hlb' => 'text/x-script',
        'hlp' => 'application/hlp',
        'hpg' => 'application/vnd.hp-hpgl',
        'hpgl' => 'application/vnd.hp-hpgl',
        'hqx' => 'application/binhex',
        'hta' => 'application/hta',
        'htc' => 'text/x-component',
        'htm' => 'text/html',
        'html' => 'text/html',
        'htmls' => 'text/html',
        'htt' => 'text/webviewhtml',
        'htx' => 'text/html',
        'ice' => 'x-conference/x-cooltalk',
        'ico' => 'image/x-icon',
        'idc' => 'text/plain',
        'ief' => 'image/ief',
        'iefs' => 'image/ief',
        'iges' => 'application/iges',
        'igs' => 'application/iges',
        'ima' => 'application/x-ima',
        'imap' => 'application/x-httpd-imap',
        'inf' => 'application/inf',
        'ins' => 'application/x-internett-signup',
        'ip' => 'application/x-ip2',
        'isu' => 'video/x-isvideo',
        'it' => 'audio/it',
        'iv' => 'application/x-inventor',
        'ivr' => 'i-world/i-vrml',
        'ivy' => 'application/x-livescreen',
        'jam' => 'audio/x-jam',
        'jav' => 'text/plain',
        'java' => 'text/plain',
        'jcm' => 'application/x-java-commerce',
        'jfif' => 'image/jpeg',
        'jfif-tbnl' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jps' => 'image/x-jps',
        'js' => 'application/x-javascript',
        'jut' => 'image/jutvision',
        'kar' => 'audio/midi',
        'ksh' => 'application/x-ksh',
        'la' => 'audio/nspaudio',
        'lam' => 'audio/x-liveaudio',
        'latex' => 'application/x-latex',
        'lha' => 'application/lha',
        'lhx' => 'application/octet-stream',
        'list' => 'text/plain',
        'lma' => 'audio/nspaudio',
        'log' => 'text/plain',
        'lsp' => 'application/x-lisp',
        'lst' => 'text/plain',
        'lsx' => 'text/x-la-asf',
        'ltx' => 'application/x-latex',
        'lzh' => 'application/octet-stream',
        'lzx' => 'application/lzx',
        'm' => 'text/plain',
        'm1v' => 'video/mpeg',
        'm2a' => 'audio/mpeg',
        'm2v' => 'video/mpeg',
        'm3u' => 'audio/x-mpequrl',
        'man' => 'application/x-troff-man',
        'map' => 'application/x-navimap',
        'mar' => 'text/plain',
        'mbd' => 'application/mbedlet',
        'mc$' => 'application/x-magic-cap-package-1.0',
        'mcd' => 'application/mcad',
        'mcf' => 'image/vasa',
        'mcp' => 'application/netmc',
        'me' => 'application/x-troff-me',
        'mht' => 'message/rfc822',
        'mhtml' => 'message/rfc822',
        'mid' => 'application/x-midi',
        'midi' => 'application/x-midi',
        'mif' => 'application/x-frame',
        'mime' => 'message/rfc822',
        'mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
        'mjpg' => 'video/x-motion-jpeg',
        'mm' => 'application/base64',
        'mme' => 'application/base64',
        'mod' => 'audio/mod',
        'moov' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg3',
        'mpa' => 'audio/mpeg',
        'mpc' => 'application/x-project',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'audio/mpeg',
        'mpga' => 'audio/mpeg',
        'mpp' => 'application/vnd.ms-project',
        'mpt' => 'application/x-project',
        'mpv' => 'application/x-project',
        'mpx' => 'application/x-project',
        'mrc' => 'application/marc',
        'ms' => 'application/x-troff-ms',
        'mv' => 'video/x-sgi-movie',
        'my' => 'audio/make',
        'mzz' => 'application/x-vnd.audioexplosion.mzz',
        'nap' => 'image/naplps',
        'naplps' => 'image/naplps',
        'nc' => 'application/x-netcdf',
        'ncm' => 'application/vnd.nokia.configuration-message',
        'nif' => 'image/x-niff',
        'niff' => 'image/x-niff',
        'nix' => 'application/x-mix-transfer',
        'nsc' => 'application/x-conference',
        'nvd' => 'application/x-navidoc',
        'o' => 'application/octet-stream',
        'oda' => 'application/oda',
        'omc' => 'application/x-omc',
        'omcd' => 'application/x-omcdatamaker',
        'omcr' => 'application/x-omcregerator',
        'p' => 'text/x-pascal',
        'p10' => 'application/pkcs10',
        'p12' => 'application/pkcs-12',
        'p7a' => 'application/x-pkcs7-signature',
        'p7c' => 'application/pkcs7-mime',
        'p7m' => 'application/pkcs7-mime',
        'p7r' => 'application/x-pkcs7-certreqresp',
        'p7s' => 'application/pkcs7-signature',
        'part' => 'application/pro_eng',
        'pas' => 'text/pascal',
        'pbm' => 'image/x-portable-bitmap',
        'pcl' => 'application/vnd.hp-pcl',
        'pct' => 'image/x-pict',
        'pcx' => 'image/x-pcx',
        'pdb' => 'chemical/x-pdb',
        'pdf' => 'application/pdf',
        'pfunk' => 'audio/make',
        'pgm' => 'image/x-portable-graymap',
        'pic' => 'image/pict',
        'pict' => 'image/pict',
        'pkg' => 'application/x-newton-compatible-pkg',
        'pko' => 'application/vnd.ms-pki.pko',
        'pl' => 'text/plain',
        'plx' => 'application/x-pixclscript',
        'pm' => 'image/x-xpixmap',
        'pm4' => 'application/x-pagemaker',
        'pm5' => 'application/x-pagemaker',
        'png' => 'image/png',
        'pnm' => 'application/x-portable-anymap',
        'pot' => 'application/mspowerpoint',
        'pov' => 'model/x-pov',
        'ppa' => 'application/vnd.ms-powerpoint',
        'ppm' => 'image/x-portable-pixmap',
        'pps' => 'application/mspowerpoint',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppt' => 'application/mspowerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppz' => 'application/mspowerpoint',
        'pre' => 'application/x-freelance',
        'prt' => 'application/pro_eng',
        'ps' => 'application/postscript',
        'psd' => 'application/octet-stream',
        'pvu' => 'paleovu/x-pv',
        'pwz' => 'application/vnd.ms-powerpoint',
        'py' => 'text/x-script.phyton',
        'pyc' => 'applicaiton/x-bytecode.python',
        'qcp' => 'audio/vnd.qcelp',
        'qd3' => 'x-world/x-3dmf',
        'qd3d' => 'x-world/x-3dmf',
        'qif' => 'image/x-quicktime',
        'qt' => 'video/quicktime',
        'qtc' => 'video/x-qtc',
        'qti' => 'image/x-quicktime',
        'qtif' => 'image/x-quicktime',
        'ra' => 'audio/x-pn-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'ras' => 'application/x-cmu-raster',
        'rast' => 'image/cmu-raster',
        'rexx' => 'text/x-script.rexx',
        'rf' => 'image/vnd.rn-realflash',
        'rgb' => 'image/x-rgb',
        'rm' => 'application/vnd.rn-realmedia',
        'rmi' => 'audio/mid',
        'rmm' => 'audio/x-pn-realaudio',
        'rmp' => 'audio/x-pn-realaudio',
        'rng' => 'application/ringing-tones',
        'rnx' => 'application/vnd.rn-realplayer',
        'roff' => 'application/x-troff',
        'rp' => 'image/vnd.rn-realpix',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'rt' => 'text/richtext',
        'rtf' => 'application/rtf',
        'rtx' => 'application/rtf',
        'rv' => 'video/vnd.rn-realvideo',
        's' => 'text/x-asm',
        's3m' => 'audio/s3m',
        'saveme' => 'application/octet-stream',
        'sbk' => 'application/x-tbook',
        'scm' => 'application/x-lotusscreencam',
        'sdml' => 'text/plain',
        'sdp' => 'application/sdp',
        'sdr' => 'application/sounder',
        'sea' => 'application/sea',
        'set' => 'application/set',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'sh' => 'application/x-bsh',
        'shar' => 'application/x-bsh',
        'shtml' => 'text/html',
        'sid' => 'audio/x-psid',
        'sit' => 'application/x-sit',
        'skd' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'skp' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'sl' => 'application/x-seelogo',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'snd' => 'audio/basic',
        'sol' => 'application/solids',
        'spc' => 'application/x-pkcs7-certificates',
        'spl' => 'application/futuresplash',
        'spr' => 'application/x-sprite',
        'sprite' => 'application/x-sprite',
        'src' => 'application/x-wais-source',
        'ssi' => 'text/x-server-parsed-html',
        'ssm' => 'application/streamingmedia',
        'sst' => 'application/vnd.ms-pki.certstore',
        'step' => 'application/step',
        'stl' => 'application/sla',
        'stp' => 'application/step',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'svf' => 'image/vnd.dwg',
        'svr' => 'application/x-world',
        'swf' => 'application/x-shockwave-flash',
        't' => 'application/x-troff',
        'talk' => 'text/x-speech',
        'tar' => 'application/x-tar',
        'tbk' => 'application/toolbook',
        'tcl' => 'application/x-tcl',
        'tcsh' => 'text/x-script.tcsh',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'text' => 'application/plain',
        'tgz' => 'application/gnutar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'tr' => 'application/x-troff',
        'tsi' => 'audio/tsp-audio',
        'tsp' => 'application/dsptype',
        'tsv' => 'text/tab-separated-values',
        'turbot' => 'image/florian',
        'txt' => 'text/plain',
        'uil' => 'text/x-uil',
        'uni' => 'text/uri-list',
        'unis' => 'text/uri-list',
        'unv' => 'application/i-deas',
        'uri' => 'text/uri-list',
        'uris' => 'text/uri-list',
        'ustar' => 'application/x-ustar',
        'uu' => 'application/octet-stream',
        'uue' => 'text/x-uuencode',
        'vcd' => 'application/x-cdlink',
        'vcs' => 'text/x-vcalendar',
        'vda' => 'application/vda',
        'vdo' => 'video/vdo',
        'vew' => 'application/groupwise',
        'viv' => 'video/vivo',
        'vivo' => 'video/vivo',
        'vmd' => 'application/vocaltec-media-desc',
        'vmf' => 'application/vocaltec-media-file',
        'voc' => 'audio/voc',
        'vos' => 'video/vosaic',
        'vox' => 'audio/voxware',
        'vqe' => 'audio/x-twinvq-plugin',
        'vqf' => 'audio/x-twinvq',
        'vql' => 'audio/x-twinvq-plugin',
        'vrml' => 'application/x-vrml',
        'vrt' => 'x-world/x-vrt',
        'vsd' => 'application/x-visio',
        'vst' => 'application/x-visio',
        'vsw' => 'application/x-visio',
        'w60' => 'application/wordperfect6.0',
        'w61' => 'application/wordperfect6.1',
        'w6w' => 'application/msword',
        'wav' => 'audio/wav',
        'wb1' => 'application/x-qpro',
        'wbmp' => 'image/vnd.wap.wbmp',
        'web' => 'application/vnd.xara',
        'wiz' => 'application/msword',
        'wk1' => 'application/x-123',
        'wmf' => 'windows/metafile',
        'wml' => 'text/vnd.wap.wml',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wmls' => 'text/vnd.wap.wmlscript',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'word' => 'application/msword',
        'wp' => 'application/wordperfect',
        'wp5' => 'application/wordperfect',
        'wp6' => 'application/wordperfect',
        'wpd' => 'application/wordperfect',
        'wq1' => 'application/x-lotus',
        'wri' => 'application/mswrite',
        'wrl' => 'application/x-world',
        'wrz' => 'model/vrml',
        'wsc' => 'text/scriplet',
        'wsrc' => 'application/x-wais-source',
        'wtk' => 'application/x-wintalk',
        'xbm' => 'image/x-xbitmap',
        'xdr' => 'video/x-amt-demorun',
        'xgz' => 'xgl/drawing',
        'xif' => 'image/vnd.xiff',
        'xl' => 'application/excel',
        'xla' => 'application/excel',
        'xlb' => 'application/excel',
        'xlc' => 'application/excel',
        'xld' => 'application/excel',
        'xlk' => 'application/excel',
        'xll' => 'application/excel',
        'xlm' => 'application/excel',
        'xls' => 'application/excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlt' => 'application/excel',
        'xlv' => 'application/excel',
        'xlw' => 'application/excel',
        'xm' => 'audio/xm',
        'xml' => 'application/xml',
        'xmz' => 'xgl/movie',
        'xpix' => 'application/x-vnd.ls-xpix',
        'xpm' => 'image/x-xpixmap',
        'x-png' => 'image/png',
        'xsr' => 'video/x-amt-showrun',
        'xwd' => 'image/x-xwd',
        'xyz' => 'chemical/x-pdb',
        'z' => 'application/x-compress',
        'zip' => 'application/zip',
        'zoo' => 'application/octet-stream',
        'zsh' => 'text/x-script.zsh',
    );

    /**
     * Method that allows you to determine a MIME type for a file which you provide the extension for.
     *
     * @param string $ext The file extension.
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public static function getMIMETypeByExtension(string $ext): string
    {
        $ext = mb_strtolower($ext);
        if (!isset(self::$extensionToMIMEMappings[$ext])) {
            throw new IllegalArguementException('Unable to determine the MIME type for the extension ['.$ext.']');
        }

        return self::$extensionToMIMEMappings[$ext];
    }

    /**
     * Renders the contents of the directory as a HTML list. Returns the current filecount for the directory.
     *
     * @param string $sourceDir    The path to the source directory.
     * @param string $fileList     The HTML list of files generated (pass by reference).
     * @param int    $fileCount    The current file count (used in recursive calls).
     * @param string[]  $excludeFiles An array of file names to exclude from the list rendered.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    public static function listDirectoryContents(string $sourceDir, string &$fileList, int $fileCount = 0, array $excludeFiles = array()): int
    {
        try {
            $dir = new DirectoryIterator($sourceDir);
            $fileCount = 0;

            foreach ($dir as $file) {
                if (!in_array($file->getFilename(), $excludeFiles, true)) {
                    if ($file->isDir() && !$file->isDot()) {
                        $fileList .= '<em>'.$file->getPathname().'</em><br>';
                        $fileCount += self::listDirectoryContents($file->getPathname(), $fileList, $fileCount, $excludeFiles);
                    } elseif (!$file->isDot()) {
                        $fileName = $file->getFilename();
                        ++$fileCount;
                        $fileList .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$fileName.'<br>';
                    }
                }
            }

            return $fileCount;
        } catch (\Exception $e) {
            throw new AlphaException('Failed list files in the ['.$sourceDir.'] directory, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Gets the contents of the directory recursively as a nested hash array (dirname => file1, file2, file3...).
     *
     * @param string $sourceDir    The path to the source directory.
     * @param array $fileList      The list of files generated (pass by reference).
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 3.0
     */
    public static function getDirectoryContents(string $sourceDir, array &$fileList = array()): array
    {
        try {
            $dir = new DirectoryIterator($sourceDir);

            foreach ($dir as $file) {
                if ($file->isDir() && !$file->isDot()) {
                    self::getDirectoryContents($file->getPathname(), $fileList);
                } elseif (!$file->isDot()) {
                    $fileList[$sourceDir][] = $file->getFilename();
                }
            }

            return $fileList;
        } catch (\Exception $e) {
            throw new AlphaException('Failed list files in the ['.$sourceDir.'] directory, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Recursively deletes the contents of the directory indicated (the directory itself is not deleted).
     *
     * @param string    $sourceDir        The path to the source directory.
     * @param string[]  $excludeFiles     An array of file names to exclude from the deletion.
     * @param boolean   $deleteSourceDir  Set to true to also delete the sourceDir, default is false.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    public static function deleteDirectoryContents(string $sourceDir, array $excludeFiles = array(), bool $deleteSourceDir = false): void
    {
        try {
            $dir = new DirectoryIterator($sourceDir);

            foreach ($dir as $file) {
                if ($file->isDir() && !$file->isDot()) {
                    if (count(scandir($file->getPathname())) == 2 && !in_array($file->getFilename(), $excludeFiles, true)) { // remove an empty directory
                        rmdir($file->getPathname());
                    } else {
                        self::deleteDirectoryContents($file->getPathname(), $excludeFiles, true);
                    }
                } elseif (!$file->isDot() && !in_array($file->getFilename(), $excludeFiles, true)) {
                    unlink($file->getPathname());
                }
            }

            if (file_exists($sourceDir) && $deleteSourceDir) {
                rmdir($sourceDir);
            }
        } catch (\Exception $e) {
            throw new AlphaException('Failed to delete files files in the ['.$sourceDir.'] directory, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Recursively copies the indicated folder, or single file, to the destination location.
     *
     * @param string $source The path to the source directory or file.
     * @param string $dest   The destination source directory or file.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.1
     */
    public static function copy(string $source, string $dest): void
    {
        if (is_file($source)) {
            if (!copy($source, $dest)) {
                throw new AlphaException("Error copying the file [$source] to [$dest].");
            }
        } else {
            // Make destination directory if it does not already exist
            if (!file_exists($dest) && !is_dir($dest)) {
                if (!mkdir($dest, 0777, true)) {
                    throw new AlphaException("Error creating the destination directory [$dest].");
                }
            }

            $dir = dir($source);
            if ($dir !== false) {
                while (false !== $entry = $dir->read()) {
                    if ($entry == '.' || $entry == '..') {
                        continue;
                    }

                    if ($dest !== "$source/$entry") {
                        self::copy("$source/$entry", "$dest/$entry");
                    }
                }

                $dir->close();
            }
        }
    }

    /**
     * Recursively compresses the contents of the source directory indicated to the destintation zip archive.
     *
     * @param string $source The path to the source directory or file.
     * @param string $dest   The destination zip file file.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.1
     */
    public static function zip(string $source, string $dest): void
    {
        if (extension_loaded('zip') === true) {
            if (file_exists($source) === true) {
                $zip = new ZipArchive();

                if ($zip->open($dest, ZipArchive::CREATE) === true) {
                    $source = realpath($source);

                    if (is_dir($source) === true) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

                        foreach ($files as $file) {
                            $file = realpath($file);

                            if (is_dir($file) === true) {
                                $zip->addEmptyDir(str_replace($source.'/', '', $file.'/'));
                            }

                            if (is_file($file) === true) {
                                $zip->addFromString(str_replace($source.'/', '', $file), file_get_contents($file));
                            }
                        }
                    }

                    if (is_file($source) === true) {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }

                $zip->close();
            }
        } else {
            throw new AlphaException('Unable to create the zip file ['.$dest.'] as the zip extension is unavailable!');
        }
    }
}

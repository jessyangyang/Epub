<?php
/**
 * PHP 5.3+ library for creation and modification of ePub files
 *
 * Copyright (c) 2002-2011, Dmitry Vinogradov <dmitri.vinogradov@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Epub
 * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
 * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       https://github.com/dmitry-vinogradov/Epub
 * @since      File available since Release 1.0.0
 */
namespace Epub
{
    require_once __DIR__ . \DIRECTORY_SEPARATOR . 'OCF.php';

    use ZipArchive;
    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;
    use Exception;

    /**
     *
     *
     * @package    Epub
     * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
     * @link       https://github.com/dmitry-vinogradov/Epub
     * @since      File available since Release 1.0.0
     */
    class Epub
    {
        /**
         * Working directory
         * @var string
         */
        protected $tmpDir;

        /**
         * Filename of the epub file
         * @var string
         */
        protected $filename;

        /**
         * OCF instance
         * @var \Epub\OCF
         */
        protected $ocf; 
        
        /**
         * Base 64 encoded content of the skeleton epub file.
         * Due to implementation lack in ZipArchive its not possible to add 
         * uncompressed files to the archive, i.e. "mimetype" as specified in OPS
         *
         * @var string
         */
        protected $zipSkeleton = '
            UEsDBAoAAAAAAChlWEBvYassFAAAABQAAAAIAAAAbWltZXR5cGVhcHBsaWNhdGlvbi9lcHViK3pp
            cFBLAQIeAwoAAAAAAChlWEBvYassFAAAABQAAAAIAAAAAAAAAAAAAACkgQAAAABtaW1ldHlwZVBL
            BQYAAAAAAQABADYAAAA6AAAAAAA=';
            
        /**
         * Zip error mapping
         *
         * @var array
         */
        protected $zipErrors = array(
            \ZIPARCHIVE::ER_EXISTS => 'File already exists',
            \ZIPARCHIVE::ER_INCONS => 'Zip archive inconsistent',
            \ZIPARCHIVE::ER_INVAL  => 'Invalid argument',
            \ZIPARCHIVE::ER_MEMORY => 'Malloc failure',
            \ZIPARCHIVE::ER_NOENT  => 'No such file',
            \ZIPARCHIVE::ER_NOZIP  => 'Not a zip archive',
            \ZIPARCHIVE::ER_OPEN   => 'Can\'t open file',
            \ZIPARCHIVE::ER_READ   => 'Read error',
            \ZIPARCHIVE::ER_SEEK   => 'Seek error',
        );

        /**
         * Constructor.
         *
         * @param string $filename Epub filename to open
         * @param string $strict   Do not tolerate epub errors if opening existing epub file
         */
        public function __construct($filename, $strict = true)
        {
            // shortcut
            $ds = \DIRECTORY_SEPARATOR;

            $tmpPath = \sys_get_temp_dir() . $ds;
            if (false === \is_dir($tmpPath) || false === \is_writable($tmpPath)) {
                throw new Exception(
                    'Temporary directory ' . $tmpPath . ' does not exist or is not writable'
                );
            }

            $tmpDir = $tmpPath . \uniqid('epub', true) . $ds;
            while (\is_dir($tmpDir)) {
                $tmpDir = $tmpPath . \uniqid('epub', true) . $ds;
            }
            if (false ===  @ \mkdir($tmpDir)) {
                throw new Exception('Cannot create working directory ' . $tmpDir);
            }
            $this->tmpDir = $tmpDir;

            $this->filename = $filename;

            if (true === \is_file($filename)) {
                $this->open($filename, $strict);
            } else {
                $this->ocf = new OCF();
            }
        }

        /**
         * Set title of the epub
         *
         * @param string $title Title of the epub
         *
         * @return void
         */
        public function setTitle($title)
        {
            $this->ocf->opf->setMetadata('title', $title, 'dc');
        }

        /**
         * Get title of the epub
         *
         * @return string
         */
        public function getTitle()
        {
            $meta = $this->ocf->opf->getMetadata('title');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set creator of the epub
         *
         * @param string $creator Creator of the epub
         * @param string $fileAs  File-as attribute's value
         *
         * @return void
         */
        public function setCreator($creator, $fileAs = null)
        {
            $this->ocf->opf->setMetadata(
                'creator', $creator, 'dc',
                array(
                    'opf:role'    => 'aut',
                    'opf:file-as' => $fileAs === null ? $creator : $fileAs,
                )
            );
        }

        /**
         * Get creator of the epub
         *
         * @return string
         */
        public function getCreator()
        {
            $meta = $this->ocf->opf->getMetadata('creator');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set language of the epub
         *
         * @param string $value Language code
         *
         * @return void
         */
        public function setLanguage($value)
        {
            $this->ocf->opf->setMetadata(
                'language', $value, 'dc', 
                array(
                    'xsi:type'    => 'dcterms:RFC3066',
                )
            );
        }

        /**
         * Get language of the epub
         *
         * @return string
         */
        public function getLanguage()
        {
            $meta = $this->ocf->opf->getMetadata('language');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set identifier of the epub
         *
         * @param string $value Identifier
         *
         * @return void
         */
        public function setIdentifier($value)
        {
            $this->ocf->opf->setMetadata(
                'identifier', $value, 'dc', 
                array(
                    'id' => 'BookId',
                )
            );
        }

        /**
         * Get identifier of the epub
         *
         * @return string
         */
        public function getIdentifier()
        {
            $meta = $this->ocf->opf->getMetadata('identifier');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set publisher of the epub
         *
         * @param string $value Publisher
         *
         * @return void
         */
        public function setPublisher($value)
        {
            $this->ocf->opf->setMetadata('publisher', $value, 'dc');
        }

        /**
         * Get publisher of the epub
         *
         * @return string
         */
        public function getPublisher()
        {
            $meta = $this->ocf->opf->getMetadata('publisher');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set date of the epub
         *
         * @param string $value Date
         *
         * @return void
         */
        public function setDate($value)
        {
            $this->ocf->opf->setMetadata(
                'date', $value, 'dc', 
                array(
                    'xsi:type' => 'dcterms:W3CDTF',
                )
            );
        }

        /**
         * Get date of the epub
         *
         * @return string
         */
        public function getDate()
        {
            $meta = $this->ocf->opf->getMetadata('date');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set rights of the epub
         *
         * @param string $value Copyright
         *
         * @return void
         */
        public function setRights($value)
        {
            $this->ocf->opf->setMetadata('rights', $value, 'dc');
        }

        /**
         * Get rights of the epub
         *
         * @return string
         */
        public function getRights()
        {
            $meta = $this->ocf->opf->getMetadata('rights');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set description of the epub
         *
         * @param string $value Description
         *
         * @return void
         */
        public function setDescription($value)
        {
            $this->ocf->opf->setMetadata('description', $value, 'dc');
        }

        /**
         * Get description of the epub
         *
         * @return string
         */
        public function getDescription()
        {
            $meta = $this->ocf->opf->getMetadata('description');
            if (false !== $meta) {
                return $meta['value'];
            }
        }

        /**
         * Set metadata of the epub
         *
         * @param string $meta   Meta name
         * @param string $value  Value
         * @param string $prefix Namespace
         * @param array  $attrs  Attributes
         *
         */
        public function setMetadata($meta, $value , $prefix = null, array $attrs = array())
        {
            $this->ocf->opf->setMetadata($name, $value, $prefix, $attrs);
        }

        /**
         * Get metadata of the epub
         *
         * @param string $meta   Meta name
         * @param string $prefix Namespace, default ``dc``
         *
         * @return mixed
         */
        public function getMetadata($meta, $prefix = 'dc')
        {
            return $this->ocf->opf->getMetadata($name, $prefix);
        }

        /**
         * Get spine of the epub.
         *
         * Returns an array of ids of documents providing a linear reading order
         *
         * @return array
         */
        public function getSpine()
        {
            return $this->ocf->opf->getSpine();
        }

        /**
         * Set spine of the epub.
         *
         * Returns an array of ids of documents providing a linear reading order
         *
         * @return void
         */
        public function setSpine(array $spine)
        {
            $this->ocf->opf->setSpine($spine);
        }
        
        /**
         * Get guide of the epub.
         *
         * Returns an array of ids of documents providing a linear reading order
         *
         * @return array
         */
        public function getGuide()
        {
            return $this->ocf->opf->getGuide();
        }

        /**
         * Set spine of the epub.
         *
         * Returns an array of ids of documents providing a linear reading order
         *
         * @return void
         */
        public function setGuide(array $guide)
        {
            $this->ocf->opf->setGuide($guide);
        }
        
        /**
         * Set cover of the book
         *
         * @param string $cover Cover image
         * @param string $title Title of the cover page
         *
         * @return void
         */
        public function setCover($cover, $title = 'Cover')
        {
            if (false === \is_file($cover)) {
                throw new Exception('Cover file ' . $cover . ' does not exist.');
            }
            if (false === \is_readable($cover)) {
                throw new Exception('Cover file ' . $cover . ' is not readable.');
            }
            if (false === strpos(($mime = OPF::getMimetype($cover)), 'image/')) {
                throw new Exception('Unexpected mimetype of the cover file ' . 
                        $cover . ': ' . $mime);
            }
            $filename = basename($cover);
            if (false === \copy($cover, $this->tmpDir . $filename)) {
                throw new Exception('Cannot copy cover file ' . $cover . ' to ' . 
                        $this->tmpDir . $filename);
            }
            
            $html = '<?xml version="1.0"?>' . PHP_EOL . 
                    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" ' . 
                    '"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . PHP_EOL . 
                    '<html xmlns="http://www.w3.org/1999/xhtml">' . PHP_EOL . 
                    '<head><title>' . $title . '</title>' . PHP_EOL . 
                    '<style type="text/css">' . PHP_EOL . 
                    'div { text-align: center }' . PHP_EOL . 
                    'img { max-width: 100%; }' . PHP_EOL . 
                    '</style></head>' . PHP_EOL . 
                    '<body><div><img src="' . $filename . '" alt="Cover" />' . PHP_EOL . 
                    '</div></body></html>';
            \file_put_contents($this->tmpDir . 'cover.html', $html);
            $this->ocf->opf->setCoverPage($this->tmpDir . 'cover.html', $title);
        }

        /**
         * Get epub chapters
         *
         * @return array
         */
        public function getChapters()
        {
            $retval    = array();
            $navPoints = array_flip($this->ocf->opf->ncx->navId2src);
            foreach ($navPoints as $href => $id) {
                if (false !== ($pos = strpos($href, '#'))) {
                    $len = strlen($href);
                    $href = substr($href, 0, $len - ($len - $pos));
                }
                $chapter  = $this->ocf->opf->getManifestByHref($href);
                $navPoint = $this->ocf->opf->ncx->getNavPoint($id);
                $chapter  = array_merge(
                    $chapter,
                    array(
                        'navId'     => $navPoint['id'],
                        'navLavel'  => $navPoint['navLabel'],
                        'navParent' => $navPoint['parent'],
                        'playOrder' => $navPoint['playOrder'],
                        'content'   => $navPoint['content'],
                    )
                );
                if (true === isset($this->ocf->opf->fileUsage[$chapter['file']])) {
                    $chapter['files'] = array();
                    foreach ($this->ocf->opf->fileUsage[$chapter['file']] as $file) {
                        $chapter['files'][] = $file;
                        if (true === isset($this->ocf->opf->fileUsage[$file])) {
                            $chapter['files'] = array_merge($chapter['files'],
                                $this->ocf->opf->fileUsage[$file]);
                        }
                    }
                    $chapter['files'] = array_keys(array_flip($chapter['files']));
                }
                $retval[] = $chapter;
            }
            return $retval;
        }

        /**
         * Add chapter
         *
         * @param string $title  The title of the chapter
         * @param string $file   The content file of the chapter
         * @param string $parent The identifier of the parent navPoint
         *
         * @return void
         */
        public function addChapter($title, $file, $parent = null)
        {
            $this->ocf->opf->addChapter($title, $file, $parent);
        }

        /**
         * Save epub
         *
         * @param string $filename Filename of the resulting epub
         *
         * @return void
         */
        public function save($filename = null)
        {
            if ($filename === null) {
                $filename = $this->filename;
            }
            $path = \dirname($filename);
            if (false === \is_dir($path) || false === \is_writable($path)) {
                throw new Exception('Directory "' . $path .
                    '" does not exist or is not writable');
            }
            if (true === \is_file($filename)) {
                if (false === \is_writable($filename)) {
                    throw new Exception('File "' . $filename . '" is not writable');
                }
                if (false === @ \rename($filename, $filename . '.bak')) {
                    throw new Exception('Cannot rename file ' . $filename . ' to ' .
                        $filename . '.bak');
                }
            }

            $rootPath = $this->tmpDir . \uniqid() . \DIRECTORY_SEPARATOR;
            \mkdir($rootPath);
            $this->ocf->asXML($rootPath);

            $zip = new ZipArchive;
            \file_put_contents($filename, \base64_decode($this->zipSkeleton));
            if (true !== ($r = $zip->open($filename))) {
                throw new Exception('Cannot open skeleton epub file due to following error: ' . 
                    $this->zipErrors[$r]);
            }

            $addedDirs = array();
            $iterator  = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath));
            foreach ($iterator as $item) {
                if (true === $item->isDir() || $item->getBasename() == 'mimetype') {
                    continue;
                }
                $name    = $item->__toString();
                $zipName = str_replace($rootPath, '', $name);
                if (false === $zip->addFile($name, $zipName)) {
                    throw new Exception('Cannot add file ' . $zipName . ' to zip archive');
                }
            } 
            $zip->close();
            unset($zip);
        }

        /**
         * Open existing epub file
         *
         * @param string $filename Epub filename
         * @param string $strict   Do not tolerate epub errors
         *
         * @return Epub instance
         */
        protected function open($filename, $strict = false)
        {
            // shortcut
            $ds = \DIRECTORY_SEPARATOR;

            if (false === \is_file($filename)) {
                throw new Exception('File "' . $filename . '" does not exist.');
            }

            if (false === \is_readable($filename)) {
                throw new Exception('File "' . $filename . '" is not readable.');
            }

            $zip = new ZipArchive();
            if (true !== ($error = $zip->open($filename))) {
                // TODO: error parsing
                throw new Exception('Unable to read epub file "' . $filename . '"');
            }

            if (false === $zip->extractTo($this->tmpDir)) {
                throw new Exception(
                    'Cannot extract to ' . $this->tmpDir . ' due to unknown reason'
                );
            }

            // check if its really epub file
            if (false === \is_file($f = $this->tmpDir . 'mimetype')
                || 'application/epub+zip' !== \file_get_contents($f)
                || false === \is_dir($this->tmpDir . 'META-INF')
                || false === \is_file($this->tmpDir . 'META-INF' . $ds . 'container.xml')
            ) {
                throw new Exception('File "' . $filename . '" is not a valid epub container.');
            }

            $this->ocf = new OCF($this->tmpDir . 'META-INF' . $ds . 'container.xml', $strict);
        }

        /**
         * Destructor.
         * Deletes the working directory with all its content
         *
         * @return void
         */
        public function __destruct()
        {
            if (false === \is_dir($this->tmpDir)) {
                return;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tmpDir),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                if ($path->isDir()) {
                    if (substr($path->__toString(), -1) === '.') {
                        continue;
                    }
                    \rmdir($path->__toString());
                } else {
                    \unlink($path->__toString());
                }
            }
            \rmdir($this->tmpDir);
        }
    }
}
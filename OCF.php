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
 * @version    Release: %RELEASE%
 */
namespace Epub
{
    require_once __DIR__ . \DIRECTORY_SEPARATOR . 'XML.php';
    require_once __DIR__ . \DIRECTORY_SEPARATOR . 'OPF.php';

    use Exception;

    /**
     * OCF (Open Container Format) class
     *
     * @package    Epub
     * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
     * @link       https://github.com/dmitry-vinogradov/Epub
     */
    class OCF
    {
        /**
         * Rootfiles container
         * @var array
         */
        protected $rootFiles = array();

        /**
         * Container for OPF instance
         * @var \Epub\OPF
         */
        protected $opf;

        /**
         * Constructor.
         *
         * @param string $xmlFile XML file
         * @param string $strict  Do not tolerate epub errors
         */
        public function __construct($xmlFile = null, $strict = true)
        {
            if ($xmlFile !== null) {
                $this->readXML($xmlFile, $strict);
            } else {
                $this->rootFiles[] = array(
                    'full-path'  => 'OEBPS/content.opf',
                    'media-type' => 'application/oebps-package+xml'
                );
                $this->opf = new OPF();
            }
        }

        /**
         * Getter for protected properties
         *
         *
         */
        public function __get($name)
        {
            return true === isset($this->{$name}) ? $this->{$name} : null;
        }

        /**
         * Read existing package XML file
         *
         * @param string $xmlFile XML file
         * @param string $strict  Do not tolerate epub errors
         *
         * @return void
         */
        protected function readXML($xmlFile, $strict = true)
        {
            // shortcut
            $ds = \DIRECTORY_SEPARATOR;

            $container = XML::loadFile($xmlFile, __DIR__ . $ds . 'Schema' . $ds . 'container.rng');
            foreach ($container->rootfiles->rootfile as $item) {
                $rootFile = array(
                    'full-path'  => XML::getAttr($item, 'full-path'),
                    'media-type' => XML::getAttr($item, 'media-type')
                );
                if (false === \is_file(\dirname($xmlFile) . $ds . '..' . $ds . $rootFile['full-path'])) {
                    throw new Exception('Rootfile "' . $rootFile['full-path'] . '" does not exist');
                }
                if ($rootFile['media-type'] === 'application/oebps-package+xml') {
                    $this->opf = new OPF(
                        \dirname($xmlFile) . $ds . '..' . $ds . $rootFile['full-path'],
                        $strict
                    );
                }
                $this->rootFiles[] = $rootFile;
            }
        }

        /**
         * Returns XML representation of the package
         *
         * @param string $rootPath Root path
         *
         * @return void
         */
        public function asXML($rootPath)
        {
            // shortcut
            $ds = \DIRECTORY_SEPARATOR;

            if (true === empty($this->rootFiles)) {
                throw new Exception('Rootfiles container cannot be empty');
            }
            if (false === \is_dir($rootPath)) {
                throw new Exception('Directory "' . $rootPath . '" does not exist.');
            }
            if (false === \is_writable($rootPath)) {
                throw new Exception('Directory "' . $rootPath . '" is nor writable.');
            }
            if (false === \is_dir($rootPath . '/META-INF')
                && false === @ \mkdir($rootPath . $ds . 'META-INF')) {
                throw new Exception('Cannot create directory "' . $rootPath . $ds .
                    'META-INF" due to unknown reason.');
            }
            $xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
                      '<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0">' . PHP_EOL . 
                      '<rootfiles>' . PHP_EOL;
            foreach ($this->rootFiles as $rootFile) {
                if (false === isset($rootFile['full-path'])) {
                    throw new Exception('Missing "full-path" in rootfile.');
                }
                if (false === isset($rootFile['media-type'])) {
                    throw new Exception('Missing "media-type" in rootfile.');
                }
                if ($rootFile['media-type'] === 'application/oebps-package+xml') {
                    if (false === ($this->opf instanceof OPF)) {
                        throw new Exception('Missing instance of \Epub\OPF for rootfile with full-path ' .
                            $rootFile['full-path']);
                    }
                    $this->opf->asXML($rootPath . $ds . $rootFile['full-path']);
                }
                $xmlStr .= '<rootfile full-path="' . $rootFile['full-path'] .
                    '" media-type="' . $rootFile['media-type'] . '" />' . PHP_EOL;
            }
            $xmlStr .= '</rootfiles>' . PHP_EOL . '</container>' . PHP_EOL;

            XML::loadString($xmlStr, __DIR__ . '/Schema/container.rng')->asXML(
                $rootPath . $ds . 'META-INF' . $ds . 'container.xml'
            );
        }
    }
}

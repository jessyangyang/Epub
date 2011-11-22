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
    use DOMDocument;
    use Exception;

    \libxml_use_internal_errors(true);

    /**
     * XML helper class
     *
     * @package    Epub
     * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
     * @version    Release: @package_version@
     * @link       https://github.com/dmitry-vinogradov/Epub
     * @since      Class available since Release 1.0.0
     */
    class XML
    {
        /**
         * Get node attribute
         *
         * @param \SimpleXMLElement $node Node instance
         * @param string            $name Attribute name
         *
         * @return string attribute value or null
         */
        public static function getAttr(\SimpleXMLElement $node, $name)
        {
            foreach ($node->attributes() as $key => $value) {
                if ($key == $name) {
                    return (string) $value;
                }
            }
        }

        /**
         * Read XML string and return \SimpleXMLElement
         *
         * @param string $xmlString XML content
         * @param string $schema    Path to the schema file to validate
         *
         * @return \SimpleXMLElement
         */
        public static function loadString($xmlString, $schema = null)
        {
            if (false !== strpos($xmlString, 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd')) {
                $xmlString = str_replace(
                    'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd',
                    __DIR__ . '/Schema/dtd/xhtml/xhtml11.dtd',
                    $xmlString
                );
            }

            $xdoc = new DOMDocument();
            if (false === $xdoc->loadXML($xmlString, \LIBXML_NSCLEAN | \LIBXML_NOCDATA | \LIBXML_DTDLOAD)
                || false !== libxml_get_last_error()) {
                self::raiseError();
            }

            if ($schema !== null) {
                if (false === is_file($schema)) {
                    throw new Exception('Schema file "' . $schema . '" does not exist');
                }
                if (false === is_readable($schema)) {
                    throw new Exception('Schema file "' . $schema . '" does not readable');
                }
                switch  (strtolower(substr($schema, -4, 4))) {
                    case '.xsd':
                        $valid = $xdoc->schemaValidate($schema);
                        break;
                    case '.dtd':
                        $valid = $xdoc->validate($schema);
                        break;
                    case '.rng':
                        $valid = $xdoc->relaxNGValidate($schema);
                        break;
                }
                if (false === $valid) {
                    self::raiseError();
                }
            }

            return \simplexml_import_dom($xdoc);
        }

        /**
         * Read XML file and return \SimpleXMLElement
         *
         * @param string $xmlFile Path to the XML file
         * @param string $schema  Path to the schema file to validate
         *
         * @return \SimpleXMLElement
         */
        public static function loadFile($xmlFile, $schema = null)
        {
            if (false === is_file($xmlFile)) {
                throw new Exception('XML file "' . $xmlFile . '" does not exist');
            }

            if (false === is_readable($xmlFile)) {
                throw new Exception('XML file "' . $xmlFile . '" does not readable');
            }

            if ($schema !== null) {
                if (false === is_file($schema)) {
                    throw new Exception('Schema file "' . $schema . '" does not exist');
                }
                if (false === is_readable($schema)) {
                    throw new Exception('Schema file "' . $schema . '" does not readable');
                }
            }

            try {
                return self::loadString(file_get_contents($xmlFile), $schema);
            } catch (\Exception $e) {
                throw new Exception('File "' . $xmlFile . '": ' . PHP_EOL . $e->getMessage());
            }
        }

        /**
         * Convert XML represented by instance of SimpleXMLElement to PHP
         *
         * @param mixed $value Instance of SimpleXMLElement
         *
         * @return mixed PHP value
         */
        public static function xml2php($value)
        {
            if ($value instanceof \SimpleXMLElement) {
                if (false === empty($value)) {
                    $value = (array)$value;
                    foreach ($value as $k => $v) {
                        // ignore comments
                        if ($k === 'comment') {
                            if (true === is_array($v)) {
                                reset($v);
                                $elem = current($v);
                            } else {
                                $elem = $v;
                            }
                            if ($elem instanceof \SimpleXMLElement
                                && false !== strpos($elem->asXML(), '<!--')) {
                                unset($value[$k]);
                                continue;
                            }
                        }

                        if (true === is_array($v) && 1 === count($value)) {
                            $value = $v;
                            foreach ($value as $_k => $_v) {
                                // ignore comments
                                if ($_k === 'comment') {
                                    if (true === is_array($_v)) {
                                        reset($_v);
                                        $elem = current($_v);
                                    } else {
                                        $elem = $_v;
                                    }
                                    if ($elem instanceof \SimpleXMLElement
                                        && false !== strpos($elem->asXML(), '<!--')) {
                                        unset($value[$_k]);
                                        continue;
                                    }
                                }
                                $value[$_k] = self::xml2php($_v);
                            }
                            break;
                        }
                        $value[$k] = self::xml2php($v);
                    }
                }
            }
            return $value;
        }

        /**
         * Get XML representation of the SimpleXMLElement object
         *
         * @param SimpleXMLElement $sxe      SimpleXMLElement object
         * @param array            $options  Tidy options
         * @param string           $encoding Encoding, default utf8
         *
         * @return string
         */
        public static function toXML(\SimpleXMLElement $sxe, array $options = array(), $encoding = 'utf8')
        {
            if (false === \extension_loaded('tidy')) {
                return $sxe->asXML();
            }

            static $tidy, $opts;
            if (null === $tidy) {
                $tidy = new \Tidy();
                $opts = array(
                    'indent'     => true,
                    'output-xml' => true,
                    'wrap'       => 110
                );
            }
            $opts = !empty($options) ? array_merge($opts, $options) : $opts;
            $tidy->parseString($sxe->asXML(), $opts, $encoding);
            $tidy->cleanRepair();
            return $tidy->html()->value;
        }

        /**
         * Raise XML error
         *
         * @return void
         * @throws Exception
         */
        protected static function raiseError()
        {
            $errorMessage = '';
            foreach (\libxml_get_errors() as $error) {
                $errorMessage .= trim($error->message) . ' on line ' . $error->line . ':' . $error->column;
                if ($error->file) {
                    $errorMessage .= ' in file ' . $error->file;
                }
                $errorMessage .= PHP_EOL;
            }
            \libxml_clear_errors();
            throw new Exception($errorMessage);
        }
    }
}

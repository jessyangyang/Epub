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
			}
			
			if ($schema !== null) {
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
			
			return self::loadString(file_get_contents($xmlFile), $schema);
			
			$xdoc = new DOMDocument();
			if (false === $xdoc->load($xmlFile, \LIBXML_NSCLEAN | \LIBXML_NOCDATA | \LIBXML_DTDLOAD) 
				|| false !== libxml_get_last_error()) {
				self::raiseError();
			}
			
			if ($schema !== null) {
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
        	throw new Exception($errorMessage);
        }
    }
}
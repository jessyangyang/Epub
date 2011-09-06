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
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'XML.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'NCX.php';
	
	class OPF
	{
		/**
		 * A unique identifier for the OPS Publication as a whole.
		 * @var string
		 */
		protected $uid;
		
		/**
		 * Version of the package.
		 * @var string
		 */
		protected $version = '2.0';
		
		/**
		 * Publication metadata (title, author, publisher, etc.).
		 * @var array
		 */
		protected $metadata;
		
		/**
		 * A list of files (documents, images, style sheets, etc.) that make up the publication. 
		 * The manifest also includes fallback declarations for files of types not supported by 
		 * this specification.
		 * @var array
		 */
		protected $manifest;
		
		/**
		 * An arrangement of documents providing a linear reading order.
		 * @var array
		 */
		protected $spine;
		
		/**
		 * A set of references to fundamental structural features of the publication, such as 
		 * table of contents, foreword, bibliography, etc. 
		 * @var array
		 */
		protected $guide;
		
		/**
		 * Navigation Control file for XML
		 * @var \Epub\NCX
		 */
		protected $ncx;
		
		/**
		 * Constructor.
		 * 
		 * @param string $xmlFile XML file
		 */
		public function __construct($xmlFile = null)
		{
			if ($xmlFile !== null) {

				$this->readXML($xmlFile);
				
			} else {
				
				$this->uid      = 'Epub-' . uniqid();
				$this->metadata = array(
					'dc:title'      => array(
						'value' => 'Untitled',
						'attrs' => array()
		            ),
		            'dc:creator'    => array(
		            	'value' => 'Unknown',
		            	'attrs' => array(
		                	'opf:file-as' => 'Unknown',
		                	'opf:role'    => 'aut'
		               	)
		            ),
		            'dc:identifier' => array(
		            	'value' => $this->uid,
		            	'attrs' => array(
		                	'id' => 'EpubId'
		                )
		            )
		        );
			}
		}
		
		/**
		 * Read existing package XML file
		 * 
		 * @param string $xmlFile XML file
		 * 
		 * @return void
		 */
		protected function readXML($xmlFile)
		{
			$package       = XML::loadFile($xmlFile);
			$this->uid     = XML::getAttr($package, 'unique-identifier');
			$this->version = XML::getAttr($package, 'version');
			
			$ns = $package->getDocNamespaces(true);
			foreach ($ns as $prefix => $uri) {
				foreach ($package->metadata->children($prefix, true) as $key => $value) {
					$key = false === empty($prefix) ? $prefix . ':' . $key :  $key;
					if ($key == 'meta') {
						if (true === isset($this->metadata['meta'])) {
							$this->metadata['meta'] = array();
						}
						$this->metadata['meta'][XML::getAttr($value, 'name')] = XML::getAttr($value, 'content');
					} else if ($key == 'x-metadata') {
						
					} else {
						$attrs = array();
                        $this->metadata[$key] = array(
                        	'value' => (string)$value,
                        	'attrs' => array() 
                        );
                        if (false === empty($prefix)) {
	                        foreach ($ns as $_prefix => $_uri) {
	                            foreach ($value->attributes($_prefix, true) as $_key => $_value) {
	                                $_key = false === empty($_prefix) ? $_prefix . ':' . $_key :  $_key;
		                         	$this->metadata[$key]['attrs'][$_key] = (string)$_value;
	                            }
	                        }
                        }
                    }
				}
			}
			
			foreach ($package->manifest->item as $item) {
				$id   = XML::getAttr($item, 'id');
				$href = XML::getAttr($item, 'href');
				$file = dirname($xmlFile) . DIRECTORY_SEPARATOR . $href;
				if (false === is_file($file)) {
					throw new \Exception('File "' . $file . '" referenced in manifest under id ' . 
						$id . ' does not exist.');
				}
                $this->manifest[$id] = array(
                    'id'         => $id,
                	'href'       => $href,
                    'media-type' => XML::getAttr($item, 'media-type'),
                	'file'   	 => $file
                );
                if ($id === 'ncx') {
                	$this->ncx = new NCX($file);
                }
            }
            
            foreach ($package->spine->itemref as $itemref) {
         		$key = XML::getAttr($itemref, 'idref');
                if ($key && true === isset($this->manifest[$key])) {
                    $this->spine[$key] = array(
                    	'href'   => $this->manifest[$key]['href'],
                    	'linear' => XML::getAttr($itemref, 'linear')
                  	);
                } else {
                    throw new \Exception(
                        'Spine error: cannot find appropriate manifest item with id ' . $key
                    );
                }
            }
		 	if ($package->guide->reference instanceof \SimpleXMLElement) {
		 		foreach ($package->guide->reference as $reference) {
		 			$this->guide[] = array(
		 				'href'  => XML::getAttr($reference, 'href'),
                        'type'  => XML::getAttr($reference, 'type'),
                        'title' => XML::getAttr($reference, 'title')
		 			);
		 		}
            }
		}
		
		/**
		 * Returns XML representation of the package
		 * 
		 * @param string $xmlFile File name of XML file. 
		 * 
		 * @return void
		 */
		public function asXML($xmlFile)
		{
			$xmlPath = \dirname($xmlFile) . DIRECTORY_SEPARATOR;
			if (false === \is_dir($xmlPath) && false === @ \mkdir($xmlPath)) {
				throw new \Exception('Cannot create directory "' . $xmlPath . '" due to unknown reason.');
			}
			
			$xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . 
					  '<package version="' . $this->version . '" xmlns="http://www.idpf.org/2007/opf" ' . 
				      'unique-identifier="' . $this->uid . '">';
			
			// metadata
			$xmlStr .= '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" ' . 
                	   'xmlns:opf="http://www.idpf.org/2007/opf">';
			foreach ($this->metadata as $key => $item) {
				if ($key === 'meta') {
					foreach ($item as $name => $content) {
						$xmlStr .= '<meta name="' . $name . '" content="' . htmlentities($content) . '" />';
					}
				} else {
					$xmlStr .= '<' . $key;
					if (true === isset($item['attrs'])) {
						foreach ($item['attrs'] as $name => $value) {
							$xmlStr .= ' ' . $name . '="' . htmlentities($value) . '"';
						}
					}
					$xmlStr .= $item['value'] === '' ? ' />' : '>' .  htmlentities($item['value']) . '</' . $key . '>';
				}
			}
			$xmlStr .= '</metadata>';
			
			// manifest
			if (empty($this->manifest)) {
				throw new \Exception('The manifest element must contain one or more item elements.');
			}
			$hrefs2ids = array();
			$xmlStr .= '<manifest>';
			foreach ($this->manifest as $id => $item) {
				if (false === isset($item['id'])
					|| false === isset($item['href'])
					|| false === isset($item['media-type'])
				) {
					throw new \Exception('Each item element contained within a manifest element must ' . 
						'have the attributes id, href and media-type');
				}
				if (false === isset($item['file']) || false === is_file($item['file'])) {
					throw new \Exception('Missing file associated with manifest item with id ' . $item['id']);
				}
				$hrefs2ids[$item['href']] = $id;
				$xmlStr .= '<item id="' . $id . '" href="' . $item['href'] . '"';
				if (true === isset($item['fallback']) && true === isset($item[$item['fallback']])) {
					$xmlStr .= ' fallback="' . $item['fallback'] . '"';
				}
				if (true === isset($item['fallback-style'])) {
					$xmlStr .= ' fallback-style="' . $item['fallback-style'] . '"';
				}
				if (true === isset($item['required-namespace'])) {
					$xmlStr .= ' required-namespace="' . $item['required-namespace'] . '"';
					if (true === isset($item['required-modules'])) {
						$xmlStr .= ' required-modules="' . $item['required-modules'] . '"';
					}
				}
				$xmlStr    .= ' media-type="' . $item['media-type'] . '" />';
				$targetPath = \dirname($xmlPath . $item['href']);
				if (false === \is_dir($targetPath) && false === @ mkdir($targetPath)) {
					throw new \Exception('Cannot create directory "' . $targetPath . 
						'" due to unknown reason.');
				}
				if (false === @ \copy($item['file'], $xmlPath . $item['href'])) {
					throw new \Exception('Cannot copy file "' . $item['file'] . 
						'" to "' . $xmlPath . $item['href'] . '" due to unknown reason.');
				}
			}
			$xmlStr .= '</manifest>';
			
			// spine
			if (empty($this->spine)) {
				throw new \Exception('The spine element must contain one or more itemref elements.');
			}
			$xmlStr .= '<spine';
			if (true === isset($this->manifest['ncx'])) {
				$xmlStr .= ' toc="ncx"';
			}
			$xmlStr .= '>';
			foreach ($this->spine as $key => $item) {
				if (false === isset($this->manifest[$key])) {
					throw new \Exception('Reference to a nonexistant content document: ' . $key);
				}
				$xmlStr .= '<itemref idref="' . $key . '"';
				if (true === isset($item['linear'])) {
					$xmlStr .= ' linear="' . $item['linear'] . '"';
				}
				$xmlStr .= ' />';
			}
			$xmlStr .= '</spine>';
			
			// guide
			if (false === empty($this->guide)) {
				$xmlStr .= '<guide>';
				foreach ($this->guide as $item) {
					if (false === isset($hrefs2ids[$item['href']])) {
						throw new \Exception('Guide element is not referenced in the manifest: ' . 
							$item['href']);
					}
					$xmlStr .= '<reference type="' . $item['type'] . 
							   '" title="' . $item['title'] . 
							   '" href="' . $item['href'] . '" />';
				}
				$xmlStr .= '</guide>';
			}
			$xmlStr .= '</package>';
file_put_contents('opf.xml', $xmlStr);
			XML::loadString($xmlStr)->asXML($xmlFile);
			if ($this->ncx) {
				if (false === isset($this->manifest['ncx']) 
					|| true === empty($this->manifest['ncx']['href'])) {
					throw new \Exception('Cannot determine filename of the NCX');
				}
				$this->ncx->asXML(dirname($xmlFile) . DIRECTORY_SEPARATOR . $this->manifest['ncx']['href']);
			}
		}
	}	
}
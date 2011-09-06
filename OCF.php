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
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'OPF.php';
	
	/**
	 * OCF container class
	 * 
	 * @package    Epub
	 * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
	 * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
	 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
	 * @link       https://github.com/dmitry-vinogradov/Epub
	 * @since      File available since Release 1.0.0
	 */
	class OCF
	{
		/**
		 * Rootfiles container
		 * @var array
		 */
		protected $rootFiles = array();
		
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
			$container = XML::loadFile($xmlFile);
			foreach ($container->rootfiles->rootfile as $item) {
				$rootFile = array(
					'full-path'  => XML::getAttr($item, 'full-path'),
					'media-type' => XML::getAttr($item, 'media-type')
				);
				if (false === \is_file(\dirname($xmlFile) . DIRECTORY_SEPARATOR . 
					'..' . DIRECTORY_SEPARATOR . $rootFile['full-path'])) {
					throw new \Exception('Rootfile "' . $rootFile['full-path'] . '" does not exist');
				}
				if ($rootFile['media-type'] === 'application/oebps-package+xml') {
					$rootFile['opf'] = new OPF(\dirname($xmlFile) . DIRECTORY_SEPARATOR . 
						'..' . DIRECTORY_SEPARATOR . $rootFile['full-path']);
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
			if (true === empty($this->rootFiles)) {
				throw new \Exception('Rootfiles container cannot be empty');
			}
			if (false === \is_dir($rootPath)) {
				throw new \Exception('Directory "' . $rootPath . '" does not exist.');
			}
			if (false === \is_writable($rootPath)) {
				throw new \Exception('Directory "' . $rootPath . '" is nor writable.');
			}
			if (false === \is_dir($rootPath . '/META-INF') 
				&& false === @ \mkdir($rootPath . DIRECTORY_SEPARATOR . 'META-INF')) {
				throw new \Exception('Cannot create directory "' . $rootPath . DIRECTORY_SEPARATOR . 
					'META-INF" due to unknown reason.');
			}
			$xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . 
					  '<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0">' . 
					  '<rootfiles>';
			foreach ($this->rootFiles as $rootFile) {
				if (false === isset($rootFile['full-path'])) {
					throw new \Exception('Missing "full-path" in rootfile.');
				}
				if (false === isset($rootFile['media-type'])) {
					throw new \Exception('Missing "media-type" in rootfile.');
				}
				if ($rootFile['media-type'] === 'application/oebps-package+xml') {
					if (false === isset($rootFile['opf']) || false === ($rootFile['opf'] instanceof OPF)) {
						throw new \Exception('Missing instance of \Eoub\OPF for rootfile with full-path ' . 
							$rootFile['full-path']);
					}
					$rootFile['opf']->asXML($rootPath . DIRECTORY_SEPARATOR . $rootFile['full-path']);
				}
				$xmlStr .= '<rootfile full-path="' . $rootFile['full-path'] . 
					'" media-type="' . $rootFile['media-type'] . '" />';
			}
			$xmlStr .= '</rootfiles></container>';
			
			XML::loadString($xmlStr)->asXML($rootPath . DIRECTORY_SEPARATOR . 'META-INF' . 
				DIRECTORY_SEPARATOR . 'container.xml');
		}
	}
}
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
	require_once __DIR__ . \DIRECTORY_SEPARATOR . 'XML.php';
	
	use Exception;
	
	/**
	 * Navigation Center eXtended (NCX) class
	 */
	class NCX
	{
		/**
		 * ´Meta information 
		 * @var array
		 */
		protected $meta = array();
		
		/**
		 * Title information
		 * @var array
		 */
		protected $docTitle = array(
			'text' => 'Untitled',
			'img'  => ''
		);
		
		/**
		 * Author information
		 * @var array
		 */
		protected $docAuthor = array();
		
		/**
		 * Page list
		 * @var array
		 */
		protected $pageList = array();
		
		/**
		 * Navigation list
		 * @var array
		 */
		protected $navList = array();
		
		/**
		 * Mapping table (navPoint id to scr)
		 * @var array
		 */
		protected $navId2src = array();
		
		/**
		 * Mapping table (navPoint id to navPoint)
		 * @var array
		 */
		protected $navId2navPoint = array();
		
		/**
		 * Mapping table (navPoint id to parent navPoint id)
		 * @var array
		 */
		protected $parents = array();
		
		/**
		 * Mapping table (child ids of the navPoint)
		 * @var array
		 */
		protected $childs = array();
		
		/**
		 * Constructor
		 * 
		 * @param string $xmlFile XML file
		 * @param string $strict  Do not tolerate epub errors
		 */
		public function __construct($xmlFile = null, $strict = true)
		{
			if ($xmlFile !== null) {
				$this->readXML($xmlFile, $strict);
			} else {
				// what?
			}
		}
		
		/**
		 * Get protected properties
		 * 
		 * @param string $name Property name
		 * 
		 * @return mixed value
		 */
		public function __get($name)
		{
			return true === isset($this->{$name}) ? $this->{$name} : null;
		}
		
		/**
		 * Get navPoint by id
		 * 
		 * @param string $id NavPoint id
		 * 
		 * @return array
		 */
		public function getNavPoint($id)
		{
			$retval = false;
			if (true === isset($this->navId2navPoint[$id])) {
				$retval = array();
				$navPoint = $this->navId2navPoint[$id];
				foreach ($this->navId2navPoint[$id] as $key => $val) {
					if (true === is_scalar($val)) {
						$retval[$key] = $val;
					}
				}
				$retval['parent'] = true === isset($this->parents[$id]) ? $this->parents[$id] : null;
			}
			return $retval;
		}
		
		/**
		 * Add nav point
		 * 
		 * @param string $id       Identifier of the navigation  point
		 * @param string $label    Label of the navigation  point
		 * @param string $href     Path to the file
		 * @param string $parentId Identifier of the parent navigation point
		 * 
		 * @return href of the previous navigation point
		 */
		public function addNavPoint($id, $label, $href, $parentId = null)
		{
			if (true === isset($this->navId2navPoint[$id])) {
				throw new Exception('Nav point with id "' . $id . '" already exists.');
			}
			if (null !== $parentId) {
				if (false === isset($this->navId2navPoint[$parentId])) {
					throw new Exception('Parent Nav point with id "' . $parentId . '" does not exist.');
				}
			}
			$tmp = array_keys($this->navId2navPoint);
			$lastNavPoint = $this->navId2navPoint[$tmp[count($tmp) - 1]];
			$item = array(
				'id'        => $id,
				'playOrder' => $lastNavPoint['playOrder'] + 1,
				'class'     => '',
				'navLabel'  => trim($label),
				'content'   => $href,
			);
			$this->navId2src[$item['id']] = $item['content'];
			$retval = false;
			if (null !== $parentId) {
				if (false === isset($this->childs[$parentId])) {
					$this->childs[$parentId] = array();
				}
				$this->parents[$item['id']] = $parentId;
				$this->childs[$parentId][] = $item['id'];
				$prev = count($this->childs[$parentId]) - 2;
				if (0 > $prev) {
					$prevId = $parentId;
				} else {
					$prevId = $this->childs[$parentId][$prev];
				}
				$retval = $this->navId2src[$prevId];
			}
			$this->navId2navPoint[$item['id']] = $item;
			return $retval;
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
			
			$ncx = XML::loadFile($xmlFile, __DIR__ . $ds . 'Schema' . $ds . 'ncx.rng');

			if (true === isset($ncx->head->meta)) {
				foreach ($ncx->head->meta as $item) {
					$this->meta[XML::getAttr($item, 'name')] = XML::getAttr($item, 'content');
				}
			}

			$this->docTitle['text'] = (string) $ncx->docTitle->text;
			if (true === isset($ncx->docTitle->img)) {
				$this->docTitle['img']['src'] = (string)XML::getAttr($ncx->docTitle->img, 'src');
			}
			
			if (true === isset($ncx->docAuthor)) {
				$this->docAuthor['text'] = (string) $ncx->docAuthor->text;
				if (true === isset($ncx->docAuthor->img)) {
					$this->docAuthor['img']['src'] = (string)XML::getAttr($ncx->docAuthor->img, 'src');
				}
			}

			foreach ($ncx->navMap->navPoint as $navPoint) {
				$this->parseNavPoints($navPoint);
			}

			if (true === isset($ncx->pageList)) {
				foreach ($ncx->pageList->pageTarget as $pageTarget) {
					$this->pageList[] = array(
						'id'        => XML::getAttr($pageTarget, 'id'),
						'playOrder' => XML::getAttr($pageTarget, 'playOrder'),
						'type'      => XML::getAttr($pageTarget, 'type'),
						'value'     => XML::getAttr($pageTarget, 'value'),
						'class'     => XML::getAttr($pageTarget, 'class'),
						'navLabel'  => trim($pageTarget->navLabel->text),
						'content'   => XML::getAttr($pageTarget->content, 'src')
					);
				}
			}
			
			if (true === isset($ncx->navList)) {
				foreach ($ncx->navList->navTarget as $navTarget) {
					$this->navList[] = array(
						'id'        => XML::getAttr($navTarget, 'id'),
						'playOrder' => XML::getAttr($navTarget, 'playOrder'),
						'value'     => XML::getAttr($navTarget, 'value'),
						'class'     => XML::getAttr($navTarget, 'class'),
						'navLabel'  => trim($navTarget->navLabel->text),
						'content'   => XML::getAttr($navTarget->content, 'src'),
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
			// shortcut
			$ds = \DIRECTORY_SEPARATOR;
			
			$xmlPath = \dirname($xmlFile) . $ds;
			if (false === \is_dir($xmlPath) && false === @ \mkdir($xmlPath)) {
				throw new Exception('Cannot create directory "' . $xmlPath . '" due to unknown reason.');
			}
			
			$xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . 
					  '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="en-US">';
			
			if (false === empty($this->meta)) {
				$xmlStr .= '<head>';
				foreach ($this->meta as $name => $content) {
					$xmlStr .= '<meta name="' . $name . '" content="' . $content . '" />';
				}
				$xmlStr .= '</head>';
			}
			
			$xmlStr .= '<docTitle><text>' . $this->docTitle['text'] . '</text>';
			if (false === empty($this->docTitle['img'])) {
				$xmlStr .= '<img src="' . $this->docTitle['img'] . '" />';
			}
			$xmlStr .= '</docTitle>';
			
			if (false === empty($this->docAuthor) && false === empty($this->docAuthor['text'])) {
				$xmlStr .= '<docAuthor><text>' . $this->docAuthor['text'] . '</text>';
				if (false === empty($this->docAuthor['img'])) {
					$xmlStr .= '<img src="' . $this->docTitle['img'] . '" />';
				}
				$xmlStr .= '</docAuthor>';
			}
			
			$xmlStr .= '<navMap>';
			foreach ($this->navId2src as $navPointId => $src) {
				if (false === isset($this->navId2navPoint[$navPointId])) {
					throw new Exception('NavPoint with Id ' . $navPointId . ' doe not exists');
				}
				$xmlStr .= $this->navPoint2Xml($this->navId2navPoint[$navPointId]);
			}
			$xmlStr .= '</navMap>';
			
			if (false === empty($this->pageList)) {
				$xmlStr .= '<pageList>';
				foreach ($this->pageList as $pageTarget) {
					if (false === isset($pageTarget['type'])) {
						throw new Exception('Missing required element "type" within pageTarget');
					}
				 	if (false === isset($pageTarget['playOrder'])) {
						throw new Exception('Missing required element "playOrder" within pageTarget');
					}
					if (false === isset($pageTarget['navLabel'])) {
						throw new Exception('Missing required element "navLabel" within pageTarget');
					}
					if (false === isset($pageTarget['content'])) {
						throw new Exception('Missing required element "content" within pageTarget');
					}
					$xmlStr .= '<pageTarget type="' . $pageTarget['id'] . 
						'" playOrder="' . $pageTarget['playOrder'] . '"';
					if (true === isset($pageTarget['id'])) {
						$xmlStr .= ' id="' . $pageTarget['id'] . '"';
					}
					if (true === isset($pageTarget['value'])) {
						$xmlStr .= ' value="' . $pageTarget['value'] . '"';
					}
					if (true === isset($pageTarget['class'])) {
						$xmlStr .= ' class="' . $pageTarget['class'] . '"';
					}
					$xmlStr .= '><navLavel><text>' . $pageTarget['navLabel'] . '</text></navLabel>';
					$xmlStr .= '<content src="' . $pageTarget['content'] . '" />';
					$xmlStr .= '</pageTarget>';
				}
				$xmlStr .= '</pageList>';
			}
			
			if (false === empty($this->navList)) {
				$xmlStr .= '<navList>';
				foreach ($this->navList as $navTarget) {
					if (false === isset($navTarget['id'])) {
						throw new Exception('Missing required element "id" within navTarget');
					}
				 	if (false === isset($pageTarget['playOrder'])) {
						throw new Exception('Missing required element "playOrder" within navTarget');
					}
					if (false === isset($pageTarget['navLabel'])) {
						throw new Exception('Missing required element "navLabel" within navTarget');
					}
					if (false === isset($pageTarget['content'])) {
						throw new Exception('Missing required element "content" within navTarget');
					}
					$xmlStr .= '<navTarget id="' . $pageTarget['id'] . 
						'" playOrder="' . $pageTarget['playOrder'] . '"';
					if (true === isset($pageTarget['value'])) {
						$xmlStr .= ' value="' . $pageTarget['value'] . '"';
					}
					if (true === isset($pageTarget['class'])) {
						$xmlStr .= ' class="' . $pageTarget['class'] . '"';
					}
					$xmlStr .= '><navLavel><text>' . $pageTarget['navLabel'] . '</text></navLabel>';
					$xmlStr .= '<content src="' . $pageTarget['content'] . '" />';
					$xmlStr .= '</navTarget>';
				}
				$xmlStr .= '</navList>';
			}
			$xmlStr .= '</ncx>';

			XML::loadString($xmlStr, __DIR__ . $ds . 'Schema' . $ds . 'ncx.rng')->asXML($xmlFile);
		}
		
		/**
		 * Parse single navPoint and its children
		 * 
		 * @param array|\SimpleXMLElemen $navPoint
		 * 
		 * @return void
		 */
		protected function parseNavPoints(\SimpleXMLElement $navPoint, $parentId = null)
		{
			$item = array(
				'id'        => XML::getAttr($navPoint, 'id'),
				'playOrder' => XML::getAttr($navPoint, 'playOrder'),
				'class'     => XML::getAttr($navPoint, 'class'),
				'navLabel'  => trim($navPoint->navLabel->text),
				'content'   => XML::getAttr($navPoint->content, 'src'),
			);
			$this->navId2src[$item['id']] = $item['content'];
			if (true === isset($navPoint->navPoint)) {
				foreach ($navPoint->navPoint as $_navPoint) {
					$this->parseNavPoints($_navPoint, $item['id']);
				}
			}
			if (null !== $parentId) {
				$this->parents[$item['id']] = $parentId;
				if (false === isset($this->childs[$parentId])) {
					$this->childs[$parentId] = array();
				}
				$this->childs[$parentId][] = $item['id'];
			}
			$this->navId2navPoint[$item['id']] = $item;
			return $item;
		}
		
		/**
		 * Returns XML representation of the navPoint array
		 * 
		 * @param array $navPoint Navigation point arraya
		 * 
		 * @return string XML
		 */
		protected function navPoint2Xml(array $navPoint)
		{
			static $added;
			if (null === $added) {
				$added = array();
			}
			if (true === isset($added[$navPoint['id']])) {
				return '';
			}
			$added[$navPoint['id']] = true;
			$retval = '<navPoint playOrder="' . $navPoint['playOrder'] . '" id="' . $navPoint['id'] . '"';
			if (true === isset($navPoint['class']) && false === empty($navPoint['class'])) {
				$retval .= ' class="' . $navPoint['class'] . '"';
			}
			$retval .= '>';
			if (false === isset($navPoint['navLabel'])) {
				throw new Exception('Missing required element "navLabel" within navPoint');
			}
			$retval .= '<navLabel><text>' . $navPoint['navLabel'] . '</text></navLabel>';
			if (false === isset($navPoint['content'])) {
				throw new Exception('Missing required element "content" within navPoint');
			}
			$retval .= '<content src="' . $navPoint['content'] . '" />';
			if (true === isset($this->childs[$navPoint['id']])) {
				foreach ($this->childs[$navPoint['id']] as $navPointId) {
					if (false === isset($this->navId2navPoint[$navPointId])) {
						throw new Exception('NavPoint with Id ' . $navPointId . ' doe not exists');
					}
					$retval .= $this->navPoint2Xml($this->navId2navPoint[$navPointId]);
				}
			}
			$retval .= '</navPoint>';
			return $retval;
		}
	}
}

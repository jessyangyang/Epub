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
		 * Set chapters of the epub
		 * 
		 * @param string $title Title of the epub
		 * 
		 * @return array
		 */
		public function setTitle($title)
		{
			$this->ocf->opf->setMetadata('title', $title);
		} 
		
		/**
		 * Get chapters of the epub
		 * 
		 * @param string $title Title of the epub
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
		 * @return array
		 */
		public function setCreator($creator, $fileAs = null)
		{
			$this->ocf->opf->setMetadata(
				'creator', $creator,
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
		 * @return array
		 */
		public function setLanguage($value)
		{
			$this->ocf->opf->setMetadata(
				'language', $value,
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
		 * @return array
		 */
		public function setIdentifier($value)
		{
			$this->ocf->opf->setMetadata(
				'identifier', $value,
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
		 * @return array
		 */
		public function setPublisher($value)
		{
			$this->ocf->opf->setMetadata('publisher', $value);
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
		 * @return array
		 */
		public function setDate($value)
		{
			$this->ocf->opf->setMetadata(
				'date', $value,
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
		 * @return array
		 */
		public function setRights($value)
		{
			$this->ocf->opf->setMetadata('rights', $value);
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
		 * @return array
		 */
		public function setDescription($value)
		{
			$this->ocf->opf->setMetadata('description', $value);
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
		 * @param string $value Description
		 * 
		 * @return array
		 */
		public function setMetadata($name, $value)
		{
			$this->ocf->opf->setMetadata($name, $value);
		}
		
		/**
		 * Get metadata of the epub
		 * 
		 * @return string
		 */
		public function getMetadata($name)
		{
			$meta = $this->ocf->opf->getMetadata($name);
			if (false !== $meta) {
				return $meta['value'];
			}
		}
		
		/**
		 * Get spine of the epub.
		 * 
		 * Returns an array of ids of documents providing a linear reading order
		 * 
		 * @return string
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
		 * @return string
		 */
		public function setSpine(array $spine)
		{
			return $this->ocf->opf->setSpine($spine);
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
		 * Save epub under given filename
		 * 
		 * @param string $filename Filename of the resulting epub
		 * 
		 * @return void
		 */
		public function save($filename)
		{		
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
            \file_put_contents($rootPath . 'mimetype', 'application/epub+zip');
            
            $zip = new ZipArchive;
            $zip->open($filename, ZipArchive::CREATE);
            
            $addedDirs = array();
			$iterator  = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($rootPath));
    		foreach ($iterator as $item) {
    			if (true === $item->isDir()) {
    				continue;
    			}
				$zip->addFile($item->__toString(), 
					str_replace($rootPath, '', $item->__toString()));
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
          		|| 'application/epub+zip' !== \trim(\file_get_contents($f))
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
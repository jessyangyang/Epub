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
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'OCF.php';
	
	use ZipArchive;
	use RecursiveIteratorIterator;
	use RecursiveDirectoryIterator;
	
	/**
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
		 * @param string Epub filename to open
		 */
		public function __construct($filename)
		{
            $tmpPath = \sys_get_temp_dir() . DIRECTORY_SEPARATOR;
            if (false === \is_dir($tmpPath) || false === \is_writable($tmpPath)) {
                throw new \Exception(
                    'Temporary directory ' . $tmpPath . ' does not exist or is not writable'
                );
            }

            $tmpDir = $tmpPath . \uniqid('epub', true) . DIRECTORY_SEPARATOR;
            while (\is_dir($tmpDir)) {
                $tmpDir = $tmpPath . \uniqid('epub', true) . DIRECTORY_SEPARATOR;
            }
            if (false ===  @ \mkdir($tmpDir)) {
                throw new \Exception('Cannot create working directory ' . $tmpDir);
            }
            $this->tmpDir = $tmpDir;
            
			$this->filename = $filename;
            
            if (true === \is_file($filename)) {
            	$this->open($filename);
            } else {
            	$this->ocf = new OCF();
            }
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
		
		/**
		 * Open existing epub file
		 * 
		 * @param string $filename Epub filename
		 * 
		 * @return Epub instance
		 */
		protected function open($filename)
		{
			if (false === \is_file($filename)) {
				throw new \Exception('File "' . $filename . '" does not exist.');
			}
			
			if (false === \is_readable($filename)) {
				throw new \Exception('File "' . $filename . '" is not readable.');
			}
			
			$zip = new ZipArchive();
			if (true !== ($error = $zip->open($filename))) {
				// TODO: error parsing
				throw new \Exception('Unable to read epub file "' . $filename . '"');
			}
		
        	if (false === $zip->extractTo($this->tmpDir)) {
        		throw new \Exception(
              		'Cannot extract to ' . $this->tmpDir . ' due to unknown reason'
               	);
          	}
          	
          	// check if its really epub file
          	if (false === \is_file($f = $this->tmpDir . 'mimetype')
          		|| 'application/epub+zip' !== \trim(\file_get_contents($f))
          		|| false === \is_dir($this->tmpDir . 'META-INF')
          		|| false === \is_file($this->tmpDir . 'META-INF' . DIRECTORY_SEPARATOR . 'container.xml')
          	) {
          		throw new \Exception('File "' . $filename . '" is not a valid epub container.');
          	}
          	
          	$this->ocf = new OCF($this->tmpDir . 'META-INF' . DIRECTORY_SEPARATOR . 'container.xml');
		}
		
		/**
		 * Save epub file
		 * 
		 * 
		 */
		public function save($filename)
		{		
            $path     = \dirname($filename);
            if (false === \is_dir($path) || false === \is_writable($path)) {
                throw new \Exception('Directory "' . $path . '" does not exist or is not writable');
            }
            if (true === \is_file($filename)) {
                if (false === \is_writable($filename)) {
                    throw new \Exception('File "' . $filename . '" is not writable');
                }
                if (false === @ \rename($filename, $filename . '.bak')) {
                    throw new \Exception('Cannot rename file ' . $filename . ' to ' . $filename . '.bak');
                }
            }
            
            $rootPath = $this->tmpDir . uniqid() . DIRECTORY_SEPARATOR;
            mkdir($rootPath);
            $this->ocf->asXML($rootPath);
            file_put_contents($rootPath . 'mimetype', 'application/epub+zip');
            
            $zip = new ZipArchive;
            $zip->open($filename, ZipArchive::CREATE);
            
            $addedDirs = array();
			$iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath));
    		foreach ($iterator as $item) {
    			if (true === $item->isDir()) {
    				continue;
    			}
				$zip->addFile($item->__toString(), str_replace($rootPath, '', $item->__toString()));
    		}
    		$zip->close();
    		unset($zip);
		}
	}
}
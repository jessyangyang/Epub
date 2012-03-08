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
namespace Epub\PHPUnitTests
{
    require_once __DIR__ . '/../Epub.php';

    use \Epub\Epub;

    /**
     * ApiTest class
     *
     * @package    Epub
     * @subpackage PHPUnitTests
     * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
     * @version    Release: @package_version@
     * @link       https://github.com/dmitry-vinogradov/Epub
     * @since      Class available since Release 1.0.0
     */
    class ApiTest extends \PHPUnit_Framework_TestCase
    {
        /**
         * Path to the temporary directory
         * @var string
         */
        protected static $tmpDir;

        /**
         * Container for instance of the new Epub file
         * @var \Epub\Epub
         */
        protected static $newEpub;

        /**
         * Container for instance of the opened Epub file
         * @var \Epub\Epub
         */
        protected static $openedEpub;

        /**
         * Set up stuff for the tests.
         *
         * @return void
         */
        public static function setUpBeforeClass()
        {
            $systmp = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR;
            if (false === \is_dir($systmp)
                || false === \is_writeable($systmp)
                || false === @ \mkdir(($path = $systmp . time() . \DIRECTORY_SEPARATOR))
            ) {
                self::$tmpDir = false;
            } else {
                self::$tmpDir = $path;
            }
        }

        /**
         * Clean up resources
         *
         * @return void
         */
        public static function tearDownAfterClass()
        {
            if (self::$tmpDir !== false) {
                $cmd = 'rm -Rf ' . self::$tmpDir;
                @ `$cmd`;
            }
        }

        /**
         * Set up stuff for the tests.
         *
         * @return void
         */
        public function setUp()
        {
            if (false === self::$tmpDir) {
                $this->markTestSkipped(
                    'Temporary directory does not exist.'
                );
            }
        }

        /**
         * Test conctructor
         *
         * @return void
         */
        public function testConstructor()
        {
            self::$newEpub = new Epub(self::$tmpDir . 'new.epub');
            $this->assertInstanceOf('\\Epub\\Epub', self::$newEpub);
        }

        /**
         * Test conctructor
         *
         * @return void
         */
        public function testOpenEpub()
        {
            $epubFile = __DIR__ . \DIRECTORY_SEPARATOR . 'moby_dick.epub';
            if (false === \is_file($epubFile)) {
                $this->markTestSkipped('Epub file ' . $epubFile . ' is missing');
                return;
            }
            self::$openedEpub = new Epub($epubFile);
            $this->assertInstanceOf('\\Epub\\Epub', self::$openedEpub);
            $this->assertSame(self::$openedEpub->getTitle(), 'Moby Dick, or, the whale');
            $this->assertSame(self::$openedEpub->getLanguage(), 'en');
            $this->assertSame(self::$openedEpub->getIdentifier(), 'http://www.gutenberg.org/ebooks/2701');
            $this->assertSame(self::$openedEpub->getCreator(), 'Herman Melville');
            $this->assertSame(self::$openedEpub->getPublisher(), false);
            $this->assertSame(self::$openedEpub->getDate(), '2012-02-09T14:21:46.825499+00:00');
            $this->assertSame(self::$openedEpub->getRights(), 'Public domain in the USA.');
            $this->assertSame(self::$openedEpub->getDescription(), false);
        }

        /**
         * Test setting of the title
         *
         * @return void
         */
        public function testSetTitle()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getTitle(), 'Moby Dick, or, the whale');
            $newTitle = 'Moby Dick or the whale';
            self::$openedEpub->setTitle($newTitle);
            $this->assertSame(self::$openedEpub->getTitle(), $newTitle);

            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setTitle($newTitle);
            $this->assertSame(self::$newEpub->getTitle(), $newTitle);
        }

        /**
         * Test setting of the creator
         *
         * @return void
         */
        public function testSetCreator()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getCreator(), 'Herman Melville');
            $newCreator = 'Melville, Herman';
            self::$openedEpub->setCreator($newCreator);
            $this->assertSame(self::$openedEpub->getCreator(), $newCreator);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setCreator($newCreator);
            $this->assertSame(self::$newEpub->getCreator(), $newCreator);
        }

        /**
         * Test setting of the language
         *
         * @return void
         */
        public function testSetLanguage()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getLanguage(), 'en');
            $newLang = 'en-GB';
            self::$openedEpub->setLanguage($newLang);
            $this->assertSame(self::$openedEpub->getLanguage(), $newLang);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setLanguage($newLang);
            $this->assertSame(self::$newEpub->getLanguage(), $newLang);
        }

        /**
         * Test setting of the identifier
         *
         * @return void
         */
        public function testSetIdentifier()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getIdentifier(),
                'http://www.gutenberg.org/ebooks/2701');
            $newIdentifier = md5('http://www.gutenberg.org/ebooks/2701');
            self::$openedEpub->setIdentifier($newIdentifier);
            $this->assertSame(self::$openedEpub->getIdentifier(), $newIdentifier);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setIdentifier($newIdentifier);
            $this->assertSame(self::$newEpub->getIdentifier(), $newIdentifier);
        }

        /**
         * Test setting of the publisher
         *
         * @return void
         */
        public function testSetPublisher()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getPublisher(), false);
            $newPublisher = 'Gutenberg.org';
            self::$openedEpub->setPublisher($newPublisher);
            $this->assertSame(self::$openedEpub->getPublisher(), $newPublisher);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setPublisher($newPublisher);
            $this->assertSame(self::$newEpub->getPublisher(), $newPublisher);
        }

        /**
         * Test setting of the date
         *
         * @return void
         */
        public function testSetDate()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getDate(), '2012-02-09T14:21:46.825499+00:00');
            $newDate = date('Y-m-d');
            self::$openedEpub->setDate($newDate);
            $this->assertSame(self::$openedEpub->getDate(), $newDate);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setDate($newDate);
            $this->assertSame(self::$newEpub->getDate(), $newDate);
        }

        /**
         * Test setting of the rights
         *
         * @return void
         */
        public function testSetRights()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getRights(), 'Public domain in the USA.');
            $newRights = 'Foo Bar Ltd.';
            self::$openedEpub->setRights($newRights);
            $this->assertSame(self::$openedEpub->getRights(), $newRights);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setRights($newRights);
            $this->assertSame(self::$newEpub->getRights(), $newRights);
        }

        /**
         * Test setting of the description
         *
         * @return void
         */
        public function testSetDescription()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $this->assertSame(self::$openedEpub->getDescription(), false);
            $newDescr = 'Foo Bar ePub';
            self::$openedEpub->setDescription($newDescr);
            $this->assertSame(self::$openedEpub->getDescription(), $newDescr);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            self::$newEpub->setDescription($newDescr);
            $this->assertSame(self::$newEpub->getDescription(), $newDescr);
        }

        /**
         * Test getting of the spine
         *
         * @return void
         */
        public function testGetSpine()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $spine = self::$openedEpub->getSpine();
            $this->assertSame(count($spine), 25);
            $this->assertTrue(isset($spine['item7']['href']));
            $this->assertSame($spine['item7']['href'], 
                '@public@vhost@g@gutenberg@html@files@2701@2701-h@2701-h-3.htm.html');
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            $spine = self::$newEpub->getSpine();
            $this->assertSame(count($spine), 0);
        }
        
        /**
         * Test getting of the spine
         *
         * @return void
         */
        public function testGetChapters()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $chapters = self::$openedEpub->getChapters();
            $this->assertSame(count($chapters), 148);
            
            if (false === (self::$newEpub instanceof Epub)) {
                $this->markTestSkipped('No new epub file exists.');
                return;
            }
            $chapters = self::$newEpub->getChapters();
            $this->assertSame(count($chapters), 0);
        }
        
        /**
         * Test getting of the cover page
         * 
         * @return void
         */
        public function getCoverPage()
        {
            if (false === (self::$openedEpub instanceof Epub)) {
                $this->markTestSkipped('No opened epub file exists.');
                return;
            }
            $chapters = self::$openedEpub->getChapters();
            $this->assertSame(count($chapters), 148);
        }
    }
}

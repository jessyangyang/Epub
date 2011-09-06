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
     * @package    DB
     * @subpackage PHPUnitTests
     * @author     Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @copyright  2002-2011 Dmitry Vinogradov <dmitri.vinogradov@gmail.com>
     * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
     * @version    Release: @package_version@
     * @link       https://github.com/dmitry-vinogradov/PHP-DB
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
            $epub = new Epub(self::$tmpDir . 'new.epub');
            $this->assertInstanceOf('\\Epub\\Epub', $epub);
        }

        /**
         * Test conctructor
         *
         * @return void
         */
        public function testOpenEpub()
        {
            $epubFile = __DIR__ . \DIRECTORY_SEPARATOR . 'taz_2010_05_22.epub';
            if (false === \is_file($epubFile)) {
                $this->markTestSkipped('Epub file ' . $epubFile . ' is missing');
                return;
            }
            $epub = new Epub($epubFile);
            $this->assertInstanceOf('\\Epub\\Epub', $epub);
            $this->assertSame($epub->getTitle(), 'taz vom 22.05.2010');
            $this->assertSame($epub->getLanguage(), 'de-DE');
            $this->assertSame($epub->getIdentifier(), 'taz_2010-05-22_v_1');
            $this->assertSame($epub->getCreator(), 'taz Entwicklungs GmbH & Co. Medien KG');
            $this->assertSame($epub->getPublisher(), 'taz, die tageszeitung');
            $this->assertSame($epub->getDate(), '2010-05-22');
            $this->assertSame($epub->getDescription(), 'taz vom 22.05.2010');
        }

    }
}

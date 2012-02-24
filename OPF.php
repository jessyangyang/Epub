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
    require_once __DIR__ . \DIRECTORY_SEPARATOR . 'NCX.php';

    use Exception;

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
        protected $manifest = array();

        /**
         * An arrangement of documents providing a linear reading order.
         * @var array
         */
        protected $spine = array();

        /**
         * A set of references to fundamental structural features of the publication, such as
         * table of contents, foreword, bibliography, etc.
         * @var array
         */
        protected $guide = array();

        /**
         * Navigation Control file for XML
         * @var \Epub\NCX
         */
        protected $ncx;

        /**
         * Container for refcounting of the files
         * @var array
         */
        protected $fileRefCounter = array();

        /**
         * Container for the file usage
         * @var array
         */
        protected $fileUsage = array();

        /**
         * href to id map
         * @var array
         */
        protected $href2id = array();

        /**
         * Constructor.
         *
         * @param string $xmlFile XML file
         * @param string $strict  Do not tolerate epub errors
         *
         */
        public function __construct($xmlFile = null, $strict = true)
        {
            if ($xmlFile !== null) {
                $this->readXML($xmlFile, $strict);
            } else {
                $this->uid      = 'Epub-' . uniqid();
                $this->metadata = array(
                    'dc:title'      => array(
                        'value' => 'Untitled',
                        'attrs' => array()
                    ),
                    'dc:language'   => array(
                        'value' => 'en',
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
                $this->ncx = new NCX();
                $this->ncx->setUid($this->uid);
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
         * Get metadata
         *
         * @param string $meta   Meta name
         * @param string $prefix Namespace, default ``dc``
         *
         * @return array dc metadata or string meta value
         */
        public function getMetadata($meta, $prefix = 'dc')
        {
            $key = $prefix . ':' . $meta;
            $ret = isset($this->metadata[$key]) ? $this->metadata[$key] : false;
            if ($ret === false && isset($this->metadata['meta'][$meta])) {
                $ret = $this->metadata['meta'][$meta];
            }
            return $ret;
        }

        /**
         * Set metadata
         *
         * @param string $meta   Meta name
         * @param string $value  Value
         * @param string $prefix Namespace
         * @param array  $attrs  Attributes
         *
         */
        public function setMetadata($meta, $value , $prefix = null, array $attrs = array())
        {
            if ($prefix === null) {
                $this->metadata['meta'][$meta] = $value;
                return;
            }
            $key = $prefix . ':' . $meta;
            $this->metadata[$key] = array(
                'value' => $value,
                'attrs' => $attrs
            );
        }

        /**
         * Get manifest by identifier
         *
         * @param string $id Manifest identifier
         *
         * @return array
         */
        public function getManifestById($id)
        {
            return null === $id ?
                $this->manifest : (true === isset($this->manifest[$id]) ? $this->manifest[$id] : null);
        }

        /**
         * Get manifest by href
         *
         * @param string $href Manifest identifier
         *
         * @return array
         */
        public function getManifestByHref($href)
        {
            foreach ($this->manifest as $item) {
                if ($item['href'] === $href) {
                    return $item;
                }
            }
        }

        /**
         * Get spine.
         *
         * @return array
         */
        public function getSpine()
        {
            return $this->spine;
        }

        /**
         * Set spine
         *
         * @param array $spine Spine to set
         *
         * @return void
         */
        public function setSpine(array $spine)
        {
            $newSpine = array();
            foreach ($spine as $idref => $linear) {
                if (true === isset($this->manifest[$idref])) {
                    $newSpine[$idref] = array(
                        'href'   => $this->manifest[$idref]['href'],
                        'linear' => $linear == 'no' ? 'no' : 'yes'
                    );
                } else {
                    throw new Exception(
                        'Spine error: cannot find appropriate manifest item with id ' . $idref
                    );
                }
            }
            $this->spine = $newSpine;
        }
        
        /**
         * Get guide
         *
         * @return array
         */
        public function getGuide()
        {
            return $this->guide;
        }
        
        /**
         * Set guide
         *
         * @param array $guide Guide to set
         *
         * @return void
         */
        public function setGuide(array $guide)
        {
            $newGuide = array();
            foreach ($guide as $ref) {
                $href = false !== ($pos = strpos('#', $ref['href'])) ? 
                    substr($ref['href'], 0, $pos) : $ref['href'];
                $item = $this->getManifestByHref($href);
                if (null !== $item) {
                    $newGuide[] = $ref;
                } else {
                    throw new Exception(
                        'Guide error: cannot find appropriate manifest item with href ' . $href
                    );
                }
            }
            $this->guide = $newGuide;
        }
        
        /**
         * Set cover of the book
         *
         * @param string $cover Cover html file
         * @param string $title Guide title
         *
         * @return void
         */
        public function setCover($cover, $title)
        {
            if (false === \is_file($cover)) {
                throw new Exception('Cover file ' . $cover . ' does not exist.');
            }
            if (false === \is_readable($cover)) {
                throw new Exception('Cover file ' . $cover . ' is not readable.');
            }

            $id = $this->addChapter($title, $cover, null, 0, 'no');
            
            // remove from NCX
            $this->ncx->removeNavPoint($id);
            
            // add meta
            $this->setMetadata('cover', $id);
            
            // set guide
            $this->guide[] = array(
                'href'  => $this->manifest[$id]['href'],
                'type'  => 'cover',
                'title' => $title
            );
        }
        

        /**
         * Add chapter
         *
         * @param string $title  The title of the chapter
         * @param string $file   The content file of the chapter
         * @param string $parent The identifier of the parent navPoint
         *
         * @return string Identifier 
         */
        public function addChapter($title, $file, $parent = null, $pos = null, $linear = 'yes')
        {
            if (false === \is_file($file)) {
                throw new Exception('File ' . $file . ' does not exist.');
            }
            if (false === \is_readable($file)) {
                throw new Exception('File ' . $file . ' is not readable.');
            }

            $mimetype = self::getMimetype($file);
            if ($mimetype !== 'application/xhtml+xml' && $mimetype !== 'application/xml') {
                throw new Exception('Unsupported mymetype of the content file "' .
                    $file . '": ' . var_export($mimetype, 1));
            }

            $ds = \DIRECTORY_SEPARATOR;
            $content = XML::loadFile($file, __DIR__ . $ds . 'Schema' . $ds . 'content-xhtml.rng');
            $content->registerXPathNamespace('ns', 'http://www.w3.org/1999/xhtml');
            $refs = \array_merge(
                $content->xpath('//ns:link'),
                $content->xpath('//ns:img'),
                $content->xpath('//ns:iframe')
            );
            $filePath = \dirname($file) . $ds;
            $files    = array();
            foreach ($refs as $ref) {
                $src = XML::getAttr($ref, 'src');
                if ($src === null) {
                    $src = XML::getAttr($ref, 'href');
                }
                if ($src === null) {
                    throw new Exception('Cannot determine file of node ' . \print_r($ref, 1));
                }
                $_file = $filePath . $src;
                if (false === \is_file($_file)) {
                    throw new Exception('File "' . $_file . '" referenced within "' .
                        $file . '" does not exist.');
                }
                if ('.css' === \strtolower(\substr($_file, -4))) {
                    $css = \file_get_contents($_file);
                    if (0 < \preg_match_all('#url\s*\(([^\)]+)\)#smi', $css, $m)) {
                        foreach ($m[1] as $val) {
                            if ("'" === ($c = $val[0]) || '"' === ($c = $val[0])) {
                                $val = \substr($val, 1, \strlen($val) - 2);
                            }
                            $path = \dirname($_file) . $ds . $val;
                            if (false === \is_file($path)) {
                                throw new Exception('File "' . $path . '" referenced within "' .
                                    $_file . '" does not exist.');
                            }
                            $files[] = $path;
                        }
                    }
                }
                $files[] = $_file;
            }
            $files[] = $file;

            // add files to the manifest
            $files = \array_keys(\array_flip($files));
            foreach ($files as $key => $_file) {
                $x = ($_file === $file ? null : $file);
                $this->addRefCount($_file, $x);
                if ($this->fileRefCounter[$_file] === 1) {
                    $pinfo = \pathinfo($_file);
                    $id    = $pinfo['filename'];
                    $href  = str_replace($filePath, '', $_file);
                    $mime  = self::getMimetype($_file);
                    if (true === isset($this->manifest[$id])) {
                        if (\md5_file($_file) === \md5_file($this->manifest[$id]['file'])) {
                            // same file under same name already exists
                            $files[$key] = $this->manifest[$id]['file'];
                            $this->delRefCount($_file, $x);
                            $this->addRefCount($files[$key], $x);
                            continue;
                        } else {
                            // files are different
                            $i = 0;
                            $newid = $id . (++$i);
                            while (true === isset($this->manifest[$newid])) {
                                $newid = $id . (++$i);
                            }
                            $id = $newid;
                        }
                    }

                    if (true === isset($this->href2id[$href])) {
                        throw new Exception('File "' . $href .
                            '" is already exists within manifest under id ' . $id);
                    }
                    
                    if ($mime == 'application/xml' && $_file === $file) {
                        $mime =  'application/xhtml+xml';
                    }
            
                    $this->manifest[$id] = array(
                        'id'         => $id,
                        'href'       => $href,
                        'media-type' => $mime,
                        'file'       => $_file
                    );
                    $this->href2id[$href] = $id;
                }
            }

            // add to navMap and spine
            $prevHref = $this->ncx->addNavPoint($id, $title, $href, $parent);
            if (false !== $prevHref) {
                $spine = array();
                foreach ($this->spine as $key => $val) {
                    $spine[$key] = $val;
                    if ($val['href'] === $prevHref) {
                        $spine[$id] = array(
                            'href'   => $href,
                            'linear' => $linear
                        );
                    }
                }
                $this->spine = $spine;
            } else {
                if ($pos !== null) {
                    $spine = array();
                    $i = 0;
                    foreach ($this->spine as $key => $val) {
                        if ($pos === $i) {
                            $spine[$id] = array(
                                'href'   => $href,
                                'linear' => $linear
                            );
                        }
                        $spine[$key] = $val;
                    }
                    $this->spine = $spine;
                } else {
                    $this->spine[$id] = array(
                        'href'   => $href,
                        'linear' => $linear
                    );
                }
            }
            return $id;
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

            $package       = XML::loadFile($xmlFile, __DIR__ . $ds . 'Schema' . $ds . 'opf.rng');
            $this->uid     = XML::getAttr($package, 'unique-identifier');
            $this->version = XML::getAttr($package, 'version');

            // parse metadata
            $ns = $package->getDocNamespaces(true);
            foreach ($ns as $prefix => $uri) {
                foreach ($package->metadata->children($prefix, true) as $key => $value) {
                    $key = false === empty($prefix) ? $prefix . ':' . $key :  $key;
                    if ($key == 'meta') {
                        if (true === isset($this->metadata['meta'])) {
                            $this->metadata['meta'] = array();
                        }
                        $this->metadata['meta'][XML::getAttr($value, 'name')] = XML::getAttr($value, 'content');
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

            // parse manifest
            foreach ($package->manifest->item as $item) {

                // get node attributes
                $id   = XML::getAttr($item, 'id');
                $href = XML::getAttr($item, 'href');
                $type = XML::getAttr($item, 'media-type');
                $file = \dirname($xmlFile) . $ds . $href;

                // check foe existance of the file
                if (false === \is_file($file)) {
                    throw new Exception('File "' . $file . '" referenced in manifest under id ' .
                        $id . ' does not exist.');
                }

                // validate content of the file
                switch ($type) {

                    case 'application/xhtml+xml':
                        $content = XML::loadFile($file, __DIR__ . $ds . 'Schema' . $ds . 'content-xhtml.rng');
                        $content->registerXPathNamespace('ns', 'http://www.w3.org/1999/xhtml');
                        $refs = \array_merge(
                            $content->xpath('//ns:link'),
                            $content->xpath('//ns:img'),
                            $content->xpath('//ns:iframe')
                        );
                        foreach ($refs as $ref) {
                            $src = XML::getAttr($ref, 'src');
                            if ($src === null) {
                                $src = XML::getAttr($ref, 'href');
                            }
                            if ($src === null) {
                                throw new Exception('Cannot determine file of node ' . print_r($ref, 1));
                            }
                            $_file = \dirname($file) . $ds . $src;
                            if (false === \is_file($_file)) {
                                throw new Exception('File "' . $_file . '" referenced within "' .
                                    $file . '" does not exist.');
                            }
                            if ('.css' === \strtolower(\substr($_file, -4))) {
                                $css = \file_get_contents($_file);
                                // remove comments
                                $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
                                if (0 < \preg_match_all('#url\s*\(([^\)]+)\)#smi', $css, $m)) {
                                    foreach ($m[1] as $val) {
                                        if ("'" === ($c = $val[0]) || '"' === ($c = $val[0])) {
                                            $val = \substr($val, 1, \strlen($val) - 2);
                                        }
                                        $path = \dirname($_file) . '/' . $val;
                                        if (false === \is_file($path)) {
                                            throw new Exception('File "' . $path . '" referenced within "' .
                                                $_file . '" does not exist.');
                                        }
                                        $this->addRefCount($path, $_file);
                                    }
                                }
                            }
                            $this->addRefCount($_file, $file);
                        }
                        break;
                }

                // increase refcounter
                $this->addRefCount($file);

                // add to manifest
                $this->manifest[$id] = array(
                    'id'         => $id,
                    'href'       => $href,
                    'media-type' => $type,
                    'file'       => $file
                );

                $this->href2id[$href] = $id;

                // instantiate NCX
                if ($id === 'ncx') {
                    $this->ncx = new NCX($file, $strict);
                }
            }

            // parse spine
            foreach ($package->spine->itemref as $itemref) {
                $idref  = XML::getAttr($itemref, 'idref');
                $linear = XML::getAttr($itemref, 'linear');
                if ($idref && true === isset($this->manifest[$idref])) {
                    $this->spine[$idref] = array(
                        'href'   => $this->manifest[$idref]['href'],
                        'linear' => $linear == 'no' ? 'no' : 'yes'
                    );
                } else {
                    throw new Exception(
                        'Spine error: cannot find appropriate manifest item with id ' . $idref
                    );
                }
            }

            // parse guide
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
            // shortcut
            $ds = \DIRECTORY_SEPARATOR;

            $xmlPath = \dirname($xmlFile) . $ds;
            if (false === \is_dir($xmlPath) && false === @ \mkdir($xmlPath)) {
                throw new Exception('Cannot create directory "' . $xmlPath . '" due to unknown reason.');
            }

            $xmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
                      '<package version="' . $this->version . '" xmlns="http://www.idpf.org/2007/opf" ' .
                      'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
                      'unique-identifier="EpubId">' . PHP_EOL;

            // metadata
            $xmlStr .= '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" ' .
                       'xmlns:opf="http://www.idpf.org/2007/opf">' . PHP_EOL;
            foreach ($this->metadata as $key => $item) {
                if ($key === 'meta') {
                    foreach ($item as $name => $content) {
                        $xmlStr .= '<meta name="' . $name . '" content="' . 
                                \htmlspecialchars($content, \ENT_COMPAT, 'UTF-8') . '" />' . PHP_EOL;
                    }
                } else {
                    $xmlStr .= '<' . $key;
                    if (true === isset($item['attrs'])) {
                        foreach ($item['attrs'] as $name => $value) {
                            $xmlStr .= ' ' . $name . '="' . \htmlspecialchars($value, \ENT_COMPAT, 'UTF-8') . '"';
                        }
                    }
                    $xmlStr .= $item['value'] === '' ? ' />' : '>' .  
                            \htmlspecialchars($item['value'], \ENT_COMPAT, 'UTF-8') . '</' . $key . '>';
                    $xmlStr .= PHP_EOL;
                }
            }
            $xmlStr .= '</metadata>' . PHP_EOL;

            // manifest
            if (empty($this->manifest)) {
                throw new Exception('The manifest element must contain one or more item elements.');
            }
            
            // add ncx
            if ($this->ncx) {
                if (!isset($this->manifest['ncx']) && $this->ncx->count()) {
                    $this->manifest['ncx'] = array(
                        'id'         => 'ncx',
                        'href'       => 'toc.ncx',
                        'media-type' => 'application/x-dtbncx+xml',
                        'file'       => $xmlPath . 'toc.ncx'
                    );
                }
                if (false === isset($this->manifest['ncx'])
                    || true === empty($this->manifest['ncx']['href'])) {
                    throw new Exception('Cannot determine filename of the NCX');
                }
                $this->ncx->asXML($xmlPath . $this->manifest['ncx']['href']);
            }

            
            $hrefs2ids = array();
            $xmlStr .= '<manifest>' . PHP_EOL;

            foreach ($this->manifest as $id => $item) {
                if (false === isset($item['id'])
                    || false === isset($item['href'])
                    || false === isset($item['media-type'])
                ) {
                    throw new Exception('Each item element contained within a manifest element must ' .
                        'have the attributes id, href and media-type');
                }
                if (false === isset($item['file']) || false === \is_file($item['file'])) {
                    throw new Exception('Missing file associated with manifest item with id ' . $item['id']);
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
                $xmlStr    .= ' media-type="' . $item['media-type'] . '" />' . PHP_EOL;
                $targetPath = \dirname($xmlPath . $item['href']);
                if (false === \is_dir($targetPath) && false === @ \mkdir($targetPath)) {
                    throw new Exception('Cannot create directory "' . $targetPath .
                        '" due to unknown reason.');
                }
                if ($item['file'] !== $xmlPath . $item['href'] && 
                        false === @ \copy($item['file'], $xmlPath . $item['href'])) {
                    throw new Exception('Cannot copy file "' . $item['file'] .
                        '" to "' . $xmlPath . $item['href'] . '" due to unknown reason.');
                }
            }
            $xmlStr .= '</manifest>' . PHP_EOL;

            // spine
            if (true === empty($this->spine)) {
                throw new Exception('The spine element must contain one or more itemref elements.');
            }
            $xmlStr .= '<spine';
            if (true === isset($this->manifest['ncx'])) {
                $xmlStr .= ' toc="ncx"';
            }
            $xmlStr .= '>' . PHP_EOL;
            foreach ($this->spine as $key => $item) {
                if (false === isset($this->manifest[$key])) {
                    throw new Exception('Reference to a nonexistant content document: ' . $key);
                }
                $xmlStr .= '<itemref idref="' . $key . '"';
                if (true === isset($item['linear'])) {
                    $xmlStr .= ' linear="' . $item['linear'] . '"';
                }
                $xmlStr .= ' />' . PHP_EOL;
            }
            $xmlStr .= '</spine>' . PHP_EOL;

            // guide
            if (false === empty($this->guide)) {
                $xmlStr .= '<guide>' . PHP_EOL;
                foreach ($this->guide as $item) {
                    if (false === isset($hrefs2ids[$item['href']])) {
                        throw new Exception('Guide element is not referenced in the manifest: ' .
                            $item['href']);
                    }
                    $xmlStr .= '<reference type="' . $item['type'] .
                               '" title="' . $item['title'] .
                               '" href="' . $item['href'] . '" />' . PHP_EOL;
                }
                $xmlStr .= '</guide>' . PHP_EOL;
            }
            $xmlStr .= '</package>' . PHP_EOL;

            XML::loadString($xmlStr, __DIR__ . $ds . 'Schema' . $ds . 'opf.rng')->asXML($xmlFile);
        }

        /**
         * Increase refcount for given file
         *
         * @param string $file   Path to the file
         * @param string $usedBy Path to the file where first file is used by
         *
         * @return void
         */
        protected function addRefCount($file, $usedBy = null)
        {
            if (false === isset($this->fileRefCounter[$file])) {
                $this->fileRefCounter[$file] = 0;
            }
            $this->fileRefCounter[$file]++;
            if (null !== $usedBy) {
                if (false === isset($this->fileUsage[$usedBy])) {
                    $this->fileUsage[$usedBy] = array();
                }
                $this->fileUsage[$usedBy][] = $file;
            }
        }

        /**
         * Decrease refcount for given file
         *
         * @param string $file Path to the file
         * @param string $usedBy Path to the file where first file is used by
         *
         * @return void
         */
        protected function delRefCount($file, $usedBy = null)
        {
            if (false === isset($this->fileRefCounter[$file])) {
                return;
            }
            $this->fileRefCounter[$file]--;
            if ($this->fileRefCounter[$file] <= 0) {
                unset($this->fileRefCounter[$file]);
            }
            if (null !== $usedBy) {
                if (false === isset($this->fileUsage[$usedBy])) {
                    return;
                }
                foreach ($this->fileUsage[$usedBy] as $key => $val) {
                    if ($val === $file) {
                        unset($this->fileUsage[$usedBy][$key]);
                        if (0 === count($this->fileUsage[$usedBy])) {
                            unset($this->fileUsage[$usedBy]);
                        }
                        return;
                    }
                }
            }
        }

        /**
         * Get mimetype of the give file
         *
         * @param string $file
         *
         * @return string
         */
        public static function getMimetype($file)
        {
            static $finfo;
            if (false === ($finfo instanceof \FInfo)) {
                $finfo = new \Finfo(\FILEINFO_MIME_TYPE);
            }
            switch (\strtolower(substr($file, -4))) {
                case '.ttf':
                    return 'application/x-font-ttf';
                    break;
                case '.css':
                    return 'text/css';
                    break;
                default:
                    return $finfo->file($file);
                    break;
            }
        }
    }
}

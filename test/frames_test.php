<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../page.php');
    require_once(dirname(__FILE__) . '/../frames.php');
    
    Mock::generate('SimplePage');
    
    class TestOfFrameset extends UnitTestCase {
        function TestOfFrameset() {
            $this->UnitTestCase();
        }
        function getPageMethods() {
            $methods = array();
            foreach (get_class_methods('SimplePage') as $method) {
                if (strtolower($method) == strtolower('SimplePage')) {
                    continue;
                }
                if (strncmp($method, '_', 1) == 0) {
                    continue;
                }
                if (strncmp($method, 'accept', 6) == 0) {
                    continue;
                }
                $methods[] = $method;
            }
            sort($methods);
            return $methods;
        }
        function getFramesetMethods() {
            $methods = array();
            foreach (get_class_methods('SimpleFrameset') as $method) {
                if (strtolower($method) == strtolower('SimpleFrameset')) {
                    continue;
                }
                if (strncmp($method, '_', 1) == 0) {
                    continue;
                }
                if (strncmp($method, 'add', 3) == 0) {
                    continue;
                }
                $methods[] = $method;
            }
            sort($methods);
            return $methods;
        }
        function _testFramsetHasPageInterface() {
            $this->assertEqual(
                    $this->dump($this->getPageMethods()),
                    $this->dump($this->getFramesetMethods()));
        }
        function testTitleReadFromFramesetPage() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getTitle', 'This page');
            $frameset = &new SimpleFrameset($page);
            $this->assertEqual($frameset->getTitle(), 'This page');
        }
        function TestHeadersReadFromFramesetByDefault() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getHeaders', 'Header: content');
            $page->setReturnValue('getMimeType', 'text/xml');
            $page->setReturnValue('getResponseCode', 401);
            $page->setReturnValue('getTransportError', 'Could not parse headers');
            $page->setReturnValue('getAuthentication', 'Basic');
            $page->setReturnValue('getRealm', 'Safe place');
            
            $frameset = &new SimpleFrameset($page);
            
            $this->assertIdentical($frameset->getHeaders(), 'Header: content');
            $this->assertIdentical($frameset->getMimeType(), 'text/xml');
            $this->assertIdentical($frameset->getResponseCode(), 401);
            $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
            $this->assertIdentical($frameset->getAuthentication(), 'Basic');
            $this->assertIdentical($frameset->getRealm(), 'Safe place');
        }
        function testEmptyFramesetHasNoContent() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRaw', 'This content');
            $frameset = &new SimpleFrameset($page);
            $this->assertEqual($frameset->getRaw(), '');
        }
        function testRawContentIsFromOnlyFrame() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRaw', 'Stuff');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            $this->assertEqual($frameset->getRaw(), 'Stuff');
        }
        function testRawContentIsFromAllFrames() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
        function testAbsoluteUrlsComeFromBothFrames() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getAbsoluteUrls');
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue(
                    'getAbsoluteUrls',
                    array('http://www.lastcraft.com/', 'http://myserver/'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue(
                    'getAbsoluteUrls',
                    array('http://www.lastcraft.com/', 'http://test/'));
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual(
                    $frameset->getAbsoluteUrls(),
                    array('http://www.lastcraft.com/', 'http://myserver/', 'http://test/'));
        }
        function testRelativeUrlsComeFromBothFrames() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRelativeUrls');
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue(
                    'getRelativeUrls',
                    array('/', '.', '/test/', 'goodbye.php'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue(
                    'getRelativeUrls',
                    array('/', '..', '/test/', 'hello.php'));
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual(
                    $frameset->getRelativeUrls(),
                    array('/', '.', '/test/', 'goodbye.php', '..', 'hello.php'));
        }
    }
    
    class TestOfFrameNavigation extends UnitTestCase {
        function TestOfFrameNavigation() {
            $this->UnitTestCase();
        }
        function testStartsWithoutFrameFocus() {
            $page = &new MockSimplePage($this);
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            $this->assertFalse($frameset->getFrameFocus());
        }
        function testCanFocusOnSingleFrame() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRaw', 'Stuff');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            
            $this->assertFalse($frameset->setFrameFocusByIndex(0));
            $this->assertTrue($frameset->setFrameFocusByIndex(1));
            $this->assertFalse($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getRaw(), 'Stuff');
            $this->assertIdentical($frameset->getFrameFocus(), 1);
        }
        function testContentComesFromFrameInFocus() {
            $page = &new MockSimplePage($this);
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            
            $this->assertTrue($frameset->setFrameFocusByIndex(1));
            $this->assertEqual($frameset->getFrameFocus(), 1);
            $this->assertEqual($frameset->getRaw(), 'Stuff1');
            
            $this->assertTrue($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getFrameFocus(), 2);
            $this->assertEqual($frameset->getRaw(), 'Stuff2');
            
            $this->assertFalse($frameset->setFrameFocusByIndex(3));
            $this->assertEqual($frameset->getFrameFocus(), 2);
            
            $frameset->clearFrameFocus();
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
        function testCanFocusByName() {
            $page = &new MockSimplePage($this);
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            
            $this->assertTrue($frameset->setFrameFocus('A'));
            $this->assertEqual($frameset->getFrameFocus(), 'A');
            $this->assertEqual($frameset->getRaw(), 'Stuff1');
            
            $this->assertTrue($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getFrameFocus(), 'B');
            $this->assertEqual($frameset->getRaw(), 'Stuff2');
            
            $this->assertFalse($frameset->setFrameFocus('z'));
            
            $frameset->clearFrameFocus();
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
        function testHeadersReadFromFrameIfInFocus() {
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getHeaders', 'Header: content');
            $frame->setReturnValue('getMimeType', 'text/xml');
            $frame->setReturnValue('getResponseCode', 401);
            $frame->setReturnValue('getTransportError', 'Could not parse headers');
            $frame->setReturnValue('getAuthentication', 'Basic');
            $frame->setReturnValue('getRealm', 'Safe place');
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame);
            $frameset->setFrameFocusByIndex(1);
            
            $this->assertIdentical($frameset->getHeaders(), 'Header: content');
            $this->assertIdentical($frameset->getMimeType(), 'text/xml');
            $this->assertIdentical($frameset->getResponseCode(), 401);
            $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
            $this->assertIdentical($frameset->getAuthentication(), 'Basic');
            $this->assertIdentical($frameset->getRealm(), 'Safe place');
        }
        function testReadUrlsFromFrameInFocus() {
            $page = &new MockSimplePage($this);
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getAbsoluteUrls', array('a'));
            $frame1->setReturnValue('getRelativeUrls', array('r'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->expectNever('getAbsoluteUrls');
            $frame2->expectNever('getRelativeUrls');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            $frameset->setFrameFocus('A');
            
            $this->assertEqual($frameset->getAbsoluteUrls(), array('a'));
            $this->assertEqual($frameset->getRelativeUrls(), array('r'));
        }
    }
?>
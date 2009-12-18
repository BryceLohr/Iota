<?php
/**
 * Basic View tests
 *
 * @category   UnitTests
 * @package    View
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once dirname(__FILE__).'/../testSetup.php';

class Iota_ViewTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorTakesTemplatePath()
    {
        // Make sure it takes an argument w/o complaining
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        // Omitting the argument should cause a PHP warning
        try {
            $v = new Iota_View;
        } catch (ErrorException $e) {
            // success
        }
    }

    public function testToStringRendersAndReturnsTemplate()
    {
        $actual = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');
        $this->assertEquals('Hello World', trim($actual)); // trim off newline
    }

    public function testEscapeEscapesHtml()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $actual = $v->escape('Plain string');
        $expected = 'Plain string';
        $this->assertEquals($expected, $actual);

        // This is meant to ensure that htmlentities() is being called, rather 
        // than to test the full range of htmlentities()'s capabilities.
        $actual = $v->escape('<tag> & "special" chars');
        $expected = '&lt;tag&gt; &amp; &quot;special&quot; chars';
        $this->assertEquals($expected, $actual);
    }

    public function testEscapePassesEmptiesThroughUntouched()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $int    = 0;
        $float  = 0.0;
        $bool   = false;
        $null   = null;
        $string = '';

        $this->assertTrue(0     === $v->escape($int));
        $this->assertTrue(0.0   === $v->escape($float));
        $this->assertTrue(false === $v->escape($bool));
        $this->assertTrue(null  === $v->escape($null));
        $this->assertTrue(''    === $v->escape($string));
    }

    public function testEscapePassesNonStringScalarsThroughUntouched()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $int   = 123;
        $float = 3.14;
        $bool  = true;

        $this->assertType('int',   $v->escape($int));
        $this->assertType('float', $v->escape($float));
        $this->assertType('bool',  $v->escape($bool));
    }

    public function testArraysAreRecursivelyEscaped()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $input = array(
            '&',
            'key' => array('plain', '<', '>'),
            '"'
        );

        $expected = array(
            '&amp;',
            'key' => array('plain', '&lt;', '&gt;'),
            '&quot;'
        );

        $actual = $v->escape($input);
        $this->assertEquals($expected, $actual);
    }

    public function testSetPropertiesAreAutoEscapedAsViewVars()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->testString = '<tag> & "special" chars';
        $v->testArray  = array(
            '&',
            'key' => array('plain', '<', '>'),
            '"'
        );

        $expectedString = '&lt;tag&gt; &amp; &quot;special&quot; chars';
        $expectedArray  = array(
            '&amp;',
            'key' => array('plain', '&lt;', '&gt;'),
            '&quot;'
        );

        $this->assertEquals($expectedString, $v->testString);
        $this->assertEquals($expectedArray,  $v->testArray);
    }

    public function testImportCopiesArraysIntoView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $test = array(
            'key1' => 'plain data',
            'key2' => 'needs <escaping>',
            'key3' => array('foo', '"bar"', 'dog'=>'cat')
        );

        $v->import($test);

        $this->assertEquals('plain data', $v->key1);
        $this->assertEquals('needs &lt;escaping&gt;', $v->key2);

        $expected = array('foo', '&quot;bar&quot;', 'dog'=>'cat');
        $this->assertEquals($expected, $v->key3);
    }

    public function testImportCopiesObjectIntoView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $test = new stdClass;
        $test->key1 = 'plain data';
        $test->key2 = 'needs <escaping>';
        $test->key3 = array('foo', '"bar"', 'dog'=>'cat');

        $v->import($test);

        $this->assertEquals('plain data', $v->key1);
        $this->assertEquals('needs &lt;escaping&gt;', $v->key2);

        $expected = array('foo', '&quot;bar&quot;', 'dog'=>'cat');
        $this->assertEquals($expected, $v->key3);
    }

    public function testImportCopiesIteratorIntoView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $t = array(
            'key1' => 'plain data',
            'key2' => 'needs <escaping>',
            'key3' => array('foo', '"bar"', 'dog'=>'cat')
        );
        $test = new ArrayObject($t);

        $v->import($test);

        $this->assertEquals('plain data', $v->key1);
        $this->assertEquals('needs &lt;escaping&gt;', $v->key2);

        $expected = array('foo', '&quot;bar&quot;', 'dog'=>'cat');
        $this->assertEquals($expected, $v->key3);
    }

    public function testSetRawDoesNotEscape()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $obj = new stdClass;
        $str = '<tag> & "special" chars';
        $arr = array(
            '&',
            'key' => array('plain', '<', '>'),
            '"'
        );

        $v->setRaw('testObj',    $obj);
        $v->setRaw('testString', $str);
        $v->setRaw('testArray',  $arr);

        $this->assertType('object', $v->getRaw('testObj'));
        $this->assertSame($obj,     $v->getRaw('testObj'));
        $this->assertType('string', $v->getRaw('testString'));
        $this->assertEquals($str,   $v->getRaw('testString'));
        $this->assertType('array',  $v->getRaw('testArray'));
        $this->assertEquals($arr,   $v->getRaw('testArray'));
    }

    public function testViewVarsAreVariablesInTemplate()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate2.phtml');

        $v->title = 'Title';
        $v->body  = 'Body <content>';

        $expected = <<<HTML
<html>
<head>
    <title>Title</title>
</head>
<body>
    <h1>Title</h1>
    <p>Body &lt;content&gt;</p>
</body>
</html>

HTML;
        
        $this->assertEquals($expected, (string)$v);
    }

    public function testSubviewReturnsNewView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $result = $v->subview(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
    }

    public function testSubviewSetsViewVarsFromArray()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $result = $v->subview(dirname(__FILE__).'/_files/viewTemplate2.phtml', array(
            'title' => 'Title',
            'body'  => 'Body'
        ));

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
        $this->assertEquals('Title', $result->title);
        $this->assertEquals('Body', $result->body);
    }

    public function testSubviewSetsViewVarsFromObject()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $test = new stdClass;
        $test->title = 'Title';
        $test->body  = 'Body';

        $result = $v->subview(dirname(__FILE__).'/_files/viewTemplate2.phtml', $test);

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
        $this->assertEquals('Title', $result->title);
        $this->assertEquals('Body', $result->body);
    }

    public function testSubviewSetsViewVarsFromIterator()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $t = array(
            'title' => 'Title',
            'body'  => 'Body'
        );
        $test = new ArrayObject($t);

        $result = $v->subview(dirname(__FILE__).'/_files/viewTemplate2.phtml', $test);

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
        $this->assertEquals('Title', $result->title);
        $this->assertEquals('Body', $result->body);
    }

    public function testChildViewsPlaceholdersAvailableInParentView()
    {
        $parent = new Iota_View(dirname(__FILE__).'/_files/parentView.phtml');
        $child  = new Iota_View(dirname(__FILE__).'/_files/childView.phtml');
        $parent->child = $child;

        $actual = (string) $parent;
        $expected = <<<TXT
Children's placeholder values:
From direct child 
From sub view
TXT;

        $this->assertEquals($expected, $actual);
    }

    public function testIncludeJsAddsScriptPath()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->includeJs());

        $v->includeJs('/test.js');
        $expected = '<script type="text/javascript" src="/test.js"></script>';
        $this->assertEquals($expected, $v->includeJs());

        $v->includeJs('/another.js');
        $expected = '<script type="text/javascript" src="/test.js"></script>'."\n".
                    '<script type="text/javascript" src="/another.js"></script>';
        $this->assertEquals($expected, $v->includeJs());

        // The same path shouldn't be added twice
        $v->includeJs('/test.js');
        $expected = '<script type="text/javascript" src="/test.js"></script>'."\n".
                    '<script type="text/javascript" src="/another.js"></script>';
        $this->assertEquals($expected, $v->includeJs());
    }

    public function testIncludeCssAddsStylePath()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->includeCss());

        $v->includeCss('/test.css');
        $expected = '<link rel="stylesheet" type="text/css" href="/test.css">';
        $this->assertEquals($expected, $v->includeCss());

        $v->includeCss('/another.css');
        $expected = '<link rel="stylesheet" type="text/css" href="/test.css">'."\n".
                    '<link rel="stylesheet" type="text/css" href="/another.css">';
        $this->assertEquals($expected, $v->includeCss());

        // The same path shouldn't be added twice
        $v->includeCss('/test.css');
        $expected = '<link rel="stylesheet" type="text/css" href="/test.css">'."\n".
                    '<link rel="stylesheet" type="text/css" href="/another.css">';
        $this->assertEquals($expected, $v->includeCss());
    }

    public function testIncludeCssSupportsMediaType()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->includeCss('/test.css', 'screen');
        $expected = '<link rel="stylesheet" type="text/css" media="screen" href="/test.css">';
        $this->assertEquals($expected, $v->includeCss());

        $v->includeCss('/another.css', 'all');
        $expected = '<link rel="stylesheet" type="text/css" media="screen" href="/test.css">'."\n".
                    '<link rel="stylesheet" type="text/css" media="all" href="/another.css">';
        $this->assertEquals($expected, $v->includeCss());
    }

    public function testAddHeadJsAddsBlockOfJs()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script>';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("more");');
        $expected = '<script type="text/javascript">alert("hi");</script>'."\n".
                    '<script type="text/javascript">alert("more");</script>';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script>'."\n".
                    '<script type="text/javascript">alert("more");</script>'."\n".
                    '<script type="text/javascript">alert("hi");</script>';
        $this->assertEquals($expected, $v->addHeadJs());
    }

    public function testAddHeadCssAddsBlockOfCss()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->addHeadCss());

        $v->addHeadCss('h1 {font-style: italic;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>';
        $this->assertEquals($expected, $v->addHeadCss());

        $v->addHeadCss('label {float: left;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css">label {float: left;}</style>';
        $this->assertEquals($expected, $v->addHeadCss());

        $v->addHeadCss('h1 {font-style: italic;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css">label {float: left;}</style>'."\n".
                    '<style type="text/css">h1 {font-style: italic;}</style>';
        $this->assertEquals($expected, $v->addHeadCss());
    }

    public function testAddHeadCssSupportsMediaType()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->addHeadCss('h1 {font-style: italic;}', 'tv');
        $expected = '<style type="text/css" media="tv">h1 {font-style: italic;}</style>';
        $this->assertEquals($expected, $v->addHeadCss());

        $v->addHeadCss('label {float: left;}', 'all');
        $expected = '<style type="text/css" media="tv">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css" media="all">label {float: left;}</style>';
        $this->assertEquals($expected, $v->addHeadCss());
    }

    public function testAddHeadJsOnceAddsOnlyOnce()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->addHeadJsOnce());

        $v->addHeadJsOnce('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script>';
        $this->assertEquals($expected, $v->addHeadJsOnce());

        $v->addHeadJsOnce('alert("more");');
        $expected = '<script type="text/javascript">alert("hi");</script>'."\n".
                    '<script type="text/javascript">alert("more");</script>';
        $this->assertEquals($expected, $v->addHeadJsOnce());

        // The duplicate code shouldn't be added twice
        $v->addHeadJsOnce('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script>'."\n".
                    '<script type="text/javascript">alert("more");</script>';
        $this->assertEquals($expected, $v->addHeadJsOnce());
    }

    public function testAddHeadCssOnceAddsOnlyOnce()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->addHeadCssOnce());

        $v->addHeadCssOnce('h1 {font-style: italic;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>';
        $this->assertEquals($expected, $v->addHeadCssOnce());

        $v->addHeadCssOnce('label {float: left;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css">label {float: left;}</style>';
        $this->assertEquals($expected, $v->addHeadCssOnce());

        // The duplicate code shouldn't be added twice
        $v->addHeadCssOnce('h1 {font-style: italic;}');
        $expected = '<style type="text/css">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css">label {float: left;}</style>';
        $this->assertEquals($expected, $v->addHeadCssOnce());
    }

    public function testAddHeadCssOnceSupportsMediaType()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->addHeadCssOnce('h1 {font-style: italic;}', 'all');
        $expected = '<style type="text/css" media="all">h1 {font-style: italic;}</style>';
        $this->assertEquals($expected, $v->addHeadCssOnce());

        $v->addHeadCssOnce('label {float: left;}', 'tty');
        $expected = '<style type="text/css" media="all">h1 {font-style: italic;}</style>'."\n".
                    '<style type="text/css" media="tty">label {float: left;}</style>';
        $this->assertEquals($expected, $v->addHeadCssOnce());
    }

    public function testUrlProxiesToRouterUrl()
    {
        $mockRouter = $this->getMock('Iota_Controller_Router', array('url'), array(array()));
        $mockRouter->expects($this->exactly(2))
                   ->method('url');

        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->url('routeName');
        $v->url('routeName', array('parm'=>'val'));
    }

    public function testUrlEscapesForHtml()
    {
        $testRoutes = array('routeName'=>array('route'=>'/path', 'controller'=>'Test'));
        $mockRouter = $this->getMock('Iota_Controller_Router', null, array($testRoutes));
        $mockRouter->expects($this->any())
                   ->method('url');

        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $actual = $v->url('routeName', array('parm'=>'val', 'foo'=>'bar'));
        $expected = '/path?parm=val&amp;foo=bar';

        $this->assertEquals($expected, $actual);
    }

    public function testAbsUrlProxiesToRouterAbsUrl()
    {
        $mockRouter = $this->getMock('Iota_Controller_Router', array('absUrl'), array(array()));
        $mockRouter->expects($this->exactly(2))
                   ->method('absUrl');

        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->absUrl('routeName');
        $v->absUrl('routeName', array('parm'=>'val'));
    }

    public function testAbsUrlEscapesForHtml()
    {
        $_SERVER['HTTP_HOST'] = 'test';

        $testRoutes = array('routeName'=>array('route'=>'/path', 'controller'=>'Test'));
        $mockRouter = $this->getMock('Iota_Controller_Router', null, array($testRoutes));
        $mockRouter->expects($this->any())
                   ->method('absUrl');

        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $actual = $v->absUrl('routeName', array('parm'=>'val', 'foo'=>'bar'));
        $expected = 'http://test/path?parm=val&amp;foo=bar';

        $this->assertEquals($expected, $actual);
    }
}

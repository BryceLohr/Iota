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
        } catch (EPhpMessage $e) {
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

    public function testEscapeCastsToString()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $int = 123;
        $float = 3.14;
        $bool = true;
        $obj = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $this->assertType('string', $v->escape($int));
        $this->assertType('string', $v->escape($float));
        $this->assertType('string', $v->escape($bool));
        $this->assertType('string', $v->escape($obj));
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

    public function testSetPropertiesAreAutoEscaped()
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

    public function testBulkCopyArraysIntoView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $test = array(
            'key1' => 'plain data',
            'key2' => 'needs <escaping>',
            'key3' => array('foo', '"bar"', 'dog'=>'cat')
        );

        $v->bulkCopy($test);

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

        $this->assertType('object', $v->testObj);
        $this->assertSame($obj,     $v->testObj);
        $this->assertType('string', $v->testString);
        $this->assertEquals($str,   $v->testString);
        $this->assertType('array',  $v->testArray);
        $this->assertEquals($arr,   $v->testArray);
    }

    public function testPropertiesAreVariablesInTemplate()
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

    public function testFactoryReturnsNewView()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $result = $v->factory(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
    }

    public function testFactorySetsPropertiesFromArray()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $result = $v->factory(dirname(__FILE__).'/_files/viewTemplate2.phtml', array(
            'title' => 'Title',
            'body'  => 'Body'
        ));

        $this->assertType('Iota_View', $result);
        $this->assertNotSame($result, $v);
        $this->assertEquals('Title', $result->title);
        $this->assertEquals('Body', $result->body);
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

    public function testAddHeadJsAddsBlockOfJs()
    {
        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $expected = '';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script>';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("more");');
        $expected = '<script type="text/javascript">alert("hi");</script><script type="text/javascript">alert("more");</script>';
        $this->assertEquals($expected, $v->addHeadJs());

        $v->addHeadJs('alert("hi");');
        $expected = '<script type="text/javascript">alert("hi");</script><script type="text/javascript">alert("more");</script><script type="text/javascript">alert("hi");</script>';
        $this->assertEquals($expected, $v->addHeadJs());
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

    public function testUrlProxiesToRouterUrl()
    {
        $mockRouter = $this->getMock('Iota_Controller_Router', array('url'), array(array()));
        $mockRouter->expects($this->exactly(3))
                   ->method('url');

        $GLOBALS['router'] = $mockRouter;

        $v = new Iota_View(dirname(__FILE__).'/_files/viewTemplate1.phtml');

        $v->url('ctrl');
        $v->url('ctrl', array('parm'=>'val'));
        $v->url('ctrl', array('parm'=>'val'), 1);

        unset($GLOBALS['router']);
    }
}

<?php
/**
 * Search Criteria tests
 *
 * @category   UnitTests
 * @package    Iota
 * @author     Bryce Lohr
 * @copyright  Bryce Lohr 2008
 * @license    http://www.gearheadsoftware.com/bsd-license.txt
 */

require_once dirname(__FILE__).'/../testSetup.php';

class Iota_SearchCriteriaTest extends PHPUnit_Framework_TestCase
{
    public $userInput;


    public function setUp()
    {
        $this->userInput = array(
            'field1'    => 'input1',
            'field2'    => 'input2',
            'field3'    => 'input3',
            'field4'    => 'input4',
            'field5'    => 'input5',
            'field6'    => 'input6',
            'field7'    => 'input7',
            'field8'    => 'input8',
            'field9_lo' => 'input9_loval',
            'field9_hi' => 'input9_hival',
            'empty1'    => '',
            'empty2'    => '',
            'empty3_lo' => '',
            'empty3_hi' => ''
        );
    }

    public function testConstructorTakesArrayOfInput()
    {
        // Make sure it takes an argument w/o complaining
        $c = new Iota_SearchCriteria($this->userInput);

        // Omitting the argument should cause a PHP warning
        try {
            $c = new Iota_SearchCriteria;
        } catch (ErrorException $e) {
            // success
        }
    }

    public function testOperatorProducesSingleTerm1()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->eq('field1');
        $this->assertEquals(
            "field1 = 'input1'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm2()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->ne('field2');
        $this->assertEquals(
            "field2 <> 'input2'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm3()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->le('field3');
        $this->assertEquals(
            "field3 <= 'input3'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm4()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->ge('field4');
        $this->assertEquals(
            "field4 >= 'input4'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm5()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->gt('field5');
        $this->assertEquals(
            "field5 > 'input5'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm6()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->lt('field6');
        $this->assertEquals(
            "field6 < 'input6'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm7()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->begins('field7');
        $this->assertEquals(
            "field7 LIKE 'input7%'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm8()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->contains('field8');
        $this->assertEquals(
            "field8 LIKE '%input8%'", 
            (string) $t
        );
    }

    public function testOperatorProducesSingleTerm9()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->between('field9');
        $this->assertEquals(
            "field9 BETWEEN 'input9_loval' AND 'input9_hival'", 
            (string) $t
        );
    }

    public function testBetweenAllowsOpenEndedRange()
    {
        // Omitting the low limit should still use the high limit
        $this->userInput['field9_lo'] = '';
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->between('field9', true);
        $this->assertEquals(
            "field9 <= 'input9_hival'", 
            (string) $t
        );

        // Omitting the high limit should still use the low limit
        $this->userInput['field9_lo'] = 'input9_loval';
        $this->userInput['field9_hi'] = '';
        $c = new Iota_SearchCriteria($this->userInput);

        $t = $c->between('field9', true);
        $this->assertEquals(
            "field9 >= 'input9_loval'", 
            (string) $t
        );
    }

    public function testTermsGroupedWithAndExpression()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('field1'), $c->ne('field2'), $c->le('field3'), 
                      $c->ge('field4'), $c->gt('field5'), $c->lt('field6'), 
                      $c->begins('field7'), $c->contains('field8'),
                      $c->between('field9'));

        $this->assertEquals(
            "field1 = 'input1' AND field2 <> 'input2' AND field3 <= 'input3' AND " .
            "field4 >= 'input4' AND field5 > 'input5' AND field6 < 'input6' AND " .
            "field7 LIKE 'input7%' AND field8 LIKE '%input8%' AND " .
            "field9 BETWEEN 'input9_loval' AND 'input9_hival'",
            (string) $e
        );
    }

    public function testTermsGroupedWithOrExpression()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->lor($c->eq('field1'), $c->ne('field2'), $c->le('field3'), 
                     $c->ge('field4'), $c->gt('field5'), $c->lt('field6'), 
                     $c->begins('field7'), $c->contains('field8'),
                     $c->between('field9'));

        $this->assertEquals(
            "field1 = 'input1' OR field2 <> 'input2' OR field3 <= 'input3' OR " .
            "field4 >= 'input4' OR field5 > 'input5' OR field6 < 'input6' OR " .
            "field7 LIKE 'input7%' OR field8 LIKE '%input8%' OR " .
            "field9 BETWEEN 'input9_loval' AND 'input9_hival'",
            (string) $e
        );
    }

    public function testNotExpressionNegatesOneTerm()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->lnot($c->begins('field1'));

        $this->assertEquals(
            "NOT(field1 LIKE 'input1%')",
            (string) $e
        );
    }

    public function testExpressionsCanBeNested1()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('field1'), $c->lor($c->lt('field2'), $c->gt('field3')));
        $this->assertEquals(
            "field1 = 'input1' AND (field2 < 'input2' OR field3 > 'input3')",
            (string) $e
        );
    }

    public function testExpressionsCanBeNested2()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->lor($c->eq('field1'), $c->land($c->lt('field2'), $c->gt('field3')));
        $this->assertEquals(
            "field1 = 'input1' OR (field2 < 'input2' AND field3 > 'input3')",
            (string) $e
        );
    }

    public function testExpressionsCanBeNested3()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->lnot($c->land($c->lt('field1'), $c->gt('field2')));
        $this->assertEquals(
            "NOT(field1 < 'input1' AND field2 > 'input2')",
            (string) $e
        );
    }

    public function testExpressionsCanBeNested4()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('field1'), $c->lor($c->lt('field2'), $c->gt('field3')),
             $c->lnot($c->land($c->eq('field4'), $c->lor($c->le('field5'),
             $c->ge('field6')))));

        $this->assertEquals(
            "field1 = 'input1' AND (field2 < 'input2' OR field3 > 'input3') AND " .
            "NOT(field4 = 'input4' AND (field5 <= 'input5' OR field6 >= 'input6'))",
            (string) $e
        );
    }

    public function testEmptyInputIsSkipped1()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $ops = array('eq', 'ne', 'lt', 'gt', 'le', 'ge', 'begins', 'contains');
        foreach ($ops as $op) {
            $t = $c->$op('empty1');
            $this->assertEquals(null, $t);
        }

        $t = $c->between('empty3');
        $this->assertEquals(null, $t);
    }

    public function testEmptyInputIsSkipped2()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('empty1'), $c->lor($c->ne('empty2'), $c->between('empty3')));
        $this->assertEquals(null, $e);
    }

    public function testEmptyInputIsSkipped3()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('field1'), $c->eq('empty1'));
        $this->assertEquals(
            "field1 = 'input1'",
            (string) $e
        );

        $e = $c->lor($c->eq('field1'), $c->eq('empty1'), $c->eq('field2'));
        $this->assertEquals(
            "field1 = 'input1' OR field2 = 'input2'",
            (string) $e
        );

        $e = $c->lor($c->eq('field1'), $c->land($c->eq('field3'), $c->eq('empty1')), $c->eq('field2'));
        $this->assertEquals(
            "field1 = 'input1' OR field3 = 'input3' OR field2 = 'input2'",
            (string) $e
        );
    }

    // Ensure it still works when the non-empty input is *not* the first in the 
    // parmater list
    public function testEmptyInputIsSkipped4()
    {
        $c = new Iota_SearchCriteria($this->userInput);

        $e = $c->land($c->eq('empty1'), $c->eq('field1'));
        $this->assertEquals(
            "field1 = 'input1'",
            (string) $e
        );

        $e = $c->lor($c->eq('empty1'), $c->eq('field1'));
        $this->assertEquals(
            "field1 = 'input1'",
            (string) $e
        );

        $e = $c->lor($c->land($c->eq('empty1'), $c->eq('field3')), $c->eq('field2'), $c->eq('field1'));
        $this->assertEquals(
            "field3 = 'input3' OR field2 = 'input2' OR field1 = 'input1'",
            (string) $e
        );
    }

    public function testTermQuoteValueQuotesValueAndEscapesChars()
    {
        $c = new Iota_SearchCriteria($this->userInput);
        $t = $c->eq('field1');

        // It gets real confusing with all the backslashes...
        $expected = '\'Evil chars: \000\n\r\\\\\\\'\"\032\'';
        $actual   = $t->quoteValue("Evil chars: \000\n\r\\'\"\032");

        $this->assertEquals($expected, $actual);
    }

    public function testBeginsEscapesLikeOperatorWildcards()
    {
        $c = new Iota_SearchCriteria($this->userInput);
        $t = $c->begins('field1');

        $expected = '\'Evil chars: \000\n\r\\\\\\\'\"\032%\'';
        $actual   = $t->quoteValue("Evil chars: \000\n\r\\'\"\032");
        $this->assertEquals($expected, $actual);

        $expected = '\'A\\\\_Value\\\\%%\'';
        $actual   = $t->quoteValue('A_Value%');
        $this->assertEquals($expected, $actual);
    }

    public function testContainsEscapesLikeOperatorWildcards()
    {
        $c = new Iota_SearchCriteria($this->userInput);
        $t = $c->contains('field1');

        $expected = '\'%Evil chars: \000\n\r\\\\\\\'\"\032%\'';
        $actual   = $t->quoteValue("Evil chars: \000\n\r\\'\"\032");
        $this->assertEquals($expected, $actual);

        $expected = '\'%A\\\\_Value\\\\%%\'';
        $actual   = $t->quoteValue('A_Value%');
        $this->assertEquals($expected, $actual);
    }
}

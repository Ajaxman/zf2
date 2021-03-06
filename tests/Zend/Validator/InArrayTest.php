<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Validator
 */

namespace ZendTest\Validator;

use Zend\Validator\InArray;

/**
 * @category   Zend
 * @package    Zend_Validator
 * @subpackage UnitTests
 * @group      Zend_Validator
 */
class InArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Ensures that the validator follows expected behavior
     *
     * @return void
     */
    public function testBasic()
    {
        $validator = new InArray(array(1, 'a', 2.3));
        $this->assertTrue($validator->isValid(1));
        $this->assertTrue($validator->isValid(1.0));
        $this->assertTrue($validator->isValid('1'));
        $this->assertTrue($validator->isValid('a'));
        $this->assertFalse($validator->isValid('A'));
        $this->assertTrue($validator->isValid(2.3));
        $this->assertTrue($validator->isValid(2.3e0));
    }

    /**
     * Ensures that getMessages() returns expected default value
     *
     * @return void
     */
    public function testGetMessages()
    {
        $validator = new InArray(array(1, 2, 3));
        $this->assertEquals(array(), $validator->getMessages());
    }

    /**
     * Ensures that getHaystack() returns expected value
     *
     * @return void
     */
    public function testGetHaystack()
    {
        $validator = new InArray(array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $validator->getHaystack());
    }

    /**
     * Ensures that getStrict() returns expected default value
     *
     * @return void
     */
    public function testGetStrict()
    {
        $validator = new InArray(array(1, 2, 3));
        $this->assertFalse($validator->getStrict());
    }

    public function testGivingOptionsAsArrayAtInitiation()
    {
        $validator = new InArray(
            array('haystack' =>
                array(1, 'a', 2.3)
            )
        );
        $this->assertTrue($validator->isValid(1));
        $this->assertTrue($validator->isValid(1.0));
        $this->assertTrue($validator->isValid('1'));
        $this->assertTrue($validator->isValid('a'));
        $this->assertFalse($validator->isValid('A'));
        $this->assertTrue($validator->isValid(2.3));
        $this->assertTrue($validator->isValid(2.3e0));
    }

    public function testSettingANewHaystack()
    {
        $validator = new InArray(
            array('haystack' =>
                array('test', 0, 'A')
            )
        );
        $this->assertTrue($validator->isValid('A'));

        $validator->setHaystack(array(1, 'a', 2.3));
        $this->assertTrue($validator->isValid(1));
        $this->assertTrue($validator->isValid(1.0));
        $this->assertTrue($validator->isValid('1'));
        $this->assertTrue($validator->isValid('a'));
        $this->assertFalse($validator->isValid('A'));
        $this->assertTrue($validator->isValid(2.3));
        $this->assertTrue($validator->isValid(2.3e0));
    }

    public function testSettingNewStrictMode()
    {
        $validator = new InArray(array(1, 2, 3));
        $this->assertFalse($validator->getStrict());
        $this->assertTrue($validator->isValid('1'));
        $this->assertTrue($validator->isValid(1));

        $validator->setStrict(true);
        $this->assertTrue($validator->getStrict());
        $this->assertFalse($validator->isValid('1'));
        $this->assertTrue($validator->isValid(1));
    }

    public function testSettingStrictViaInitiation()
    {
        $validator = new InArray(
            array(
                'haystack' => array('test', 0, 'A'),
                'strict'   => true
            )
        );
        $this->assertTrue($validator->getStrict());
    }

    public function testGettingRecursiveOption()
    {
        $validator = new InArray(array(1, 2, 3));
        $this->assertFalse($validator->getRecursive());

        $validator->setRecursive(true);
        $this->assertTrue($validator->getRecursive());
    }

    public function testSettingRecursiveViaInitiation()
    {
        $validator = new InArray(
            array(
                'haystack'  => array('test', 0, 'A'),
                'recursive' => true
            )
        );
        $this->assertTrue($validator->getRecursive());
    }

    public function testRecursiveDetection()
    {
        $validator = new InArray(
            array(
                'haystack'  =>
                    array(
                        'firstDimension' => array('test', 0, 'A'),
                        'secondDimension' => array('value', 2, 'a')),
                'recursive' => false
            )
        );
        $this->assertFalse($validator->isValid('A'));

        $validator->setRecursive(true);
        $this->assertTrue($validator->isValid('A'));
    }

    public function testRecursiveStandalone()
    {
        $validator = new InArray(
            array(
                'firstDimension' => array('test', 0, 'A'),
                'secondDimension' => array('value', 2, 'a')
            )
        );
        $this->assertFalse($validator->isValid('A'));

        $validator->setRecursive(true);
        $this->assertTrue($validator->isValid('A'));
    }

    public function testEqualsMessageTemplates()
    {
        $validator = new InArray(array());
        $this->assertAttributeEquals($validator->getOption('messageTemplates'),
                                     'messageTemplates', $validator);
    }
}

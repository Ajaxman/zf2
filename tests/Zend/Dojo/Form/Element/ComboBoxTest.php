<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Dojo
 */

namespace ZendTest\Dojo\Form\Element;

use Zend\Dojo\Form\Element\ComboBox as ComboBoxElement;
use Zend\Dojo\View\Helper\Dojo as DojoHelper;
use Zend\Form\SubForm;
use Zend\Registry;
use Zend\View;

/**
 * Test class for Zend_Dojo_Form_Element_ComboBox.
 *
 * @category   Zend
 * @package    Zend_Dojo
 * @subpackage UnitTests
 * @group      Zend_Dojo
 * @group      Zend_Dojo_Form
 */
class ComboBoxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        Registry::_unsetInstance();
        DojoHelper::setUseDeclarative();

        $this->view    = $this->getView();
        $this->element = $this->getElement();
        $this->element->setView($this->view);
    }

    public function getView()
    {
        $view = new View\Renderer\PhpRenderer();
        \Zend\Dojo\Dojo::enableView($view);
        return $view;
    }

    public function getElement()
    {
        $element = new ComboBoxElement(
            'foo',
            array(
                'label' => 'ComboBox',
            )
        );
        return $element;
    }

    public function testSettingStoreIdShouldProxyToStoreDijitParam()
    {
        $this->element->setStoreId('someStore');
        $this->assertTrue($this->element->hasDijitParam('store'));
        $store = $this->element->getDijitParam('store');
        $this->assertTrue(array_key_exists('store', $store));
        $this->assertEquals('someStore', $store['store']);
        $this->assertEquals($this->element->getStoreId(), $store['store']);
    }

    public function testSettingStoreTypeShouldProxyToStoreDijitParam()
    {
        $this->element->setStoreType('dojo.data.ItemFileReadStore');
        $this->assertTrue($this->element->hasDijitParam('store'));
        $store = $this->element->getDijitParam('store');
        $this->assertTrue(array_key_exists('type', $store));
        $this->assertEquals('dojo.data.ItemFileReadStore', $store['type']);
        $this->assertEquals($this->element->getStoreType(), $store['type']);
    }

    public function testSettingStoreParamsShouldProxyToStoreDijitParam()
    {
        $this->element->setStoreParams(array('url' => '/js/foo.json'));
        $this->assertTrue($this->element->hasDijitParam('store'));
        $store = $this->element->getDijitParam('store');
        $this->assertTrue(array_key_exists('params', $store));
        $this->assertEquals(array('url' => '/js/foo.json'), $store['params']);
        $this->assertEquals($this->element->getStoreParams(), $store['params']);
    }

    public function testAutocompleteAccessorsShouldProxyToDijitParams()
    {
        $this->assertFalse($this->element->getAutocomplete());
        $this->assertFalse(array_key_exists('autocomplete', $this->element->dijitParams));
        $this->element->setAutocomplete(true);
        $this->assertTrue($this->element->getAutocomplete());
        $this->assertTrue(array_key_exists('autocomplete', $this->element->dijitParams));
    }

    /**
     * @group ZF-3286
     */
    public function testShouldNeverRegisterInArrayValidatorAutomatically()
    {
        $options = array(
            'foo' => 'Foo Value',
            'bar' => 'Bar Value',
            'baz' => 'Baz Value',
        );
        $this->element->setMultiOptions($options);
        $this->assertFalse($this->element->getValidator('InArray'));
        $this->element->isValid('test');
        $this->assertFalse($this->element->getValidator('InArray'));
    }

    public function testShouldRenderComboBoxDijit()
    {
        $html = $this->element->render();
        $this->assertContains('dojoType="dijit.form.ComboBox"', $html);
    }

    /**
     * @group ZF-7134
     * @group ZF-7266
     */
    public function testComboBoxInSubFormShouldCreateJsonStoreBasedOnQualifiedId()
    {
        DojoHelper::setUseProgrammatic();
        $this->element->setStoreId('foo')
                      ->setStoreType('dojo.data.ItemFileReadStore')
                      ->setStoreParams(array(
                          'url' => '/foo',
                        ));

        $subform = new SubForm(array('name' => 'bar'));
        $subform->addElement($this->element);
        $html = $this->element->render();
        $dojo = $this->view->plugin('dojo')->__toString();
        $this->assertContains('"store":"foo"', $dojo, $dojo);
    }
}

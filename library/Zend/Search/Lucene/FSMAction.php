<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Search
 */

namespace Zend\Search\Lucene;

/**
 * Abstract Finite State Machine
 *
 *
 * @category   Zend
 * @package    Zend_Search_Lucene
 */
class FSMAction
{
    /**
     * Object reference
     *
     * @var object
     */
    private $_object;

    /**
     * Method name
     *
     * @var string
     */
    private $_method;

    /**
     * Object constructor
     *
     * @param object $object
     * @param string $method
     */
    public function __construct($object, $method)
    {
        $this->_object = $object;
        $this->_method = $method;
    }

    public function doAction()
    {
        $methodName = $this->_method;
        $this->_object->$methodName();
    }
}

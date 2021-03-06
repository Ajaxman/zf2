<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Amf
 */

namespace ZendTest\Amf;

use Zend\Amf\Parser;
use Zend\Amf\Server;

/**
 * @category   Zend
 * @package    Zend_Amf
 * @subpackage UnitTests
 * @group      Zend_Amf
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Enter description here...
     *
     * @var Zend_Amf_Server
     */
    protected $_server;

    public function setUp()
    {
        $this->_server = new \Zend\Amf\Server();
        $this->_server->setProduction(false);
        Parser\TypeLoader::resetMap();
    }

    protected function tearDown()
    {
        unset($this->_server);
    }

    protected function _callService($method, $class = 'ZendTest\\Amf\\TestAsset\\testclass')
    {
        $request = new \Zend\Amf\Request\StreamRequest();
        $request->setObjectEncoding(0x03);
        $this->_server->setClass($class);
        $newBody = new \Zend\Amf\Value\MessageBody("$class.$method","/1",array("test"));
        $request->addAmfBody($newBody);
        $this->_server->handle($request);
        $response = $this->_server->getResponse();
        return $response;
    }

    public function testFile()
    {
        $resp = $this->_callService("returnFile");
        $this->assertContains("test data", $resp->getResponse());
    }

    /**
     * Defining new unknown resource type
     */
    public function testCtxNoResource()
    {
        $this->setExpectedException('Zend\Amf\Exception\RuntimeException', 'serialize resource type');
        $this->_callService("returnCtx");
    }

    /**
     * Defining new unknown resource type via plugin loader and handling it
     */
    public function testCtxLoader()
    {
        $this->markTestSkipped('Plugin loader implementation needs to be revisited');
        Parser\TypeLoader::addResourceDirectory("Test\\Resource", __DIR__ . "/TestAsset/Resources");
        $resp = $this->_callService("returnCtx");
        $this->assertContains("Accept-language:", $resp->getResponse());
        $this->assertContains("foo=bar", $resp->getResponse());
    }

    /**
     * Defining new unknown resource type and handling it
     *
     */
    public function testCtx()
    {
        $this->markTestSkipped('Plugin loader implementation needs to be revisited');
        Parser\TypeLoader::setResourceLoader(new TestAsset\TestResourceLoader("2"));
        $resp = $this->_callService("returnCtx");
        $this->assertContains("Accept-language:", $resp->getResponse());
        $this->assertContains("foo=bar", $resp->getResponse());
    }

    /**
     * Defining new unknown resource type, handler has no parse()
     *
     */
    public function testCtxNoParse()
    {
        $this->markTestSkipped('Plugin loader implementation needs to be revisited');
        Parser\TypeLoader::setResourceLoader(new TestAsset\TestResourceLoader("3"));
        $this->setExpectedException('Zend\Amf\Exception\RuntimeException', 'Could not call parse()');
        $resp = $this->_callService("returnCtx");
    }

}

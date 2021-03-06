<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Service
 */

namespace ZendTest\Service\WindowsAzure\Credentials;

use Zend\Service\WindowsAzure\Credentials\SharedAccessSignature;
use Zend\Service\WindowsAzure\Storage\Storage;

/**
 * @category   Zend
 * @package    Zend_Service_WindowsAzure
 * @subpackage UnitTests
 * @group      Zend_Service
 * @group      Zend_Service_WindowsAzure
 */
class SharedAccessSignatureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test signing a container
     */
    public function testGenerateSignatureContainer()
    {
        $credentials = new SharedAccessSignature('myaccount', 'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==', false);
        $result = $credentials->createSignature(
            'pictures',
            'c',
            'r',
            '2009-02-09',
            '2009-02-10',
            'YWJjZGVmZw=='
        );
        $this->assertEquals('TEfqYYiY9Qrb7fH7nhiRCP9o5BzfO/VL8oYgfVpUl6s=', $result);
    }

    /**
     * Test signing a blob
     */
    public function testGenerateSignatureBlob()
    {
        $credentials = new SharedAccessSignature('myaccount', 'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==', false);
        $result = $credentials->createSignature(
            'pictures/blob.txt',
            'b',
            'r',
            '2009-08-14T11:03:40Z',
            '2009-08-14T11:53:40Z'
        );
        $this->assertEquals('hk78uZGGWd8B2NYoBwKSPs5gen3xYqsd3DPO8BQhgTU=', $result);
    }

    /**
     * Test container signed query string
     */
    public function testContainerSignedQueryString()
    {
        $credentials = new SharedAccessSignature('myaccount', '', false);
        $result = $credentials->createSignedQueryString(
            'pictures',
            '',
            'c',
            'r',
            '2009-02-09',
            '2009-02-10',
            'YWJjZGVmZw=='
        );
        $this->assertEquals('st=2009-02-09&se=2009-02-10&sr=c&sp=r&si=YWJjZGVmZw%3D%3D&sig=iLe%2BC%2Be85l8%2BMneC9psdTCg7hJxKh314aRq3SnqPuyM%3D', $result);
    }

    /**
     * Test blob signed query string
     */
    public function testBlobSignedQueryString()
    {
        $credentials = new SharedAccessSignature('myaccount', '', false);
        $result = $credentials->createSignedQueryString(
            'pictures/blob.txt',
        	'',
            'b',
            'w',
            '2009-02-09',
            '2009-02-10'
        );
        $this->assertEquals('st=2009-02-09&se=2009-02-10&sr=b&sp=w&sig=MUrHltHOJkj4425gorWWKr%2FO6mHC3XeRQ2MD6jn8jI8%3D', $result);
    }

    /**
     * Test sign request URL
     */
    public function testSignRequestUrl()
    {
        $credentials = new SharedAccessSignature('myaccount', '', false);
        $queryString = $credentials->createSignedQueryString('pictures/blob.txt', '', 'b', 'r', '2009-02-09', '2009-02-10');

        $credentials->setPermissionSet(array(
            'http://blob.core.windows.net/myaccount/pictures/blob.txt?' . $queryString
        ));

        $requestUrl = 'http://blob.core.windows.net/myaccount/pictures/blob.txt?comp=metadata';
        $result = $credentials->signRequestUrl($requestUrl, Storage::RESOURCE_BLOB);

        $this->assertEquals('http://blob.core.windows.net/myaccount/pictures/blob.txt?comp=metadata&' . $queryString, $result);
    }
}

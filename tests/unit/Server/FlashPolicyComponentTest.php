<?php
namespace Ratchet\Application\Server;

use PHPUnit\Framework\TestCase;
use Ratchet\Server\FlashPolicy;

/**
 * @covers Ratchet\Server\FlashPolicy
 */
class FlashPolicyTest extends TestCase {

    protected $_policy;

    public function setUp(): void {
        $this->_policy = new FlashPolicy();
    }

    public function testPolicyRender(): void {
        $this->_policy->setSiteControl('all');
        $this->_policy->addAllowedAccess('example.com', '*');
        $this->_policy->addAllowedAccess('dev.example.com', '*');

        $this->assertInstanceOf('SimpleXMLElement', $this->_policy->renderPolicy());
    }

    public function testInvalidPolicyReader(): void {
        $this->expectException('UnexpectedValueException');
        $this->_policy->renderPolicy();
    }

    public function testInvalidDomainPolicyReader(): void {
        $this->expectException('UnexpectedValueException');
        $this->_policy->setSiteControl('all');
        $this->_policy->addAllowedAccess('dev.example.*', '*');
        $this->_policy->renderPolicy();
    }

    /**
     * @dataProvider siteControl
     */
    public function testSiteControlValidation($accept, $permittedCrossDomainPolicies): void {
        $this->assertEquals($accept, $this->_policy->validateSiteControl($permittedCrossDomainPolicies));
    }

    public static function siteControl(): array {
        return array(
            array(true, 'all')
          , array(true, 'none')
          , array(true, 'master-only')
          , array(false, 'by-content-type')
          , array(false, 'by-ftp-filename')
          , array(false, '')
          , array(false, 'all ')
          , array(false, 'asdf')
          , array(false, '@893830')
          , array(false, '*')
        );
    }

    /**
     * @dataProvider URI
     */
    public function testDomainValidation($accept, $domain): void {
        $this->assertEquals($accept, $this->_policy->validateDomain($domain));
    }

    public static function URI(): array {
        return array(
            array(true, '*')
          , array(true, 'example.com')
          , array(true, 'exam-ple.com')
          , array(true, '*.example.com')
          , array(true, 'www.example.com')
          , array(true, 'dev.dev.example.com')
          , array(true, 'http://example.com')
          , array(true, 'https://example.com')
          , array(true, 'http://*.example.com')
          , array(false, 'exam*ple.com')
          , array(true, '127.0.255.1')
          , array(true, 'localhost')
          , array(false, 'www.example.*')
          , array(false, 'www.exa*le.com')
          , array(false, 'www.example.*com')
          , array(false, '*.example.*')
          , array(false, 'gasldf*$#a0sdf0a8sdf')
        );
    }

    /**
     * @dataProvider ports
     */
    public function testPortValidation($accept, $ports): void {
        $this->assertEquals($accept, $this->_policy->validatePorts($ports));
    }

    public static function ports(): array {
        return array(
            array(true, '*')
          , array(true, '80')
          , array(true, '80,443')
          , array(true, '507,516-523')
          , array(true, '507,516-523,333')
          , array(true, '507,516-523,507,516-523')
          , array(false, '516-')
          , array(true, '516-523,11')
          , array(false, '516,-523,11')
          , array(false, 'example')
          , array(false, 'asdf,123')
          , array(false, '--')
          , array(false, ',,,')
          , array(false, '838*')
        );
    }

    public function testAddAllowedAccessOnlyAcceptsValidPorts(): void {
        $this->expectException('UnexpectedValueException');

        $this->_policy->addAllowedAccess('*', 'nope');
    }

    public function testSetSiteControlThrowsException(): void {
        $this->expectException('UnexpectedValueException');

        $this->_policy->setSiteControl('nope');
    }

    public function testErrorClosesConnection(): void {
        $conn = $this->getMockBuilder('\\Ratchet\\ConnectionInterface')->getMock();
        $conn->expects($this->once())->method('close');

        $this->_policy->onError($conn, new \Exception);
    }

    public function testOnMessageSendsString(): void {
        $this->_policy->addAllowedAccess('*', '*');

        $conn = $this->getMockBuilder('\\Ratchet\\ConnectionInterface')->getMock();
        $conn->expects($this->once())->method('send')->with($this->isType('string'));

        $this->_policy->onMessage($conn, ' ');
    }

    public function testOnOpenExists(): void {
        $this->assertTrue(method_exists($this->_policy, 'onOpen'));
        $conn = $this->getMockBuilder('\Ratchet\ConnectionInterface')->getMock();
        $this->_policy->onOpen($conn);
    }

    public function testOnCloseExists(): void {
        $this->assertTrue(method_exists($this->_policy, 'onClose'));
        $conn = $this->getMockBuilder('\Ratchet\ConnectionInterface')->getMock();
        $this->_policy->onClose($conn);
    }
}

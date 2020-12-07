<?php declare(strict_types=1);
namespace Duo\Tests;

use Duo\DuoUniversal\Client;
use Duo\DuoUniversal\DuoException;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public $client_id = "12345678901234567890";
    public $client_secret = "1234567890123456789012345678901234567890";
    public $api_host = "api-123456.duo.com";
    public $redirect_url = "https://redirect_example.com";
    public $bad_client_id = "1234567890123456789";
    public $long_client_secret = "1234567890123456789012345678901234567890000";
    public $bad_client_secret = "1111111111111111111111111111111111111111";
    public $bad_api_host = 123456;
    public $bad_redirect_url = 123456;
    public $good_http_request = "{\"response\": {\"timestamp\": 1607009339}, \"stat\": \"OK\"}";
    public $bad_http_request = "{\"message\": \"invalid_client\", \"code\": 40002, \"timestamp\": 1607014550, \"message_detail\": \"Failed to verify signature.\", \"stat\": \"FAIL\"}";
    public $missing_stat_health_check = "{\"response\": {\"timestamp\": 1607009339}}";
    public $missing_message_health_check = "{\"stat\": \"Fail\"}";
    public $bad_http_request_exception = "invalid_client: Failed to verify signature.";
    public $bad_http_connection = false;
    public $expected_good_http_request = array("response" => array("timestamp" => 1607009339), "stat" => "OK");


    /**
     * Test that creating a client with proper inputs does not throw an error.
     */
    public function testClientGood(): void
    {
        $client = new Client(
            $this->client_id,
            $this->client_secret,
            $this->api_host,
            $this->redirect_url
        );
        $this->assertTrue(true);
    }
    /**
     * Test that an invalid client_id will cause the Client to throw a DuoException
     */
    public function testClientBadClientId(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::INVALID_CLIENT_ID_ERROR);
        $client = new Client(
            $this->bad_client_id,
            $this->client_secret,
            $this->api_host,
            $this->redirect_url
        );
    }

    /**
     * Test that an invalid client_secret
     * will cause the Client to throw a DuoException
     */
    public function testClientBadClientSecret(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::INVALID_CLIENT_SECRET_ERROR);
        $client = new Client(
            $this->client_id,
            $this->long_client_secret,
            $this->api_host,
            $this->redirect_url
        );
    }

    /**
     * Test that a non-string api_host will cause the Client to throw a DuoException
     */
    public function testClientBadApiHost(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::PARSING_CONFIG_ERROR);
        $client = new Client(
            $this->client_id,
            $this->client_secret,
            $this->bad_api_host,
            $this->redirect_url
        );
    }

    /**
     * Test that a non-string redirect_url will
     * cause the Client to throw a DuoException
     */
    public function testClientBadRedirectUrl(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::PARSING_CONFIG_ERROR);
        $client = new Client(
            $this->client_id,
            $this->client_secret,
            $this->api_host,
            $this->bad_redirect_url
        );
    }

    /**
     * Test that generateState does not return the same
     * string twice.
     */
    public function testGenerateState(): void
    {
        $client = new Client(
            $this->client_id,
            $this->client_secret,
            $this->api_host,
            $this->redirect_url
        );
        $string_1 = $client->generateState();
        $this->assertNotEquals(
            $string_1,
            $client->generateState()
        );
    }

    /**
     * Test that a successful health check returns a successful result.
     */
    public function testHealthCheckGood(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->client_id, $this->client_secret, $this->api_host, $this->redirect_url])
            ->setMethods(['makeHttpsCall'])
            ->getMock();
        $client->method('makeHttpsCall')
            ->will($this->returnValue($this->good_http_request));
        $result = $client->healthCheck();
        $this->assertEquals($this->expected_good_http_request, $result);
    }

    /**
     * Test that a failed connection to Duo throws a FAILED_CONNECTION exception.
     */
    public function testHealthCheckConnectionFail(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::FAILED_CONNECTION);
        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->client_id, $this->client_secret, $this->api_host, $this->redirect_url])
            ->setMethods(['makeHttpsCall'])
            ->getMock();
        $client->method('makeHttpsCall')
            ->will($this->throwException(new DuoException(Client::FAILED_CONNECTION)));
        $client->healthCheck();
    }

    /**
     * Test that when Duo is down the client throws an error
     */
    public function testHealthCheckBadSig(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage($this->bad_http_request_exception);
        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->client_id, $this->client_secret, $this->api_host, $this->redirect_url])
            ->setMethods(['makeHttpsCall'])
            ->getMock();
        $client->method('makeHttpsCall')
            ->will($this->returnValue($this->bad_http_request));
        $client->healthCheck();
    }

    /**
     * Test that if the health check response is missing stat then the client throws an error.
     */
    public function testHealthCheckMissingStat(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::MALFORMED_RESPONSE);
        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->client_id, $this->client_secret, $this->api_host, $this->redirect_url])
            ->setMethods(['makeHttpsCall'])
            ->getMock();
        $client->method('makeHttpsCall')
            ->will($this->returnValue($this->missing_stat_health_check));
        $client->healthCheck();
    }

    /**
     * Test that if the health check failed and the response is malformed then the client throws an error.
     */
    public function testHealthCheckMissingMessage(): void
    {
        $this->expectException(DuoException::class);
        $this->expectExceptionMessage(Client::MALFORMED_RESPONSE);
        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->client_id, $this->client_secret, $this->api_host, $this->redirect_url])
            ->setMethods(['makeHttpsCall'])
            ->getMock();
        $client->method('makeHttpsCall')
            ->will($this->returnValue($this->missing_message_health_check));
        $client->healthCheck();
    }
}

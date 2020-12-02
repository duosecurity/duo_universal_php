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
    public $bad_client_secret = "123456789012345678901234567890123456789";
    public $bad_api_host = 123456;
    public $bad_redirect_url = 123456;

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
            $this->bad_client_secret,
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
}

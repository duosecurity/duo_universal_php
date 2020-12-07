<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Middleware\Session;
use Slim\Views\PhpRenderer;
use Slim\Factory\AppFactory;
use Duo\DuoUniversal\Client;
use Duo\DuoUniversal\DuoException;

require __DIR__ . '/vendor/autoload.php';


$config = parse_ini_file("duo.conf");

try {
    $duo_client = new Client(
        $config['client_id'],
        $config['client_secret'],
        $config['api_hostname'],
        $config['redirect_uri']
    );
} catch (DuoException $e) {
    echo "*** Duo config error. Verify the values in duo.conf are correct ***";
    throw $e;
}

$app = AppFactory::create();
$app->add(new Session());

$app->get('/', function (Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('./templates');
    return $renderer->render($response, "login.php", $args);
});

$app->post('/', function (Request $request, Response $response, $args) use ($duo_client) {
    $state = $duo_client->generateState();
    $session = new \SlimSession\Helper();
    $session->set("state", $state);

    return $response
        ->withHeader('Location', 'https://www.duo.com')
        ->withStatus(302);
});

$app->get('/duo-callback', function (Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('./templates');
    $session = new \SlimSession\Helper();
    $state = $session->get("state");
    return $renderer->render($response, "success.php", $args);
});

$app->run();

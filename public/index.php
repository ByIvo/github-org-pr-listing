<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use \GithubPrListing\PullRequestSearcher;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();

$app->get('/listPullRequests', function (Request $request, Response $response, $args) {
	$pullRequestSearcher = new PullRequestSearcher(new \GuzzleHttp\Client([]));

	$pullRequestList = $pullRequestSearcher->search();

	$payload = json_encode($pullRequestList);
	$response->getBody()->write($payload);

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(200);
});

$app->run();

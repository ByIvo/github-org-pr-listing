<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use \GithubPrListing\PullRequestSearcher;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();

$app->get('/listPullRequests', function (Request $request, Response $response, $args) {
	$queryParams = $request->getQueryParams();
	$start = new DateTime($queryParams['start']);
	$end = new DateTime($queryParams['end']);

	$pullRequestSearcher = new PullRequestSearcher(new \GuzzleHttp\Client([]));
	$pullRequestList = $pullRequestSearcher->search($start, $end);

	$payload = json_encode($pullRequestList);
	$response->getBody()->write($payload);

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(200);
});

$app->get('/listCodeReviews', function (Request $request, Response $response, $args) {
	$queryParams = $request->getQueryParams();

	$start = new DateTime($queryParams['start']);
	$end = new DateTime($queryParams['end']);

	$pullRequestSearcher = new PullRequestSearcher(new \GuzzleHttp\Client([]));
	$pullRequestList = $pullRequestSearcher->searchCodeReview($start, $end);

	$payload = json_encode($pullRequestList);
	$response->getBody()->write($payload);

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(200);
});

$app->get('/graphics', function (Request $request, Response $response, $args) {
	$file = './graphics.html';

	$streamFactory = new \Slim\Psr7\Factory\StreamFactory();

	if (file_exists($file)) {
		return $response->withBody($streamFactory->createStreamFromFile($file));
	} else {
		throw new \Slim\Exception\NotFoundException($request, $response);
	}
});

$app->run();

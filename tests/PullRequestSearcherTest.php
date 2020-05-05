<?php

namespace Test\GithubPrListing;

use GithubPrListing\RangePullRequestInfo;
use GithubPrListing\PullRequestSearcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PullRequestSearcherTest extends TestCase {

	/** @var array */
	private $envProperties;

	protected function setUp() {
		parent::setUp();

		$this->envProperties = [];
	}

	/** @test */
	public function givenAGithubPullRequestResponse_whenListingPullRequest_shouldCreateAPullRequestInfoWithTotalCount(): void {
		$this->setEnv("PR_LISTING_AUTHOR","authorUsername");
		$expectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(2);
		$client = $this->createClientWithMockedResponses([$expectedGithubResponse]);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestList = $pullRequestSearcher->search(new \DateTime(), new \DateTime());

		$expectedPullRequestResponse = new RangePullRequestInfo();
		$expectedPullRequestResponse->addAuthorPullRequestInfo('authorUsername', 2);
		Assert::assertEquals($expectedPullRequestResponse, $pullRequestList);
	}

	/** @test */
	public function givenMultipleAuthors_whenRequestAPullRequestSearch_shouldRequestATotalCountPullRequestEach(): void {
		$this->setEnv("PR_LISTING_AUTHOR","byivo jhmachado deenison");
		$firstAuthorExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(1);
		$secondExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(2);
		$lastExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(4);
		$expectedGithubResponses = [
			$firstAuthorExpectedGithubResponse,
			$secondExpectedGithubResponse,
			$lastExpectedGithubResponse
		];
		$requestHistoryWithMutableReference = [];
		$client = $this->createClientWithMockedResponses($expectedGithubResponses, $requestHistoryWithMutableReference);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$rangePullRequestInfo = $pullRequestSearcher->search(new \DateTime(), new \DateTime());

		Assert::assertEquals(7, $rangePullRequestInfo->getPullRequestTotalCount());
		Assert::assertEquals(3, sizeof($requestHistoryWithMutableReference));
	}

	/** @test */
	public function givenAllEnvironmentVariables_whenRequestAPullRequestSearch_shouldCreateAFlawlesslyRequest(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_GITHUB_ORG","great_org");
		$this->setEnv("PR_LISTING_AUTHOR","author1");
		$this->setEnv("PR_LISTING_BASIC_AUTH_CREDENTIALS","username:auth_token");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search(new \DateTime('2019-10-01'), new \DateTime('2019-12-31'));

		$firstRequest = $this->extractFirstRequest($requestHistoryWithMutableReference);
		$authorizationHeader =	$firstRequest->getHeaderLine('Authorization');
		$uri = $firstRequest->getUri();
		$allRequestQueryParameters = $this->extractQueryParametersFromRequest($firstRequest);

		Assert::assertEquals(
			'type:pr is:closed org:great_org author:author1 merged:2019-10-01..2019-12-31',
			$allRequestQueryParameters['q']
		);
		Assert::assertEquals('Basic ' . base64_encode('username:auth_token'), $authorizationHeader);
		Assert::assertStringStartsWith('https://api.github.com/search/issues', strval($uri));
	}

	/** @test */
	public function givenAGithubCodeReviewResponse_shouldCreateACodeReviewInfoWithTotalCount(): void {
		$this->setEnv("PR_LISTING_AUTHOR","commenterUsername");
		$expectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(2);
		$client = $this->createClientWithMockedResponses([$expectedGithubResponse]);

		$codeReviewSearcher = new PullRequestSearcher($client);
		$codeReviewList = $codeReviewSearcher->searchCodeReview(new \DateTime(), new \DateTime());

		$expectedPullRequestResponse = new RangePullRequestInfo();
		$expectedPullRequestResponse->addAuthorPullRequestInfo('commenterUsername', 2);
		Assert::assertEquals($expectedPullRequestResponse, $codeReviewList);
	}

	/** @test */
	public function givenAGithubCodeReviewResponse_shouldRequestATotalCountPullRequestEach(): void {
		$this->setEnv("PR_LISTING_AUTHOR","byivo caju juca");
		$firstAuthorExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(1);
		$secondExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(2);
		$lastExpectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(4);
		$expectedGithubResponses = [
			$firstAuthorExpectedGithubResponse,
			$secondExpectedGithubResponse,
			$lastExpectedGithubResponse
		];
		$requestHistoryWithMutableReference = [];
		$client = $this->createClientWithMockedResponses($expectedGithubResponses, $requestHistoryWithMutableReference);

		$codeReviewSearcher = new PullRequestSearcher($client);
		$codeReviewList = $codeReviewSearcher->searchCodeReview(new \DateTime(), new \DateTime());

		Assert::assertEquals(7, $codeReviewList->getPullRequestTotalCount());
		Assert::assertEquals(3, sizeof($requestHistoryWithMutableReference));
	}

	/** @test */
	public function givenAGithubCodeReviewResponse_shouldNotCountPRWhichCommenterIsTheSameAsAuthor(): void {
		$this->setEnv("PR_LISTING_AUTHOR","commenterUsername");
		$expectedGithubResponse = $this->createTwoCodeReviewsWhereOneOfThemTheCommenterIsTheSameAsTheAuthor("commenterUsername");
		$client = $this->createClientWithMockedResponses([$expectedGithubResponse]);

		$codeReviewSearcher = new PullRequestSearcher($client);
		$codeReviewList = $codeReviewSearcher->searchCodeReview(new \DateTime(), new \DateTime());

		$expectedPullRequestResponse = new RangePullRequestInfo();
		$expectedPullRequestResponse->addAuthorPullRequestInfo('commenterUsername', 1);
		Assert::assertEquals($expectedPullRequestResponse, $codeReviewList);
	}

	private function fillRequestHistoryAndCreateClientWithEmptyMockedResponses(array &$requestHistoryWithMutableReference): Client {
		$emptyResponse = $this->getMockedResponseWithTotalAmount(1);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $emptyResponse);
		return $this->createClientWithMockedResponses([$expectedGithubResponse], $requestHistoryWithMutableReference);
	}

	private function extractQueryParametersFromRequest(Request $request): array {
		$requestQueryParameters = $request->getUri()->getQuery();
		$filterParameter = [];
		parse_str($requestQueryParameters, $filterParameter);

		return $filterParameter;
	}

	private function extractFirstRequest(array $requestHistoryWithMutableReference): Request {
		/** @var $request \GuzzleHttp\Psr7\Request */
		return $requestHistoryWithMutableReference[0]['request'];
	}

	private function getStatusOkResponseWithTotalCountPullRequest(int $totalPullRequestCount): Response {
		$body = $this->getMockedResponseWithTotalAmount($totalPullRequestCount);
		return new Response($status = 200, $headers = [], $body);
	}

	private function getMockedResponseWithTotalAmount(int $totalPullRequestCount): string {
		return <<<JSON
{
    "total_count": {$totalPullRequestCount}
}
JSON;
	}

	private function createTwoCodeReviewsWhereOneOfThemTheCommenterIsTheSameAsTheAuthor(string $commenter): Response {
		$body = <<<JSON
{
    "total_count": 2,
    "items": [
      {
        "user": {
          "login": "someoneelse"
        }
      },
      {
        "user": {
          "login": "$commenter"
        }
      }
    ]
}
JSON;
		return new Response($status = 200, $headers = [], $body);
	}

	private function createClientWithMockedResponses(array $expectedGithubResponses, &$requestHistoryWithMutableReference = []): Client {
		$mocks = new MockHandler($expectedGithubResponses);

		$handler = HandlerStack::create($mocks);

		$middlewareHistory = Middleware::history($requestHistoryWithMutableReference);
		$handler->push($middlewareHistory);

		return new Client([
			'handler' => $handler,
		]);
	}

	private function setEnv(string $property, $value): bool {
		$this->envProperties[] = $property;
		return putenv("{$property}={$value}");
	}

	private function clearAllEnvProperties(): void {
		foreach ($this->envProperties as $envProperty) {
			$this->setEnv($envProperty, '');
		}
	}

	protected function tearDown() {
		parent::tearDown();

		$this->clearAllEnvProperties();
	}
}

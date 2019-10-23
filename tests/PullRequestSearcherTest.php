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
		$expectedGithubResponse = $this->getStatusOkResponseWithTotalCountPullRequest(2);
		$client = $this->createClientWithMockedResponses([$expectedGithubResponse]);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestList = $pullRequestSearcher->search();

		$expectedPullRequestResponse = new RangePullRequestInfo(2);
		Assert::assertEquals($expectedPullRequestResponse, $pullRequestList);
	}

	/** @test */
	public function whenRequestAPullRequestSearch_shouldFilterOnlyClosedPullRequests(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('type:pr', $rawFilterParameters);
		Assert::assertContains('is:closed', $rawFilterParameters);
	}

	/** @test */
	public function whenRequestAPullRequestSearch_shouldFilterOrganizationProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_GITHUB_ORG","desired-company");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('org:desired-company', $rawFilterParameters);
	}

	/** @test */
	public function whenRequestAPullRequestSearch_shouldFilterAuthorProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_AUTHOR","byivo");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('author:byivo', $rawFilterParameters);
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
		$rangePullRequestInfo = $pullRequestSearcher->search();

		Assert::assertEquals(7, $rangePullRequestInfo->getPullRequestTotalCount());
		Assert::assertEquals(3, sizeof($requestHistoryWithMutableReference));
	}

	/** @test */
	public function whenRequestAPullRequestSearch_shouldFilterMergedDateProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_MERGE_INTERVAL","2019-07-01..2019-09-30");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('merged:2019-07-01..2019-09-30', $rawFilterParameters);
	}

	/** @test */
	public function givenAllEnvironmentVariables_whenRequestAPullRequestSearch_shouldAddQueryParameterWithAllFilters(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_GITHUB_ORG","great_org");
		$this->setEnv("PR_LISTING_AUTHOR","author1");
		$this->setEnv("PR_LISTING_MERGE_INTERVAL","2019-10-01..2019-12-31");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$allRequestQueryParameters = $this->extractQueryParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertEquals(
			'type:pr is:closed org:great_org author:author1 merged:2019-10-01..2019-12-31',
			$allRequestQueryParameters['q']
		);
	}

	/** @test */
	public function givenAnEnvironmentCredentials_whenRequestAPullRequestSearch_shouldCreateBasicAuthenticationInRequestHeader(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		$this->setEnv("PR_LISTING_BASIC_AUTH_CREDENTIALS","username:auth_token");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$firstRequestAuthorizationHeader =	$this->extractFirstRequest($requestHistoryWithMutableReference)->getHeaderLine('Authorization');
		$expectedBasicAuth = 'Basic ' . base64_encode('username:auth_token');
		Assert::assertEquals($expectedBasicAuth, $firstRequestAuthorizationHeader);
	}

	/** @test */
	public function whenRequestAPullRequestSearch_shouldUseCorrectlyGithubUri(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$uri =	$this->extractFirstRequest($requestHistoryWithMutableReference)->getUri();
		Assert::assertStringStartsWith('https://api.github.com/search/issues', strval($uri));
	}

	private function fillRequestHistoryAndCreateClientWithEmptyMockedResponses(array &$requestHistoryWithMutableReference): Client {
		$emptyResponse = $this->getMockedResponseWithTotalAmount(1);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $emptyResponse);
		return $this->createClientWithMockedResponses([$expectedGithubResponse], $requestHistoryWithMutableReference);
	}

	private function extractFilterParametersFromFirstRequest(array $requestHistoryWithMutableReference): string {
		$filterParameter = $this->extractQueryParametersFromFirstRequest($requestHistoryWithMutableReference);
		return $filterParameter['q'];
	}

	private function extractQueryParametersFromFirstRequest(array $requestHistoryWithMutableReference): array {
		$request = $this->extractFirstRequest($requestHistoryWithMutableReference);

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

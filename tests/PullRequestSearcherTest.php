<?php

namespace Test\GithubPrListing;

use GithubPrListing\PullRequest;
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

	/** @test */
	public function givenAGithubPullRequestListResponse_whenListingPullRequest_shouldReturnRelevantProperties(): void {
		$body = $this->getMockedResponseWithTotalAmount(2);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $body);
		$client = $this->createClientWithMockedResponses([$expectedGithubResponse]);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestList = $pullRequestSearcher->search();

		$expectedPullRequestList = [
			new PullRequest('Fake', 'Fake', 'Fake', new \DateTime('2019-10-12 21:35:32')),
			new PullRequest('Fake', 'Fake', 'Fake', new \DateTime('2019-10-12 21:35:32')),
		];
		Assert::assertEquals($expectedPullRequestList, $pullRequestList);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterOnlyClosedPullRequests(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('type:pr', $rawFilterParameters);
		Assert::assertContains('is:closed', $rawFilterParameters);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterOrganizationProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_GITHUB_ORG=desired-company");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('org:desired-company', $rawFilterParameters);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterAuthorProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_AUTHOR=byivo");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('author:byivo', $rawFilterParameters);
	}

	/** @test */
	public function givenMultipleAuthors_whenRequestAPullRequestList_shouldAddAllAuthorsInFilterParameters(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_AUTHOR=byivo jhmachado deenison");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('author:byivo', $rawFilterParameters);
		Assert::assertContains('author:jhmachado', $rawFilterParameters);
		Assert::assertContains('author:deenison', $rawFilterParameters);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterMergedDateProvidedAsEnvironmentVariable(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_MERGE_INTERVAL=2019-07-01..2019-09-30");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$rawFilterParameters =	$this->extractFilterParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertContains('merged:2019-07-01..2019-09-30', $rawFilterParameters);
	}

	/** @test */
	public function givenAllEnvironmentVariables_whenRequestAPullRequestList_shouldAddQueryParameterWithAllFilters(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_GITHUB_ORG=great_org");
		putenv("PR_LISTING_AUTHOR=author1 author2");
		putenv("PR_LISTING_MERGE_INTERVAL=2019-10-01..2019-12-31");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$allRequestQueryParameters = $this->extractQueryParametersFromFirstRequest($requestHistoryWithMutableReference);
		Assert::assertEquals(
			'type:pr is:closed org:great_org author:author1 author:author2 merged:2019-10-01..2019-12-31',
			$allRequestQueryParameters['q']
		);
	}

	/** @test */
	public function givenAnEnvironmentCredentials_whenRequestAPullRequestList_shouldCreateBasicAuthenticationInRequestHeader(): void {
		$requestHistoryWithMutableReference = [];
		$client = $this->fillRequestHistoryAndCreateClientWithEmptyMockedResponses($requestHistoryWithMutableReference);
		putenv("PR_LISTING_BASIC_AUTH_CREDENTIALS=username:auth_token");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		$firstRequestAuthorizationHeader =	$this->extractFirstRequest($requestHistoryWithMutableReference)->getHeaderLine('Authorization');
		$expectedBasicAuth = 'Basic ' . base64_encode('username:auth_token');
		Assert::assertEquals($expectedBasicAuth, $firstRequestAuthorizationHeader);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldUseCorrectlyGithubUri(): void {
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
}

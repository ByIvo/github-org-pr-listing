<?php

namespace Test\GithubPrListing;

use GithubPrListing\PullRequest;
use GithubPrListing\PullRequestSearcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PullRequestSearcherTest extends TestCase {

	/** @test */
	public function givenAGithubPullRequestListResponse_whenListingPullRequest_shouldReturnRelevantProperties(): void {
		$body = $this->getMockedGithubPullRequestListResponse([
			['title' => 'PR 01', 'author' => 'ByIvo 1', 'url' => 'url 01', 'closedAt' => '2019-10-12T21:35:32Z'],
			['title' => 'PR 02', 'author' => 'ByIvo 2', 'url' => 'url 02', 'closedAt' => '2019-10-15T13:13:13Z'],
		]);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $body);
		$client = $this->createClientWithMockedResponse($expectedGithubResponse);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestList = $pullRequestSearcher->search();

		$expectedPullRequestList = [
			new PullRequest('PR 01', 'ByIvo 1', 'url 01', new \DateTime('2019-10-12 21:35:32')),
			new PullRequest('PR 02', 'ByIvo 2', 'url 02', new \DateTime('2019-10-15 13:13:13')),
		];
		Assert::assertEquals($expectedPullRequestList, $pullRequestList);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterOnlyClosedPullRequests(): void {
		$requestHistory = [];
		$emptyResponse = $this->getMockedGithubPullRequestListResponse([]);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $emptyResponse);
		$client = $this->createClientWithMockedResponse($expectedGithubResponse, $requestHistory);

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		/** @var $request \GuzzleHttp\Psr7\Request */
		$request = $requestHistory[0]['request'];

		$requestQueryParameters = $request->getUri()->getQuery();
		$filterParameter = [];
		parse_str($requestQueryParameters, $filterParameter);
		$rawQueryString = $filterParameter['q'];

		Assert::assertContains('type:pr', $rawQueryString);
		Assert::assertContains('is:closed', $rawQueryString);
	}

	/** @test */
	public function whenRequestAPullRequestList_shouldFilterOrganizationProvidedAsEnvironmentVariable(): void {
		$requestHistory = [];
		$emptyResponse = $this->getMockedGithubPullRequestListResponse([]);
		$expectedGithubResponse = new Response($status = 200, $headers = [], $emptyResponse);
		$client = $this->createClientWithMockedResponse($expectedGithubResponse, $requestHistory);
		putenv("PR_LISTING_GITHUB_ORG=desired-company");

		$pullRequestSearcher = new PullRequestSearcher($client);
		$pullRequestSearcher->search();

		/** @var $request \GuzzleHttp\Psr7\Request */
		$request = $requestHistory[0]['request'];

		$requestQueryParameters = $request->getUri()->getQuery();
		$filterParameter = [];
		parse_str($requestQueryParameters, $filterParameter);
		$rawQueryString = $filterParameter['q'];

		Assert::assertContains('org:desired-company', $rawQueryString);
	}


	private function getMockedGithubPullRequestListResponse(array $mockedPullRequests): string {
		$pullRequestsCount = count($mockedPullRequests);

		$parsedPullRequestItems = array_map(function (array $mockedPullRequestData) {
			return self::parsePullRequestDataIntoJsonResponseItem($mockedPullRequestData);
		}, $mockedPullRequests);

		$mockedJsonPullRequests = implode(',', $parsedPullRequestItems);

		return <<<JSON
{
    "total_count": {$pullRequestsCount},
    "items": [
        {$mockedJsonPullRequests}
    ]
}
JSON;
	}

	private static function parsePullRequestDataIntoJsonResponseItem(array $mockedPullRequestData): string {
		return <<<JSON
{
    "html_url": "{$mockedPullRequestData['url']}",
    "title": "{$mockedPullRequestData['title']}",
    "user": {
        "login": "{$mockedPullRequestData['author']}"
    },
    "state": "closed",
    "created_at": "2019-07-01T21:35:32Z",
    "closed_at": "{$mockedPullRequestData['closedAt']}"
}
JSON;

	}

	private function createClientWithMockedResponse(Response $expectedGithubResponse, &$requestHistory = []): Client {
		$mocks = new MockHandler([
			$expectedGithubResponse
		]);

		$handler = HandlerStack::create($mocks);

		$middlewareHistory = Middleware::history($requestHistory);
		$handler->push($middlewareHistory);

		return new Client([
			'handler' => $handler,
		]);
	}
}

<?php

namespace Test\GithubPrListing;

use GithubPrListing\PullRequestApiRequest;
use GithubPrListing\PullRequestSearcher;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PullRequestApiRequestTest extends TestCase {

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldFilterOnlyClosedPullRequests(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = '', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$rawFilterParameters =	$this->extractFilterParametersParametersFromRequest($request);
		Assert::assertContains('type:pr', $rawFilterParameters);
		Assert::assertContains('is:closed', $rawFilterParameters);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldFilterOrganizationProvidedAsEnvironmentVariable(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = 'desired-company', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$rawFilterParameters =	$this->extractFilterParametersParametersFromRequest($request);
		Assert::assertContains('org:desired-company', $rawFilterParameters);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldFilterAuthorProvidedAsEnvironmentVariable(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = '', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = 'byivo');

		$rawFilterParameters =	$this->extractFilterParametersParametersFromRequest($request);
		Assert::assertContains('author:byivo', $rawFilterParameters);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldFilterMergedDateProvidedAsEnvironmentVariable(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = '', $mergeInterval = '2019-07-01..2019-09-30');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$rawFilterParameters =	$this->extractFilterParametersParametersFromRequest($request);
		Assert::assertContains('merged:2019-07-01..2019-09-30', $rawFilterParameters);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldAddQueryParameterWithAllFilters(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = 'great_org', $mergeInterval = '2019-10-01..2019-12-31');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = 'author1');

		$allRequestQueryParameters = $this->extractAllQueryParametersFromRequest($request);
		Assert::assertEquals(
			'type:pr is:closed org:great_org author:author1 merged:2019-10-01..2019-12-31',
			$allRequestQueryParameters['q']
		);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldCreateBasicAuthenticationInRequestHeader(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = 'username:auth_token', $githubOrganization = '', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$firstRequestAuthorizationHeader =	$request->getHeaderLine('Authorization');
		$expectedBasicAuth = 'Basic ' . base64_encode('username:auth_token');
		Assert::assertEquals($expectedBasicAuth, $firstRequestAuthorizationHeader);
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldUseCorrectlyGithubUri(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = '', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$uri = $request->getUri();
		Assert::assertStringStartsWith('https://api.github.com/search/issues', strval($uri));
	}

	/** @test */
	public function whenCreateAPRListInfoApiRequest_shouldUseGetMethod(): void {
		$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials = '', $githubOrganization = '', $mergeInterval = '');
		$request = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername = '');

		$method = $request->getMethod();
		Assert::assertStringStartsWith('GET', $method);
	}

	private function extractAllQueryParametersFromRequest(Request $request): array {
		$requestQueryParameters = $request->getUri()->getQuery();
		$filterParameter = [];
		parse_str($requestQueryParameters, $filterParameter);

		return $filterParameter;
	}

	private function extractFilterParametersParametersFromRequest(Request $request): string {
		$filterParameter = $this->extractAllQueryParametersFromRequest($request);

		return $filterParameter['q'];
	}
}

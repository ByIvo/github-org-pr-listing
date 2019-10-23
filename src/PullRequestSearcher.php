<?php

namespace GithubPrListing;

use GuzzleHttp\Client;

class PullRequestSearcher {

	/** @var Client */
	private $client;

	public function __construct(Client $client) {
		$this->client = $client;
	}

	public function search(): RangePullRequestInfo {
		$githubOrganization = getenv('PR_LISTING_GITHUB_ORG');
		$mergeInterval = getenv('PR_LISTING_MERGE_INTERVAL');
		$githubCredentials = getenv('PR_LISTING_BASIC_AUTH_CREDENTIALS');
		$pullRequestAuthors = getenv('PR_LISTING_AUTHOR');
		$authors = mb_split(' ', $pullRequestAuthors);

		$totalCount = 0;
		foreach ($authors as $authorUsername) {
			$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials, $githubOrganization, $mergeInterval);
			$authorPRRequest = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername);
			$response = $this->client->send($authorPRRequest, $options = []);
			$rawBodyResponse = strval($response->getBody());

			$parsedResponse = json_decode($rawBodyResponse);

			$authorRangePRInfo = new RangePullRequestInfo($parsedResponse->total_count);

			$totalCount += $authorRangePRInfo->getPullRequestTotalCount();
		}

		return new RangePullRequestInfo($totalCount);
	}
}

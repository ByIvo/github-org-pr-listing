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
			$response = $this->client->get('https://api.github.com/search/issues', [
				'query' => [
					'q' => "type:pr is:closed org:{$githubOrganization} author:{$authorUsername} merged:{$mergeInterval}"
				],
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode($githubCredentials),
				],
			]);
			$rawBodyResponse = strval($response->getBody());

			$parsedResponse = json_decode($rawBodyResponse);

			$totalCount += $parsedResponse->total_count;
		}

		return new RangePullRequestInfo($totalCount);
	}
}

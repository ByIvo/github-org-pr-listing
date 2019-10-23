<?php

namespace GithubPrListing;

use GuzzleHttp\Client;

class PullRequestSearcher {

	/** @var Client */
	private $client;

	public function __construct(Client $client) {
		$this->client = $client;
	}

	public function search(): array {
		$githubOrganization = getenv('PR_LISTING_GITHUB_ORG');
		$mergeInterval = getenv('PR_LISTING_MERGE_INTERVAL');
		$githubCredentials = getenv('PR_LISTING_BASIC_AUTH_CREDENTIALS');
		$pullRequestAuthors = getenv('PR_LISTING_AUTHOR');
		$authorsFilterParameter = 'author:' . implode(' author:', mb_split(' ', $pullRequestAuthors));

		$response = $this->client->get('https://api.github.com/search/issues', [
			'query' => [
				'q' => "type:pr is:closed org:{$githubOrganization} {$authorsFilterParameter} merged:{$mergeInterval}"
			],
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode($githubCredentials),
			],
		]);
		$rawBodyResponse = strval($response->getBody());

		$parsedResponse = json_decode($rawBodyResponse);

		$pullRequestList = [];

		for ($i=0; $i < $parsedResponse->total_count; $i++) {
			$brandNewFakePullRequest = new PullRequest('Fake', 'Fake', 'Fake', new \DateTime('2019-10-12 21:35:32'));
			$pullRequestList[] = $brandNewFakePullRequest;
		}

		return $pullRequestList;
	}
}

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
		$response = $this->client->get('uri', [
			query => [
				'q' => "type:pr is:closed org:{$githubOrganization}"
			]
		]);
		$rawBodyResponse = strval($response->getBody());

		$parsedResponse = json_decode($rawBodyResponse);

		$pullRequestList = [];
		foreach ($parsedResponse->items as $pullRequest) {
			$title = $pullRequest->title;
			$author = $pullRequest->user->login;
			$url = $pullRequest->html_url;
			$closedAt = new \DateTime($pullRequest->closed_at);

			$pullRequestList[] = new PullRequest($title, $author, $url, $closedAt);
		}

		return $pullRequestList;
	}
}

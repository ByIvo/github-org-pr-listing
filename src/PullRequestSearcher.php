<?php

namespace GithubPrListing;

use GuzzleHttp\Client;
use http\QueryString;

class PullRequestSearcher {

	/** @var Client */
	private $client;

	public function __construct(Client $client) {
		$this->client = $client;
	}

	public function search(): array {
		$response = $this->client->get('uri', [
			query => [
				'q' => 'type:pr is:closed'
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

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

		$rangePullRequestInfo = new RangePullRequestInfo();
		foreach ($authors as $authorUsername) {
			$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials, $githubOrganization, $mergeInterval);
			$authorPRRequest = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername);
			$response = $this->client->send($authorPRRequest, $options = []);
			$rawBodyResponse = strval($response->getBody());

			$parsedResponse = json_decode($rawBodyResponse);

			$authorPullRequestTotalCount = $parsedResponse->total_count;
			$rangePullRequestInfo->addAuthorPullRequestInfo($authorUsername, $authorPullRequestTotalCount);
		}

		return $rangePullRequestInfo;
	}

	public function searchCodeReview(\DateTime $start, \DateTime $end): RangePullRequestInfo {
		$githubOrganization = getenv('PR_LISTING_GITHUB_ORG');
		$mergeInterval = getenv('PR_LISTING_MERGE_INTERVAL');
		$githubCredentials = getenv('PR_LISTING_BASIC_AUTH_CREDENTIALS');
		$pullRequestAuthors = getenv('PR_LISTING_AUTHOR');
		$authors = mb_split(' ', $pullRequestAuthors);

		$rangePullRequestInfo = new RangePullRequestInfo();

		foreach ($authors as $authorUsername) {
			$pullRequestApiRequest = new PullRequestApiRequest($githubCredentials, $githubOrganization, $mergeInterval);

			$authorPRRequest = $pullRequestApiRequest->createRequestWithPRInfoOfAuthor($authorUsername);
			$response = $this->client->send($authorPRRequest, $options = []);
			$rawBodyResponse = strval($response->getBody());

			$parsedResponse = json_decode($rawBodyResponse);

			$authorPullRequestTotalCount = $parsedResponse->total_count;
			$rangePullRequestInfo->addAuthorPullRequestInfo($authorUsername, $authorPullRequestTotalCount);
		}

		return $rangePullRequestInfo;
	}
}

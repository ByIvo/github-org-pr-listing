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
		$githubCredentials = getenv('PR_LISTING_BASIC_AUTH_CREDENTIALS');
		$codeReviewCommenters = getenv('PR_LISTING_AUTHOR');
		$commenters = mb_split(' ', $codeReviewCommenters);
		$createInterval = $this->createIntervalFilter($start, $end);

		$rangePullRequestInfo = new RangePullRequestInfo();
		foreach ($commenters as $commenterUsername) {
			$apiRequest = new PullRequestApiRequest($githubCredentials, $githubOrganization, $createInterval);

			$codeReviewCommenterRequest = $apiRequest->createCodeReviewRequest($commenterUsername);
			$response = $this->client->send($codeReviewCommenterRequest, $options = []);
			$rawBodyResponse = strval($response->getBody());

			$parsedResponse = json_decode($rawBodyResponse);

			/* Fix mocks in all request so we can remove this unnecessary default */
			$onlyCodeReviews = array_filter($parsedResponse->items ?? [], function($issue) use ($commenterUsername) {
				$author = $issue->user->login;
				return $author !== $commenterUsername;
			});


			/* Fix mocks in all request so we can remove this bizarre comparison */
			$commenterIsTheAuthorInSome = $parsedResponse->total_count !== sizeof($onlyCodeReviews) && !empty($onlyCodeReviews);
			$commenterCodeReviewTotalCount = $commenterIsTheAuthorInSome ? sizeof($onlyCodeReviews) : $parsedResponse->total_count;
			$rangePullRequestInfo->addAuthorPullRequestInfo($commenterUsername, $commenterCodeReviewTotalCount);
		}

		return $rangePullRequestInfo;
	}

	private function createIntervalFilter(\DateTime $start, \DateTime $end): string {
		$filterDateFormat = 'Y-m-d';
		$startFormatted = $start->format($filterDateFormat);
		$endFormatted = $end->format($filterDateFormat);

		return "$startFormatted..$endFormatted";
	}
}

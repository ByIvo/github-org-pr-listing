<?php

namespace GithubPrListing;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

class PullRequestApiRequest {

	/** @var string */
	private $githubCredentials;
	/** @var string */
	private $githubOrganization;
	/** @var string */
	private $mergeInterval;

	public function __construct(string $githubCredentials, string $githubOrganization, string $mergeInterval) {
		$this->githubCredentials = $githubCredentials;
		$this->githubOrganization = $githubOrganization;
		$this->mergeInterval = $mergeInterval;
	}

	public function createRequestWithPRInfoOfAuthor(string $authorUsername): Request {
		$pullRequestFilters = <<<FILTER
type:pr is:closed org:{$this->githubOrganization} author:{$authorUsername} merged:{$this->mergeInterval}
FILTER;

		return $this->createRequestWithFilters($pullRequestFilters);
	}

	public function createCodeReviewRequest(string $authorUsername): Request {
		$pullRequestFilters = <<<FILTER
type:pr org:{$this->githubOrganization} commenter:{$authorUsername} created:{$this->mergeInterval}
FILTER;

		return $this->createRequestWithFilters($pullRequestFilters);
	}

	private function createRequestWithFilters(string $filters): Request {
		$uri = new Uri('https://api.github.com/search/issues');
		$uriWithFilters = Uri::withQueryValue($uri, 'q', $filters);

		return new Request(
			$method = 'GET',
			$uriWithFilters,
			$headers = [
				'Authorization' => 'Basic ' . base64_encode($this->githubCredentials)
			]
		);
	}
}

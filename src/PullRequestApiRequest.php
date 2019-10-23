<?php

namespace GithubPrListing;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

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
		$uri = $this->createUriWithAllFiltersAndAuthToAuthorUsername($authorUsername);

		return new Request(
			$method = 'GET',
			$uri,
			$headers = [
				'Authorization' => 'Basic ' . base64_encode($this->githubCredentials)
			]
		);
	}

	private function createUriWithAllFiltersAndAuthToAuthorUsername(string $authorUsername): UriInterface {
		$uri = new Uri('https://api.github.com/search/issues');

		$pullRequestFilters = "type:pr is:closed org:{$this->githubOrganization} author:{$authorUsername} merged:{$this->mergeInterval}";
		return Uri::withQueryValue($uri, 'q', $pullRequestFilters);
	}
}

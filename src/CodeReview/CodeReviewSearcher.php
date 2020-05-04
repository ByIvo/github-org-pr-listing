<?php

namespace GithubPrListing\CodeReview;

use GithubPrListing\RangePullRequestInfo;

class CodeReviewSearcher {

	/** @var string */
	private $githubOrganization;
	/** @var string */
	private $githubCredential;
	/** @var array */
	private $authors;

	public function __construct(string $githubOrganization, string $githubCredential, array $authors) {
		$this->githubOrganization = $githubOrganization;
		$this->githubCredential = $githubCredential;
		$this->authors = $authors;
	}

	public function countCodeReviews(\DateInterval $searchInterval): RangePullRequestInfo {
		return null;
	}
}

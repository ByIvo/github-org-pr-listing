<?php

namespace GithubPrListing;

class RangePullRequestInfo implements \JsonSerializable {

	/** @var int */
	private $pullRequestTotalCount;

	public function __construct() {
		$this->pullRequestTotalCount = 0;
	}

	public function jsonSerialize() {
		return [
			'totalCount' => $this->pullRequestTotalCount
		];
	}

	public function getPullRequestTotalCount(): int {
		return $this->pullRequestTotalCount;
	}

	public function addAuthorPullRequestInfo($authorPullRequestTotalCount) {
		return $this->pullRequestTotalCount += $authorPullRequestTotalCount;
	}
}

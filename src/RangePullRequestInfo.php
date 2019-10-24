<?php

namespace GithubPrListing;

class RangePullRequestInfo implements \JsonSerializable {

	/** @var array */
	private $authorTotalCount;

	public function __construct() {
		$this->authorTotalCount = [];
	}

	public function jsonSerialize() {
		return [
			'totalCount' => $this->getPullRequestTotalCount(),
			'authorTotalCount' => $this->authorTotalCount,
		];
	}

	public function getPullRequestTotalCount(): int {
		return array_sum($this->authorTotalCount);
	}

	public function addAuthorPullRequestInfo(?string $authorUsername, int $authorPullRequestTotalCount) {
		$this->authorTotalCount[$authorUsername] = $authorPullRequestTotalCount;
	}
}

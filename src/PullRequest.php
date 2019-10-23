<?php

namespace GithubPrListing;

class PullRequest implements \JsonSerializable {

	/** @var int */
	private $pullRequestTotalCount;

	public function __construct(int $pullRequestTotalCount) {
		$this->pullRequestTotalCount = $pullRequestTotalCount;
	}

	public function jsonSerialize() {
		return [
			'totalCount' => $this->pullRequestTotalCount
		];
	}
}

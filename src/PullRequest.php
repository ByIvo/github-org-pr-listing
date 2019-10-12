<?php

namespace GithubPrListing;

class PullRequest {

	/** @var string */
	private $title;
	/** @var string */
	private $author;
	/** @var string */
	private $url;
	/** @var \DateTime */
	private $closedAt;

	public function __construct(string $title, string $author, string $url, \DateTime $closedAt) {
		$this->title = $title;
		$this->author = $author;
		$this->url = $url;
		$this->closedAt = $closedAt;
	}
}

<?php

namespace GithubPrListing;

class PullRequest implements \JsonSerializable {

	/** @var string */
	private $title;
	/** @var string */
	private $author;
	/** @var string */
	private $url;
	/** @var \DateTime */
	private $mergedAt;

	public function __construct(string $title, string $author, string $url, \DateTime $mergedAt) {
		$this->title = $title;
		$this->author = $author;
		$this->url = $url;
		$this->mergedAt = $mergedAt;
	}

	public function jsonSerialize() {
		return [
			'title' => $this->title,
			'author' => $this->author,
			'url' => $this->url,
			'mergedAt' => $this->mergedAt->format('d/m/Y'),
		];
	}
}

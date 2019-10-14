<?php

namespace Test\GithubPrListing;

use GithubPrListing\PullRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PullRequestTest extends TestCase {

	public function getPullRequestData(): array {
		return [
			['title 1', 'author 1', 'http://url1.com', '2019-01-28T15:00:00+02:00', '28/01/2019'],
			['title 2', 'author 2', 'http://url2.com', '2019-12-31T15:00:00+02:00', '31/12/2019'],
		];
	}

	/** @test @dataProvider getPullRequestData */
	public function whenParseObjectAsJson_shouldReturnAllObjectProperties(
		string $title,
		string $author,
		string $url,
		string $mergedAt,
		string $expectedMergeDate
	): void {
		$pullRequest = new PullRequest($title, $author, $url, new \DateTime($mergedAt));

		$serializedObject = $pullRequest->jsonSerialize();

		Assert::assertEquals([
			'title' => $title,
			'author' => $author,
			'url' => $url,
			'mergedAt' => $expectedMergeDate,
		], $serializedObject);
	}
}

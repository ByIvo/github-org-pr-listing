<?php

namespace Test\GithubPrListing;

use GithubPrListing\PullRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PullRequestTest extends TestCase {

	public function getPullRequestQuantity(): array {
		return [
			[1],
			[500],
			[1000],
		];
	}

	/** @test @dataProvider getPullRequestQuantity */
	public function whenParseObjectAsJson_shouldReturnAllObjectProperties(int $pullRequestTotalCount): void {
		$pullRequest = new PullRequest($pullRequestTotalCount);

		$serializedObject = $pullRequest->jsonSerialize();

		Assert::assertEquals([
			'totalCount' => $pullRequestTotalCount,
		], $serializedObject);
	}
}

<?php

namespace Test\GithubPrListing;

use GithubPrListing\RangePullRequestInfo;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class RangePullRequestInfoTest extends TestCase {

	public function getPullRequestQuantity(): array {
		return [
			[1],
			[500],
			[1000],
		];
	}

	/** @test @dataProvider getPullRequestQuantity */
	public function whenParseObjectAsJson_shouldReturnAllObjectProperties(int $pullRequestTotalCount): void {
		$pullRequest = new RangePullRequestInfo($pullRequestTotalCount);

		$serializedObject = $pullRequest->jsonSerialize();

		Assert::assertEquals([
			'totalCount' => $pullRequestTotalCount,
		], $serializedObject);
	}
}

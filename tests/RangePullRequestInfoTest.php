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
		$pullRequest = new RangePullRequestInfo();

		$pullRequest->addAuthorPullRequestInfo($pullRequestTotalCount);

		$serializedObject = $pullRequest->jsonSerialize();
		Assert::assertEquals([
			'totalCount' => $pullRequestTotalCount,
		], $serializedObject);
	}

	/** @test */
	public function givenMultipleAuthorPRInfo_whenParseObjectAsJson_shouldSumAllAuthorTotalCount(): void {
		$pullRequest = new RangePullRequestInfo();

		$pullRequest->addAuthorPullRequestInfo(2);
		$pullRequest->addAuthorPullRequestInfo(3);
		$pullRequest->addAuthorPullRequestInfo(7);

		Assert::assertEquals(12, $pullRequest->getPullRequestTotalCount());
	}
}

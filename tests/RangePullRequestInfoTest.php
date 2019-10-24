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

		$pullRequest->addAuthorPullRequestInfo('githubUsername', $pullRequestTotalCount);

		$serializedObject = $pullRequest->jsonSerialize();
		Assert::assertEquals([
			'totalCount' => $pullRequestTotalCount,
			'authorTotalCount' => [
				'githubUsername' => $pullRequestTotalCount
			],
		], $serializedObject);
	}

	/** @test */
	public function givenMultipleAuthorPRInfo_whenParseObjectAsJson_shouldSumAllAuthorTotalCount(): void {
		$pullRequest = new RangePullRequestInfo();

		$pullRequest->addAuthorPullRequestInfo('username1', 2);
		$pullRequest->addAuthorPullRequestInfo('username2', 3);
		$pullRequest->addAuthorPullRequestInfo('username3', 7);

		Assert::assertEquals(12, $pullRequest->getPullRequestTotalCount());
	}
}

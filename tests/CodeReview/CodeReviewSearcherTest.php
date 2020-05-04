<?php

namespace Test\GithubPrListing\CodeReview;

use GithubPrListing\CodeReview\CodeReviewSearcher;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class CodeReviewSearcherTest extends TestCase {

	/** @test */
	public function givenAnUser_shouldQueryAllTheirReviewedPullRequests(): void {
		$codeReviewSearcher = new CodeReviewSearcher('org', 'credential', $authors = ['byivo']);


		$codeReviewResult = $codeReviewSearcher->countCodeReviews();

		$expectedCodeReviewCount = [
			'totalCount' => 3,
			'authorTotalCount' => [
				'byivo' => 3
			]
		];
		Assert::assertEquals($expectedCodeReviewCount, $codeReviewResult->jsonSerialize());
	}
}

<?php

namespace Test\GithubPrListing\CodeReview\ResponseMocks;

class CodeReviewResponseMocks {

	public static function getResponseWithCommentsInOtherPeoplePullRequests(): string {
		return <<<JSON
{
    "total_count": 3,
    "items": [
        {
            "title": "PR Title 1",
            "user": {
                "login": "otherAuthor",
                "type": "User",
            },
            "state": "closed",
            "created_at": "2020-04-29T19:06:48Z",
            "updated_at": "2020-04-30T12:48:12Z",
            "closed_at": "2020-04-30T12:48:12Z"
        },
       {
            "title": "PR Title 2",
            "user": {
                "login": "otherAuthor",
                "type": "User",
            },
            "state": "closed",
            "created_at": "2020-04-29T19:06:48Z",
            "updated_at": "2020-04-30T12:48:12Z",
            "closed_at": "2020-04-30T12:48:12Z"
        },
        {
            "title": "PR Title 3",
            "user": {
                "login": "otherAuthor02",
                "type": "User",
            },
            "state": "closed",
            "created_at": "2020-04-29T19:06:48Z",
            "updated_at": "2020-04-30T12:48:12Z",
            "closed_at": "2020-04-30T12:48:12Z"
        },
    ]
}
JSON;
	}
}

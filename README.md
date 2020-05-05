# github-org-pr-listing
List all closed pull requests from a given Github Organization and users(authors/commenter).

**Important:** It'll only count closed and merged in Pull Requests list

# Running

## Creating a github token access

Open [Personal token access](https://github.com/settings/tokens) at github and create a new token exclusively to you. 
You have to check the *repo* role, that will provide _Full control of private repositories_.

## Running in development mode

Create your docker-composer.override.yml and setup the required environment variables (you can find them in docker-composer.yml).

Example:
```yml
version: '3'
services:
  web:
    environment:
      - PR_LISTING_GITHUB_ORG=organization
      - PR_LISTING_AUTHOR=author1 author2
      - PR_LISTING_BASIC_AUTH_CREDENTIALS=user:token
```

Then simply run `docker-compose up`

### Required env variables

* PR_LISTING_GITHUB_ORG: Should be valued as github organization name (the one you find in url)
* PR_LISTING_AUTHOR: Splitted by spaces, you can provide as many github authors as you want
* PR_LISTING_BASIC_AUTH_CREDENTIALS: Take your github token access along with your username. The value should be username:token

## Running in production mode

Currently, the Dockerfile is running the embedded php server, what is'nt recommended to production. 
But if you know what you're doing, you can build the image using `docker build -t image_name .` and then run it as a container (don't forget to export the port 9000 and also set up the env variables).

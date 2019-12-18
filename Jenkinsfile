#!/usr/bin/env groovy

pipeline {

  agent any

  environment {
    PR_LISTING_BASIC_AUTH_CREDENTIALS = credentials('github_access_token')
  }

  stages {

    stage('Install dependencies') {
      agent {
        docker { image 'composer:latest' }
      }

      steps {
        sh 'composer install'
      }
    }

    stage('Run phpunit tests') {
      agent {
        docker { image 'php:7.1' }
      }

      steps {
        sh './vendor/bin/phpunit'
      }
    }

    stage('Package docker image') {
      steps {
        sh 'docker stop $(docker ps -q --filter ancestor=ebanxteam/github-org-pr-listing) || true'
        sh 'docker build -t ebanxteam/github-org-pr-listing .'
      }
    }

    stage('Deploy') {
      steps {
        sh 'docker rm github-org-pr-listing || true'
        sh "docker run -d --name github-org-pr-listing \
        -p 9000:9000 \
        -e PR_LISTING_GITHUB_ORG='$PR_LISTING_GITHUB_ORG' \
        -e PR_LISTING_AUTHOR='$PR_LISTING_AUTHOR' \
        -e PR_LISTING_MERGE_INTERVAL='$PR_LISTING_MERGE_INTERVAL' \
        -e PR_LISTING_BASIC_AUTH_CREDENTIALS='$PR_LISTING_BASIC_AUTH_CREDENTIALS' \
        ebanxteam/github-org-pr-listing"
      }
    }
  }
}

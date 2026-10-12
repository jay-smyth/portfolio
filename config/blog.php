<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blog Guides
    |--------------------------------------------------------------------------
    |
    | Each guide is a series of static Markdown pages stored in
    | resources/content/blog/{guide}. The guide's landing page is index.md
    | (titled by "introduction" in the contents list) and each chapter is
    | {chapter}.md, listed here in reading order.
    |
    */

    'guides' => [

        'terraform-aws' => [
            'title' => 'Deploying to AWS with Terraform',
            'summary' => 'A step-by-step guide to the Terraform project that provisions this site\'s AWS infrastructure — S3, ECR, IAM and EC2 — from installing the tools to tearing it all down again.',
            'published' => '2026-10-12',
            'tags' => ['Terraform', 'AWS', 'DevOps'],
            'introduction' => 'What is Terraform?',
            'chapters' => [
                'the-deployments-directory' => 'Inside the deployments directory',
                'aws-resources' => 'The AWS resources it creates',
                'installing-the-tools' => 'Installing the tools',
                'connecting-to-aws' => 'Connecting your machine to AWS',
                'bootstrapping-state' => 'Bootstrapping remote state',
                'deploying-dev' => 'Deploying the dev environment',
                'using-your-infrastructure' => 'Using your infrastructure',
                'changes-and-teardown' => 'Making changes and tearing down',
            ],
        ],

    ],

];

---
title: DevOps
version: "1.0"
---

Are you repeatedly typing git commands to merge pull requests? This tool makes
life easier for maintainers. Only 2 commands replace all the git commands you 
usually type. It's based on the Zend Framework maintainers workflow so be sure
to check out if it works for your workflow as well.

## Installation

```bash
composer global require iswai/devops:1.0.x-dev
```

## Getting started

You need to export your forked project first:

```bash
git clone git@github.com:<your_username>/<project>.git 
```

### Step 1. upstream:set

Next up is setting the upstream to the original project location, which needs
to be done only once:

Format: `devops upstream:set <uri-to-original-project>`

```bash
devops upstream:set git@github.com:<original_organization>/<project>.git
```

This will also set default tracking to the upstream.

### Step 2. upstream:sync

Every time you feel like merging those pull requests, make sure your local
copy is synced with the original upstream: 

Format: `devops upstream:sync [--origin]`

```bash
devops upstream:sync --origin
```

This will sync you local master and develop (if it exists) branch with the 
upstream remote. If the optional `--origin` argument is used, your forked 
project will be synced as well.

### Step 3. pr:checkout

Next up is a checkout of the pull request. You have to add the pull request
type (hotfix or feature) and number. The type will determine how the pull 
request is merged and to which branches.

Format: `devops pr:checkout <hotfix|feature> <pull_request_number>`

```bash
devops pr:checkout hotfix 1
devops pr:checkout feature 1
```

Behind the scenes this will create a new branch (`hotfix\1` or `feature\1`)
depending on the pull request you chose. After that it will fetch the pull 
request data and merge it into the created new branch with the message: 
`"merge: pull request #1"`.

### Step 4. Run tests and add a changelog entry

Now you have all the time you need to run tests, add a changelog entry and
do a final review if needed.

Some suggestions are being displayed after step 3 which may help you to add
the correct changelog entries with
[keep-a-changelog](https://github.com/phly/keep-a-changelog).  

### Step 5. pr:merge

Finally merge the pull request and update the upstream remote.

```bash
devops pr:merge
```

This is a very short command but there is a lot more to it. First it detects
the pull request type and number. Makes sure you are on the correct local
branch you created in step 3, e.g. `hotfix\1` or `feature\1`.

Hotfixes are merged into the master and develop branch. Merged into the master
branch a `Close #<pr>` comment is added to the commit. Merging a hotfix into 
the develop branch will add `Forward port #<pr>`. If there is no upstream 
develop branch this part is skipped.

Features will be merged into the develop branch or the master branch if there
is no develop branch. The `Close #<pr>` comment is appended here as well.

Next the changed master and develop branches will be pushed to the upstream
remote. And finally the master branch is checked out again and the temporary
hotfix or feature branch, created in step 3, is deleted.

Repeat step 3-5 or create a new release.

# devops

[![Build Status](https://github.com/iswai/devops/workflows/QA%20tests/badge.svg)](https://github.com/iswai/develop/actions)
[![Downloads](https://img.shields.io/packagist/dt/iswai/devops.svg)](https://packagist.org/packages/iswai/devops)
[![Packagist](https://img.shields.io/packagist/v/iswai/devops.svg)](https://packagist.org/packages/iswai/devops)

## Installation

```bash
composer global require iswai/devops
```

## Usage

```bash
devops list

# Set upstream url
devops upstream:set git@github.com:<org>/<repo>.git 

# Sync upstream branches
devops upstream:sync --origin

# Checkout pull request
devops pr:checkout hotfix 1
devops pr:checkout feature 1

# Merge pull request
devops pr:merge
```

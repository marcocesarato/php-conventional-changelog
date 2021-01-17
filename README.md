# PHP Conventional Changelog

![Requirements](https://img.shields.io/badge/php-%3E%3D%205.5-4F5D95?style=for-the-badge)
![Code Style](https://img.shields.io/badge/code%20style-PSR-blue?style=for-the-badge)
![License](https://img.shields.io/github/license/marcocesarato/php-conventional-changelog?style=for-the-badge)
[![GitHub](https://img.shields.io/badge/GitHub-Repo-6f42c1?style=for-the-badge)](https://github.com/marcocesarato/php-conventional-changelog)

Generate changelogs and release notes from a project's commit messages and metadata using php composer.

## 📖 Installation

You can install it easily with composer

`composer require --dev marcocesarato/php-conventional-changelog`

## 💻 Usage

Generate a changelog without committing files:

`php vendor/bin/conventional-changelog`

or with auto commit and auto version tagging:

`php vendor/bin/conventional-changelog --commit`

### Commands List

```
-c      --commit        bool        Commit the new release once changelog is generated
-f      --from-date     str         Get commits from specified date
-h      --help          bool        Show the helper with all commands available
-m      --major         bool        Major release (important changes)
-n      --minor         bool        Minor release (add functionality)
-p      --patch         bool        Patch release (bug fixes)
-t      --to-date       str         Get commits from today (or specified on --from-date) to specified date
-v      --version       str         Specify next release version code (Semver)
```
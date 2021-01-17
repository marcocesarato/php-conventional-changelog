# PHP Conventional Changelog

![Version](https://img.shields.io/badge/version-1.0.2-brightgreen?style=for-the-badge)
![Requirements](https://img.shields.io/badge/php-%3E%3D%205.5-4F5D95?style=for-the-badge)
![Code Style](https://img.shields.io/badge/code%20style-PSR-blue?style=for-the-badge)
![License](https://img.shields.io/github/license/marcocesarato/php-conventional-changelog?style=for-the-badge)
[![GitHub](https://img.shields.io/badge/GitHub-Repo-6f42c1?style=for-the-badge)](https://github.com/marcocesarato/php-conventional-changelog)

Generate changelogs and release notes from a project's commit messages and metadata using php composer and automate versioning with semver.org and conventionalcommits.org

## 📖 Installation

You can install it easily with composer

`composer require --dev marcocesarato/php-conventional-changelog`

#### Composer script *(Optional)*

For easy use you can add to your `composer.json` the scripts:

> You can customize it according to your needs

```
{
  ...
  "scripts": {
    "changelog": "php vendor/bin/conventional-changelog",
    "release": "php vendor/bin/conventional-changelog --commit",
    "release:minor": "php vendor/bin/conventional-changelog --minor --commit",
    "release:major": "php vendor/bin/conventional-changelog --major --commit"
  },
  ...
}
```

Now you can just run `composer changelog` to generate your changelog.

## 💻 Usage

> **PS:** all following commands must be run (working dir) on the root of the project or in the path where the changelog should be generated

The changelog generator will generate a log of changes from the date of the last tag (if not specified with `--from-date`) to the current date (if not specified with` --to-date`),
and it will put all commit logs in the latest version just created (at the moment it doesn't generate the entire git commit version release history)

- Generate a changelog without committing files:
  
    `php vendor/bin/conventional-changelog`


- Generate a changelog with auto commit and auto version tagging:

    `php vendor/bin/conventional-changelog --commit`


- Generate a changelog from a specified date to another specified date:

    `php vendor/bin/conventional-changelog --from-date="2020-12-01" --to-date="2021-01-01"`

### Commands List

```
-c      --commit        bool        Commit the new release once changelog is generated
-f      --from-date     str         Get commits from specified date [YYYY-MM-DD]
-h      --help          bool        Show the helper with all commands available
-m      --major         bool        Major release (important changes)
-n      --minor         bool        Minor release (add functionality)
-p      --patch         bool        Patch release (bug fixes)
-t      --to-date       str         Get commits from last tag date (or specified on --from-date) to specified date [YYYY-MM-DD]
-v      --version       str         Specify next release version code (Semver)
```
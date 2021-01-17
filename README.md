# PHP Conventional Changelog

![Version](https://img.shields.io/badge/version-1.1.0-brightgreen?style=for-the-badge)
![Requirements](https://img.shields.io/badge/php-%3E%3D%205.5-4F5D95?style=for-the-badge)
![Code Style](https://img.shields.io/badge/code%20style-PSR-blue?style=for-the-badge)
![License](https://img.shields.io/github/license/marcocesarato/php-conventional-changelog?style=for-the-badge)
[![GitHub](https://img.shields.io/badge/GitHub-Repo-6f42c1?style=for-the-badge)](https://github.com/marcocesarato/php-conventional-changelog)

Generate changelogs and release notes from a project's commit messages and metadata using php composer and automate versioning with semver.org and conventionalcommits.org

## 📖 Installation

You can install it easily with composer

`composer require --dev marcocesarato/php-conventional-changelog`

#### Scripts *(Optional)*

For easy use the changelog generator or release faster your new version you can add to your `composer.json` the scripts:

> You can customize it according to your needs

```
{
  ...
  "scripts": {
    "changelog": "conventional-changelog",
    "release": "conventional-changelog --commit",
    "release:minor": "conventional-changelog --minor --commit",
    "release:major": "conventional-changelog --major --commit"
  },
  ...
}
```

Now you can just run `composer changelog` to generate your changelog.

## 💻 Usage

> **Note:** all following commands must be run (working dir) on the root of the project or in the path where the changelog should be generated

The changelog generator will generate a log of changes from the date of the last tag *(if not specified with `--from-date`)* to the current date *(if not specified with `--to-date`)*,
and it will put all commit logs in the latest version just created (at the moment it doesn't generate the entire git commit version release history).
By default, will be added one to the patch semver part *(Example, if the last version is `1.0.2` the newer, if not specified the identity of the release, will be `1.0.3`)*.

- To generate your changelog for your first release:
  
  > **Note:** If the version code (`--ver`) isn't specified it will be automatically `1.0.0`

  `php vendor/bin/conventional-changelog --first-release`


- To generate your changelog without committing files:
  
    `php vendor/bin/conventional-changelog`


- To generate your changelog with auto commit and auto version tagging:

    `php vendor/bin/conventional-changelog --commit`


- To generate your changelog from a specified date to another specified date:

    `php vendor/bin/conventional-changelog --from-date="2020-12-01" --to-date="2021-01-01"`


- To generate your changelog with a specific version code:

  `php vendor/bin/conventional-changelog --ver="2.0.1"`


- To generate your changelog with the entire history of changes of all releases:

  > **Warn:** This operation will overwrite the `CHANGELOG.md` file if it already exists

  `php vendor/bin/conventional-changelog --history`


### Commands List

> You can have more info about running  `php vendor/bin/conventional-changelog --help`

```
-c      --commit          bool        Commit the new release once changelog is generated
-p      --patch           bool        Patch release (bug fixes) [default]
-min    --minor           bool        Minor release (add functionality)
-maj    --major           bool        Major release (important changes)
        --first-release   bool        Run at first release (if --ver isn't specified version code will be 1.0.0)
        --to-date         str         Get commits from last tag date (or specified on --from-date) to specified date [YYYY-MM-DD]
        --from-date       str         Get commits from specified date [YYYY-MM-DD]
        --ver             str         Define the next release version code (semver)
        --history         bool        Generate the entire history of changes of all releases
```
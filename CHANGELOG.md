# Changelog
All notable changes to this project will be documented in this file.


## [1.2.0](https://github.com/marcocesarato/php-conventional-changelog/compare/v1.1.0...v1.2.0) (2021-01-17)


### Features

* Add history option ([d53555](https://github.com/marcocesarato/php-conventional-changelog/commit/d53555442dbff0375db46649a306234309ba60ae))
* Add commit type exclusions options and add changelog file on commit ([055497](https://github.com/marcocesarato/php-conventional-changelog/commit/0554976a445d82db914cd256cc931f70b5923db6))

### Fixes

* Add current release on history only with commit option flagged ([bac00d](https://github.com/marcocesarato/php-conventional-changelog/commit/bac00d478a9a93831af797080841c6991c6d660b))
* Git commit add files to the repository ([301184](https://github.com/marcocesarato/php-conventional-changelog/commit/301184dc9d17649b25db06f4e4d45ac316533c74))
* Auto commit success message ([5dec89](https://github.com/marcocesarato/php-conventional-changelog/commit/5dec89ce412e37476868076a0e550b41d122049d))
* Git commit release message ([b6f3e4](https://github.com/marcocesarato/php-conventional-changelog/commit/b6f3e461d348e21fbfde278385a8a7119defdf98))

### Refactoring

* Move some methods to git and utils class ([6f8e4a](https://github.com/marcocesarato/php-conventional-changelog/commit/6f8e4a5617a09703f317d08594d42bbbbb0eeebd))
* Move git commit and tag actions to git class ([113597](https://github.com/marcocesarato/php-conventional-changelog/commit/11359787566db571fd2a10fd6022d39e912399ee))

### Docs


##### Readme

* Add history option with examples ([33d51b](https://github.com/marcocesarato/php-conventional-changelog/commit/33d51b1ab1c7c9821554c407e69f6b95925755bc))
* Add no chores and no refactor options on command list ([106114](https://github.com/marcocesarato/php-conventional-changelog/commit/106114359633a230270139b867b0be18af8086e2))

### Chores

* Update history option description ([b7d180](https://github.com/marcocesarato/php-conventional-changelog/commit/b7d180fb0a506d79828b6d4ef88df7b33f4aab2d))

##### Composer

* Add scripts for changelog and release ([a5bd92](https://github.com/marcocesarato/php-conventional-changelog/commit/a5bd92f4baff375a1cafb4bc87190ff346930cb9))

---

## [v1.1.0](https://github.com/marcocesarato/php-conventional-changelog/compare/v1.0.2...vv1.1.0) (2021-01-17)


### Features

* Add autoloader ([8e5667](https://github.com/marcocesarato/php-conventional-changelog/commit/8e5667550c2188e78a0d3ec8e5d388f413f0b813))
* Add information output about commit tag and file path ([7c7f4f](https://github.com/marcocesarato/php-conventional-changelog/commit/7c7f4f43995dd88a5b836b7c5f45b1144b7856fe))
* Implementing symfony console ([78e11b](https://github.com/marcocesarato/php-conventional-changelog/commit/78e11be242147cee1f7ab8ce526a9b969ef79b09))
* Add first release option ([143d6b](https://github.com/marcocesarato/php-conventional-changelog/commit/143d6b7828c8989e33ef23d22476ed14a7d4d935))

### Docs

* Update description and add new instructions on readme ([3e1086](https://github.com/marcocesarato/php-conventional-changelog/commit/3e1086f844bd4af6609911333cbf97b0ed99cdee))

##### Readme

* Fix json composer scripts ([118cfd](https://github.com/marcocesarato/php-conventional-changelog/commit/118cfd275099a8534cd38176d5b6e1820fc88a4e))
* Remove duplicate line ([d88a00](https://github.com/marcocesarato/php-conventional-changelog/commit/d88a00b704f7d621f664facb5656e384b273f531))
* Clarify bump version processs ([ba9227](https://github.com/marcocesarato/php-conventional-changelog/commit/ba92277bdd60c2ff97a1ee26918f8bfca5527652))
* Add first release option, update commands list and last version code ([59e3bb](https://github.com/marcocesarato/php-conventional-changelog/commit/59e3bb92016dfcc1d553cd83450a0427007069a4))

### Chores

* Indicate default value of patch flag on helper list ([91df9c](https://github.com/marcocesarato/php-conventional-changelog/commit/91df9cc1600b9a2fb91be2ad2ba3eed9dafd3802))

##### Code Standard

* Change script for code standard fixing ([dba6bb](https://github.com/marcocesarato/php-conventional-changelog/commit/dba6bb1807ef72808cb879d913dad86eeee53c90))

---

## [v1.0.2](https://github.com/marcocesarato/php-conventional-changelog/compare/v1.0.1...vv1.0.2) (2021-01-17)


### Docs

* Specified date format to use on commands ([cdb712](https://github.com/marcocesarato/php-conventional-changelog/commit/cdb71211ce49c04e8789338b700839aaeb5303e1))

### Chores

##### Bin

* Move conventional changelog file to root ([f7a20c](https://github.com/marcocesarato/php-conventional-changelog/commit/f7a20c5ae16ea372b26f6402695d94a47b432866))

---

## [v1.0.1](https://github.com/marcocesarato/php-conventional-changelog/compare/v1.0.0...vv1.0.1) (2021-01-17)


### Docs


##### Readme

* Add package description ([5a63a4](https://github.com/marcocesarato/php-conventional-changelog/commit/5a63a4e9e59347aa99753444e7156ca23b2b6058))
* Add info shields ([b96291](https://github.com/marcocesarato/php-conventional-changelog/commit/b9629156f0af4c3207e77739a52ccb5ddcf4f4da))

### Chores

* Report only fatal error ([0de3e0](https://github.com/marcocesarato/php-conventional-changelog/commit/0de3e0fc6d5419bd823bd73dd0bae27ecb7c3d33))

---

## [v1.0.0](https://github.com/marcocesarato/php-conventional-changelog/compare/021a49f43ef65ac7a594450374f1772eef1fd8b0...vv1.0.0) (2021-01-17)

### Description

- Generate changelogs and release notes from a project's commit messages and metadata using php composer and automate versioning with semver.org and conventionalcommits.org

---


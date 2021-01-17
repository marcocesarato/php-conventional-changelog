# TODO

- Amend to commit `--amend`
- Pre release (`--pre-release`)
- Add `⚠ BREAKING CHANGES` list on top of the version release changes
- Add `Refs` and `Closes` on changes line
- Automated bump version (if not specified version bump)
    - Major if find `BREAKING CHANGES`
    - Minor if find `feat`
    - Patch if find only `fix`
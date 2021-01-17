# TODO

- First release (`--first-release`)
- Add `⚠ BREAKING CHANGES` list on top of the version release changes
- Add `Refs` and `Closes` on changes line
- Generate entire git release history (`--history`)
- Automated bump version (if not specified version bump)
    - Major if find `BREAKING CHANGES`
    - Minor if find `feat`
    - Patch if find only `fix`
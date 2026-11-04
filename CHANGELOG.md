# Changelog

## [0.5.0](https://github.com/david-grosskost/suitecrm-core/compare/v0.4.0...v0.5.0) (2026-11-04)


### Features

* complete the French labels missing from the language pack ([#13](https://github.com/david-grosskost/suitecrm-core/issues/13)) ([92b6da9](https://github.com/david-grosskost/suitecrm-core/commit/92b6da9ef5610af318ed4355480d2fd9b9ebb068))

## [0.4.0](https://github.com/david-grosskost/suitecrm-core/compare/v0.3.1...v0.4.0) (2026-11-04)


### Features

* keep instance data in the instance directory on servers ([#11](https://github.com/david-grosskost/suitecrm-core/issues/11)) ([3c9a642](https://github.com/david-grosskost/suitecrm-core/commit/3c9a642becc465cf7b7cab3523f639388bd4bdbf))

## [0.3.1](https://github.com/david-grosskost/suitecrm-core/compare/v0.3.0...v0.3.1) (2026-11-02)


### Bug Fixes

* run the deployment smoke test with devkit v0.7.1 ([#8](https://github.com/david-grosskost/suitecrm-core/issues/8)) ([07d52d3](https://github.com/david-grosskost/suitecrm-core/commit/07d52d32f7fefeb1937e6109bf99fed027a6ad9b))

## [0.3.0](https://github.com/david-grosskost/suitecrm-core/compare/v0.2.0...v0.3.0) (2026-11-02)


### Features

* smoke test the demo after each deployment ([#6](https://github.com/david-grosskost/suitecrm-core/issues/6)) ([27c5f5a](https://github.com/david-grosskost/suitecrm-core/commit/27c5f5a7611c68af3f54deecc7e415461da01525))

## [0.2.0](https://github.com/david-grosskost/suitecrm-core/compare/v0.1.0...v0.2.0) (2026-11-02)


### Features

* add demo and prod compose overlays for servers ([#3](https://github.com/david-grosskost/suitecrm-core/issues/3)) ([203a527](https://github.com/david-grosskost/suitecrm-core/commit/203a52724e7d85dd5f0ac05f0f1ebbea0e46ab37))
* deploy every release to the demo ([#5](https://github.com/david-grosskost/suitecrm-core/issues/5)) ([d8a51fe](https://github.com/david-grosskost/suitecrm-core/commit/d8a51fe651cb4641ba13fb38c7081a60724acef6))

## 0.1.0 (2026-11-01)


### Features

* **docker:** add base image with Apache, PHP 8.4 and SuiteCRM 8.10.2 ([0f5d178](https://github.com/david-grosskost/suitecrm-core/commit/0f5d17841d671642498a33ee1ba0de249816dfe2))
* **docker:** add compose stack with MariaDB behind the reverse proxy ([57ad5dc](https://github.com/david-grosskost/suitecrm-core/commit/57ad5dcef33da900d595feb76deca7bcd59b4e95))
* **docker:** add cron service running the SuiteCRM schedulers ([8388366](https://github.com/david-grosskost/suitecrm-core/commit/8388366b7d25b871827030bbfa661153a536e435))
* **docker:** keep the application in a volume synced from the image ([2f3aa09](https://github.com/david-grosskost/suitecrm-core/commit/2f3aa09d5d043e863d1e9ced8b2e35f4b0f7ee03))
* **i18n:** add French language pack and make it the default ([94ebf80](https://github.com/david-grosskost/suitecrm-core/commit/94ebf80b8455ab01de57ef79cc04735affa226d4))
* **i18n:** use French formats by default ([895f8a0](https://github.com/david-grosskost/suitecrm-core/commit/895f8a0748bae5d0dcdea502f2a55eb63fd50aa2))
* **make:** add app commands to manage the SuiteCRM stack ([1c573c6](https://github.com/david-grosskost/suitecrm-core/commit/1c573c689bd55ffd4e488bacc99ee61c42c74c21))
* **make:** install SuiteCRM silently with app@install ([3b3b7d2](https://github.com/david-grosskost/suitecrm-core/commit/3b3b7d23e3f901da14756ee7599b6c3dc20867d5))

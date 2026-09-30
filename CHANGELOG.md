# Changelog

## [v0.3.0](https://github.com/runapi-ai/kling-php/releases/tag/v0.3.0) - 2026-09-30

### Changed
- Send request parameters to the service without local validation. Model ids and parameter values the service supports work without an SDK upgrade; static types and enum constants remain for completion.
  Migration: Invalid parameters now throw `ValidationException` built from the service's 400 response, including its status and message, instead of a `ValidationException` thrown locally before the request.


## [v0.2.1](https://github.com/runapi-ai/kling-php/releases/tag/v0.2.1) - 2026-09-29

### Changed
- Enforce the kling-v2.6 sound-mode rule only through the generated contract rules; the rejection and its message are unchanged.


## [v0.2.0](https://github.com/runapi-ai/kling-php/releases/tag/v0.2.0) - 2026-08-21

### Added
- Add typed text-to-video and edit-video resources for Kling V3 Omni workflows.


## [v0.1.6](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.6) - 2026-08-04

### Fixed
- Reject reference URLs whose host is an incomplete or malformed numeric address.


## [v0.1.5](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.5) - 2026-07-28

### Added
- Add Kling O1 reference-media fields and client-side cross-field validation to text-to-video and image-to-video resources.


## [v0.1.4](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.4) - 2026-07-23

### Added
- Add Kling 2.6 motion-control model support and contract-driven request validation.


## [v0.1.3](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.3) - 2026-07-23

### Added
- Add Kling V3 Omni model constants, request fields, and final-frame duration validation.
- Add PHP SDK support for continuing completed Kling v2.5 Turbo videos.


## [v0.1.2](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.2) - 2026-07-22

### Added
- Add Kling 2.6 model constants, request fields, and conditional sound and final-frame validation.


## [v0.1.1](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.1) - 2026-07-16

### Changed
- Add Kling V3 Turbo text-to-video and image-to-video resource helpers to the PHP package.
- Include typed model and parameter support for the new Kling V3 Turbo variants.

## [v0.1.0](https://github.com/runapi-ai/kling-php/releases/tag/v0.1.0) - 2026-06-25

### Added
- Publish the first RunAPI PHP Composer package release for `runapi-ai/kling`.
- Include typed PHP client resources, package README, Apache-2.0 license, and Composer CI.

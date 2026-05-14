# Changelog

Alle noemenswaardige wijzigingen aan `emeq/mollie-api` worden hier bijgehouden.

Het formaat is gebaseerd op [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/),
en dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## [Unreleased]

### Added

- Initiële package-skeleton (Phase 2 emeq/hub v0.2): `MollieServiceProvider`, `Mollie`-facade,
  `MollieCredentialResolver`-contract, dual creds (`MollieApiKeyCredentials` + `MollieOAuthCredentials`),
  exception-laag (`MollieException` + `MissingCredentialResolverException`), Pest-suite ≥10 cases.

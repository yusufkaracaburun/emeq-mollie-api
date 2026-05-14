<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Contracts;

use Emeq\MollieApi\Data\MollieCredentials;

/**
 * Strategy that produces Mollie credentials for the *current* request /
 * job / console-command. The host app implements this and binds it in the
 * container — see README.md "Credentials wiring".
 *
 * Implementations return either:
 *  - Emeq\MollieApi\Data\MollieApiKeyCredentials  — when the tenant uses a
 *    direct API-key (test_ or live_ prefix), e.g. Emeq's own Mollie account.
 *  - Emeq\MollieApi\Data\MollieOAuthCredentials   — when the tenant uses
 *    Mollie Connect OAuth (access_ prefix), e.g. an end-customer's Mollie
 *    account linked via the Hub's OAuth-broker.
 *
 * Typical implementations:
 *  - read from `tenant()->settings()` (stancl/tenancy)
 *  - read from `Connection->access_token` on a multi-tenant Hub
 *  - read from `config('services.mollie')` for single-tenant apps
 */
interface MollieCredentialResolver
{
    public function resolve(): MollieCredentials;
}

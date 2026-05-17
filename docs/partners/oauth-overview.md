---
source_url: https://docs.mollie.com/oauth/overview
fetched: 2026-05-14
scope: Phase 4 (HUB-02 retroactief) + Phase 5a (MOLL-04, HUB-03)
status: hybrid (Mollie's `/oauth/*` pages zijn JS-rendered en leveren via WebFetch alleen overview-tekst; canonical endpoint-URLs komen uit `app/OAuth/Mollie/MollieConnectOAuthFlow.php` Phase-4-implementatie + scope-tabel uit `/docs/permissions`)
canonical_implementation: app/OAuth/Mollie/MollieConnectOAuthFlow.php
related_urls:
  - https://docs.mollie.com/docs/permissions
  - https://docs.mollie.com/connect/getting-started
  - https://docs.mollie.com/docs/connect-platforms-setting-up-oauth
---

# Mollie Connect — OAuth2 overview

> **NB:** Mollie's OAuth-referentiepagina's (`/reference/oauth2-authorize`, `/reference/oauth2-tokens`) zijn JS-rendered en leveren via WebFetch geen technische velden. De endpoint-URLs en body-shapes hieronder komen uit de **productie-geteste** Phase-4-implementatie (`MollieConnectOAuthFlow`, 7 feature-tests groen, alle 5 SC's bewezen). De scope-tabel komt wél direct uit Mollie's `/docs/permissions` page (HTML-rendered).

## Wat het is

Mollie Connect = Mollie's OAuth2-platform-rol. Een Connect-partner (Emeq) regisseert in Mollie's developer portal één Connect-app met `client_id` + `client_secret`. Eindmerchants (Accounts in Hub-termen) klikken op "Connect with Mollie", autoriseren scopes, en koppelen daarmee hun eigen Mollie-organisatie aan de partner-app. De partner roept Mollie's API aan **namens** die merchant met een per-merchant `access_token` — niet met de partner's eigen API-key.

Mollie-quote (uit `/oauth/overview`): *"All these use cases are facilitated through the use of OAuth. (…) Once your account is configured as a partner account, any merchant you sign up through the OAuth onboarding flow will automatically be linked to your account."*

## Endpoints

| Doel | Method | URL |
|---|---|---|
| Authorize (browser-redirect) | `GET` | `https://my.mollie.com/oauth2/authorize` |
| Token exchange (code → tokens) | `POST` | `https://api.mollie.com/oauth2/tokens` |
| Token refresh | `POST` | `https://api.mollie.com/oauth2/tokens` |
| Revoke (logout/uninstall) | `DELETE` | `https://api.mollie.com/oauth2/tokens` |

> Note hostname-split: **`my.mollie.com`** voor de browser-facing authorize-redirect, **`api.mollie.com`** voor server-to-server token-calls. Niet door elkaar halen.

## Authorize-redirect

Browser-redirect, alle params als query-string:

```
GET https://my.mollie.com/oauth2/authorize
  ?client_id=app_xxx
  &redirect_uri=https://hub.emeq.test:8090/v1/oauth/mollie/callback
  &state=<48-char random>
  &scope=payments.read+payments.write+customers.read
  &response_type=code
  &approval_prompt=auto
```

| Param | Verplicht | Notes |
|---|---|---|
| `client_id` | ✅ | Connect-app's identifier (formaat `app_xxx`) |
| `redirect_uri` | ✅ | Moet exact matchen met wat in Mollie-portal voor deze app geregistreerd is |
| `state` | ✅ | CSRF-bescherming. Hub genereert 48-char random per flow + opslag in `connections.oauth_state` met 30 min TTL (Phase 4 D-08). Callback verifieert match |
| `scope` | ✅ | **Spatie-separated** (door `http_build_query()` URL-encoded naar `+`). Lijst zie scope-tabel hieronder |
| `response_type` | ✅ | Altijd `code` (authorization-code-flow) |
| `approval_prompt` | optioneel | `auto` (default — herauthoriseert niet als merchant al toestemming gaf) of `force` (toont consent-screen altijd) |
| `locale` | optioneel | `nl_NL`, `en_US`, etc. — beïnvloedt taal van consent-screen |

**Redirect-back:**

```
GET <redirect_uri>?code=<authcode>&state=<original-state>
```

Hub callback (`CallbackController`):
1. Match `state` ↔ `Connection::where('oauth_state', ...)->where('oauth_state_expires_at', '>', now())`. Geen match of expired → 400.
2. Roep `MollieConnectOAuthFlow::exchangeCode($connection, $code)` aan.
3. State wordt op `null` gezet (replay-protectie).

## Token-exchange (`grant_type=authorization_code`)

```http
POST https://api.mollie.com/oauth2/tokens
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=<auth-code-from-callback>
&redirect_uri=<same as in authorize-step>
&client_id=app_xxx
&client_secret=<secret>
```

> Auth-methode: Hub's Phase-4-implementatie zet `client_id` + `client_secret` **in de body** (niet als HTTP Basic Auth). Mollie accepteert beide; in-body is wat in productie draait en groen door 4 feature-tests komt.

## Token-refresh (`grant_type=refresh_token`)

```http
POST https://api.mollie.com/oauth2/tokens
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token=<stored-refresh-token>
&client_id=app_xxx
&client_secret=<secret>
```

## Token-response

Identieke shape voor authorization_code en refresh_token:

```json
{
  "access_token": "access_xxxxxxx",
  "refresh_token": "refresh_xxxxxxx",
  "expires_in": 3600,
  "token_type": "bearer",
  "scope": "payments.read payments.write customers.read"
}
```

| Veld | Hub-mapping |
|---|---|
| `access_token` | `connections.access_token` (encrypted at rest) |
| `refresh_token` | `connections.refresh_token` (encrypted at rest). **Bij refresh: rotate als response 'm bevat, anders behoud de huidige** (Mollie levert er soms wel/niet één — Phase 4 fallback: `$response['refresh_token'] ?? $connection->refresh_token`) |
| `expires_in` | Mollie-default = **3600s** (1 uur). Hub schrijft `connections.expires_at = now()->addSeconds($expires_in)` |
| `scope` | **Space-separated** in response (NIET plus-separated zoals in authorize-request). Hub splitst op `' '` naar `connections.scopes` (json-array kolom) |
| `token_type` | Altijd `bearer` — Hub negeert dit veld |

### Refresh-policy (Phase 4 D-04 / D-06)

Lazy refresh in `HubMollieCredentialResolver::resolve()`:
- Geen scheduled job, geen cron — refresh gebeurt **just-in-time** wanneer een Hub-pass-through-call de Connection nodig heeft.
- Buffer: refresh wanneer `expires_at <= now() + 5min`. Voorkomt een 401-roundtrip aan het einde van de token-lifetime.
- **Concurrency-lock:** `Cache::lock("oauth:refresh:{$connection->id}", 30)->block(15, …)` — twee parallelle requests op dezelfde Connection wachten op elkaar in plaats van twee parallelle refreshes te doen (zou een refresh-token-rotation race opleveren waarbij één van beide met een verlopen token achterblijft).

## Revoke (`DELETE /oauth2/tokens`)

```http
DELETE https://api.mollie.com/oauth2/tokens
Authorization: Basic <base64(client_id:client_secret)>
Content-Type: application/x-www-form-urlencoded

token_type_hint=access_token
&token=<access-token-to-revoke>
```

> Auth-methode: revoke gebruikt **HTTP Basic Auth** met client_id:client_secret (niet in-body). Phase 4-implementatie volgt dat verschil (`->withBasicAuth(...)`).

Hub-side: `Connection::update(['status' => 'revoked', 'revoked_at' => now()])`. Een gerevoked Connection wordt door `ResolveMollieAccount`-middleware (Phase 5a D-03) herkend en niet meer gekozen.

## OAuth-scopes (37)

Bron: https://docs.mollie.com/docs/permissions (HTML-rendered, geverifieerd 2026-05-14).

| Scope | Allows |
|---|---|
| `balances.read` | View merchant's balances information |
| `balance-transfers.read` | View merchant's balance transfers |
| `balance-transfers.write` | Create balance transfers |
| `customers.read` | View merchant's customers |
| `customers.write` | Manage merchant's customers |
| `external-accounts.read` | View merchant's external accounts |
| `external-accounts.write` | Manage merchant's external accounts |
| `invoices.read` | View merchant's invoices |
| `mandates.read` | View merchant's mandates |
| `mandates.write` | Manage merchant's mandates |
| `onboarding.read` | View merchant's onboarding status |
| `onboarding.write` | Submit onboarding data |
| `orders.read` | View merchant's orders |
| `orders.write` | Manage merchant's orders |
| `organizations.read` | View merchant's organizational details |
| `organizations.write` | Change merchant's organizational details |
| `payment-links.read` | View merchant's payment links |
| `payment-links.write` | Create payment links |
| `payments.read` | View payments, chargebacks and payment methods |
| `payments.write` | Create payments (added to merchant's balance) |
| `payouts.read` | View payouts |
| `payouts.write` | Create, cancel and update payouts |
| `persons.read` | View merchant's persons and stakeholders |
| `persons.write` | Manage merchant's persons and stakeholders |
| `profiles.read` | View merchant's website profiles |
| `profiles.write` | Manage merchant's website profiles |
| `refunds.read` | View merchant's refunds |
| `refunds.write` | Create or cancel refunds |
| `settlements.read` | View merchant's settlements |
| `shipments.read` | View merchant's order shipments |
| `shipments.write` | Manage merchant's order shipments |
| `subscriptions.read` | View merchant's subscriptions |
| `subscriptions.write` | Manage merchant's subscriptions |
| `terminals.read` | View merchant's POS terminals |
| `terminals.write` | Manage merchant's POS terminals |
| `webhooks.read` | View merchant's webhooks |
| `webhooks.write` | Manage merchant's webhooks |

### Phase 5a-scope-set (default voor Naschool-flow)

7 scopes dekken de 7 in-scope resources van `05a-CONTEXT.md`:

```
payments.read payments.write
customers.read customers.write
mandates.read mandates.write
subscriptions.read subscriptions.write
refunds.read refunds.write
payment-links.read payment-links.write
profiles.read
```

> `payment-methods` heeft géén eigen scope — vereist `profiles.read` (methods worden per profile uitgelezen).

## Phase-mapping (waar leeft dit in de Hub?)

| Component | Bestand | Phase |
|---|---|---|
| OAuthFlow-contract | `app/OAuth/Contracts/OAuthFlow.php` | 4 (D-13) |
| Mollie-implementatie | `app/OAuth/Mollie/MollieConnectOAuthFlow.php` | 4 |
| Registry | `app/OAuth/OAuthFlowRegistry.php` | 4 |
| Init endpoint | `POST /v1/oauth/mollie/init` (`InitController`) | 4 D-01/D-08 |
| Callback endpoint | `GET /v1/oauth/mollie/callback` (`CallbackController`) | 4 |
| Pending-cleanup command | `oauth:prune-pending` (`PruneOAuthPendingConnections`) | 4 D-09 |
| Credential-resolver | `App\Mollie\HubMollieCredentialResolver` | 4 (D-16) |
| Per-request context | `App\Mollie\MollieConnectionContext` (scoped) | 4 |
| Scope-set bij init | `config('services.mollie.connect.scopes')` (env-driven) | 4 |
| Webhook-secret (één per platform) | `config('services.mollie.webhook_secret')` | 5a (D-08) |

Env-keys (`.env.example` lines 69-71):

```
MOLLIE_CONNECT_CLIENT_ID=
MOLLIE_CONNECT_CLIENT_SECRET=
MOLLIE_CONNECT_REDIRECT_URI=https://hub.emeq.test:8090/v1/oauth/mollie/callback
```

## Phase 5a-relevantie

- Pass-through-controllers vragen Mollie-tokens via `HubMollieCredentialResolver` — die leest `MollieConnectionContext` (gezet door `ResolveMollieAccount`-middleware, D-03) en voert lazy refresh uit zodra `expires_at` binnen 5 min ligt.
- Webhook-flow (D-08) gebruikt **één platform-webhook-secret** (Connect-webhooks zijn platform-signed, niet per-Connection). Anti-spoofing-stap fetch het Mollie-resource met de Connection's `access_token` om resource-ownership te bevestigen.
- Geen pre-flight scope-check (D-11): Hub stuurt blind door, mapt Mollie's 403 → Hub `502 mollie_auth_failed` via `MollieUpstreamErrorMapper`.

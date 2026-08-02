# Pterodactyl Panel — Customized Fork

This is a customized fork of [Pterodactyl Panel](https://pterodactyl.io), the open-source game server management panel built with PHP (Laravel), React, and Go. The core panel — Docker-isolated game servers, a modern UI, node/location management, and the full admin toolset — is unchanged from upstream Pterodactyl.

On top of that core, this fork adds a **self-service billing and monetization layer**, letting users fund a wallet and instantly provision their own game servers without any manual admin intervention — turning the panel into a standalone SaaS hosting platform.

## What's different from upstream Pterodactyl

### Billing & Monetization

- **Wallet system** — every user has a wallet balance (`wallet_balance`) that can be topped up and spent on hosting plans.
- **Transactions ledger** — every deposit and charge is recorded (`pending` / `success` / `failed`) with a unique reference, so payment history is fully auditable per user.
- **Paystack payment gateway integration**:
  - Card payments via Paystack's standard charge flow.
  - **M-Pesa STK Push** via Paystack's mobile money charge endpoint — the primary payment method for the Kenyan market this fork targets.
  - Webhook endpoint with **HMAC-SHA512 signature verification** to confirm payments server-to-server.
  - Idempotent verify-and-credit flow wrapped in a database transaction, so a payment can never be credited twice.
- **Multi-currency support** — admins can define currencies with exchange rates relative to a base currency (KES by default), each toggleable active/inactive.
- **Resource pricing** — configurable per-unit pricing for CPU, memory, disk, etc., independent of fixed plans.
- **Hosting plans** — admin-defined packages (name, price, billing period, memory/disk/CPU/database/backup/allocation limits) tied to a specific egg and nest, with featured/active flags and custom sort order.

### Self-Service Server Provisioning

- Users browse active plans from their account dashboard and purchase directly from their wallet balance.
- On purchase, a server is **automatically provisioned** — no admin step required. If provisioning fails for any reason, the wallet is never charged.
- Successful purchases are logged as `charge` transactions tied to the resulting server.

### Account Registration

- Public self-registration is enabled (`/auth/register`), gated behind reCAPTCHA, so new users can create their own accounts rather than requiring an admin to create them.

### Configurable Panel Theme

- Admins can re-theme the entire panel from **Admin → Settings → Theme**, without touching code or rebuilding assets.
- Six built-in presets (Default, Black & White, Green & Black, Crimson Night, Purple Haze, Sunset Orange) or fully custom colors via a color picker with a live preview.
- Under the hood, a single neutral color and a single accent color are expanded into a full 50–900 shade ramp using a fixed lightness curve, then injected as CSS custom properties at runtime — so the change applies instantly across the whole panel (sidebar, buttons, links, badges) for every logged-in user.

### Refreshed Auth Pages

- Login, Register, Forgot Password, Reset Password, and Login Checkpoint all share a redesigned, more compact layout — a small centered logo above the form instead of a large logo dominating half the card, and a sensible max-width instead of stretching across large screens.

## Tech Stack

- **Backend**: PHP 8.2+/8.3, Laravel
- **Frontend**: React, TypeScript, Tailwind CSS
- **Payments**: Paystack (card + M-Pesa mobile money)
- **Deployment**: Docker-isolated game servers via Wings, Nginx, Certbot, PM2 (or equivalent process manager) for the queue worker

## Documentation

Since the core panel is unmodified Pterodactyl, the official documentation still applies for anything not covered above:

- [Panel Documentation](https://pterodactyl.io/panel/1.0/getting_started.html)
- [Wings Documentation](https://pterodactyl.io/wings/1.0/installing.html)
- [Community Guides](https://pterodactyl.io/community/about.html)

## License

This project remains licensed under the [MIT License](./LICENSE.md), consistent with upstream Pterodactyl. Credit to [Dane Everitt](https://github.com/DaneEveritt), [Matthew Penner](https://github.com/matthewpi), and the Pterodactyl contributors for the original panel this fork is built on.

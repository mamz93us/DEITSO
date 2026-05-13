# IT Solutions Platform — Project Blueprint (Final, Web-First)

> **How to use this document.** Save as `PROJECT.md` at the repo root. It is the single source of truth for the web platform. The companion `AGENT.md` covers the Phase 2 Windows endpoint agent. To start coding, paste this file into a fresh Claude Code session and use the Sprint 0 prompt at the very end. Build sprint by sprint. Foundation first. Modules fast.

> **Recommended release strategy.** Ship an internal alpha after Sprint 6 (Foundation + Branding + Domains + Employees + ITAM + Licenses). Use it inside SamirGroup for at least three weeks before continuing. Requests, HR workflows, Ticketing, and Visits will be 10× better designed if you've been a user of your own product for a month before you build them. Do not skip this step.

---

## Table of Contents

1. [Mission](#1-mission)
2. [Tech Stack](#2-tech-stack)
3. [Architectural Rules](#3-architectural-rules)
4. [Multi-Tenancy & Host Routing](#4-multi-tenancy--host-routing)
5. [Roles & Permissions](#5-roles--permissions)
6. [Module Breakdown — Web v1 Scope](#6-module-breakdown--web-v1-scope)
7. [Key Data Models](#7-key-data-models)
8. [Workflow Specifications](#8-workflow-specifications)
9. [Domain & SSL Architecture](#9-domain--ssl-architecture)
10. [cPanel / WHM Module — Security Architecture](#10-cpanel--whm-module--security-architecture)
11. [Code Generation](#11-code-generation)
12. [Printable Labels & QR Codes](#12-printable-labels--qr-codes)
13. [End-User Portal](#13-end-user-portal)
14. [Folder Structure](#14-folder-structure)
15. [Sprint Plan — Full Web v1](#15-sprint-plan--full-web-v1)
16. [Phase 2 — Mobile, Agent, Commercial](#16-phase-2--mobile-agent-commercial)
17. [Coding Standards](#17-coding-standards)
18. [Definition of Done (per sprint)](#18-definition-of-done-per-sprint)
19. [Open Decisions](#19-open-decisions)
20. [Branding & Naming](#20-branding--naming)
21. [Sprint 0 Kickoff Prompt](#sprint-0-kickoff-prompt--paste-this-into-claude-code)

---

## 1. Mission

A multi-tenant **ITSM / MSP platform** for IT service companies in MENA. Web app is fully featured before any mobile work. Differentiators:

- Multi-organization with branches and per-organization RBAC
- **Full white-label per organization** — logo, colors, branded emails, branded PDF reports, and a domain per client (auto-provisioned subdomain via GoDaddy DNS API, or manually-mapped custom domain)
- **Automatic SSL** for every client domain via Caddy on-demand TLS
- Full IT asset lifecycle: supplier-tracked catalog, instances, transfers, scrap, costs, remote-access credentials, printable QR labels
- Suppliers per organization linked to assets and asset models
- Employee profiles paired 1:1 with system users; every employee can log in
- Assigned devices, accessories, and licenses per employee
- **Employee Requests** workflow (new asset / accessory / upgrade / license) with manager + admin approval and supplier-linked fulfillment
- **HR Onboarding & Offboarding workflows** — template-driven, multi-task processes that orchestrate Employees, ITAM, Licenses, cPanel email, and access
- Ticketing with end-user portal, two-sided comments, attachments, SLA tracking
- Field service: online + offline visits with GPS check-in (mobile browser), customer signature, time tracking, parts used
- Costing engine: rate cards, travel zones, visit charges, contracted hours
- **cPanel / WHM email management** with strict audit
- Bilingual Arabic / English with full RTL
- Clean API for a future Flutter mobile app and a future Windows endpoint agent (see `AGENT.md`)

---

## 2. Tech Stack

### Backend & Web Admin
- PHP 8.3, Laravel 11
- Filament 3 (panels for System Admin and Org; HR cluster within the Org panel)
- Livewire / Blade end-user portal at `/portal`
- MySQL 8 / MariaDB 10.6+
- Redis (cache, queue, sessions, locks)
- Laravel Horizon (queue dashboard)
- Laravel Reverb (real-time updates)
- Laravel Sanctum (future mobile API + agent API)

### Infrastructure
- **Caddy v2** with **on-demand TLS** for automatic Let's Encrypt certificates per client hostname
- **GoDaddy DNS API** for auto-provisioning client subdomains; abstracted behind a `DnsProvider` interface

### Required Packages
| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Per-org RBAC (`team_id = organization_id`) |
| `bezhansalleh/filament-shield` | Auto-bind permissions to Filament resources |
| `spatie/laravel-translatable` | Bilingual model fields |
| `spatie/laravel-activitylog` | Audit trail |
| `spatie/laravel-medialibrary` | File / image attachments |
| `spatie/laravel-query-builder` | API filtering |
| `spatie/laravel-data` | Typed DTOs |
| `spatie/laravel-model-states` | State machines (EmployeeRequest, HrProcessTask, Ticket) |
| `laravel/scout` + Meilisearch | Search across tickets, assets, employees |
| `gregoriohc/laravel-cpanel-whm` | WHM / cPanel client |
| `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` | Labels, reports, handover PDFs |
| `simplesoftwareio/simple-qrcode` | QR codes |
| `guzzlehttp/guzzle` | DNS provider HTTP clients |
| `pestphp/pest` | Testing |
| `laravel/pint` | Code style |

---

## 3. Architectural Rules

These are **non-negotiable**. Deviating breaks the design.

1. Every tenant-owned table has `organization_id`; operational tables also have `branch_id`. A `BelongsToOrganization` global scope filters by the active organization.
2. Roles are **per-organization** via Spatie's `team_id` = `organization_id`. A user can have different roles in different organizations.
3. Sensitive credentials (cPanel tokens, GoDaddy API secrets, remote-access passwords, third-party API keys) are encrypted at the model layer with `Crypt::encryptString()`. The application key is stored **outside** the database. Viewing decrypted credentials requires its own permission.
4. All write business logic lives in **Action classes** in `app/Actions/...`. Filament resources, controllers, console commands, and queue listeners all call the same Actions.
5. API versioning from day one (`/api/v1/...`). v1 is additive-only once mobile or the agent ships.
6. Translatable fields are JSON columns (`{"en": "...", "ar": "..."}`).
7. Soft deletes everywhere except join tables and immutable logs.
8. Activity log on by default for every model.
9. Money is `bigint` minor units + 3-char `currency` (default `EGP`). Never floats.
10. UTC in the database; convert at the app layer using the user / org timezone.
11. **Destructive third-party operations** (delete email account, scrap asset, remove subdomain) require explicit confirmation and are logged with full before/after state.
12. **Employees and Users are paired 1:1.** Every Employee has exactly one User; every Org-scoped User has exactly one Employee. System Admins are Users without an Employee.
13. **State-machine-driven workflows** use `spatie/laravel-model-states`. No manual `status = '...'` updates.
14. **HR workflow tasks are typed and self-executing.** A `create_email` task knows how to call the cPanel module; a `collect_asset` task knows how to release an asset assignment. Manual tasks exist only when explicitly typed `manual`.

---

## 4. Multi-Tenancy & Host Routing

Single database, row-level isolation by `organization_id`. Active org resolved per request in this priority:

1. **Custom domain match** — `Host` header matches a verified row in `organization_domains`.
2. **Platform subdomain match** — host is `{slug}.app.platform-domain.tld` (matched via `APP_PLATFORM_BASE_DOMAIN` config).
3. **Session / token claim** — fallback when users log in at the master domain and pick an org from a switcher.

System Admins (`users.is_system_admin = true`) bypass the org scope; their actions log `acting_as_org_id`.

---

## 5. Roles & Permissions

### Role Matrix

| Role | Scope | Notes |
|---|---|---|
| **System Admin** | Global | All orgs, system settings, DNS provider config, billing |
| **Org Admin** | Per org | Full control: branches, employees, all modules, branding, domain, cPanel servers, monitoring config (Phase 2) |
| **Branch Manager** | Per branch | Branch assets, tickets, employees, visits, requests, onboardings; sees costs |
| **Senior Technician** | Per org | Tickets, visits, asset transfer/scrap, remote/email credentials |
| **Technician** | Per org (assigned branches) | Tickets, visits, IT tasks in HR processes; sees remote IDs but not passwords |
| **HR / Asset Coordinator** | Per org | Primary user of HR cluster; employees, onboarding/offboarding, asset assignments |
| **Procurement Officer** | Per org | Suppliers, request fulfillment |
| **Accountant** | Per org | Read-only on costs, charges, contracts; financial exports |
| **Manager** (line manager) | Per org (their direct reports) | Approves their reports' requests, can initiate offboarding |
| **End User (Employee)** | Per org (own branch) | Tickets, requests, own assets, own monitoring data (Phase 2) |

### Permission Keys (Web v1)

Permission keys follow `module.resource.action`:

- `org.*` — branch, employee.invite, branding.edit, domain.manage
- `employee.profile.create | update | terminate`
- `itam.supplier.create | update | delete`
- `itam.asset_model.create | update | delete`
- `itam.asset.create | update | transfer | scrap | print_label`
- `itam.asset.view_remote_id` | `itam.asset.view_remote_credentials`
- `itam.license.create | assign | revoke`
- `requests.request.create | submit | cancel`
- `requests.request.approve_manager | approve_admin | reject`
- `requests.request.fulfill | view_all`
- `hr.template.create | update | delete`
- `hr.onboarding.initiate | complete | cancel`
- `hr.offboarding.initiate | complete | cancel`
- `hr.process.view_all | view_assigned`
- `hr.task.complete`
- `ticketing.ticket.create | assign | close | view_internal_notes`
- `ticketing.ticket.comment_internal | comment_public`
- `visits.visit.create | start | close | set_cost | checkin_offline`
- `costing.rate_card.manage | travel_zone.manage | contract.manage`
- `email.server.manage | view_credentials`
- `email.account.create | reset_password | suspend | delete`
- `system.dns_provider.manage | view_credentials`
- `reports.itam.view | ticketing.view | financial.view | audit.view`

### Permission Keys (Phase 2 — Agent)

Added when the Windows agent ships (see `AGENT.md`):

- `agent.device.enroll | view | suspend | retire`
- `agent.command.lock | reset_password | restart | shutdown | initiate_remote_session`
- `agent.command.registry_edit | run_script` (advanced, sensitive)
- `agent.inventory.view`
- `agent.policy.create | apply`
- `agent.monitoring.config` — enable/disable tiers, configure retention
- `agent.monitoring.view_aggregate` — team/department aggregates
- `agent.monitoring.view_individual` — drill into named employee
- `agent.monitoring.view_own` — employees viewing their own data (default for all)
- `agent.monitoring.investigate.open | view | close`
- `agent.monitoring.audit.view`

---

## 6. Module Breakdown — Web v1 Scope

### Foundation
1. Tenancy & Org Management — Organizations, Branches, Users, Memberships, Roles, Permissions, Activity Log
2. Branding — Per-org logo, colors, branded email layout, branded PDF header/footer
3. Domains — Auto-provisioned subdomain via GoDaddy API + custom client domain via CNAME; SSL via Caddy on-demand TLS

### Employee
4. Employees — 1:1 with Users; departments, positions, manager hierarchy, photo, assigned-assets summary

### ITAM
5. Suppliers — Per-org with contacts, terms, status; linked to Asset and AssetModel
6. Asset Models (catalog) — Reusable model defs with specs, image, preferred supplier
7. Asset Categories — Tree, translatable, `tracking_mode` (`serialized | bulk | license`)
8. Assets — Instances with auto-generated code, status lifecycle, supplier, cost
9. Asset Remote Access — TeamViewer / AnyDesk / RDP / VNC / SSH; encrypted credentials
10. Asset Assignments — History of holders (employee / branch / user)
11. Asset Transfers — Optional approval workflow
12. Asset Scrap — Evidence and approval
13. Asset Labels — Single label PDF + bulk A4 sheet
14. Licenses — Key (encrypted), seats, expiry, assignments
15. Maintenance & Warranty — Warranty, contracts, reminders

### Employee Requests
16. Request types: `new_asset`, `new_accessory`, `upgrade_existing`, `new_license`, `other`
17. Employee Requests with single-step or two-step approval (configurable per org)
18. Fulfillment from stock or via supplier purchase (creates Asset with traceable `source_request_id`)
19. Request comments (two-sided thread with attachments)

### HR Workflows (Onboarding / Offboarding)
20. Onboarding Templates — Per org, optionally scoped by department or position
21. Offboarding Templates — Same structure, reverse-direction tasks
22. Onboarding Processes — Instances initiated for new hires
23. Offboarding Processes — Instances initiated for departures
24. Checklist Tasks — Typed; self-executing where possible (assign_asset, create_email, assign_license, collect_asset, delete_email, disable_user, etc.)
25. HR Dashboard — Active processes, upcoming starts, upcoming exits, stuck tasks, KPIs
26. Handover PDFs — Generated on offboarding completion

### Ticketing
27. Ticket Categories — Translatable, tree
28. Tickets — Status, priority, SLA timers, source, links to asset / employee
29. Ticket Comments — Internal vs public; image and file attachments
30. SLA Policies — Response + resolution; business hours; breach events
31. End-User Portal — Branded login, My Tickets, My Requests, My Assets, Profile

### Visits & Costing
32. Visits — Online / offline, GPS check-in via mobile browser, signature pad, time tracking, parts used
33. Rate Cards — Hourly by technician seniority / visit type
34. Travel Zones — Named zones with flat fees
35. Visit Charges — Computed on visit close
36. Contracts & Contracted Hours — Monthly included hours, ledger, renewal

### cPanel / WHM Email Management
37. Mail Servers — Per org, encrypted WHM URL and API token, connection test
38. Email Domains — Domains hosted on each mail server, per org
39. Email Accounts — Create, reset, change quota, suspend, unsuspend, delete; one-time password reveal
40. Email Audit — Immutable audit log

### Reports
41. Asset reports — Inventory, valuation, by status / branch / category / age, transfers, scrapped, license expiry, **spend by supplier**
42. Supplier reports — Spend per supplier, items per supplier, supplier directory
43. Request reports — Volume by type / status / requester, approval cycle times, fulfillment rate
44. HR reports — Onboarding cycle time, offboarding completeness, IT-asset-recovery rate, average time-per-task-type
45. Ticketing reports — Backlog, by status / priority / agent, SLA breach, time-to-resolve
46. Visit & Cost reports — Revenue, contract utilization, by technician / org / period
47. Employee asset summary PDF
48. Audit reports — Activity log filtered by user / module / action / date

---

## 7. Key Data Models

See the full blueprint in the project plan for complete schemas. Models span:

- Identity & Tenancy: User, Organization, Branch, organization_user pivot
- Branding & Domain: OrganizationBranding, OrganizationDomain, DnsProviderAccount
- Employee: Department, Employee (1:1 User)
- ITAM: Supplier, SupplierContact, AssetCategory, AssetModel, Asset, AssetRemoteAccess, AssetAssignment, AssetTransfer, AssetScrap, License
- Requests: EmployeeRequest, RequestComment
- HR: HrWorkflowTemplate, HrWorkflowTemplateTask, HrProcess, HrProcessTask
- Ticketing: TicketCategory, Ticket, TicketComment, SlaPolicy
- Visits & Costing: Visit, VisitPart, RateCard, TravelZone, Contract, ContractedHoursLedger
- Email: MailServer, EmailDomain, EmailAccount, EmailAction

All tenant-scoped tables include `organization_id` and use ULID primary keys. Translatable fields are JSON. Money is `bigint` minor units. Soft deletes default-on except for join tables and immutable logs.

---

## 8. Workflow Specifications

Detailed flows for: Employee Lifecycle, Asset Code Generation, Employee Request approval state machine, HR Onboarding/Offboarding execution, Visit GPS check-in + charge computation, Ticket SLA timer behavior. See full blueprint.

---

## 9. Domain & SSL Architecture

Two paths share one SSL pipeline:

**Path A — Platform Subdomain (auto-provisioned)** — system admin creates org → `ProvisionOrganizationSubdomainJob` calls GoDaddy DNS API → polls DNS → hits Caddy allow-list endpoint → Let's Encrypt on first HTTPS request.

**Path B — Custom Client Domain (manual CNAME)** — org admin enters hostname → system shows required CNAME + TXT records → client adds records → hourly `VerifyCustomDomainJob` until verified → Caddy provisions cert on first hit.

`DnsProviderInterface` abstracts GoDaddy, Cloudflare, Route53. Caddy `Caddyfile` uses on-demand TLS with `ask` callback to allow-list endpoint that returns 200 only for verified domains.

---

## 10. cPanel / WHM Module — Security Architecture

- No mailbox passwords stored; show-once on reset.
- API tokens encrypted at rest with `Crypt::encryptString`; app key outside DB.
- All operations behind explicit permissions.
- Rate limiting on destructive operations (5 deletes/hour/user, 20 password resets/hour/user).
- Connection verification before batch operations.
- No bulk delete in v1.

---

## 11. Code Generation

Per-resource templates per-org (e.g. `SMR-CAI-LAP-0001` for asset, `TCK-2026-00001` for ticket). Shared `OrganizationScopedCodeGenerator` Redis-locked service for collision-free sequences.

---

## 12. Printable Labels & QR Codes

`GenerateAssetLabelPdf` action produces single sticker (50×30 mm) or A4 sheet (Avery layout). QR encodes `https://{org-host}/scan/{asset_ulid}` → auth-gated asset detail.

---

## 13. End-User Portal

Livewire/Blade at `/portal`. Branded per-org from host. Pages: Login, Dashboard, My Tickets, My Requests, My Assets, My Onboarding/Offboarding (when active), Profile. Public comments only on tickets.

---

## 14. Folder Structure

See full blueprint for the canonical `app/Actions/{Module}/`, `app/Filament/{System,App}/`, `app/Services/{DnsProvider,Cpanel,Sla}/`, `app/Models/{Concerns,States}/`, plus `routes/{web,portal,api,internal}.php`, `caddy/Caddyfile.example`, and `tests/`.

---

## 15. Sprint Plan — Full Web v1

19–20 weeks of focused work. Sprints 0–6 deliver foundation + ITAM through internal alpha cut. Then ship internal alpha to SamirGroup, dogfood for 3 weeks. Sprints 7–15 deliver Requests, Tickets, Portal, Visits, Costing, cPanel, custom domains, HR workflows, reports + hardening.

| Sprint | Scope | Duration |
|---|---|---|
| 0 | Bootstrap | 3–5 days |
| 1 | Tenancy, users, branding, domains, DNS provider | ~2 weeks |
| 2 | Employees | ~1.5 weeks |
| 3 | ITAM foundation + suppliers | ~2 weeks |
| 4 | Assignments, transfer, scrap, remote access | ~1.5 weeks |
| 5 | Labels & ITAM reports | ~1 week |
| 6 | Licenses + **Internal Alpha Cut** | ~5 days |
| 7 | Employee Requests | ~2 weeks |
| 8 | Ticketing foundation | ~1.5 weeks |
| 9 | End-user portal + tickets | ~1.5 weeks |
| 10 | Visits foundation | ~2 weeks |
| 11 | Costing | ~1.5 weeks |
| 12 | cPanel/WHM email module | ~2 weeks |
| 13 | Custom domains polish + notifications | ~1 week |
| 14 | HR onboarding & offboarding | ~2 weeks |
| 15 | Reports, polish, hardening | ~1.5 weeks |

---

## 16. Phase 2 — Mobile, Agent, Commercial

After Web v1: Flutter mobile (technician + end-user), Windows endpoint agent (see `AGENT.md`), full Purchase Order module, service contracts deepening, invoicing, customer self-serve signup, additional DNS providers (Cloudflare, Route53).

---

## 17. Coding Standards

- PSR-12 via Pint pre-commit
- Model relations explicitly typed
- Actions are invokable single-purpose classes
- Form requests for all validation
- PHP 8.1 backed enums in `app/Enums/`
- Pest feature tests for every Action and every API endpoint
- Migrations append-only after first push
- No business logic in Filament resources — they call Actions
- No raw queries against tenant tables bypassing the global scope without justifying comment
- State transitions via `spatie/laravel-model-states` only

---

## 18. Definition of Done (per sprint)

1. All deliverables implemented and code-reviewed
2. Pest tests pass and cover Actions and critical paths
3. Pint passes
4. Filament screens work in both `ar` and `en` with correct RTL
5. Permission matrix verified
6. Activity log captures every state-changing operation
7. Short demo recording attached to sprint summary

---

## 19. Open Decisions

- HR template scoping — both department and position; department wins
- Offboarding access cutoff — at `disable_user` task (default)
- Customer signature on visit close — required or optional? TBD
- Travel cost model — flat-fee zones in v1
- WhatsApp transactional templates — which events trigger? TBD
- Email password reset delivery — show-once + alternate email
- Bulk cPanel operations — deferred from v1
- Request approval auto-skip manager step if no manager set — fallback to admin-only

---

## 20. Branding & Naming

Pick the product name early. Reserve domain, App Store / Play Store names, social handles. The name leaks into emails, notifications, demo seed quickly and is painful to change later.

---

## Sprint 0 Kickoff Prompt — Paste This Into Claude Code

> You are coding inside an empty Laravel project directory. Read PROJECT.md (above this message) carefully. We are starting **Sprint 0 only — do not start any module work yet**.
>
> Do the following:
>
> 1. Bootstrap a fresh Laravel 11 project. Configure `.env` for MySQL, Redis, queue=redis, cache=redis, session=redis. Default app timezone `Africa/Cairo`, default locale `en`, fallback `ar` (configurable).
>
> 2. Install and configure: Filament 3, Laravel Sanctum, Laravel Horizon, Laravel Reverb, Laravel Scout (Meilisearch driver — config only). Install all packages listed in PROJECT.md Section 2, including `spatie/laravel-model-states` and `guzzlehttp/guzzle`. Publish their configs.
>
> 3. Configure Filament for `ar` and `en` with full RTL switching. Default currency `EGP`.
>
> 4. Create `app/Models/BaseModel.php` with traits `HasUlids`, `SoftDeletes`, `LogsActivity`, and conditional `HasTranslations` via a property flag.
>
> 5. Create `app/Models/Concerns/BelongsToOrganization.php`. Must: add `organization()` BelongsTo; register global scope filtering by active org from `current.organization` singleton; auto-fill `organization_id` on create; cleanly bypass scope for system context.
>
> 6. Create middleware:
>    - `ResolveOrganizationFromHost` — inspect `Host` header, match against `organization_domains` (stub query, return null gracefully if table not yet present), parse wildcard `{slug}.app.{base_domain}` using config `app.platform_base_domain`. Bind `current.organization` singleton.
>    - `SetActiveOrganization` — fallback at master domain.
>    - `ApplyOrganizationBranding` — load `OrganizationBranding` (stub) and share with views.
>    - `IdempotencyKey` — for API: `Idempotency-Key` header, 24-hour cache.
>
> 7. Create `app/Services/DnsProvider/DnsProviderInterface.php` with signatures: `createSubdomain`, `recordExists`, `resolves`, `removeSubdomain`. Implementation comes in Sprint 1.
>
> 8. Set up Pint with `composer pint` script. Set up Pest with a feature test that `/` returns 200.
>
> 9. Set up GitHub Actions running `composer pint --test` and `php artisan test` on push.
>
> 10. Write Pest tests for `BelongsToOrganization` auto-fill, `IdempotencyKey` middleware, `ResolveOrganizationFromHost` wildcard parsing.
>
> 11. Add `caddy/Caddyfile.example` with on-demand TLS pointing at `/internal/domains/allow`.
>
> 12. **Do not create any business models** yet. Those start in Sprint 1.

*End of blueprint. Foundation first. Modules fast.*

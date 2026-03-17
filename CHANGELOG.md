# Changelog

## 2026-03-16

- Reworked the new `Dialer` experience around professional outbound patterns inspired by vendor guidance for guided workspaces, campaign studios, compliance governance, and disposition handling, splitting the UI into smaller operational panels with clearer flows and inline tooltips.
- Hardened dialer campaign persistence by moving create/update normalization into the service layer, validating queue handoff and compliance profile compatibility, and returning actionable `422` responses instead of generic `500` errors when campaign payloads are invalid.
- Added reusable custom dialer compliance profiles with CRUD flows, campaign assignment, and Anatel-aligned Brazilian baseline scheduling so teams can keep UF defaults while creating operation-specific governance profiles.
- Reworked the `Contact Center` landing page into a more professional wallboard with stronger KPI coverage, queue coverage-gap visibility, supervision tools, and safer configuration flows that now use backend-provided routes instead of brittle hardcoded endpoints.
- Fixed frontend compatibility issues introduced by the redesign, including a missing Vite alias for shared page components and tab-state mismatches between the new `Contact Center` UI and the backend initial-tab values.
- Expanded PT-BR coverage for the redesigned dialer and contact center surfaces, especially for operational labels, guidance text, compliance wording, wallboard metrics, and inline validation feedback.
- Added bilingual application support for `EN` and `PT-BR`, including a language switcher, Laravel locale middleware, shared Inertia locale data, and PT-BR translation files.
- Added bundled `Call Center` and `Dialer` modules with routes, controllers, services, Vue pages, permissions, menu integration, dashboard shortcuts, scheduler support, and dialer migrations.
- Added dialer modes for `manual`, `preview`, `progressive`, and `power/predictive`.
- Expanded the dialer with retry policy controls, dispositions, do-not-call management, AMD-ready fields, queued lead imports, webhook delivery jobs, and API endpoints for campaigns and leads.
- Replaced generic US state dialing defaults with Brazilian `UF` dialing rules, timezone mapping, Anatel-aligned national baseline windows, and a synchronization migration for existing installs.
- Expanded the call center with wallboard metrics, callback tracking, pause reasons, agent pause/resume flows, monitoring session orchestration, queue event storage, and API endpoints for queues, agents, and wallboard data.
- Redesigned the `Dialer` workspace into clearer operational areas for live dialing, campaign building, contact intake, UF compliance, and disposition management, with a more polished visual style.
- Refined the `Call Center` landing experience with a stronger operational hero, clearer quick navigation, and more natural PT-BR terminology for wallboard and callback workflows.
- Kept the module naming aligned with operation language by preserving `Call Center` in PT-BR instead of translating it to `Central de Atendimento`, while still normalizing other production-facing labels such as `Wakeup Calls` -> `Despertador` and `Callbacks` -> `Retornos`.
- Reworked the `Dialer` again into clearer launch, workspace, compliance, and campaign-design lanes inspired by professional outbound suites, including stronger launch checklists, better routing sections, and more explicit pacing/compliance guidance.
- Reworked the `Call Center` again around floor-control concepts used by wallboard-oriented platforms, adding clearer floor-navigation cards, live conversation surfacing, and stronger supervision metrics.
- Changed the installer to fetch and install the PT-BR `karina` FreeSWITCH prompt set by default, while rewriting FreeSWITCH sound defaults during provisioning and initial seeding.
- Hardened the PT-BR FreeSWITCH sounds installer to try both `pt-BR` and `pt-br` archive variants, require only the base `8000` prompt set for success, treat higher sample rates as optional, and align runtime prompt lookups with the detected on-disk dialect casing.
- Changed Brazilian installation defaults to `pt-br` and `America/Sao_Paulo` for the initial superadmin, new-user defaults, scheduled-job timezone defaults, and FreeSWITCH sound fallbacks used by greetings and prompt selection.
- Added FreeSWITCH webhook synchronization for dialer attempt updates and call center queue events.
- Added unit coverage for state-based dialer compliance scheduling.
- Updated repository and installation references to `eduardozfr/fspbx`.
- Hardened the installer for the public GitHub repository by cloning `eduardozfr/fspbx` on the `main` branch, reusing existing checkouts safely, creating Laravel cache/storage directories before Composer, and retrying Composer installs with root-safe settings.
- Fixed installation bootstrap issues around `bootstrap/cache`, `storage/framework/views`, `storage/app/public`, and asset publication to prevent `package:discover` and `storage:link` failures on clean servers.
- Hardened frontend builds during install and update by recreating `modules_statuses.json` automatically when missing, preserving enabled module state, and raising the Node heap limit for `vite build` to avoid out-of-memory failures on small servers.
- Added a low-memory `Vite` build mode for installation and update routines, disabled expensive minification in that path, silenced repeated Sass deprecation noise, and enabled temporary swap creation in the installer for small servers that would otherwise hit `npm run build` exit `137`.
- Added adaptive Node heap sizing based on server RAM, an automatic ultra-light frontend build retry for `npm run build` failures caused by memory pressure, and extra Rollup reductions for clean installs on low-memory VPS hosts.
- Split production frontend compilation into smaller `classic` and `inertia` Vite passes with manifest merging, removed Blade layouts from the heavy `Echo/Reverb` bundle by introducing a lightweight `classic.js`, and folded legacy toast assets back into the classic theme bundle to reduce clean-install memory pressure.
- Moved bundled `Ace Editor` modes, themes, workers, and extensions out of the Vite graph into static assets under `public/vendor/ace`, reducing provisioning-form build pressure on small servers.
- Fixed module frontend imports for `Call Center` and `Dialer` by switching shared locale access to a Vite alias instead of brittle deep relative paths, preventing clean-install build failures.
- Replaced project documentation URLs with repository-aligned references and GitHub Pages-compatible documentation URLs.
- Added English and PT-BR documentation companions for the main README and onboarding guides.


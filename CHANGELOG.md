# Changelog

## 2026-03-16

- Added bilingual application support for `EN` and `PT-BR`, including a language switcher, Laravel locale middleware, shared Inertia locale data, and PT-BR translation files.
- Added bundled `Call Center` and `Dialer` modules with routes, controllers, services, Vue pages, permissions, menu integration, dashboard shortcuts, scheduler support, and dialer migrations.
- Added dialer modes for `manual`, `preview`, `progressive`, and `power/predictive`.
- Expanded the dialer with retry policy controls, dispositions, do-not-call management, AMD-ready fields, queued lead imports, webhook delivery jobs, and API endpoints for campaigns and leads.
- Added per-state dialing compliance rules with editable schedules and conservative default windows for all US states plus stricter overrides where configured.
- Expanded the call center with wallboard metrics, callback tracking, pause reasons, agent pause/resume flows, monitoring session orchestration, queue event storage, and API endpoints for queues, agents, and wallboard data.
- Exposed the new call center and dialer operational controls in the Vue UI, including state rule editing, queue-to-agent assignment, user provisioning, richer callback ownership, and advanced campaign compliance settings.
- Added FreeSWITCH webhook synchronization for dialer attempt updates and call center queue events.
- Added unit coverage for state-based dialer compliance scheduling.
- Updated repository and installation references to `eduardozfr/fspbx`.
- Hardened the installer for the public GitHub repository by cloning `eduardozfr/fspbx` on the `main` branch, reusing existing checkouts safely, creating Laravel cache/storage directories before Composer, and retrying Composer installs with root-safe settings.
- Fixed installation bootstrap issues around `bootstrap/cache`, `storage/framework/views`, `storage/app/public`, and asset publication to prevent `package:discover` and `storage:link` failures on clean servers.
- Hardened frontend builds during install and update by recreating `modules_statuses.json` automatically when missing, preserving enabled module state, and raising the Node heap limit for `vite build` to avoid out-of-memory failures on small servers.
- Replaced project documentation URLs with repository-aligned references and GitHub Pages-compatible documentation URLs.
- Added English and PT-BR documentation companions for the main README and onboarding guides.


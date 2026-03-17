# Changelog

## 2026-03-16

- Added bilingual application support for `EN` and `PT-BR`, including a language switcher, Laravel locale middleware, shared Inertia locale data, and PT-BR translation files.
- Added bundled `Call Center` and `Dialer` modules with routes, controllers, services, Vue pages, permissions, menu integration, dashboard shortcuts, scheduler support, and dialer migrations.
- Added dialer modes for `manual`, `preview`, `progressive`, and `power/predictive`.
- Expanded the dialer with retry policy controls, dispositions, do-not-call management, AMD-ready fields, queued lead imports, webhook delivery jobs, and API endpoints for campaigns and leads.
- Replaced generic US state dialing defaults with Brazilian `UF` dialing rules, timezone mapping, Anatel-aligned national baseline windows, and a synchronization migration for existing installs.
- Expanded the call center with wallboard metrics, callback tracking, pause reasons, agent pause/resume flows, monitoring session orchestration, queue event storage, and API endpoints for queues, agents, and wallboard data.
- Redesigned the `Dialer` workspace into clearer operational areas for live dialing, campaign building, contact intake, UF compliance, and disposition management, with a more polished visual style.
- Refined the `Call Center` landing experience with a stronger operational hero, clearer quick navigation, and more natural PT-BR terminology for wallboard and callback workflows.
- Normalized several PT-BR terms that were confusing in production, including `Wakeup Calls` -> `Despertador`, `Callbacks` -> `Retornos`, and `Call Center` -> `Central de Atendimento`.
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


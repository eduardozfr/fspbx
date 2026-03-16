# FS PBX

[Leia em portugues (Brasil)](README.pt-BR.md)

## Overview

FS PBX is a Laravel + Vue.js management portal for FreeSWITCH environments. This repository is maintained at `eduardozfr/fspbx` and keeps the original project structure while extending it with:

- bilingual UI support (`EN` and `PT-BR`) with English fallback
- bundled `Call Center` and `Dialer` modules
- updated installation and documentation references for this repository

## Highlights

- Laravel backend with Inertia + Vue 3 frontend
- FreeSWITCH integration through the existing project services
- Call Center operations for queue and agent management
- Outbound Dialer with `manual`, `preview`, `progressive`, and `power/predictive` modes
- PT-BR localization for Laravel auth/validation messages and core frontend navigation

## Requirements

- Debian 12 or 13
- 4 GB RAM minimum
- 30 GB of storage minimum

## Installation

1. Run the installer from this repository:

```bash
wget -O- https://raw.githubusercontent.com/eduardozfr/fspbx/main/install/install-fspbx.sh | bash
```

2. After installation, access the PBX from your domain or server hostname.

3. Run database migrations after updates when needed:

```bash
cd /var/www/fspbx
php artisan migrate
php artisan app:update
```

## Localization

- The application now supports `English` and `Portugues (Brasil)`.
- Users can switch the language from the login screen and the main navigation.
- Laravel keeps English as the fallback locale.
- Default timezone and regional defaults are aligned with `America/Sao_Paulo` and `BRL`.

## Included Modules

### Call Center

- queue overview with live agent status hints
- queue and agent CRUD management
- supervisor and agent provisioning hooks from extensions

### Dialer

- `Manual`: the agent chooses when to place the call
- `Preview`: the next contact is shown before the call is launched
- `Progressive`: automatically dials when the queue has available agents
- `Power/Predictive`: dials multiple leads based on pacing ratio for higher volume

## Documentation

- English docs workspace: [documentation/README.md](documentation/README.md)
- PT-BR docs workspace: [documentation/README.pt-BR.md](documentation/README.pt-BR.md)
- PT-BR project overview: [README.pt-BR.md](README.pt-BR.md)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the customization summary in this repository.

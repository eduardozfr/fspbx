---
id: installation
title: Installation
slug: /installation
sidebar_position: 2
---

# Installation

Getting started with FS PBX is straightforward.

## Requirements

- Debian 12 or 13
- 4 GB RAM minimum
- 30 GB storage minimum

## Install

1. Download and run the installer from this repository:

```bash
wget -O- https://raw.githubusercontent.com/eduardozfr/fspbx/main/install/install-fspbx.sh | bash
```

2. Access the server using your domain or hostname after the installer completes.

3. Apply pending migrations if required:

```bash
php artisan migrate
```

## Notes

- The project now includes `Call Center` and `Dialer` modules by default.
- The UI supports `EN` and `PT-BR`.
- For a Portuguese version of this guide, see `02-installation.pt-BR.md`.

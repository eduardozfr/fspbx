---
id: installation-pt-br
title: Instalacao
slug: /pt-br/installation
sidebar_position: 2
---

# Instalacao

Comecar a usar o FS PBX e simples.

## Requisitos

- Debian 12 ou 13
- minimo de 4 GB de RAM
- minimo de 30 GB de armazenamento

## Instalar

1. Baixe e execute o instalador deste repositorio:

```bash
wget -O- https://raw.githubusercontent.com/eduardozfr/fspbx-main/main/install/install-fspbx.sh | bash
```

2. Acesse o servidor usando o dominio ou hostname apos a conclusao do instalador.

3. Execute migrations pendentes quando necessario:

```bash
php artisan migrate
```

## Observacoes

- O projeto agora inclui os modulos `Call Center` e `Discador` por padrao.
- A interface suporta `EN` e `PT-BR`.
- A versao em ingles deste guia continua disponivel em `02-installation.md`.

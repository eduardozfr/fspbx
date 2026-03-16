# FS PBX

[Read in English](README.md)

## Visao geral

O FS PBX e um portal de gerenciamento para ambientes FreeSWITCH construido com Laravel + Vue.js. Este repositorio passa a ser mantido em `eduardozfr/fspbx-main` e preserva a estrutura original do projeto, adicionando:

- interface bilingue (`EN` e `PT-BR`) com fallback para ingles
- modulos `Call Center` e `Discador` integrados ao projeto
- referencias de instalacao e documentacao atualizadas para este repositorio

## Destaques

- backend em Laravel com frontend Inertia + Vue 3
- integracao com FreeSWITCH usando os servicos existentes do projeto
- modulo de `Call Center` para filas, agentes e supervisao
- modulo `Discador` com modos `manual`, `preview`, `progressivo` e `power/preditivo`
- localizacao PT-BR para autenticacao, validacao e navegacao principal

## Requisitos

- Debian 12 ou 13
- minimo de 4 GB de RAM
- minimo de 30 GB de armazenamento

## Instalacao

1. Execute o instalador deste repositorio:

```bash
wget -O- https://raw.githubusercontent.com/eduardozfr/fspbx-main/main/install/install-fspbx.sh | bash
```

2. Acesse o PBX pelo dominio ou hostname configurado.

3. Sempre que houver atualizacoes, execute as migrations quando necessario:

```bash
cd /var/www/fspbx
php artisan migrate
php artisan app:update
```

## Idiomas e regionalizacao

- O sistema agora suporta `English` e `Portugues (Brasil)`.
- O usuario pode trocar o idioma pela tela de login e pelo menu principal.
- O Laravel continua usando ingles como idioma de fallback.
- O projeto foi alinhado com `America/Sao_Paulo` e `BRL` como padroes regionais.

## Modulos incluidos

### Call Center

- visao geral das filas com indicacao de status dos agentes
- CRUD de filas e agentes
- provisionamento de supervisor e agente a partir dos ramais

### Discador

- `Manual`: o agente escolhe quando disparar a chamada
- `Preview`: exibe o proximo contato antes da chamada
- `Progressivo`: disca automaticamente quando ha agentes disponiveis
- `Power/Preditivo`: disca varios leads de acordo com o fator de discagem

## Documentacao

- workspace de documentacao em ingles: [documentation/README.md](documentation/README.md)
- workspace de documentacao em PT-BR: [documentation/README.pt-BR.md](documentation/README.pt-BR.md)
- changelog das customizacoes: [CHANGELOG.md](CHANGELOG.md)

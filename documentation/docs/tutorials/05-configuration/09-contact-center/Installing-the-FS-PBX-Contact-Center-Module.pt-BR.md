---
id: contact-center-module-pt-br
title: Configurando o Call Center e o Discador do FS PBX
slug: /pt-br/configuration/contact-center/installing-contact-center-module/
sidebar_position: 1
---

# Configurando o Call Center e o Discador do FS PBX

Os modulos `Call Center` e `Discador` agora fazem parte deste repositorio e nao exigem download separado.

## Etapa 1: Atualize a aplicacao

```bash
git pull
php artisan migrate
php artisan app:update
```

## Etapa 2: Revise grupos e permissoes

Garanta que os grupos e permissoes abaixo existam no ambiente:

- `Contact Center Supervisor`
- `Contact Center Agent`
- `Dialer Manager`

## Etapa 3: Prepare usuarios do Call Center

Na tela de ramais, use as acoes do Call Center para provisionar:

- um usuario agente
- um usuario supervisor

## Etapa 4: Crie filas e agentes

Abra `/contact-center` e configure:

- filas de atendimento
- estrategias de fila
- associacao entre filas e agentes
- regras de timeout, pos-atendimento e atraso de rejeicao

## Etapa 5: Crie campanhas de saida

Abra `/dialer` e configure campanhas com um dos modos suportados:

- `Manual`
- `Preview`
- `Progressivo`
- `Power/Preditivo`

## Etapa 6: Adicione leads e execute a discagem

- adicione leads a uma ou mais campanhas
- use os modos manual ou preview para abordagens assistidas por agentes
- use os modos progressivo ou power para despacho automatico via filas

## Etapa 7: Ative o processamento automatico

O repositorio registra o comando `dialer:run` no scheduler. Garanta que o cron do Laravel esteja ativo para que campanhas progressivas e power sejam processadas automaticamente.

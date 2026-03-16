---
id: contact-center-module
title: Configuring the FS PBX Call Center and Dialer
slug: /configuration/contact-center/installing-contact-center-module/
sidebar_position: 1
---

# Configuring the FS PBX Call Center and Dialer

The `Call Center` and `Dialer` modules are now bundled with this repository and do not require a separate download step.

## Step 1: Update the application

```bash
git pull
php artisan migrate
php artisan app:update
```

## Step 2: Seed or review permissions

Make sure the required groups and permissions exist in your environment:

- `Contact Center Supervisor`
- `Contact Center Agent`
- `Dialer Manager`

## Step 3: Prepare Call Center users

Go to the extensions screen and use the Call Center actions to provision:

- an agent user
- a supervisor user

## Step 4: Create queues and agents

Open `/contact-center` and configure:

- call center queues
- queue strategies
- queue-to-agent assignments
- agent timeout, wrap-up, and reject delay rules

## Step 5: Create outbound campaigns

Open `/dialer` and configure campaigns using one of the supported modes:

- `Manual`
- `Preview`
- `Progressive`
- `Power/Predictive`

## Step 6: Attach leads and run dialing

- add leads to one or more campaigns
- use manual or preview dialing for agent-assisted outreach
- use progressive or power modes for automated queue-driven dispatch

## Step 7: Schedule automatic processing

The repository registers the `dialer:run` Artisan command in the scheduler. Make sure your Laravel cron entry is active so progressive and power campaigns are processed automatically.

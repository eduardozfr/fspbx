<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),380px]">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ editingCampaignUuid ? t('Update campaign') : t('Campaign studio') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Design campaigns with a clearer flow for dialing mode, answered-call routing, compliance, AMD behavior, and outcome mapping.') }}</p>
                </div>
                <button v-if="editingCampaignUuid" type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="resetCampaignForm">{{ t('Cancel editing') }}</button>
            </div>

            <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                {{ message.text }}
            </div>

            <form class="mt-6 space-y-6" @submit.prevent="submitCampaign">
                <section class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ t('Identity and ownership') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ t('Start with the operational identity so supervisors know what this campaign does and when it should be active.') }}</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Campaign name') }}</span>
                                <HelpTooltip :text="t('Use an operational name that already tells supervisors what the list is and who owns it.')"/>
                            </label>
                            <input v-model="campaignForm.name" type="text" class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Example: Retention | Fiber | SP capital | Mar 2026')" />
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Campaign status') }}</span>
                                <HelpTooltip :text="t('Draft keeps the campaign editable, active allows dialing, paused freezes execution, and completed closes the operation.')"/>
                            </label>
                            <select v-model="campaignForm.status" class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option v-for="status in options.statuses || []" :key="status.value" :value="status.value">{{ t(status.label) }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Dialing mode') }}</span>
                                <HelpTooltip :text="t('Manual and preview are agent-assisted. Progressive and power require a staffed queue for answered calls.')"/>
                            </label>
                            <select v-model="campaignForm.mode" class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option v-for="mode in options.modes || []" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option>
                            </select>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Description') }}</label>
                            <textarea v-model="campaignForm.description" rows="3" class="w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Add campaign scope, owner, list source, or launch notes for the floor team.')" />
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 xl:grid-cols-4">
                        <article
                            v-for="profile in modeProfiles"
                            :key="profile.value"
                            class="rounded-3xl border p-4 transition"
                            :class="campaignForm.mode === profile.value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-900'"
                        >
                            <div class="text-[11px] uppercase tracking-[0.22em]" :class="campaignForm.mode === profile.value ? 'text-slate-300' : 'text-slate-400'">{{ t(profile.kicker) }}</div>
                            <div class="mt-3 text-lg font-semibold">{{ t(profile.title) }}</div>
                            <p class="mt-2 text-sm leading-6" :class="campaignForm.mode === profile.value ? 'text-slate-200' : 'text-slate-500'">{{ t(profile.description) }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5">
                    <h3 class="text-lg font-semibold text-slate-900">{{ t('Call routing and destination flow') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Define how the dialer presents the call, where answered conversations land, and what happens after no-answer, voicemail, or callback scenarios.') }}</p>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Queue handoff') }}</span>
                                <HelpTooltip :text="t('Required for progressive and power. Live answers from automated campaigns are transferred to this queue.')"/>
                            </label>
                            <select v-model="campaignForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('No queue handoff configured') }}</option>
                                <option v-for="queue in options.queues || []" :key="queue.value" :value="queue.value">{{ queue.label }}</option>
                            </select>
                            <p class="text-xs leading-5 text-slate-500">
                                {{ requiresQueueHandoff
                                    ? t('Progressive and power depend on this queue to receive live answers.')
                                    : t('Manual and preview keep the live answer with the selected agent extension. Queue handoff is only used by progressive and power campaigns.') }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Outbound prefix') }}</span>
                                <HelpTooltip :text="t('Use a carrier or route prefix when this campaign must force a specific outbound path.')"/>
                            </label>
                            <input v-model="campaignForm.outbound_prefix" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Optional route prefix for carrier selection')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Caller ID name') }}</label>
                            <input v-model="campaignForm.caller_id_name" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Caller ID name')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Caller ID number') }}</label>
                            <input v-model="campaignForm.caller_id_number" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Caller ID number')" />
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 xl:grid-cols-4">
                        <article v-for="card in callFlowCards" :key="card.label" class="rounded-3xl border p-4" :class="card.tone">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t(card.label) }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ t(card.value) }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t(card.help) }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5">
                    <h3 class="text-lg font-semibold text-slate-900">{{ t('Compliance and pacing') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Set the legal baseline, retry policy, preview time, and pacing guardrails before scaling the campaign.') }}</p>
                    <div class="mt-5 grid gap-3 xl:grid-cols-4">
                        <article v-for="card in modeControlCards" :key="card.label" class="rounded-3xl border p-4" :class="card.tone">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t(card.label) }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ t(card.value) }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t(card.help) }}</p>
                        </article>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Compliance profile') }}</span>
                                <HelpTooltip :text="t('Apply a named governance profile when the operation needs a schedule different from the default UF baseline.')"/>
                            </label>
                            <select v-model="campaignForm.dialer_compliance_profile_uuid" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('National baseline') }}</option>
                                <option v-for="profile in complianceProfiles" :key="profile.uuid" :value="profile.uuid">{{ profile.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Default state') }}</label>
                            <select v-model="campaignForm.default_state_code" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" @change="syncCampaignTimezone">
                                <option value="">{{ t('No state') }}</option>
                                <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Default timezone') }}</label>
                            <input v-model="campaignForm.default_timezone" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Default timezone')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Maximum attempts') }}</label>
                            <input v-model.number="campaignForm.max_attempts" type="number" min="1" max="25" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Maximum attempts')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Daily retry limit') }}</label>
                            <input v-model.number="campaignForm.daily_retry_limit" type="number" min="1" max="25" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Daily retry limit')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Retry backoff (minutes)') }}</label>
                            <input v-model.number="campaignForm.retry_backoff_minutes" type="number" min="1" max="43200" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Retry backoff (minutes)')" />
                        </div>
                        <div v-if="usesPreviewWindow" class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Preview seconds') }}</label>
                            <input v-model.number="campaignForm.preview_seconds" type="number" min="5" max="3600" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Preview seconds')" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Originate timeout') }}</label>
                            <input v-model.number="campaignForm.originate_timeout" type="number" min="5" max="120" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Originate timeout')" />
                        </div>
                        <div v-if="usesPacingRatio" class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Pacing ratio') }}</label>
                            <input v-model.number="campaignForm.pacing_ratio" type="number" min="1" max="10" step="0.1" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Pacing ratio')" />
                        </div>
                        <div v-if="isAutomatedMode" class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Max inflight calls') }}</span>
                                <HelpTooltip :text="t('Caps how many outbound calls from this campaign may stay in progress at the same time, even when pacing suggests more volume.')"/>
                            </label>
                            <input v-model.number="campaignForm.max_inflight_calls" type="number" min="1" max="1000" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Leave blank to follow pacing')" />
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.respect_dnc" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Respect do-not-call list') }}</span></label>
                        <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.amd_enabled" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Enable AMD / voicemail detection') }}</span></label>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-[#fffaf0] p-5">
                    <h3 class="text-lg font-semibold text-slate-900">{{ t('Call analysis and automated outcomes') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ t('AMD is the machine-detection layer that classifies voicemail, answering machine, or mailbox behavior. Define what the dialer should record for each operational outcome so supervisors are never guessing.') }}</p>
                    <div v-if="!campaignForm.amd_enabled" class="mt-5 rounded-3xl border border-slate-200 bg-white px-4 py-4 text-sm leading-6 text-slate-600">
                        <div class="font-semibold text-slate-900">{{ t('AMD is off right now') }}</div>
                        <p class="mt-2">{{ t('Live-answer routing, busy, no-answer, invalid-number, retries, and callback outcomes still work with AMD disabled. Only voicemail or machine classification waits for AMD to be enabled.') }}</p>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Callback disposition code') }}</span>
                                <HelpTooltip :text="t('Used when an agent or automation marks the lead for callback. This is independent from AMD.')"/>
                            </label>
                            <select v-model="campaignForm.callback_disposition_code" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose callback disposition') }}</option>
                                <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }} ({{ disposition.code }})</option>
                            </select>
                        </div>
                        <div v-if="campaignForm.amd_enabled" class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('AMD signal source') }}</span>
                                <HelpTooltip :text="t('FreeSWITCH AVMD starts machine detection on answer. External webhook expects the carrier or provider to send the machine result back.')"/>
                            </label>
                            <select v-model="campaignForm.amd_strategy" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option v-for="strategy in amdStrategies" :key="strategy.value" :value="strategy.value">{{ t(strategy.label) }}</option>
                            </select>
                        </div>
                        <div v-if="campaignForm.amd_enabled" class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Voicemail disposition code') }}</label>
                            <select v-model="campaignForm.voicemail_disposition_code" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose voicemail disposition') }}</option>
                                <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }} ({{ disposition.code }})</option>
                            </select>
                        </div>
                        <div v-if="campaignForm.amd_enabled" class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Voicemail action') }}</span>
                                <HelpTooltip :text="t('Hang up ends the call as soon as voicemail is detected. Continue keeps the call up so a later automation can decide what to do.')"/>
                            </label>
                            <select v-model="campaignForm.voicemail_action" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="hangup">{{ t('Hang up') }}</option>
                                <option value="continue">{{ t('Continue') }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Busy disposition code') }}</span>
                                <HelpTooltip :text="t('Used when the carrier or FreeSWITCH reports a busy destination.')"/>
                            </label>
                            <select v-model="campaignForm.busy_disposition_code" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose busy disposition') }}</option>
                                <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }} ({{ disposition.code }})</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('No-answer disposition code') }}</span>
                                <HelpTooltip :text="t('Used when ringing times out or the destination never answers.')"/>
                            </label>
                            <select v-model="campaignForm.no_answer_disposition_code" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose no-answer disposition') }}</option>
                                <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }} ({{ disposition.code }})</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span>{{ t('Invalid-number disposition code') }}</span>
                                <HelpTooltip :text="t('Used for unreachable, unallocated, or invalid destinations returned by the network.')"/>
                            </label>
                            <select v-model="campaignForm.invalid_number_disposition_code" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose invalid-number disposition') }}</option>
                                <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }} ({{ disposition.code }})</option>
                            </select>
                        </div>
                        <div v-if="campaignForm.amd_enabled && campaignForm.amd_strategy === 'external-webhook'" class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Webhook secret') }}</label>
                            <input v-model="campaignForm.webhook_secret" type="text" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Webhook secret')" />
                        </div>
                        <div v-if="campaignForm.amd_enabled && campaignForm.amd_strategy === 'external-webhook'" class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Webhook URL') }}</label>
                            <input v-model="campaignForm.webhook_url" type="url" class="w-full rounded-2xl border-amber-200 bg-white shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Webhook URL')" />
                            <p class="text-xs leading-5 text-slate-500">{{ t('Required when external AMD is enabled so the provider can send machine or voicemail results back to the dialer.') }}</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 xl:grid-cols-3">
                        <article class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-amber-700">{{ t('AMD behavior') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ t(amdBehaviorTitle) }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t(amdBehaviorDescription) }}</p>
                        </article>
                        <article class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-amber-700">{{ t('Voicemail route') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ campaignForm.amd_enabled ? `${resolvedVoicemailDisposition} / ${resolvedVoicemailAction}` : t('AMD disabled') }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t('When AMD returns machine or voicemail, the dialer will classify the attempt using this disposition and follow the selected voicemail action.') }}</p>
                        </article>
                        <article class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-amber-700">{{ t('Callback route') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ resolvedCallbackDisposition }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t('This outcome is used whenever the lead must come back to the operation later, whether the callback was requested by the agent or by an automation rule.') }}</p>
                        </article>
                        <article class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-amber-700">{{ t('System outcomes') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ `${resolvedBusyDisposition} / ${resolvedNoAnswerDisposition} / ${resolvedInvalidDisposition}` }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t('Busy, no-answer, and invalid-number are saved explicitly so reports and recycle rules stay operationally meaningful.') }}</p>
                        </article>
                    </div>
                </section>

                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ editingCampaignUuid ? t('Update campaign') : t('Create campaign') }}</button>
            </form>
        </section>

        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Campaign portfolio') }}</h2>
                <div class="mt-4 space-y-3">
                    <article v-for="campaign in campaigns" :key="campaign.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-slate-900">{{ campaign.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ t(campaign.mode) }} | {{ t(campaign.status) }}</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="editCampaign(campaign)">{{ t('Edit') }}</button>
                                <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteCampaign(campaign.uuid)">{{ t('Delete') }}</button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-[2rem] bg-[#fffaf0] p-5 shadow-sm ring-1 ring-amber-200">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Professional dialing playbook') }}</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Manual: best for relationship, retention, or regulated journeys.') }}</div>
                    <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Preview: best for premium or high-value leads because the agent sees the record first.') }}</div>
                    <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Progressive: safest automated option when a staffed queue receives answered calls.') }}</div>
                    <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Power: activate only after monitoring answer rate, abandonment, and occupancy closely.') }}</div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Launch checklist') }}</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200">{{ t('Confirm queue staffing before activating progressive or power pacing.') }}</div>
                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200">{{ t('Keep retry limits and callback codes explicit before importing the list.') }}</div>
                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200">{{ t('Attach a compliance profile whenever the operation follows a rule set beyond the standard UF baseline.') }}</div>
                </div>
            </section>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    dispositions: { type: Array, default: () => [] },
    complianceProfiles: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    selectedCampaignUuid: { type: String, default: '' },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const editingCampaignUuid = ref(null)
const defaultCampaignForm = () => ({
    name: '',
    description: '',
    mode: 'manual',
    status: 'draft',
    caller_id_name: '',
    caller_id_number: '',
    outbound_prefix: '',
    call_center_queue_uuid: '',
    dialer_compliance_profile_uuid: '',
    pacing_ratio: 1,
    max_inflight_calls: '',
    preview_seconds: 30,
    originate_timeout: 30,
    max_attempts: 3,
    default_state_code: '',
    default_timezone: '',
    retry_backoff_minutes: 30,
    daily_retry_limit: 3,
    respect_dnc: true,
    amd_enabled: false,
    amd_strategy: 'avmd',
    webhook_url: '',
    webhook_secret: '',
    callback_disposition_code: 'callback',
    voicemail_disposition_code: 'voicemail',
    busy_disposition_code: 'busy',
    no_answer_disposition_code: 'no-answer',
    invalid_number_disposition_code: 'invalid-number',
    voicemail_action: 'hangup',
})
const campaignForm = reactive(defaultCampaignForm())
const modeProfiles = [
    { value: 'manual', kicker: 'Assisted', title: 'Manual', description: 'Best when the agent chooses the exact lead and keeps full control of each call start.' },
    { value: 'preview', kicker: 'Guided', title: 'Preview', description: 'Shows the next callable lead first, then lets the agent decide whether to place the call.' },
    { value: 'progressive', kicker: 'Automated', title: 'Progressive', description: 'Dials one lead per ready agent and sends answered calls to the configured queue.' },
    { value: 'power', kicker: 'High volume', title: 'Power / Predictive', description: 'Uses the pacing ratio to place more calls than available agents and requires tighter supervision.' },
]
const amdStrategies = [
    { value: 'avmd', label: 'Native FreeSWITCH AVMD' },
    { value: 'external-webhook', label: 'External webhook / carrier AMD' },
]
const isAutomatedMode = computed(() => ['progressive', 'power'].includes(campaignForm.mode))
const requiresQueueHandoff = computed(() => ['progressive', 'power'].includes(campaignForm.mode))
const usesPreviewWindow = computed(() => campaignForm.mode === 'preview')
const usesPacingRatio = computed(() => campaignForm.mode === 'power')
const queueMap = computed(() => Object.fromEntries((props.options?.queues || []).map((queue) => [queue.value, queue.label])))
const dispositionMap = computed(() => Object.fromEntries((props.dispositions || []).map((disposition) => [disposition.code, disposition.name])))
const resolvedCallbackDisposition = computed(() => dispositionMap.value[campaignForm.callback_disposition_code] || campaignForm.callback_disposition_code || t('Not configured'))
const resolvedVoicemailDisposition = computed(() => dispositionMap.value[campaignForm.voicemail_disposition_code] || campaignForm.voicemail_disposition_code || t('Not configured'))
const resolvedBusyDisposition = computed(() => dispositionMap.value[campaignForm.busy_disposition_code] || campaignForm.busy_disposition_code || t('Not configured'))
const resolvedNoAnswerDisposition = computed(() => dispositionMap.value[campaignForm.no_answer_disposition_code] || campaignForm.no_answer_disposition_code || t('Not configured'))
const resolvedInvalidDisposition = computed(() => dispositionMap.value[campaignForm.invalid_number_disposition_code] || campaignForm.invalid_number_disposition_code || t('Not configured'))
const resolvedVoicemailAction = computed(() => campaignForm.voicemail_action === 'continue' ? t('Continue') : t('Hang up'))
const modeControlCards = computed(() => ([
    {
        label: 'Answered-call owner',
        value: requiresQueueHandoff.value
            ? (queueMap.value[campaignForm.call_center_queue_uuid] || t('Queue handoff required'))
            : t('Selected agent extension at launch'),
        help: requiresQueueHandoff.value
            ? 'Progressive and power deliver live answers to the configured queue.'
            : 'Manual and preview keep the live answer with the agent who launched the call.',
        tone: requiresQueueHandoff.value && !campaignForm.call_center_queue_uuid ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50',
    },
    {
        label: 'Concurrent call ceiling',
        value: isAutomatedMode.value
            ? (campaignForm.max_inflight_calls || t('Driven by pacing'))
            : t('Agent paced'),
        help: isAutomatedMode.value
            ? 'This ceiling prevents the campaign from keeping more outbound calls in flight than the operation can absorb.'
            : 'Manual and preview modes depend on the agent, so a concurrent call ceiling is not used.',
        tone: isAutomatedMode.value ? 'border-violet-200 bg-violet-50' : 'border-slate-200 bg-slate-50',
    },
    {
        label: 'Mode pacing',
        value: usesPreviewWindow.value
            ? `${campaignForm.preview_seconds || 0}s`
            : (usesPacingRatio.value ? `x${campaignForm.pacing_ratio || 1}` : t('Agent controlled')),
        help: usesPreviewWindow.value
            ? 'Preview mode shows the record first and lets the agent decide within this time window.'
            : (usesPacingRatio.value
                ? 'Power mode multiplies ready-agent capacity by this ratio before launching new calls.'
                : 'Manual and progressive modes rely on one live route per operator-ready action.'),
        tone: usesPreviewWindow.value || usesPacingRatio.value ? 'border-cyan-200 bg-cyan-50' : 'border-slate-200 bg-slate-50',
    },
    {
        label: 'Retry rhythm',
        value: `${campaignForm.daily_retry_limit || 0} ${t('daily retries')} / ${campaignForm.retry_backoff_minutes || 0} ${t('minutes backoff')}`,
        help: 'No-answer attempts follow this daily cap and retry delay before the lead becomes callable again.',
        tone: 'border-slate-200 bg-slate-50',
    },
]))
const callFlowCards = computed(() => ([
    {
        label: 'Answered-call destination',
        value: isAutomatedMode.value
            ? (queueMap.value[campaignForm.call_center_queue_uuid] || t('Queue handoff required'))
            : t('Selected agent extension at launch'),
        help: isAutomatedMode.value
            ? 'Answered calls from automated modes are transferred to the chosen call center queue.'
            : 'Manual and preview calls stay with the extension chosen when the agent launches the call.',
        tone: isAutomatedMode.value && !campaignForm.call_center_queue_uuid ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white',
    },
    {
        label: 'No-answer action',
        value: resolvedNoAnswerDisposition.value,
        help: 'When ringing times out or nobody answers, the dialer records this outcome before the retry policy decides whether to recycle the lead.',
        tone: 'border-slate-200 bg-white',
    },
    {
        label: 'Voicemail action',
        value: campaignForm.amd_enabled ? `${resolvedVoicemailDisposition.value} / ${resolvedVoicemailAction.value}` : t('AMD disabled'),
        help: 'Machine or voicemail detections are mapped to the configured voicemail disposition and voicemail action.',
        tone: campaignForm.amd_enabled ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white',
    },
    {
        label: 'Callback action',
        value: resolvedCallbackDisposition.value,
        help: 'Use a callback outcome that immediately tells supervisors the lead should return to a follow-up queue or schedule.',
        tone: 'border-cyan-200 bg-cyan-50',
    },
    {
        label: 'Busy action',
        value: resolvedBusyDisposition.value,
        help: 'Busy destinations are stored with their own outcome so recycling and reporting can stay distinct from no-answer cases.',
        tone: 'border-slate-200 bg-white',
    },
    {
        label: 'Invalid-number action',
        value: resolvedInvalidDisposition.value,
        help: 'Network failures such as unallocated or invalid numbers are saved explicitly to keep bad data out of later recycle passes.',
        tone: 'border-slate-200 bg-white',
    },
]))
const amdBehaviorTitle = computed(() => {
    if (!campaignForm.amd_enabled) return 'AMD is disabled for this campaign'
    return campaignForm.amd_strategy === 'external-webhook'
        ? 'AMD expects an external webhook result'
        : 'AMD starts natively inside FreeSWITCH on answer'
})
const amdBehaviorDescription = computed(() => {
    if (!campaignForm.amd_enabled) return 'The dialer will not try to classify machine or voicemail automatically until AMD is enabled.'
    return campaignForm.amd_strategy === 'external-webhook'
        ? 'Use this when the carrier or provider reports machine detection and sends machine/voicemail back through the dialer webhook.'
        : 'Use this when the platform should start AVMD on answer and classify voicemail directly from the FreeSWITCH signaling flow without an external webhook.'
})

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const stateMap = () => Object.fromEntries((props.options?.states || []).map((state) => [state.value, state]))
const syncCampaignTimezone = () => { if (stateMap()[campaignForm.default_state_code]) campaignForm.default_timezone = stateMap()[campaignForm.default_state_code].timezone }
const resetCampaignForm = () => { editingCampaignUuid.value = null; Object.assign(campaignForm, defaultCampaignForm()) }
const editCampaign = (campaign) => {
    editingCampaignUuid.value = campaign.uuid
    Object.assign(campaignForm, {
        ...defaultCampaignForm(),
        ...campaign,
        max_inflight_calls: campaign.max_inflight_calls || '',
        call_center_queue_uuid: campaign.call_center_queue_uuid || '',
        dialer_compliance_profile_uuid: campaign.dialer_compliance_profile_uuid || '',
        respect_dnc: Boolean(campaign.respect_dnc),
        amd_enabled: Boolean(campaign.amd_enabled),
        amd_strategy: campaign.amd_strategy || 'avmd',
        callback_disposition_code: campaign.callback_disposition_code || 'callback',
        voicemail_disposition_code: campaign.voicemail_disposition_code || 'voicemail',
        busy_disposition_code: campaign.busy_disposition_code || 'busy',
        no_answer_disposition_code: campaign.no_answer_disposition_code || 'no-answer',
        invalid_number_disposition_code: campaign.invalid_number_disposition_code || 'invalid-number',
        voicemail_action: campaign.voicemail_action || 'hangup',
    })
}
watch(
    () => props.selectedCampaignUuid,
    (uuid) => {
        if (!uuid) {
            resetCampaignForm()
            return
        }

        const campaign = props.campaigns.find((item) => item.uuid === uuid)

        if (campaign) {
            editCampaign(campaign)
        }
    },
    { immediate: true }
)

const submitCampaign = async () => {
    if (!campaignForm.name?.trim()) {
        return setMessage('error', t('Provide a campaign name before saving.'))
    }

    if (requiresQueueHandoff.value && !campaignForm.call_center_queue_uuid) {
        return setMessage('error', t('Select a queue before using progressive or power dialing.'))
    }

    if (campaignForm.amd_enabled && campaignForm.amd_strategy === 'external-webhook' && !campaignForm.webhook_url?.trim()) {
        return setMessage('error', t('Provide a webhook URL before using external AMD.'))
    }

    try {
        await axios[editingCampaignUuid.value ? 'put' : 'post'](
            editingCampaignUuid.value
                ? routeFor(props.routes.updateCampaign || '/dialer/campaigns/__CAMPAIGN__', '__CAMPAIGN__', editingCampaignUuid.value)
                : (props.routes.storeCampaign || '/dialer/campaigns'),
            campaignForm
        )
        setMessage('success', editingCampaignUuid.value ? t('Campaign updated successfully.') : t('Campaign created successfully.'))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || error.response?.data?.message || t('Unable to save the campaign right now.'))
    }
}
const deleteCampaign = async (uuid) => {
    if (!window.confirm(t('Delete this campaign?'))) return
    try {
        await axios.delete(routeFor(props.routes.destroyCampaign || '/dialer/campaigns/__CAMPAIGN__', '__CAMPAIGN__', uuid))
        setMessage('success', t('Campaign deleted successfully.'))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the campaign right now.'))
    }
}
</script>

<template>
    <MainLayout>
        <main class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#eef4f7_100%)] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl">
                    <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <span class="inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100">{{ t('Dialer') }}</span>
                            <h1 class="mt-4 text-3xl font-semibold tracking-tight">{{ t('Professional outbound console') }}</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ t('Build compliant campaigns, guide agents through assisted dialing, and automate progressive or power pacing with clearer operational controls.') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-3xl bg-white/5 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-300">{{ t('Campaigns') }}</div>
                                <div class="mt-3 text-3xl font-semibold">{{ summary.campaigns }}</div>
                            </div>
                            <div class="rounded-3xl bg-emerald-400/10 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-emerald-100">{{ t('Active') }}</div>
                                <div class="mt-3 text-3xl font-semibold text-emerald-100">{{ summary.activeCampaigns }}</div>
                            </div>
                            <div class="rounded-3xl bg-white/5 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-300">{{ t('Leads') }}</div>
                                <div class="mt-3 text-3xl font-semibold">{{ summary.leads }}</div>
                            </div>
                            <div class="rounded-3xl bg-amber-400/10 p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-amber-100">{{ t('Do-not-call list') }}</div>
                                <div class="mt-3 text-3xl font-semibold text-amber-100">{{ summary.dnc }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm shadow-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                    {{ message.text }}
                </div>

                <div class="flex flex-wrap gap-2">
                    <button v-for="tab in tabs" :key="tab.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeTab === tab.value ? 'bg-slate-950 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'" @click="activeTab = tab.value">{{ t(tab.label) }}</button>
                </div>

                <div v-if="activeTab === 'control'" class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ t('Campaign runway') }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ t('Select a campaign to operate, monitor pacing, and move quickly between assisted and automated dialing.') }}</p>
                            </div>
                            <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="activeTab = 'campaigns'">{{ t('Open builder') }}</button>
                        </div>
                        <div class="mt-5 space-y-3">
                            <button v-for="campaign in props.campaigns" :key="campaign.uuid" type="button" class="w-full rounded-3xl border p-4 text-left transition" :class="selectedCampaignUuid === campaign.uuid ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50'" @click="selectedCampaignUuid = campaign.uuid">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ campaign.name }}</div>
                                        <div class="mt-1 text-xs" :class="selectedCampaignUuid === campaign.uuid ? 'text-slate-300' : 'text-slate-500'">{{ t(campaign.mode) }} | {{ t(campaign.status) }}</div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/10 text-slate-100' : 'bg-white text-slate-700 ring-1 ring-slate-200'">{{ campaign.queue_label || t('No queue') }}</span>
                                </div>
                                <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                                    <div class="rounded-2xl px-3 py-2" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/5' : 'bg-white ring-1 ring-slate-200'">{{ t('Queued leads') }}: <span class="font-semibold">{{ campaign.queued_leads_count }}</span></div>
                                    <div class="rounded-2xl px-3 py-2" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/5' : 'bg-white ring-1 ring-slate-200'">{{ t('Calling') }}: <span class="font-semibold">{{ campaign.calling_leads_count }}</span></div>
                                    <div class="rounded-2xl px-3 py-2" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/5' : 'bg-white ring-1 ring-slate-200'">{{ t('Default state') }}: <span class="font-semibold">{{ campaign.default_state_code || t('No state') }}</span></div>
                                </div>
                            </button>
                            <div v-if="!props.campaigns?.length" class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">{{ t('No campaigns yet. Create the first outbound workflow in the builder tab.') }}</div>
                        </div>
                    </section>

                    <div class="space-y-6">
                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Live console') }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">{{ t('Use the selected campaign, extension, and contact to run assisted calls without jumping between multiple panels.') }}</p>
                                </div>
                                <button v-if="selectedCampaign" type="button" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white" @click="editCampaign(selectedCampaign)">{{ t('Edit campaign') }}</button>
                            </div>

                            <div v-if="selectedCampaign" class="mt-5 grid gap-4 lg:grid-cols-[1fr,0.92fr]">
                                <div class="space-y-4">
                                    <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ t('Current campaign') }}</div>
                                        <div class="mt-2 text-xl font-semibold text-slate-900">{{ selectedCampaign.name }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                            <span class="rounded-full bg-slate-900 px-3 py-1 text-white">{{ t(selectedCampaign.mode) }}</span>
                                            <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ selectedCampaign.queue_label || t('No queue') }}</span>
                                        </div>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <select v-model="operation.extension_uuid" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                            <option value="">{{ t('Select an extension') }}</option>
                                            <option v-for="extension in props.options.extensions" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                                        </select>
                                        <select v-model="operation.lead_uuid" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                            <option value="">{{ t('Select a recent lead') }}</option>
                                            <option v-for="lead in props.leads" :key="lead.uuid" :value="lead.uuid">{{ lead.name }} - {{ lead.phone_number }}</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <button type="button" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white" @click="previewCampaign">{{ t('Preview next lead') }}</button>
                                        <button type="button" class="rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white" @click="launchPreviewCall">{{ t('Place preview call') }}</button>
                                        <button type="button" class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200" @click="manualDial">{{ t('Call now') }}</button>
                                        <button type="button" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white" @click="runCampaign">{{ t('Run cycle') }}</button>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ t('Next preview contact') }}</div>
                                    <div class="mt-3 text-lg font-semibold text-slate-900">{{ previewLead?.name || t('Preview mode shows the next callable lead before connecting the agent.') }}</div>
                                    <div v-if="previewLead" class="mt-4 space-y-1 text-sm text-slate-600">
                                        <div>{{ previewLead.phone_number }}</div>
                                        <div>{{ previewLead.company || t('No company') }}</div>
                                        <div>{{ previewLead.email || '-' }}</div>
                                    </div>
                                    <p class="mt-4 text-xs leading-5 text-slate-500">{{ t('Progressive and power modes dispatch calls based on queue capacity, agent availability, and pacing.') }}</p>
                                </div>
                            </div>
                            <div v-else class="mt-5 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">{{ t('Select a campaign from the left to open the live console.') }}</div>
                        </section>

                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Latest attempts') }}</h2>
                            <div class="mt-5 space-y-3">
                                <article v-for="attempt in props.attempts" :key="attempt.uuid" class="rounded-3xl border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ attempt.lead_name || attempt.destination_number }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ attempt.campaign_name || '-' }} | {{ attempt.destination_number }} | {{ t(attempt.mode) }}</div>
                                        </div>
                                        <div class="text-right text-xs text-slate-500">
                                            <div>{{ t('Talk') }}: {{ attempt.talk_seconds || 0 }}s</div>
                                            <div>{{ t('Waiting') }}: {{ attempt.wait_seconds || 0 }}s</div>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 xl:grid-cols-[1fr,1fr,1fr,auto]">
                                        <select v-model="attemptForms[attempt.uuid].disposition_uuid" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                            <option value="">{{ t('Choose disposition') }}</option>
                                            <option v-for="disposition in props.dispositions" :key="disposition.uuid" :value="disposition.uuid">{{ disposition.name }} ({{ disposition.code }})</option>
                                        </select>
                                        <input v-model="attemptForms[attempt.uuid].preferred_callback_at" type="datetime-local" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                        <input v-model="attemptForms[attempt.uuid].notes" type="text" :placeholder="t('Notes')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                        <button type="button" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white" @click="saveAttemptDisposition(attempt.uuid)">{{ t('Save disposition') }}</button>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ attempt.disposition || t('Pending') }}</span>
                                        <span v-if="attempt.completed_at" class="rounded-full bg-slate-100 px-3 py-1">{{ formatDate(attempt.completed_at, { dateStyle: 'medium', timeStyle: 'short' }) }}</span>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>

                <div v-else-if="activeTab === 'campaigns'" class="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ editingCampaignUuid ? t('Update campaign') : t('Create campaign') }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ t('Configure pacing, compliance defaults, AMD, and webhook automation per campaign.') }}</p>
                            </div>
                            <button v-if="editingCampaignUuid" type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="resetCampaignForm">{{ t('Cancel editing') }}</button>
                        </div>
                        <form class="mt-5 space-y-4" @submit.prevent="submitCampaign">
                            <div class="grid gap-4 md:grid-cols-2">
                                <input v-model="campaignForm.name" type="text" :placeholder="t('Campaign name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <select v-model="campaignForm.status" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option v-for="status in props.options.statuses" :key="status.value" :value="status.value">{{ t(status.label) }}</option>
                                </select>
                                <select v-model="campaignForm.mode" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option v-for="mode in props.options.modes" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option>
                                </select>
                                <select v-model="campaignForm.call_center_queue_uuid" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option value="">{{ t('No queue') }}</option>
                                    <option v-for="queue in props.options.queues" :key="queue.value" :value="queue.value">{{ queue.label }}</option>
                                </select>
                                <input v-model="campaignForm.caller_id_name" type="text" :placeholder="t('Caller ID name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="campaignForm.caller_id_number" type="text" :placeholder="t('Caller ID number')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <select v-model="campaignForm.default_state_code" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" @change="syncCampaignTimezone">
                                    <option value="">{{ t('Default state') }}</option>
                                    <option v-for="state in props.options.states" :key="state.value" :value="state.value">{{ state.label }}</option>
                                </select>
                                <input v-model="campaignForm.default_timezone" type="text" :placeholder="t('Default timezone')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                            </div>
                            <textarea v-model="campaignForm.description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950"></textarea>
                            <div class="grid gap-4 md:grid-cols-3">
                                <input v-model.number="campaignForm.max_attempts" type="number" min="1" max="25" :placeholder="t('Maximum attempts')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model.number="campaignForm.daily_retry_limit" type="number" min="1" max="25" :placeholder="t('Daily retry limit')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model.number="campaignForm.retry_backoff_minutes" type="number" min="1" max="43200" :placeholder="t('Retry backoff (minutes)')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model.number="campaignForm.preview_seconds" type="number" min="5" max="3600" :placeholder="t('Preview seconds')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model.number="campaignForm.originate_timeout" type="number" min="5" max="120" :placeholder="t('Originate timeout')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model.number="campaignForm.pacing_ratio" type="number" min="1" max="10" step="0.1" :placeholder="t('Pacing ratio')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <input v-model="campaignForm.webhook_url" type="url" :placeholder="t('Webhook URL')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="campaignForm.webhook_secret" type="text" :placeholder="t('Webhook secret')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="campaignForm.amd_strategy" type="text" :placeholder="t('AMD strategy')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950 md:col-span-2" />
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.respect_dnc" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Respect do-not-call list') }}</span></label>
                                <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.amd_enabled" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Enable AMD / voicemail detection') }}</span></label>
                            </div>
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ editingCampaignUuid ? t('Update campaign') : t('Create campaign') }}</button>
                        </form>
                    </section>

                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Campaign portfolio') }}</h2>
                        <div class="mt-5 space-y-3">
                            <article v-for="campaign in props.campaigns" :key="campaign.uuid" class="rounded-3xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ campaign.name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ t(campaign.mode) }} | {{ t(campaign.status) }} | {{ campaign.queue_label || t('No queue') }}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="editCampaign(campaign)">{{ t('Edit') }}</button>
                                        <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteCampaign(campaign.uuid)">{{ t('Delete') }}</button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div v-else-if="activeTab === 'contacts'" class="space-y-6">
                    <div class="grid gap-6 xl:grid-cols-3">
                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Add lead') }}</h2>
                            <form class="mt-5 space-y-4" @submit.prevent="submitLead">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <input v-model="leadForm.first_name" type="text" :placeholder="t('First name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="leadForm.last_name" type="text" :placeholder="t('Last name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="leadForm.company" type="text" :placeholder="t('Company')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="leadForm.phone_number" type="text" :placeholder="t('Phone number')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="leadForm.email" type="email" :placeholder="t('Email')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <select v-model="leadForm.state_code" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" @change="syncLeadTimezone">
                                        <option value="">{{ t('Lead state') }}</option>
                                        <option v-for="state in props.options.states" :key="state.value" :value="state.value">{{ state.label }}</option>
                                    </select>
                                    <input v-model="leadForm.timezone" type="text" :placeholder="t('Lead timezone')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950 md:col-span-2" />
                                </div>
                                <select v-model="leadForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option v-for="campaign in props.campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                                </select>
                                <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="leadForm.do_not_call" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Mark as do-not-call immediately') }}</span></label>
                                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Add lead') }}</button>
                            </form>
                        </section>

                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead import') }}</h2>
                            <form class="mt-5 space-y-4" @submit.prevent="submitImport">
                                <select v-model="importForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option v-for="campaign in props.campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                                </select>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <select v-model="importForm.default_state_code" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" @change="syncImportTimezone">
                                        <option value="">{{ t('Default state') }}</option>
                                        <option v-for="state in props.options.states" :key="state.value" :value="state.value">{{ state.label }}</option>
                                    </select>
                                    <input v-model="importForm.default_timezone" type="text" :placeholder="t('Default timezone')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                </div>
                                <input type="file" accept=".csv,.txt,.xls,.xlsx" class="block w-full text-sm" @change="handleImportFile" />
                                <button type="submit" class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white">{{ t('Queue import') }}</button>
                            </form>
                            <div class="mt-5 space-y-2 text-sm">
                                <div v-for="batch in props.importBatches" :key="batch.uuid" class="rounded-2xl border border-slate-200 p-3">{{ batch.file_name }} <span class="text-slate-500">| {{ t(batch.status) }} | {{ batch.imported_rows }}/{{ batch.total_rows }}</span></div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Do-not-call list') }}</h2>
                            <form class="mt-5 space-y-4" @submit.prevent="submitDnc">
                                <input v-model="dncForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="dncForm.reason" type="text" :placeholder="t('Reason')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="dncForm.source" type="text" :placeholder="t('Source')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save DNC entry') }}</button>
                            </form>
                            <div class="mt-5 space-y-2 text-sm">
                                <div v-for="entry in props.dncEntries" :key="entry.uuid" class="flex items-center justify-between rounded-2xl border border-slate-200 p-3">
                                    <div>{{ entry.phone_number }} <span class="text-slate-500">| {{ entry.reason || '-' }}</span></div>
                                    <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteDnc(entry.uuid)">{{ t('Delete') }}</button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Recent contacts') }}</h2>
                        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="lead in props.leads" :key="lead.uuid" class="rounded-3xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ lead.name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ lead.phone_number }}</div>
                                    </div>
                                    <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteLead(lead.uuid)">{{ t('Delete') }}</button>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ t(lead.status) }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ lead.state_code || t('No state') }}</span>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div v-else-if="activeTab === 'compliance'" class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('UF compliance rules') }}</h2>
                        <div class="mt-4 rounded-3xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">{{ t('This baseline follows national guidance published by Anatel for telemarketing and should still be reviewed against local Procon or municipal restrictions before going live.') }}</div>
                        <div class="mt-5 space-y-3">
                            <button v-for="rule in props.stateRules" :key="rule.uuid" type="button" class="w-full rounded-3xl border p-4 text-left transition" :class="selectedStateRuleUuid === rule.uuid ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50'" @click="loadSelectedStateRule(rule)">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ rule.state_code }} - {{ rule.state_name }}</div>
                                        <div class="mt-1 text-xs" :class="selectedStateRuleUuid === rule.uuid ? 'text-slate-300' : 'text-slate-500'">{{ rule.timezone }}</div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="selectedStateRuleUuid === rule.uuid ? 'bg-white/10 text-slate-100' : 'bg-white text-slate-700 ring-1 ring-slate-200'">{{ rule.schedule_summary }}</span>
                                </div>
                            </button>
                        </div>
                    </section>

                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Edit UF rule') }}</h2>
                            <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="useNationalBaseline">{{ t('Use national baseline') }}</button>
                        </div>
                        <form class="mt-5 space-y-4" @submit.prevent="saveStateRule">
                            <div class="grid gap-4 md:grid-cols-2">
                                <input v-model="stateRuleForm.state_code" type="text" maxlength="2" :placeholder="t('State code')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="stateRuleForm.state_name" type="text" :placeholder="t('State name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <input v-model="stateRuleForm.timezone" type="text" :placeholder="t('Timezone')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950 md:col-span-2" />
                            </div>
                            <div class="space-y-3 rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <div v-for="day in dayRows" :key="day.key" class="grid items-center gap-3 md:grid-cols-[0.8fr,0.7fr,0.7fr,auto]">
                                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700"><input v-model="stateRuleForm.schedule[day.key].enabled" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t(day.label) }}</span></label>
                                    <input v-model="stateRuleForm.schedule[day.key].start" type="time" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="stateRuleForm.schedule[day.key].end" type="time" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <span class="text-xs text-slate-500">{{ stateRuleForm.schedule[day.key].enabled ? t('Enabled') : t('Disabled') }}</span>
                                </div>
                            </div>
                            <textarea v-model="stateRuleForm.notes" rows="4" :placeholder="t('Compliance notes')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950"></textarea>
                            <input v-model="stateRuleForm.legal_reference_url" type="url" :placeholder="t('Legal reference URL')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save state rule') }}</button>
                        </form>
                    </section>
                </div>

                <div v-else class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Outcome library') }}</h2>
                            <form class="mt-5 space-y-4" @submit.prevent="submitDisposition">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <input v-model="dispositionForm.name" type="text" :placeholder="t('Disposition name')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                    <input v-model="dispositionForm.code" type="text" :placeholder="t('Disposition code')" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                </div>
                                <textarea v-model="dispositionForm.description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950"></textarea>
                                <input v-model.number="dispositionForm.default_callback_offset_minutes" type="number" min="1" max="43200" :placeholder="t('Default callback offset (minutes)')" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" />
                                <div class="grid gap-3 md:grid-cols-3">
                                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.is_final" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Final disposition') }}</span></label>
                                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.is_callback" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Creates callback') }}</span></label>
                                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.mark_dnc" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Mark as do-not-call immediately') }}</span></label>
                                </div>
                                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save disposition') }}</button>
                            </form>
                        </section>

                        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Dispositions') }}</h2>
                            <div class="mt-5 space-y-3">
                                <article v-for="disposition in props.dispositions" :key="disposition.uuid" class="rounded-3xl border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ disposition.name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ disposition.code }}</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <span v-if="disposition.is_final" class="rounded-full bg-slate-900 px-3 py-1 text-white">{{ t('Final disposition') }}</span>
                                            <span v-if="disposition.is_callback" class="rounded-full bg-cyan-50 px-3 py-1 text-cyan-700">{{ t('Creates callback') }}</span>
                                            <span v-if="disposition.mark_dnc" class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">{{ t('Do-not-call list') }}</span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                </div>
            </div>
        </main>
    </MainLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@layouts/MainLayout.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({ campaigns: Array, leads: Array, attempts: Array, dispositions: Array, dncEntries: Array, stateRules: Array, importBatches: Array, options: Object, routes: Object })
const page = usePage()
const { t, formatDate } = useLocale()

const tabs = [
    { value: 'control', label: 'Operations' },
    { value: 'campaigns', label: 'Campaigns' },
    { value: 'contacts', label: 'Contacts' },
    { value: 'compliance', label: 'Compliance' },
    { value: 'outcomes', label: 'Dispositions' },
]

const dayRows = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' },
]

const baseline = () => ({
    monday: { enabled: true, start: '09:00', end: '21:00' },
    tuesday: { enabled: true, start: '09:00', end: '21:00' },
    wednesday: { enabled: true, start: '09:00', end: '21:00' },
    thursday: { enabled: true, start: '09:00', end: '21:00' },
    friday: { enabled: true, start: '09:00', end: '21:00' },
    saturday: { enabled: true, start: '10:00', end: '16:00' },
    sunday: { enabled: false, start: '', end: '' },
})

const message = reactive({ type: 'success', text: '' })
const activeTab = ref('control')
const selectedCampaignUuid = ref(props.campaigns?.[0]?.uuid || '')
const selectedStateRuleUuid = ref(props.stateRules?.[0]?.uuid || '')
const previewLead = ref(null)
const editingCampaignUuid = ref(null)

const campaignForm = reactive({
    name: '',
    description: '',
    mode: 'manual',
    status: 'draft',
    caller_id_name: '',
    caller_id_number: '',
    outbound_prefix: '',
    call_center_queue_uuid: '',
    pacing_ratio: 1,
    preview_seconds: 30,
    originate_timeout: 30,
    max_attempts: 3,
    default_state_code: '',
    default_timezone: '',
    retry_backoff_minutes: 30,
    daily_retry_limit: 3,
    respect_dnc: true,
    amd_enabled: false,
    amd_strategy: '',
    webhook_url: '',
    webhook_secret: '',
})

const leadForm = reactive({
    first_name: '',
    last_name: '',
    company: '',
    phone_number: '',
    email: '',
    state_code: '',
    timezone: '',
    do_not_call: false,
    campaign_uuids: [],
})

const dispositionForm = reactive({
    name: '',
    code: '',
    is_final: false,
    is_callback: false,
    mark_dnc: false,
    default_callback_offset_minutes: '',
    description: '',
})

const dncForm = reactive({ phone_number: '', reason: '', source: 'manual' })
const importForm = reactive({ file: null, campaign_uuids: [], default_state_code: '', default_timezone: '' })
const stateRuleForm = reactive({ state_code: '', state_name: '', timezone: 'America/Sao_Paulo', schedule: baseline(), notes: '', legal_reference_url: '' })
const operation = reactive({ extension_uuid: props.options?.extensions?.[0]?.value || '', lead_uuid: props.leads?.[0]?.uuid || '' })
const attemptForms = reactive(Object.fromEntries((props.attempts || []).map((attempt) => [attempt.uuid, { disposition_uuid: '', notes: '', preferred_callback_at: '' }])))

const stateMap = computed(() => Object.fromEntries((props.options?.states || []).map((state) => [state.value, state])))
const selectedCampaign = computed(() => (props.campaigns || []).find((campaign) => campaign.uuid === selectedCampaignUuid.value) || null)
const summary = computed(() => ({
    campaigns: props.campaigns?.length || 0,
    activeCampaigns: (props.campaigns || []).filter((campaign) => campaign.status === 'active').length,
    leads: props.leads?.length || 0,
    dnc: props.dncEntries?.length || 0,
}))

const setMessage = (type, text) => {
    message.type = type
    message.text = text
}

const reloadPage = () => window.location.reload()
const url = (base, suffix = '') => `${base}/${suffix}`

const syncCampaignTimezone = () => {
    if (stateMap.value[campaignForm.default_state_code]) {
        campaignForm.default_timezone = stateMap.value[campaignForm.default_state_code].timezone
    }
}

const syncLeadTimezone = () => {
    if (stateMap.value[leadForm.state_code]) {
        leadForm.timezone = stateMap.value[leadForm.state_code].timezone
    }
}

const syncImportTimezone = () => {
    if (stateMap.value[importForm.default_state_code]) {
        importForm.default_timezone = stateMap.value[importForm.default_state_code].timezone
    }
}

const resetCampaignForm = () => {
    editingCampaignUuid.value = null
    Object.assign(campaignForm, {
        name: '',
        description: '',
        mode: 'manual',
        status: 'draft',
        caller_id_name: '',
        caller_id_number: '',
        outbound_prefix: '',
        call_center_queue_uuid: '',
        pacing_ratio: 1,
        preview_seconds: 30,
        originate_timeout: 30,
        max_attempts: 3,
        default_state_code: '',
        default_timezone: '',
        retry_backoff_minutes: 30,
        daily_retry_limit: 3,
        respect_dnc: true,
        amd_enabled: false,
        amd_strategy: '',
        webhook_url: '',
        webhook_secret: '',
    })
}

const editCampaign = (campaign) => {
    activeTab.value = 'campaigns'
    editingCampaignUuid.value = campaign.uuid
    Object.assign(campaignForm, {
        ...campaign,
        call_center_queue_uuid: campaign.call_center_queue_uuid || '',
        respect_dnc: !!campaign.respect_dnc,
        amd_enabled: !!campaign.amd_enabled,
    })
}

const submitCampaign = async () => {
    try {
        if ((campaignForm.mode === 'progressive' || campaignForm.mode === 'power') && !campaignForm.call_center_queue_uuid) {
            return setMessage('error', t('Queue is required for progressive and power modes.'))
        }

        await axios[editingCampaignUuid.value ? 'put' : 'post'](
            editingCampaignUuid.value ? url('/dialer/campaigns', editingCampaignUuid.value) : props.routes.storeCampaign,
            campaignForm
        )
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the campaign right now.'))
    }
}

const submitLead = async () => {
    try {
        await axios.post(props.routes.storeLead, leadForm)
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the lead right now.'))
    }
}

const submitDisposition = async () => {
    try {
        await axios.post(props.routes.storeDisposition, dispositionForm)
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.'))
    }
}

const submitDnc = async () => {
    try {
        await axios.post(props.routes.storeDnc, dncForm)
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the do-not-call entry right now.'))
    }
}

const deleteDnc = async (uuidValue) => {
    try {
        await axios.delete(url('/dialer/dnc', uuidValue))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the do-not-call entry right now.'))
    }
}

const deleteCampaign = async (uuidValue) => {
    if (!window.confirm(t('Delete this campaign?'))) {
        return
    }

    try {
        await axios.delete(url('/dialer/campaigns', uuidValue))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the campaign right now.'))
    }
}

const deleteLead = async (uuidValue) => {
    if (!window.confirm(t('Delete this lead?'))) {
        return
    }

    try {
        await axios.delete(url('/dialer/leads', uuidValue))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the lead right now.'))
    }
}

const loadSelectedStateRule = (rule) => {
    selectedStateRuleUuid.value = rule.uuid
    Object.assign(stateRuleForm, {
        state_code: rule.state_code,
        state_name: rule.state_name,
        timezone: rule.timezone,
        schedule: JSON.parse(JSON.stringify(rule.schedule || baseline())),
        notes: rule.notes || '',
        legal_reference_url: rule.legal_reference_url || '',
    })
}

const useNationalBaseline = () => {
    stateRuleForm.schedule = baseline()
}

const saveStateRule = async () => {
    try {
        const target = (props.stateRules || []).find((rule) => rule.uuid === selectedStateRuleUuid.value)
        await axios[target ? 'put' : 'post'](target ? url('/dialer/state-rules', target.uuid) : props.routes.storeStateRule, stateRuleForm)
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the state rule right now.'))
    }
}

const previewCampaign = async () => {
    if (!selectedCampaign.value) {
        return setMessage('error', t('Select a campaign from the left to open the live console.'))
    }

    try {
        const response = await axios.get(url('/dialer/campaigns', `${selectedCampaign.value.uuid}/preview`))
        previewLead.value = response.data.lead
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to load the next lead right now.'))
    }
}

const launchPreviewCall = async () => {
    if (!selectedCampaign.value || !previewLead.value) {
        return setMessage('error', t('Select a campaign and load a preview lead before starting the call.'))
    }

    if (!operation.extension_uuid) {
        return setMessage('error', t('Select an extension before placing the preview call.'))
    }

    try {
        await axios.post(url('/dialer/campaigns', `${selectedCampaign.value.uuid}/dial`), {
            lead_uuid: previewLead.value.uuid,
            extension_uuid: operation.extension_uuid,
        })
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.'))
    }
}

const manualDial = async () => {
    if (!selectedCampaign.value) {
        return setMessage('error', t('Select campaign for manual dialing'))
    }

    if (!operation.extension_uuid || !operation.lead_uuid) {
        return setMessage('error', t('Select a campaign and an extension before placing the manual call.'))
    }

    try {
        await axios.post(url('/dialer/campaigns', `${selectedCampaign.value.uuid}/dial`), {
            lead_uuid: operation.lead_uuid,
            extension_uuid: operation.extension_uuid,
        })
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.'))
    }
}

const runCampaign = async () => {
    if (!selectedCampaign.value) {
        return setMessage('error', t('Select a campaign from the left to open the live console.'))
    }

    try {
        await axios.post(url('/dialer/campaigns', `${selectedCampaign.value.uuid}/run`))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to run the campaign right now.'))
    }
}

const handleImportFile = (event) => {
    importForm.file = event.target.files?.[0] || null
}

const submitImport = async () => {
    if (!importForm.file) {
        return setMessage('error', t('Choose a file to import.'))
    }

    try {
        const formData = new FormData()
        formData.append('file', importForm.file)
        importForm.campaign_uuids.forEach((uuidValue, index) => formData.append(`campaign_uuids[${index}]`, uuidValue))
        if (importForm.default_state_code) formData.append('default_state_code', importForm.default_state_code)
        if (importForm.default_timezone) formData.append('default_timezone', importForm.default_timezone)
        await axios.post(props.routes.importLeads, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to queue the import right now.'))
    }
}

const saveAttemptDisposition = async (attemptUuid) => {
    try {
        await axios.post(url('/dialer/attempts', `${attemptUuid}/disposition`), attemptForms[attemptUuid])
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the attempt disposition right now.'))
    }
}

onMounted(() => {
    if (props.stateRules?.[0]) {
        loadSelectedStateRule(props.stateRules[0])
    }

    const domainUuid = page.props.selectedDomainUuid
    if (!domainUuid || !window.Echo) {
        return
    }

    window.Echo.private(`dialer.domain.${domainUuid}`).listen('.dialer.updated', () => {
        setMessage('success', t('Dialer data updated in real time.'))
    })
})

onBeforeUnmount(() => {
    const domainUuid = page.props.selectedDomainUuid
    if (domainUuid && window.Echo) {
        window.Echo.leave(`dialer.domain.${domainUuid}`)
    }
})
</script>

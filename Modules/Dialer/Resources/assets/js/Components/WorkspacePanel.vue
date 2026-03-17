<template>
    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Operator command desk') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Keep manual launch, preview, paced cycles, and the latest operational risk signals inside one action board.') }}</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700">{{ formatNumber(campaigns.length) }} {{ t('Campaigns') }}</div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl border p-4" :class="card.tone">
                    <div class="text-[11px] uppercase tracking-[0.22em]" :class="card.kickerTone">{{ t(card.label) }}</div>
                    <div class="mt-3 text-3xl font-semibold" :class="card.valueTone">{{ card.value }}</div>
                    <div class="mt-2 text-xs leading-5" :class="card.helpTone">{{ t(card.help) }}</div>
                </article>
            </div>
        </section>

        <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            {{ message.text }}
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),390px]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Campaign cockpit') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Choose the active campaign, load the next preview lead, or trigger an automated cycle without leaving the workspace.') }}</p>
                        </div>
                        <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="openSelectedCampaignStudio">{{ t('Open in studio') }}</button>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Select campaign for manual dialing') }}</label>
                            <select v-model="selectedCampaignUuid" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Choose campaign') }}</option>
                                <option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">{{ t('Select agent extension') }}</label>
                            <select v-model="selectedExtensionUuid" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                <option value="">{{ t('Select an extension') }}</option>
                                <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 xl:grid-cols-4">
                        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Mode pacing') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ selectedCampaignModeLabel }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t('Shows whether the selected campaign runs as manual, preview, progressive, or power.') }}</p>
                        </article>
                        <article class="rounded-3xl border p-4" :class="selectedCampaign?.call_center_queue_uuid ? 'border-cyan-200 bg-cyan-50' : 'border-slate-200 bg-slate-50'">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Answered-call owner') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ selectedCampaignQueueLabel }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t('Progressive and power should always have a queue handoff for live answers.') }}</p>
                        </article>
                        <article class="rounded-3xl border p-4" :class="selectedCampaign?.amd_enabled ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('AMD signal source') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ selectedCampaignAmdLabel }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t('Native AVMD keeps voicemail detection inside FreeSWITCH without depending on a webhook.') }}</p>
                        </article>
                        <article class="rounded-3xl border p-4" :class="selectedCampaign?.max_inflight_calls ? 'border-violet-200 bg-violet-50' : 'border-slate-200 bg-slate-50'">
                            <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Concurrent call ceiling') }}</div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ selectedCampaignInflightLabel }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ t('Use this ceiling to stop automated campaigns from creating more live calls than the floor can absorb.') }}</p>
                        </article>
                    </div>

                    <div class="mt-5 grid gap-6 xl:grid-cols-[minmax(0,1fr),330px]">
                        <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                            <div>
                                <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Execution lane') }}</div>
                                <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ selectedCampaign?.name || t('Choose campaign') }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ selectedCampaignExecutionHelp }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-3xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                    <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Queued leads') }}</div>
                                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ formatNumber(selectedCampaign?.queued_leads_count || 0) }}</div>
                                </div>
                                <div class="rounded-3xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                    <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Calling') }}</div>
                                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ formatNumber(selectedCampaign?.calling_leads_count || 0) }}</div>
                                </div>
                                <div class="rounded-3xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                    <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Daily retry limit') }}</div>
                                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ formatNumber(selectedCampaign?.daily_retry_limit || 0) }}</div>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3">
                                <button type="button" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="loadPreviewLead">{{ t('Load preview lead') }}</button>
                                <button type="button" class="rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white" @click="startCallFromWorkspace">{{ t('Call now') }}</button>
                                <button type="button" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white" @click="runCampaignCycle()">{{ t('Run cycle') }}</button>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Next preview lead') }}</div>
                                    <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ previewLead?.name || t('No preview lead loaded.') }}</h3>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ selectedLeadSourceLabel }}</span>
                            </div>

                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <div class="rounded-3xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                                    <div class="font-semibold text-slate-900">{{ t('Phone number') }}</div>
                                    <div class="mt-1">{{ activeLead?.phone_number || '-' }}</div>
                                </div>
                                <div class="rounded-3xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                                    <div class="font-semibold text-slate-900">{{ t('Company') }}</div>
                                    <div class="mt-1">{{ activeLead?.company || t('No company') }}</div>
                                </div>
                                <div class="rounded-3xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                                    <div class="font-semibold text-slate-900">{{ t('Email') }}</div>
                                    <div class="mt-1">{{ activeLead?.email || '-' }}</div>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <label class="text-sm font-semibold text-slate-700">{{ t('Selected lead') }}</label>
                                <select v-model="selectedLeadUuid" class="w-full rounded-2xl border-slate-300 bg-slate-50 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option value="">{{ t('Choose lead') }}</option>
                                    <option v-for="lead in leads" :key="lead.uuid" :value="lead.uuid">{{ lead.name }} | {{ lead.phone_number }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Campaign command board') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Move from campaign to campaign with live visibility into queue handoff, pacing ceiling, AMD posture, and current load.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ readyCampaignCount }} {{ t('ready') }}</div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                        <div class="grid grid-cols-[minmax(0,1.2fr),110px,150px,100px,100px,210px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <div>{{ t('Campaign') }}</div>
                            <div>{{ t('Status') }}</div>
                            <div>{{ t('Queue') }}</div>
                            <div>{{ t('Queued leads') }}</div>
                            <div>AMD</div>
                            <div>{{ t('Actions') }}</div>
                        </div>
                        <div v-if="campaignRows.length" class="divide-y divide-slate-200">
                            <div v-for="campaign in campaignRows" :key="campaign.uuid" class="grid grid-cols-[minmax(0,1.2fr),110px,150px,100px,100px,210px] gap-3 px-4 py-4 text-sm">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ campaign.name }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">{{ t(campaign.mode) }} | {{ campaign.compliance_profile_name || t('National baseline') }}</div>
                                </div>
                                <div>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="campaign.status === 'active' ? 'bg-emerald-100 text-emerald-700' : (campaign.status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700')">
                                        {{ t(campaign.status) }}
                                    </span>
                                </div>
                                <div class="text-slate-600">{{ campaign.queue_label || t('No queue') }}</div>
                                <div class="font-semibold text-slate-900">{{ formatNumber(campaign.queued_leads_count || 0) }}</div>
                                <div class="text-slate-600">{{ campaign.amd_enabled ? t(campaign.amd_strategy === 'external-webhook' ? 'External webhook' : 'Native AVMD') : t('Disabled') }}</div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="focusCampaign(campaign.uuid)">{{ t('Operate') }}</button>
                                    <button type="button" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" @click="runCampaignCycle(campaign.uuid)">{{ t('Run cycle') }}</button>
                                    <button type="button" class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700" @click="emit('openStudio', campaign.uuid)">{{ t('Edit') }}</button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No campaigns are visible right now.') }}</div>
                    </div>
                </section>
            </section>

            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Governance board') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Surface the items that make a campaign safe to launch before the floor feels the impact.') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="item in governanceChecklist" :key="item.label" class="rounded-3xl border p-4" :class="item.done ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold text-slate-900">{{ t(item.label) }}</div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="item.done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ t(item.done ? 'Ready' : 'Attention') }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t(item.help) }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Operational alerts') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Do not hide high-risk configuration signals behind another tab. Keep them visible during launch.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ formatNumber(alertRows.length) }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="alert in alertRows" :key="`${alert.title}-${alert.description}`" class="rounded-3xl border p-4" :class="alert.severity === 'critical' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
                            <div class="font-semibold text-slate-900">{{ t(alert.title) }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ t(alert.description) }}</p>
                        </article>
                        <div v-if="!alertRows.length" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-sm text-emerald-700">{{ t('No dialer alerts require attention right now.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Recent dial flow') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Keep the latest outcome stream visible so supervisors can spot voicemail, busy, or invalid-number patterns quickly.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ formatNumber(attempts.length) }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="attempt in recentAttempts" :key="attempt.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ attempt.lead_name || attempt.destination_number }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">{{ attempt.campaign_name || '-' }} | {{ attempt.destination_number }}</div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ attempt.disposition || attempt.hangup_cause || '-' }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-500">
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">AMD: <span class="font-semibold text-slate-900">{{ attempt.amd_result || '-' }}</span></div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">{{ t('Talk') }}: <span class="font-semibold text-slate-900">{{ attempt.talk_seconds || 0 }}s</span></div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">{{ t('Waiting') }}: <span class="font-semibold text-slate-900">{{ attempt.wait_seconds || 0 }}s</span></div>
                            </div>
                        </article>
                        <div v-if="!recentAttempts.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No recent attempts are visible right now.') }}</div>
                    </div>
                </section>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    leads: { type: Array, default: () => [] },
    attempts: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    alerts: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['openStudio'])
const { t, formatNumber } = useLocale()

const message = reactive({ type: 'success', text: '' })
const selectedCampaignUuid = ref(props.campaigns?.[0]?.uuid || '')
const selectedExtensionUuid = ref(props.options?.extensions?.[0]?.value || '')
const selectedLeadUuid = ref(props.leads?.[0]?.uuid || '')
const previewLead = ref(null)

const selectedCampaign = computed(() => props.campaigns.find((campaign) => campaign.uuid === selectedCampaignUuid.value) || null)
const selectedLead = computed(() => props.leads.find((lead) => lead.uuid === selectedLeadUuid.value) || null)
const activeLead = computed(() => previewLead.value || selectedLead.value || null)
const readyCampaignCount = computed(() => props.campaigns.filter((campaign) => campaign.status === 'active' && (!['progressive', 'power'].includes(campaign.mode) || campaign.call_center_queue_uuid)).length)
const campaignRows = computed(() => [...props.campaigns].sort((left, right) => {
    if (left.status === right.status) return (right.queued_leads_count || 0) - (left.queued_leads_count || 0)
    if (left.status === 'active') return -1
    if (right.status === 'active') return 1
    return left.name.localeCompare(right.name)
}))
const recentAttempts = computed(() => props.attempts.slice(0, 8))
const alertRows = computed(() => props.alerts || [])

const summaryCards = computed(() => ([
    { label: 'Ready to launch', value: formatNumber(readyCampaignCount.value), help: 'Campaigns already aligned with their routing prerequisites.', tone: 'border-emerald-100 bg-emerald-50', kickerTone: 'text-emerald-700', valueTone: 'text-emerald-700', helpTone: 'text-emerald-700/80' },
    { label: 'Queued leads', value: formatNumber(props.campaigns.reduce((total, campaign) => total + Number(campaign.queued_leads_count || 0), 0)), help: 'Contacts still waiting to enter the dial flow.', tone: 'border-cyan-100 bg-cyan-50', kickerTone: 'text-cyan-700', valueTone: 'text-cyan-700', helpTone: 'text-cyan-700/80' },
    { label: 'Calling', value: formatNumber(props.campaigns.reduce((total, campaign) => total + Number(campaign.calling_leads_count || 0), 0)), help: 'Calls currently in progress across the dialer.', tone: 'border-violet-100 bg-violet-50', kickerTone: 'text-violet-700', valueTone: 'text-violet-700', helpTone: 'text-violet-700/80' },
    { label: 'Native AVMD', value: formatNumber(props.campaigns.filter((campaign) => campaign.amd_enabled && campaign.amd_strategy === 'avmd').length), help: 'Campaigns using native FreeSWITCH voicemail detection.', tone: 'border-amber-100 bg-amber-50', kickerTone: 'text-amber-700', valueTone: 'text-amber-700', helpTone: 'text-amber-700/80' },
    { label: 'Recent attempts', value: formatNumber(props.attempts.length), help: 'Latest attempt stream available to the operator.', tone: 'border-slate-200 bg-slate-50', kickerTone: 'text-slate-500', valueTone: 'text-slate-900', helpTone: 'text-slate-500' },
    { label: 'Open alerts', value: formatNumber(alertRows.value.length), help: 'Configuration items the floor should not ignore.', tone: 'border-rose-100 bg-rose-50', kickerTone: 'text-rose-700', valueTone: 'text-rose-700', helpTone: 'text-rose-700/80' },
]))

const selectedCampaignModeLabel = computed(() => selectedCampaign.value ? t(selectedCampaign.value.mode) : t('Choose campaign'))
const selectedCampaignQueueLabel = computed(() => selectedCampaign.value?.queue_label || t('No queue'))
const selectedCampaignAmdLabel = computed(() => !selectedCampaign.value?.amd_enabled ? t('Disabled') : t(selectedCampaign.value.amd_strategy === 'external-webhook' ? 'External webhook' : 'Native AVMD'))
const selectedCampaignInflightLabel = computed(() => selectedCampaign.value?.max_inflight_calls ? formatNumber(selectedCampaign.value.max_inflight_calls) : t('Driven by pacing'))
const selectedLeadSourceLabel = computed(() => previewLead.value ? t('Preview') : t('Lead inventory'))
const selectedCampaignExecutionHelp = computed(() => {
    if (!selectedCampaign.value) return t('Choose a campaign to start operating the dialer desk.')
    if (['progressive', 'power'].includes(selectedCampaign.value.mode)) return t('This campaign is automated. Review queue handoff, AMD posture, and inflight guardrails before triggering a new cycle.')
    if (selectedCampaign.value.mode === 'preview') return t('This campaign is running in preview mode. Load the next lead, review the record, and then place the call with the chosen agent extension.')
    return t('This campaign is running in manual mode. Select a lead and extension, then dispatch the call with full operator control.')
})

const governanceChecklist = computed(() => {
    const campaign = selectedCampaign.value
    if (!campaign) {
        return [
            { label: 'Campaign selected', done: false, help: 'Choose the campaign that the floor is about to operate.' },
            { label: 'Agent extension selected', done: false, help: 'Pick the extension that will own manual or preview calls.' },
        ]
    }

    return [
        { label: 'Campaign selected', done: true, help: 'The workspace is already focused on one campaign, so the floor is not acting blindly.' },
        { label: 'Queue handoff', done: !['progressive', 'power'].includes(campaign.mode) || Boolean(campaign.call_center_queue_uuid), help: 'Progressive and power should always have a staffed queue ready to receive live answers.' },
        { label: 'Do-not-call protection', done: Boolean(campaign.respect_dnc), help: 'Keep DNC protection enabled unless a legal exception has been documented outside the dialer.' },
        { label: 'AMD operating model', done: !campaign.amd_enabled || campaign.amd_strategy === 'avmd' || Boolean(campaign.webhook_url), help: 'AMD should use native AVMD or a fully configured external webhook before the campaign goes live.' },
        { label: 'Inflight guardrail', done: !['progressive', 'power'].includes(campaign.mode) || Boolean(campaign.max_inflight_calls) || Number(campaign.pacing_ratio || 0) > 0, help: 'Automated modes should have either a clear inflight ceiling or a deliberate pacing configuration.' },
        { label: 'Agent extension selected', done: Boolean(selectedExtensionUuid.value), help: 'Manual and preview calls need a selected extension before launch.' },
    ]
})

watch(selectedCampaign, () => {
    previewLead.value = null
})

const setMessage = (type, text) => {
    message.type = type
    message.text = text
}

const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const routeFor = (template, token, value) => (template || '').replace(token, value)
const requireCampaign = () => {
    if (!selectedCampaign.value) {
        setMessage('error', t('Select a campaign first.'))
        return false
    }

    return true
}
const requireExtension = () => {
    if (!selectedExtensionUuid.value) {
        setMessage('error', t('Select an extension before placing the preview call.'))
        return false
    }

    return true
}

const focusCampaign = (campaignUuid) => {
    selectedCampaignUuid.value = campaignUuid
}

const openSelectedCampaignStudio = () => {
    if (!requireCampaign()) return
    emit('openStudio', selectedCampaign.value.uuid)
}

const loadPreviewLead = async () => {
    if (!requireCampaign()) return

    try {
        const response = await axios.get(routeFor(props.routes.previewCampaign || '/dialer/campaigns/__CAMPAIGN__/preview', '__CAMPAIGN__', selectedCampaign.value.uuid))
        previewLead.value = response.data?.lead || null
        if (previewLead.value?.uuid) selectedLeadUuid.value = previewLead.value.uuid
        setMessage('success', previewLead.value ? t('Preview lead loaded successfully.') : t('No leads are available in this campaign right now.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to load the next lead right now.'))
    }
}

const startCallFromWorkspace = async () => {
    if (!requireCampaign() || !requireExtension()) return

    const leadUuid = activeLead.value?.uuid || selectedLeadUuid.value
    if (!leadUuid) return setMessage('error', t('Select a lead or load the next preview record before placing the call.'))

    try {
        const response = await axios.post(routeFor(props.routes.dialCampaign || '/dialer/campaigns/__CAMPAIGN__/dial', '__CAMPAIGN__', selectedCampaign.value.uuid), {
            lead_uuid: leadUuid,
            extension_uuid: selectedExtensionUuid.value,
        })
        setMessage('success', response.data?.messages?.success?.[0] || t('Call dispatched to FreeSWITCH.'))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.'))
    }
}

const runCampaignCycle = async (campaignUuid = null) => {
    const targetUuid = campaignUuid || selectedCampaign.value?.uuid
    if (!targetUuid) return setMessage('error', t('Select a campaign first.'))

    try {
        const response = await axios.post(routeFor(props.routes.runCampaign || '/dialer/campaigns/__CAMPAIGN__/run', '__CAMPAIGN__', targetUuid))
        setMessage('success', response.data?.messages?.success?.[0] || t('Campaign cycle processed.'))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to run the campaign right now.'))
    }
}
</script>

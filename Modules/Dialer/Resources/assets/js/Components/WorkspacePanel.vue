<template>
    <div class="grid gap-6 xl:grid-cols-[360px,minmax(0,1fr),360px]">
        <section class="rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Campaign rail') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Keep active campaigns visible and jump directly into the next action.') }}</p>
                </div>
                <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="$emit('openStudio', '')">{{ t('New') }}</button>
            </div>
            <div class="mt-5 space-y-3">
                <button v-for="campaign in campaigns" :key="campaign.uuid" type="button" class="w-full rounded-3xl border p-4 text-left transition" :class="selectedCampaignUuid === campaign.uuid ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50'" @click="selectedCampaignUuid = campaign.uuid">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">{{ campaign.name }}</div>
                            <div class="mt-1 text-xs" :class="selectedCampaignUuid === campaign.uuid ? 'text-slate-300' : 'text-slate-500'">{{ t(campaign.mode) }} | {{ t(campaign.status) }}</div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/10 text-slate-100' : 'bg-white text-slate-700 ring-1 ring-slate-200'">{{ campaign.compliance_profile_name || t('National baseline') }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-2xl px-3 py-2" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/5' : 'bg-white ring-1 ring-slate-200'">{{ t('Queued leads') }}: <span class="font-semibold">{{ campaign.queued_leads_count }}</span></div>
                        <div class="rounded-2xl px-3 py-2" :class="selectedCampaignUuid === campaign.uuid ? 'bg-white/5' : 'bg-white ring-1 ring-slate-200'">{{ t('Calling') }}: <span class="font-semibold">{{ campaign.calling_leads_count }}</span></div>
                    </div>
                </button>
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Agent command desk') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('Preview leads, launch manual calls, or run automated cycles from one place.') }}</p>
                    </div>
                    <button v-if="selectedCampaign" type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="$emit('openStudio', selectedCampaign.uuid)">{{ t('Open campaign studio') }}</button>
                </div>

                <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                    {{ message.text }}
                </div>

                <div v-if="selectedCampaign" class="mt-5 grid gap-5 lg:grid-cols-[1.1fr,0.9fr]">
                    <div class="space-y-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ t('Selected campaign') }}</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ selectedCampaign.name }}</div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-slate-950 px-3 py-1 font-semibold text-white">{{ t(selectedCampaign.mode) }}</span>
                                <span class="rounded-full bg-white px-3 py-1 font-semibold text-slate-700 ring-1 ring-slate-200">{{ selectedCampaign.queue_label || t('No queue') }}</span>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <span>{{ t('Agent extension') }}</span>
                                    <HelpTooltip :text="t('Select the extension that receives preview calls or originates assisted calls.')"/>
                                </label>
                                <select v-model="operation.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option value="">{{ t('Select an extension') }}</option>
                                    <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <span>{{ t('Lead focus') }}</span>
                                    <HelpTooltip :text="t('Choose a lead for manual action or leave it blank and let preview fetch the next callable record.')"/>
                                </label>
                                <select v-model="operation.lead_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                                    <option value="">{{ t('Select a lead') }}</option>
                                    <option v-for="lead in leads" :key="lead.uuid" :value="lead.uuid">{{ lead.name }} - {{ lead.phone_number }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <button type="button" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="previewCampaign">{{ t('Preview next lead') }}</button>
                            <button type="button" class="rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white" @click="launchPreviewCall">{{ t('Place preview call') }}</button>
                            <button type="button" class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200" @click="manualDial">{{ t('Dial selected lead') }}</button>
                            <button type="button" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white" @click="runCampaign">{{ t('Run automated cycle') }}</button>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-[#fffaf0] p-4">
                        <div class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ t('Preview workspace') }}</div>
                        <div class="mt-3 text-xl font-semibold text-slate-900">{{ previewLead?.name || t('Fetch the next callable lead to show the agent context before dialing.') }}</div>
                        <div v-if="previewLead" class="mt-4 space-y-1 text-sm text-slate-600">
                            <div>{{ previewLead.phone_number }}</div>
                            <div>{{ previewLead.company || t('No company') }}</div>
                            <div>{{ previewLead.email || '-' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Recent dialing flow') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="attempt in attempts.slice(0, 5)" :key="attempt.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="font-semibold text-slate-900">{{ attempt.lead_name || attempt.destination_number }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ attempt.campaign_name || '-' }} | {{ attempt.destination_number }} | {{ t(attempt.mode) }}</div>
                    </article>
                </div>
            </section>
        </div>

        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Readiness checklist') }}</h2>
                <div class="mt-5 space-y-3">
                    <div v-for="item in readinessItems" :key="item.label" class="flex items-start justify-between gap-3 rounded-3xl border px-4 py-3" :class="item.ok ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                        <div>
                            <div class="text-sm font-semibold" :class="item.ok ? 'text-emerald-700' : 'text-amber-700'">{{ t(item.label) }}</div>
                            <div class="mt-1 text-xs leading-5" :class="item.ok ? 'text-emerald-700/80' : 'text-amber-700/80'">{{ t(item.hint) }}</div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="item.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ item.ok ? t('Ready') : t('Review') }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Operational alerts') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="alert in localAlerts" :key="alert.title + alert.description" class="rounded-3xl border px-4 py-4" :class="alert.severity === 'critical' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
                        <div class="text-sm font-semibold" :class="alert.severity === 'critical' ? 'text-rose-700' : 'text-amber-700'">{{ t(alert.title) }}</div>
                        <div class="mt-1 text-xs leading-5" :class="alert.severity === 'critical' ? 'text-rose-700/80' : 'text-amber-700/80'">{{ t(alert.description) }}</div>
                    </article>
                    <div v-if="!localAlerts.length" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-6 text-sm text-emerald-700">{{ t('No dialer alerts detected right now.') }}</div>
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
    leads: { type: Array, default: () => [] },
    attempts: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    alerts: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
})

defineEmits(['openStudio'])

const { t } = useLocale()
const selectedCampaignUuid = ref(props.campaigns?.[0]?.uuid || '')
const previewLead = ref(null)
const message = reactive({ type: 'success', text: '' })
const operation = reactive({ extension_uuid: props.options?.extensions?.[0]?.value || '', lead_uuid: props.leads?.[0]?.uuid || '' })

const selectedCampaign = computed(() => props.campaigns.find((campaign) => campaign.uuid === selectedCampaignUuid.value) || null)
const localAlerts = computed(() => props.alerts || [])
const readinessItems = computed(() => {
    const campaign = selectedCampaign.value
    if (!campaign) return []
    return [
        { label: 'Caller ID configured', hint: 'Agents should present a validated outbound identity.', ok: Boolean(campaign.caller_id_number || campaign.caller_id_name) },
        { label: 'Queue handoff ready', hint: 'Progressive and power need a staffed queue.', ok: campaign.mode === 'manual' || campaign.mode === 'preview' || Boolean(campaign.call_center_queue_uuid) },
        { label: 'Compliance anchor set', hint: 'Use a state baseline or a custom compliance profile.', ok: Boolean(campaign.default_state_code || campaign.compliance_profile_name) },
        { label: 'Retry policy configured', hint: 'Backoff and daily cap should be explicit.', ok: Number(campaign.retry_backoff_minutes || 0) > 0 && Number(campaign.daily_retry_limit || 0) > 0 },
    ]
})
watch(selectedCampaignUuid, () => {
    previewLead.value = null
})

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const previewCampaign = async () => {
    if (!selectedCampaignUuid.value) {
        return setMessage('error', t('Select a campaign before previewing leads.'))
    }

    try {
        const response = await axios.get(routeFor(props.routes.previewCampaign || '/dialer/campaigns/__CAMPAIGN__/preview', '__CAMPAIGN__', selectedCampaignUuid.value))
        previewLead.value = response.data?.lead || null
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to fetch the preview lead right now.'))
    }
}
const manualDial = async () => {
    if (!selectedCampaignUuid.value) {
        return setMessage('error', t('Select a campaign before placing the manual call.'))
    }

    if (!operation.extension_uuid || !operation.lead_uuid) {
        return setMessage('error', t('Select a lead and an extension before placing the manual call.'))
    }

    try {
        await axios.post(routeFor(props.routes.dialCampaign || '/dialer/campaigns/__CAMPAIGN__/dial', '__CAMPAIGN__', selectedCampaignUuid.value), {
            extension_uuid: operation.extension_uuid,
            lead_uuid: operation.lead_uuid,
        })
        setMessage('success', t('Call dispatched to FreeSWITCH.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.'))
    }
}
const launchPreviewCall = async () => {
    if (!selectedCampaignUuid.value) {
        return setMessage('error', t('Select a campaign before placing the preview call.'))
    }

    if (!operation.extension_uuid) {
        return setMessage('error', t('Select an extension before placing the preview call.'))
    }

    if (!previewLead.value) {
        await previewCampaign()
    }

    if (!previewLead.value) {
        return setMessage('error', t('No callable preview lead is available right now.'))
    }

    try {
        await axios.post(routeFor(props.routes.dialCampaign || '/dialer/campaigns/__CAMPAIGN__/dial', '__CAMPAIGN__', selectedCampaignUuid.value), {
            extension_uuid: operation.extension_uuid,
            lead_uuid: previewLead.value.uuid,
        })
        setMessage('success', t('Call dispatched to FreeSWITCH.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.'))
    }
}
const runCampaign = async () => {
    if (!selectedCampaignUuid.value) {
        return setMessage('error', t('Select a campaign before running the automated cycle.'))
    }

    try {
        const response = await axios.post(routeFor(props.routes.runCampaign || '/dialer/campaigns/__CAMPAIGN__/run', '__CAMPAIGN__', selectedCampaignUuid.value))
        setMessage('success', response.data?.messages?.success?.[0] || t('Campaign cycle processed.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to run the campaign right now.'))
    }
}
</script>

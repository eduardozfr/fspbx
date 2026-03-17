<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),380px]">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ editingCampaignUuid ? t('Update campaign') : t('Campaign studio') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Create campaigns with clearer sections for routing, pacing, compliance, and automation.') }}</p>
                </div>
                <button v-if="editingCampaignUuid" type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="resetCampaignForm">{{ t('Cancel editing') }}</button>
            </div>

            <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                {{ message.text }}
            </div>

            <form class="mt-6 space-y-6" @submit.prevent="submitCampaign">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span>{{ t('Campaign name') }}</span>
                            <HelpTooltip :text="t('Use an operational name that already tells supervisors what the list is and who owns it.')"/>
                        </label>
                        <input v-model="campaignForm.name" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Example: Retention | Fiber | SP capital | Mar 2026')" />
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span>{{ t('Campaign status') }}</span>
                            <HelpTooltip :text="t('Draft keeps the campaign editable, active allows dialing, paused freezes execution, and completed closes the operation.')"/>
                        </label>
                        <select v-model="campaignForm.status" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                            <option v-for="status in options.statuses || []" :key="status.value" :value="status.value">{{ t(status.label) }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span>{{ t('Dialing mode') }}</span>
                            <HelpTooltip :text="t('Manual and preview are agent-assisted. Progressive and power require a staffed queue for answered calls.')"/>
                        </label>
                        <select v-model="campaignForm.mode" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                            <option v-for="mode in options.modes || []" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option>
                        </select>
                    </div>
                    <input v-model="campaignForm.caller_id_name" type="text" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Caller ID name')" />
                    <input v-model="campaignForm.caller_id_number" type="text" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Caller ID number')" />
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span>{{ t('Queue handoff') }}</span>
                            <HelpTooltip :text="t('Choose the call center queue that receives answered calls from progressive or power campaigns.')"/>
                        </label>
                        <select v-model="campaignForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                            <option value="">{{ t('No queue handoff configured') }}</option>
                            <option v-for="queue in options.queues || []" :key="queue.value" :value="queue.value">{{ queue.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span>{{ t('Compliance profile') }}</span>
                            <HelpTooltip :text="t('Apply a named governance profile when the operation needs a schedule different from the default UF baseline.')"/>
                        </label>
                        <select v-model="campaignForm.dialer_compliance_profile_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950">
                            <option value="">{{ t('National baseline') }}</option>
                            <option v-for="profile in complianceProfiles" :key="profile.uuid" :value="profile.uuid">{{ profile.name }}</option>
                        </select>
                    </div>
                    <select v-model="campaignForm.default_state_code" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" @change="syncCampaignTimezone">
                        <option value="">{{ t('No state') }}</option>
                        <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                    </select>
                    <input v-model="campaignForm.default_timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Default timezone')" />
                    <input v-model.number="campaignForm.max_attempts" type="number" min="1" max="25" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Maximum attempts')" />
                    <input v-model.number="campaignForm.daily_retry_limit" type="number" min="1" max="25" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Daily retry limit')" />
                    <input v-model.number="campaignForm.retry_backoff_minutes" type="number" min="1" max="43200" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Retry backoff (minutes)')" />
                    <input v-model.number="campaignForm.preview_seconds" type="number" min="5" max="3600" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Preview seconds')" />
                    <input v-model.number="campaignForm.originate_timeout" type="number" min="5" max="120" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Originate timeout')" />
                    <input v-model.number="campaignForm.pacing_ratio" type="number" min="1" max="10" step="0.1" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Pacing ratio')" />
                    <input v-model="campaignForm.webhook_url" type="url" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950 md:col-span-2" :placeholder="t('Webhook URL')" />
                    <input v-model="campaignForm.webhook_secret" type="text" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('Webhook secret')" />
                    <input v-model="campaignForm.amd_strategy" type="text" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :placeholder="t('AMD strategy')" />
                    <textarea v-model="campaignForm.description" rows="3" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950 md:col-span-2" :placeholder="t('Description')" />
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.respect_dnc" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Respect do-not-call list') }}</span></label>
                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="campaignForm.amd_enabled" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Enable AMD / voicemail detection') }}</span></label>
                </div>
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
        </section>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    complianceProfiles: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    selectedCampaignUuid: { type: String, default: '' },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const editingCampaignUuid = ref(null)
const campaignForm = reactive({ name: '', description: '', mode: 'manual', status: 'draft', caller_id_name: '', caller_id_number: '', outbound_prefix: '', call_center_queue_uuid: '', dialer_compliance_profile_uuid: '', pacing_ratio: 1, preview_seconds: 30, originate_timeout: 30, max_attempts: 3, default_state_code: '', default_timezone: '', retry_backoff_minutes: 30, daily_retry_limit: 3, respect_dnc: true, amd_enabled: false, amd_strategy: 'webhook', webhook_url: '', webhook_secret: '', callback_disposition_code: 'callback', voicemail_disposition_code: 'voicemail' })

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = () => window.location.reload()
const stateMap = () => Object.fromEntries((props.options?.states || []).map((state) => [state.value, state]))
const syncCampaignTimezone = () => { if (stateMap()[campaignForm.default_state_code]) campaignForm.default_timezone = stateMap()[campaignForm.default_state_code].timezone }
const resetCampaignForm = () => { editingCampaignUuid.value = null; Object.assign(campaignForm, { name: '', description: '', mode: 'manual', status: 'draft', caller_id_name: '', caller_id_number: '', outbound_prefix: '', call_center_queue_uuid: '', dialer_compliance_profile_uuid: '', pacing_ratio: 1, preview_seconds: 30, originate_timeout: 30, max_attempts: 3, default_state_code: '', default_timezone: '', retry_backoff_minutes: 30, daily_retry_limit: 3, respect_dnc: true, amd_enabled: false, amd_strategy: 'webhook', webhook_url: '', webhook_secret: '', callback_disposition_code: 'callback', voicemail_disposition_code: 'voicemail' }) }
const editCampaign = (campaign) => { editingCampaignUuid.value = campaign.uuid; Object.assign(campaignForm, { ...campaign, call_center_queue_uuid: campaign.call_center_queue_uuid || '', dialer_compliance_profile_uuid: campaign.dialer_compliance_profile_uuid || '', respect_dnc: Boolean(campaign.respect_dnc), amd_enabled: Boolean(campaign.amd_enabled), callback_disposition_code: campaign.callback_disposition_code || 'callback', voicemail_disposition_code: campaign.voicemail_disposition_code || 'voicemail' }) }
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

    if (['progressive', 'power'].includes(campaignForm.mode) && !campaignForm.call_center_queue_uuid) {
        return setMessage('error', t('Select a queue before using progressive or power dialing.'))
    }

    try {
        await axios[editingCampaignUuid.value ? 'put' : 'post'](
            editingCampaignUuid.value
                ? routeFor(props.routes.updateCampaign || '/dialer/campaigns/__CAMPAIGN__', '__CAMPAIGN__', editingCampaignUuid.value)
                : (props.routes.storeCampaign || '/dialer/campaigns'),
            campaignForm
        )
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || error.response?.data?.message || t('Unable to save the campaign right now.'))
    }
}
const deleteCampaign = async (uuid) => {
    if (!window.confirm(t('Delete this campaign?'))) return
    try {
        await axios.delete(routeFor(props.routes.destroyCampaign || '/dialer/campaigns/__CAMPAIGN__', '__CAMPAIGN__', uuid))
        reloadPage()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the campaign right now.'))
    }
}
</script>

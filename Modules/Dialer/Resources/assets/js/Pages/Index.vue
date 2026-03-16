<template>
    <MainLayout>
        <main class="mx-auto max-w-8xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-slate-900 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.3em] text-amber-100">{{ t('Dialer') }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ t('Compliance-aware outbound automation for contact center teams') }}</h1>
                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-2xl bg-white/10 px-4 py-3"><div class="text-xs uppercase tracking-wide text-amber-100">{{ t('Campaigns') }}</div><div class="mt-2 text-2xl font-semibold">{{ campaigns.length }}</div></div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3"><div class="text-xs uppercase tracking-wide text-amber-100">{{ t('Leads') }}</div><div class="mt-2 text-2xl font-semibold">{{ leads.length }}</div></div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3"><div class="text-xs uppercase tracking-wide text-amber-100">{{ t('Attempts') }}</div><div class="mt-2 text-2xl font-semibold">{{ attempts.length }}</div></div>
                    <div class="rounded-2xl bg-rose-400/20 px-4 py-3"><div class="text-xs uppercase tracking-wide text-rose-100">{{ t('DNC') }}</div><div class="mt-2 text-2xl font-semibold">{{ dncEntries.length }}</div></div>
                </div>
            </section>

            <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">{{ message.text }}</div>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ editingCampaignId ? t('Edit campaign') : t('Create campaign') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitCampaign">
                        <input v-model="campaignForm.name" type="text" :placeholder="t('Campaign name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <textarea v-model="campaignForm.description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                        <div class="grid gap-3 md:grid-cols-3">
                            <input v-model="campaignForm.caller_id_name" type="text" :placeholder="t('Caller ID name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.caller_id_number" type="text" :placeholder="t('Caller ID number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.outbound_prefix" type="text" :placeholder="t('Outbound prefix')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <select v-model="campaignForm.mode" class="w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="mode in options.modes" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option></select>
                            <select v-model="campaignForm.status" class="w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="status in options.statuses" :key="status.value" :value="status.value">{{ t(status.label) }}</option></select>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <select v-model="campaignForm.default_state_code" class="w-full rounded-2xl border-slate-300 shadow-sm" @change="syncCampaignTimezone"><option value="">{{ t('Default state') }}</option><option v-for="state in options.states" :key="state.value" :value="state.value">{{ state.label }}</option></select>
                            <input v-model="campaignForm.default_timezone" type="text" :placeholder="t('Default timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-5">
                            <input v-model.number="campaignForm.max_attempts" type="number" min="1" :placeholder="t('Maximum attempts')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model.number="campaignForm.retry_backoff_minutes" type="number" min="1" :placeholder="t('Retry backoff (minutes)')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model.number="campaignForm.daily_retry_limit" type="number" min="1" :placeholder="t('Daily retry limit')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model.number="campaignForm.originate_timeout" type="number" min="5" :placeholder="t('Originate timeout')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <select v-model="campaignForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Queue for connected calls') }}</option><option v-for="queue in options.queues" :key="queue.value" :value="queue.value">{{ queue.label }}</option></select>
                        </div>
                        <div class="grid gap-3 md:grid-cols-4">
                            <input v-model.number="campaignForm.pacing_ratio" type="number" min="1" step="0.1" :placeholder="t('Pacing ratio')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model.number="campaignForm.preview_seconds" type="number" min="5" :placeholder="t('Preview seconds')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.callback_disposition_code" type="text" :placeholder="t('Callback disposition code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.voicemail_disposition_code" type="text" :placeholder="t('Voicemail disposition code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-3">
                            <input v-model="campaignForm.amd_strategy" type="text" :placeholder="t('AMD strategy')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.webhook_url" type="url" :placeholder="t('Webhook URL')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="campaignForm.webhook_secret" type="text" :placeholder="t('Webhook secret')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                            <label class="inline-flex items-center gap-2"><input v-model="campaignForm.respect_dnc" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Respect do-not-call list') }}</span></label>
                            <label class="inline-flex items-center gap-2"><input v-model="campaignForm.amd_enabled" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Enable AMD / voicemail detection') }}</span></label>
                        </div>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ editingCampaignId ? t('Update campaign') : t('Create campaign') }}</button>
                    </form>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Add lead') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitLead">
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="leadForm.first_name" type="text" :placeholder="t('First name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="leadForm.last_name" type="text" :placeholder="t('Last name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="leadForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="leadForm.email" type="email" :placeholder="t('Email')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <select v-model="leadForm.state_code" class="w-full rounded-2xl border-slate-300 shadow-sm" @change="syncLeadTimezone"><option value="">{{ t('Lead state') }}</option><option v-for="state in options.states" :key="state.value" :value="state.value">{{ state.label }}</option></select>
                            <input v-model="leadForm.timezone" type="text" :placeholder="t('Lead timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input v-model="leadForm.do_not_call" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Mark as do-not-call immediately') }}</span></label>
                        <select v-model="leadForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option></select>
                        <button type="submit" class="rounded-full bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Add lead to campaigns') }}</button>
                    </form>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Campaign list') }}</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="campaign in campaigns" :key="campaign.uuid" class="rounded-2xl border border-slate-200 p-4">
                            <div class="font-medium text-slate-900">{{ campaign.name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ t('Queued leads') }}: {{ campaign.queued_leads_count }} | {{ t('Retry') }}: {{ campaign.retry_backoff_minutes }}m | {{ campaign.default_state_code || t('Default state') }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ t(campaign.mode) }} | {{ t(campaign.status) }} | {{ t('Maximum attempts') }}: {{ campaign.max_attempts || '-' }}</div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="editCampaign(campaign)">{{ t('Edit') }}</button>
                                <button type="button" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700" @click="runCampaign(campaign)">{{ t('Run cycle') }}</button>
                                <button type="button" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700" @click="previewCampaign(campaign)">{{ t('Preview next lead') }}</button>
                                <button type="button" class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteCampaign(campaign.uuid)">{{ t('Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Manual dialing') }}</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <select v-model="manualCampaignUuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select campaign for manual dialing') }}</option><option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option></select>
                        <select v-model="manualExtensionUuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select agent extension') }}</option><option v-for="extension in options.extensions" :key="extension.value" :value="extension.value">{{ extension.label }}</option></select>
                    </div>
                    <div v-if="previewLead" class="mt-4 rounded-2xl bg-slate-900 p-4 text-white">
                        <div class="text-sm font-semibold">{{ previewLead.name }}</div>
                        <div class="text-xs text-slate-300">{{ previewLead.phone_number }}</div>
                        <select v-model="previewExtensionUuid" class="mt-3 w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-2 text-sm text-white">
                            <option value="" class="text-slate-900">{{ t('Select agent extension') }}</option>
                            <option v-for="extension in options.extensions" :key="extension.value" :value="extension.value" class="text-slate-900">{{ extension.label }}</option>
                        </select>
                        <button type="button" class="mt-3 rounded-full bg-orange-500 px-4 py-2 text-sm font-semibold text-white" @click="launchPreviewCall">{{ t('Call now') }}</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="lead in leads" :key="lead.uuid" class="rounded-2xl border border-slate-200 p-4">
                            <div class="font-medium text-slate-900">{{ lead.name }}</div>
                            <div class="text-xs text-slate-500">{{ lead.phone_number }} | {{ lead.state_code || t('No state') }} | {{ lead.timezone || t('No timezone') }}</div>
                            <div class="mt-3 flex gap-2">
                                <button type="button" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700" @click="manualDial(lead.uuid)">{{ t('Call now') }}</button>
                                <button type="button" class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteLead(lead.uuid)">{{ t('Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Dispositions') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitDisposition">
                        <input v-model="dispositionForm.name" type="text" :placeholder="t('Disposition name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="dispositionForm.code" type="text" :placeholder="t('Disposition code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model.number="dispositionForm.default_callback_offset_minutes" type="number" min="1" :placeholder="t('Default callback offset (minutes)')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <textarea v-model="dispositionForm.description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                        <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                            <label class="inline-flex items-center gap-2"><input v-model="dispositionForm.is_final" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Final disposition') }}</span></label>
                            <label class="inline-flex items-center gap-2"><input v-model="dispositionForm.is_callback" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Creates callback') }}</span></label>
                            <label class="inline-flex items-center gap-2"><input v-model="dispositionForm.mark_dnc" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Mark as do-not-call immediately') }}</span></label>
                        </div>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save disposition') }}</button>
                    </form>
                    <div class="mt-4 space-y-2 text-sm">
                        <div v-for="disposition in dispositions" :key="disposition.uuid" class="rounded-2xl border border-slate-200 p-3">
                            <div class="font-medium text-slate-900">{{ disposition.name }} ({{ disposition.code }})</div>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                <span v-if="disposition.is_final">{{ t('Final disposition') }}</span>
                                <span v-if="disposition.is_callback">{{ t('Creates callback') }}</span>
                                <span v-if="disposition.mark_dnc">{{ t('Respect do-not-call list') }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Do-not-call list') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitDnc">
                        <input v-model="dncForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="dncForm.reason" type="text" :placeholder="t('Reason')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <button type="submit" class="rounded-full bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save DNC entry') }}</button>
                    </form>
                    <div class="mt-4 space-y-2 text-sm">
                        <div v-for="entry in dncEntries" :key="entry.uuid" class="flex items-center justify-between rounded-2xl border border-slate-200 p-3">
                            <span>{{ entry.phone_number }}</span>
                            <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="deleteDnc(entry.uuid)">{{ t('Delete') }}</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('State dialing rules') }}</h2>
                    <select v-model="selectedStateRuleUuid" class="mt-4 w-full rounded-2xl border-slate-300 shadow-sm" @change="loadSelectedStateRule">
                        <option value="">{{ t('Create new rule') }}</option>
                        <option v-for="rule in stateRules" :key="rule.uuid" :value="rule.uuid">{{ rule.state_code }} - {{ rule.state_name }}</option>
                    </select>
                    <div v-if="currentStateRule" class="mt-3 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600">
                        <div>{{ currentStateRule.schedule_summary }}</div>
                        <a v-if="currentStateRule.legal_reference_url" :href="currentStateRule.legal_reference_url" target="_blank" rel="noreferrer" class="mt-2 inline-flex text-slate-900 underline">
                            {{ t('Legal reference') }}
                        </a>
                    </div>
                    <form class="mt-4 space-y-3" @submit.prevent="saveStateRule">
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="stateRuleForm.state_code" type="text" :placeholder="t('State code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="stateRuleForm.state_name" type="text" :placeholder="t('State name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <input v-model="stateRuleForm.timezone" type="text" :placeholder="t('Timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="stateRuleForm.weekday_start" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="stateRuleForm.weekday_end" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="stateRuleForm.saturday_start" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="stateRuleForm.saturday_end" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="stateRuleForm.sunday_start" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="stateRuleForm.sunday_end" type="time" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input v-model="stateRuleForm.saturday_enabled" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Enable Saturday dialing') }}</span></label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input v-model="stateRuleForm.sunday_enabled" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Enable Sunday dialing') }}</span></label>
                        <input v-model="stateRuleForm.legal_reference_url" type="url" :placeholder="t('Legal reference URL')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <textarea v-model="stateRuleForm.notes" rows="3" :placeholder="t('Compliance notes')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save state rule') }}</button>
                    </form>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.8fr,1.2fr]">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Lead import') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitImport">
                        <input type="file" accept=".csv,.txt,.xls,.xlsx" @change="handleImportFile" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <select v-model="importForm.default_state_code" class="w-full rounded-2xl border-slate-300 shadow-sm" @change="syncImportTimezone"><option value="">{{ t('Default state') }}</option><option v-for="state in options.states" :key="state.value" :value="state.value">{{ state.label }}</option></select>
                            <input v-model="importForm.default_timezone" type="text" :placeholder="t('Default timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <select v-model="importForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option></select>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Queue import') }}</button>
                    </form>
                    <div class="mt-4 space-y-2 text-sm">
                        <div v-for="batch in importBatches" :key="batch.uuid" class="rounded-2xl border border-slate-200 p-3">
                            <div class="font-medium text-slate-900">{{ batch.file_name }}</div>
                            <div class="text-slate-500">{{ t(batch.status) }} | {{ batch.imported_rows }} / {{ batch.total_rows }}</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Recent attempts') }}</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="attempt in attempts" :key="attempt.uuid" class="rounded-2xl border border-slate-200 p-4">
                            <div class="text-sm font-medium text-slate-900">{{ attempt.lead_name }} - {{ attempt.destination_number }}</div>
                            <div class="text-xs text-slate-500">{{ attempt.campaign_name }} | {{ attempt.disposition || t('Pending') }}</div>
                            <div class="mt-3 flex gap-2">
                                <select v-model="attemptDispositionForms[attempt.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm"><option value="">{{ t('Choose disposition') }}</option><option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.code">{{ disposition.name }}</option></select>
                                <button type="button" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white" @click="saveAttemptDisposition(attempt.uuid)">{{ t('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </section>
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
const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const editingCampaignId = ref(null)
const previewCampaignId = ref(null)
const previewLead = ref(null)
const previewExtensionUuid = ref('')
const manualCampaignUuid = ref('')
const manualExtensionUuid = ref('')
const selectedStateRuleUuid = ref(props.stateRules[0]?.uuid || '')
const importFile = ref(null)
const currentStateRule = computed(() => props.stateRules.find((rule) => rule.uuid === selectedStateRuleUuid.value) || null)

const campaignForm = reactive({
    name: '',
    description: '',
    caller_id_name: '',
    caller_id_number: '',
    outbound_prefix: '',
    mode: 'manual',
    status: 'draft',
    default_state_code: '',
    default_timezone: '',
    call_center_queue_uuid: '',
    pacing_ratio: 1,
    preview_seconds: 15,
    originate_timeout: 30,
    max_attempts: 5,
    retry_backoff_minutes: 30,
    daily_retry_limit: 3,
    respect_dnc: true,
    amd_enabled: false,
    amd_strategy: '',
    webhook_url: '',
    webhook_secret: '',
    callback_disposition_code: 'callback',
    voicemail_disposition_code: 'voicemail',
})
const leadForm = reactive({ first_name: '', last_name: '', phone_number: '', email: '', state_code: '', timezone: '', do_not_call: false, campaign_uuids: [] })
const dispositionForm = reactive({ name: '', code: '', is_final: false, is_callback: false, mark_dnc: false, default_callback_offset_minutes: '', description: '' })
const dncForm = reactive({ phone_number: '', reason: '' })
const importForm = reactive({ campaign_uuids: [], default_state_code: '', default_timezone: '' })
const stateRuleForm = reactive({ state_code: '', state_name: '', timezone: '', weekday_start: '08:00', weekday_end: '20:00', saturday_start: '08:00', saturday_end: '20:00', sunday_start: '08:00', sunday_end: '20:00', saturday_enabled: true, sunday_enabled: true, notes: '', legal_reference_url: '' })
const attemptDispositionForms = reactive(Object.fromEntries(props.attempts.map((attempt) => [attempt.uuid, attempt.disposition || ''])))

const getStateOption = (stateCode) => props.options.states.find((state) => state.value === stateCode) || null
const setMessage = (type, text) => { message.type = type; message.text = text }
const syncCampaignTimezone = () => { const state = getStateOption(campaignForm.default_state_code); if (state?.timezone) campaignForm.default_timezone = state.timezone }
const syncLeadTimezone = () => { const state = getStateOption(leadForm.state_code); if (state?.timezone) leadForm.timezone = state.timezone }
const syncImportTimezone = () => { const state = getStateOption(importForm.default_state_code); if (state?.timezone) importForm.default_timezone = state.timezone }
const editCampaign = (campaign) => { editingCampaignId.value = campaign.uuid; Object.assign(campaignForm, { ...campaign, max_attempts: campaign.max_attempts ?? 5, pacing_ratio: campaign.pacing_ratio ?? 1, preview_seconds: campaign.preview_seconds ?? 15, originate_timeout: campaign.originate_timeout ?? 30, callback_disposition_code: campaign.callback_disposition_code ?? 'callback', voicemail_disposition_code: campaign.voicemail_disposition_code ?? 'voicemail' }) }
const submitCampaign = async () => { try { editingCampaignId.value ? await axios.put(`/dialer/campaigns/${editingCampaignId.value}`, campaignForm) : await axios.post(props.routes.storeCampaign, campaignForm); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the campaign right now.')) } }
const submitLead = async () => { try { await axios.post(props.routes.storeLead, leadForm); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the lead right now.')) } }
const submitDisposition = async () => { try { await axios.post(props.routes.storeDisposition, dispositionForm); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.')) } }
const submitDnc = async () => { try { await axios.post(props.routes.storeDnc, dncForm); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the do-not-call entry right now.')) } }
const deleteDnc = async (uuid) => { try { await axios.delete(`/dialer/dnc/${uuid}`); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the do-not-call entry right now.')) } }
const loadSelectedStateRule = () => {
    const rule = currentStateRule.value
    if (!rule) {
        Object.assign(stateRuleForm, { state_code: '', state_name: '', timezone: '', weekday_start: '08:00', weekday_end: '20:00', saturday_start: '08:00', saturday_end: '20:00', sunday_start: '08:00', sunday_end: '20:00', saturday_enabled: true, sunday_enabled: true, notes: '', legal_reference_url: '' })
        return
    }
    Object.assign(stateRuleForm, {
        state_code: rule.state_code,
        state_name: rule.state_name,
        timezone: rule.timezone,
        weekday_start: rule.schedule?.monday?.start || '08:00',
        weekday_end: rule.schedule?.monday?.end || '20:00',
        saturday_start: rule.schedule?.saturday?.start || '08:00',
        saturday_end: rule.schedule?.saturday?.end || '20:00',
        sunday_start: rule.schedule?.sunday?.start || '08:00',
        sunday_end: rule.schedule?.sunday?.end || '20:00',
        saturday_enabled: rule.schedule?.saturday?.enabled ?? true,
        sunday_enabled: rule.schedule?.sunday?.enabled ?? true,
        notes: rule.notes || '',
        legal_reference_url: rule.legal_reference_url || '',
    })
}
const saveStateRule = async () => {
    const payload = {
        state_code: stateRuleForm.state_code,
        state_name: stateRuleForm.state_name,
        timezone: stateRuleForm.timezone,
        notes: stateRuleForm.notes,
        legal_reference_url: stateRuleForm.legal_reference_url,
        schedule: {
            monday: { enabled: true, start: stateRuleForm.weekday_start, end: stateRuleForm.weekday_end },
            tuesday: { enabled: true, start: stateRuleForm.weekday_start, end: stateRuleForm.weekday_end },
            wednesday: { enabled: true, start: stateRuleForm.weekday_start, end: stateRuleForm.weekday_end },
            thursday: { enabled: true, start: stateRuleForm.weekday_start, end: stateRuleForm.weekday_end },
            friday: { enabled: true, start: stateRuleForm.weekday_start, end: stateRuleForm.weekday_end },
            saturday: { enabled: stateRuleForm.saturday_enabled, start: stateRuleForm.saturday_start, end: stateRuleForm.saturday_end },
            sunday: { enabled: stateRuleForm.sunday_enabled, start: stateRuleForm.sunday_start, end: stateRuleForm.sunday_end },
        },
    }
    try {
        if (currentStateRule.value?.uuid) {
            await axios.put(`/dialer/state-rules/${currentStateRule.value.uuid}`, payload)
        } else {
            await axios.post(props.routes.storeStateRule, payload)
        }
        window.location.reload()
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the state rule right now.'))
    }
}
const saveAttemptDisposition = async (attemptUuid) => { try { await axios.post(`/dialer/attempts/${attemptUuid}/disposition`, { disposition_code: attemptDispositionForms[attemptUuid] }); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the attempt disposition right now.')) } }
const previewCampaign = async (campaign) => { try { const { data } = await axios.get(`/dialer/campaigns/${campaign.uuid}/preview`); previewCampaignId.value = campaign.uuid; previewLead.value = data.lead } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to load the next lead right now.')) } }
const launchPreviewCall = async () => { if (!previewCampaignId.value || !previewLead.value || !previewExtensionUuid.value) { setMessage('error', t('Select an extension before placing the preview call.')); return } try { await axios.post(`/dialer/campaigns/${previewCampaignId.value}/dial`, { lead_uuid: previewLead.value.uuid, extension_uuid: previewExtensionUuid.value }); setMessage('success', t('Call dispatched to FreeSWITCH.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.')) } }
const manualDial = async (leadUuid) => { if (!manualCampaignUuid.value || !manualExtensionUuid.value) { setMessage('error', t('Select a campaign and an extension before placing the manual call.')); return } try { await axios.post(`/dialer/campaigns/${manualCampaignUuid.value}/dial`, { lead_uuid: leadUuid, extension_uuid: manualExtensionUuid.value }); setMessage('success', t('Call dispatched to FreeSWITCH.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start the call right now.')) } }
const runCampaign = async (campaign) => { try { const { data } = await axios.post(`/dialer/campaigns/${campaign.uuid}/run`); setMessage('success', data.messages?.success?.[0] || t('Campaign cycle processed.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to run the campaign right now.')) } }
const deleteCampaign = async (uuid) => { if (!window.confirm(t('Delete this campaign?'))) return; try { await axios.delete(`/dialer/campaigns/${uuid}`); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the campaign right now.')) } }
const deleteLead = async (uuid) => { if (!window.confirm(t('Delete this lead?'))) return; try { await axios.delete(`/dialer/leads/${uuid}`); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the lead right now.')) } }
const handleImportFile = (event) => { importFile.value = event.target.files?.[0] || null }
const submitImport = async () => { if (!importFile.value) { setMessage('error', t('Choose a file to import.')); return } const formData = new FormData(); formData.append('file', importFile.value); importForm.campaign_uuids.forEach((uuid) => formData.append('campaign_uuids[]', uuid)); if (importForm.default_state_code) formData.append('default_state_code', importForm.default_state_code); if (importForm.default_timezone) formData.append('default_timezone', importForm.default_timezone); try { await axios.post(props.routes.importLeads, formData, { headers: { 'Content-Type': 'multipart/form-data' } }); window.location.reload() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to queue the import right now.')) } }
loadSelectedStateRule()

onMounted(() => {
    const domainUuid = page.props.selectedDomainUuid
    if (!domainUuid || !window.Echo) return
    window.Echo.private(`dialer.domain.${domainUuid}`).listen('.dialer.updated', () => setMessage('success', t('Dialer data updated in real time.')))
})

onBeforeUnmount(() => {
    const domainUuid = page.props.selectedDomainUuid
    if (domainUuid && window.Echo) {
        window.Echo.leave(`dialer.domain.${domainUuid}`)
    }
})
</script>

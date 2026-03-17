<template>
    <div class="space-y-6">
        <div
            v-if="message.text"
            class="rounded-2xl border px-4 py-3 text-sm"
            :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
        >
            {{ message.text }}
        </div>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead intake and governance') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        {{ t('Bring manual capture, file import, recent inventory, and the do-not-call registry into one operating view.') }}
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl border p-4" :class="card.tone">
                    <div class="text-[11px] uppercase tracking-[0.22em]" :class="card.kickerTone">{{ t(card.label) }}</div>
                    <div class="mt-3 text-3xl font-semibold" :class="card.valueTone">{{ card.value }}</div>
                    <div class="mt-2 text-xs leading-5" :class="card.helpTone">{{ t(card.help) }}</div>
                </article>
            </div>
        </section>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead operations launchpad') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        {{ t('Keep the screen focused on inventory and governance. Use dedicated actions to add one lead, queue an import batch, or register a DNC rule without spreading forms across the page.') }}
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                    <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">{{ t('Priority intake') }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ t('Add lead manually') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ t('Use this path for premium contacts, warm callbacks, or manual recoveries that should not wait for file import.') }}</p>
                    <button type="button" class="mt-5 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white" @click="showLeadModal = true">{{ t('New lead') }}</button>
                </article>

                <article class="rounded-[1.75rem] border border-cyan-100 bg-cyan-50 p-5">
                    <div class="text-[11px] uppercase tracking-[0.22em] text-cyan-700">{{ t('Batch ingest') }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ t('Queue import batch') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ t('Attach campaigns, set a default state and timezone, and send the file to the ingestion queue in one guided step.') }}</p>
                    <button type="button" class="mt-5 rounded-full bg-cyan-600 px-4 py-2 text-sm font-semibold text-white" @click="showImportModal = true">{{ t('Import list') }}</button>
                </article>

                <article class="rounded-[1.75rem] border border-rose-100 bg-rose-50 p-5">
                    <div class="text-[11px] uppercase tracking-[0.22em] text-rose-700">{{ t('Protection') }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ t('Register DNC rule') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ t('Treat opt-out, blocked, and invalid numbers as first-class operating records instead of hidden exceptions.') }}</p>
                    <button type="button" class="mt-5 rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white" @click="showDncModal = true">{{ t('Add DNC') }}</button>
                </article>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr),380px]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead inventory') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Review the latest leads with state, timezone, status, and do-not-call posture before they enter the dialer flow.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ leads.length }} {{ t('visible') }}</div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                        <div class="grid grid-cols-[minmax(0,1.35fr),150px,120px,120px,90px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <div>{{ t('Lead') }}</div>
                            <div>{{ t('Phone number') }}</div>
                            <div>{{ t('State') }}</div>
                            <div>{{ t('Status') }}</div>
                            <div>DNC</div>
                        </div>
                        <div v-if="leads.length" class="divide-y divide-slate-200">
                            <div v-for="lead in leads" :key="lead.uuid" class="grid grid-cols-[minmax(0,1.35fr),150px,120px,120px,90px] gap-3 px-4 py-4 text-sm">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ lead.name }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">{{ lead.company || lead.email || '-' }} | {{ lead.timezone || t('No timezone') }}</div>
                                </div>
                                <div class="font-semibold text-slate-900">{{ lead.phone_number }}</div>
                                <div class="text-slate-600">{{ lead.state_code || '-' }}</div>
                                <div>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="lead.status === 'blocked' ? 'bg-rose-100 text-rose-700' : (lead.status === 'calling' ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-700')">
                                        {{ t(lead.status || 'new') }}
                                    </span>
                                </div>
                                <div class="font-semibold" :class="lead.do_not_call ? 'text-rose-700' : 'text-emerald-700'">{{ lead.do_not_call ? t('Yes') : t('No') }}</div>
                            </div>
                        </div>
                        <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No leads are visible right now.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Import queue') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Track current import batches and verify whether rows were accepted, rejected, or are still processing.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ importBatches.length }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="batch in importBatches" :key="batch.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ batch.file_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ t(batch.status) }} | {{ batch.imported_rows }}/{{ batch.total_rows }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="batch.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : (batch.status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700')">
                                    {{ t(batch.status) }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-500">
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">{{ t('Rows') }}: <span class="font-semibold text-slate-900">{{ batch.total_rows || 0 }}</span></div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">{{ t('Imported') }}: <span class="font-semibold text-slate-900">{{ batch.imported_rows || 0 }}</span></div>
                                <div class="rounded-2xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">{{ t('Errors') }}: <span class="font-semibold text-slate-900">{{ batch.error_rows || 0 }}</span></div>
                            </div>
                        </article>
                        <div v-if="!importBatches.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No import batches are queued right now.') }}</div>
                    </div>
                </section>
            </section>

            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Recent DNC entries') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Treat opt-out and blocked numbers as a first-class operating list, not as hidden exceptions.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ dncEntries.length }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="entry in dncEntries" :key="entry.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ entry.phone_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ entry.reason || t('No reason') }} | {{ entry.source }}</div>
                                </div>
                                <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteDnc(entry.uuid)">{{ t('Delete') }}</button>
                            </div>
                            <div v-if="entry.expires_at" class="mt-2 text-xs text-slate-500">{{ t('Expires') }}: {{ entry.expires_at }}</div>
                        </article>
                        <div v-if="!dncEntries.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No do-not-call entries are visible right now.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-[#fffaf0] p-6 shadow-sm ring-1 ring-amber-200">
                    <div class="text-[11px] uppercase tracking-[0.22em] text-amber-700">{{ t('Governance notes') }}</div>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ t('Protection is part of the workflow') }}</h2>
                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Use manual intake for urgent or premium contacts, not as a replacement for structured list import.') }}</div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Always apply state and timezone on ingestion so campaigns inherit the right compliance context from the first attempt.') }}</div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">{{ t('Treat DNC as an operating registry. The floor should see blocked numbers as clearly as it sees active leads.') }}</div>
                    </div>
                </section>
            </section>
        </div>

        <ExecutiveModal
            :show="showLeadModal"
            :title="t('Add lead manually')"
            :description="t('Capture one contact with campaign assignment, timezone posture, and immediate DNC handling in a guided flow.')"
            :kicker="t('Priority intake')"
            custom-class="sm:max-w-3xl"
            @close="showLeadModal = false"
        >
            <form class="space-y-5" @submit.prevent="submitLead">
                <div class="grid gap-4 md:grid-cols-2">
                    <input v-model="leadForm.first_name" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('First name')" />
                    <input v-model="leadForm.last_name" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Last name')" />
                    <input v-model="leadForm.company" type="text" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Company')" />
                    <input v-model="leadForm.phone_number" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Phone number')" />
                    <input v-model="leadForm.email" type="email" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Email')" />
                    <select v-model="leadForm.state_code" class="rounded-2xl border-slate-300 shadow-sm" @change="syncLeadTimezone">
                        <option value="">{{ t('Lead state') }}</option>
                        <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                    </select>
                    <input v-model="leadForm.timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Lead timezone')" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">{{ t('Campaign targets') }}</label>
                    <select v-model="leadForm.campaign_uuids" multiple class="h-40 w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200">
                    <input v-model="leadForm.do_not_call" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" />
                    <span>{{ t('Mark as do-not-call immediately') }}</span>
                </label>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showLeadModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white">{{ t('Save lead') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal
            :show="showImportModal"
            :title="t('Queue import batch')"
            :description="t('Load the file, assign destination campaigns, and declare the baseline state and timezone before the batch reaches the dialer.')"
            :kicker="t('Batch ingest')"
            custom-class="sm:max-w-3xl"
            @close="showImportModal = false"
        >
            <form class="space-y-5" @submit.prevent="submitImport">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">{{ t('Campaign targets') }}</label>
                    <select v-model="importForm.campaign_uuids" multiple class="h-40 w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                    </select>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <select v-model="importForm.default_state_code" class="rounded-2xl border-slate-300 shadow-sm" @change="syncImportTimezone">
                        <option value="">{{ t('Default state') }}</option>
                        <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                    </select>
                    <input v-model="importForm.default_timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Default timezone')" />
                </div>
                <input type="file" accept=".csv,.txt,.xls,.xlsx" class="block w-full text-sm text-slate-600" @change="handleImportFile" />
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showImportModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Queue import') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal
            :show="showDncModal"
            :title="t('Register do-not-call entry')"
            :description="t('Create a protected number record with reason and source so the operation sees why the contact cannot be called again.')"
            :kicker="t('Protection')"
            custom-class="sm:max-w-2xl"
            @close="showDncModal = false"
        >
            <form class="space-y-5" @submit.prevent="submitDnc">
                <input v-model="dncForm.phone_number" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Phone number')" />
                <input v-model="dncForm.reason" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Reason')" />
                <input v-model="dncForm.source" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Source')" />
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showDncModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Save DNC entry') }}</button>
                </div>
            </form>
        </ExecutiveModal>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import ExecutiveModal from '@pages/components/modal/ExecutiveModal.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    leads: { type: Array, default: () => [] },
    dncEntries: { type: Array, default: () => [] },
    importBatches: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const showLeadModal = ref(false)
const showImportModal = ref(false)
const showDncModal = ref(false)
const leadForm = reactive({ first_name: '', last_name: '', company: '', phone_number: '', email: '', state_code: '', timezone: '', do_not_call: false, campaign_uuids: [] })
const importForm = reactive({ file: null, campaign_uuids: [], default_state_code: '', default_timezone: '' })
const dncForm = reactive({ phone_number: '', reason: '', source: 'manual' })

const summaryCards = computed(() => ([
    {
        label: 'Visible leads',
        value: props.leads.length,
        help: 'Most recent contacts loaded in the workspace.',
        tone: 'border-slate-200 bg-slate-50',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'DNC registry',
        value: props.dncEntries.length,
        help: 'Opt-out and blocked numbers currently visible.',
        tone: 'border-rose-100 bg-rose-50',
        kickerTone: 'text-rose-700',
        valueTone: 'text-rose-700',
        helpTone: 'text-rose-700/80',
    },
    {
        label: 'Import batches',
        value: props.importBatches.length,
        help: 'Recent list ingestion jobs.',
        tone: 'border-cyan-100 bg-cyan-50',
        kickerTone: 'text-cyan-700',
        valueTone: 'text-cyan-700',
        helpTone: 'text-cyan-700/80',
    },
    {
        label: 'Campaign targets',
        value: props.campaigns.length,
        help: 'Campaigns available to receive new contacts.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Blocked leads',
        value: props.leads.filter((lead) => lead.do_not_call || lead.status === 'blocked').length,
        help: 'Contacts already restricted from calling.',
        tone: 'border-amber-100 bg-amber-50',
        kickerTone: 'text-amber-700',
        valueTone: 'text-amber-700',
        helpTone: 'text-amber-700/80',
    },
]))

const setMessage = (type, text) => {
    message.type = type
    message.text = text
}

const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const succeedAndReload = (text) => {
    setMessage('success', text)
    reloadPage()
}

const stateMap = () => Object.fromEntries((props.options?.states || []).map((state) => [state.value, state]))

const syncLeadTimezone = () => {
    if (stateMap()[leadForm.state_code]) {
        leadForm.timezone = stateMap()[leadForm.state_code].timezone
    }
}

const syncImportTimezone = () => {
    if (stateMap()[importForm.default_state_code]) {
        importForm.default_timezone = stateMap()[importForm.default_state_code].timezone
    }
}

const submitLead = async () => {
    try {
        const response = await axios.post(props.routes.storeLead || '/dialer/leads', leadForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Lead created successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the lead right now.'))
    }
}

const handleImportFile = (event) => {
    importForm.file = event.target.files?.[0] || null
}

const submitImport = async () => {
    try {
        const formData = new FormData()
        if (importForm.file) formData.append('file', importForm.file)
        importForm.campaign_uuids.forEach((uuid) => formData.append('campaign_uuids[]', uuid))
        if (importForm.default_state_code) formData.append('default_state_code', importForm.default_state_code)
        if (importForm.default_timezone) formData.append('default_timezone', importForm.default_timezone)
        const response = await axios.post(props.routes.importLeads || '/dialer/imports', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        succeedAndReload(response.data?.messages?.success?.[0] || t('Lead import queued successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to queue the import right now.'))
    }
}

const submitDnc = async () => {
    try {
        const response = await axios.post(props.routes.storeDnc || '/dialer/dnc', dncForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Do-not-call entry saved successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the do-not-call entry right now.'))
    }
}

const routeFor = (template, token, value) => (template || '').replace(token, value)

const deleteDnc = async (uuid) => {
    try {
        const response = await axios.delete(routeFor(props.routes.destroyDnc || '/dialer/dnc/__ENTRY__', '__ENTRY__', uuid))
        succeedAndReload(response.data?.messages?.success?.[0] || t('Do-not-call entry deleted successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the do-not-call entry right now.'))
    }
}
</script>

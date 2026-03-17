<template>
    <div class="space-y-6">
        <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            {{ message.text }}
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead intake') }}</h2>
                    <HelpTooltip :text="t('Inject priority contacts into one or more campaigns without waiting for file import.')"/>
                </div>
                <form class="mt-5 space-y-4" @submit.prevent="submitLead">
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
                    <select v-model="leadForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                    </select>
                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200">
                        <input v-model="leadForm.do_not_call" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" />
                        <span>{{ t('Mark as do-not-call immediately') }}</span>
                    </label>
                    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save lead') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Lead import desk') }}</h2>
                    <HelpTooltip :text="t('Load CSV or spreadsheet lists and apply a default state and timezone when needed.')"/>
                </div>
                <form class="mt-5 space-y-4" @submit.prevent="submitImport">
                    <select v-model="importForm.campaign_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="campaign in campaigns" :key="campaign.uuid" :value="campaign.uuid">{{ campaign.name }}</option>
                    </select>
                    <div class="grid gap-4 md:grid-cols-2">
                        <select v-model="importForm.default_state_code" class="rounded-2xl border-slate-300 shadow-sm" @change="syncImportTimezone">
                            <option value="">{{ t('Default state') }}</option>
                            <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                        </select>
                        <input v-model="importForm.default_timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Default timezone')" />
                    </div>
                    <input type="file" accept=".csv,.txt,.xls,.xlsx" class="block w-full text-sm text-slate-600" @change="handleImportFile" />
                    <button type="submit" class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white">{{ t('Queue import') }}</button>
                </form>
                <div class="mt-5 space-y-2 text-sm">
                    <article v-for="batch in importBatches" :key="batch.uuid" class="rounded-2xl border border-slate-200 p-3">
                        <div class="font-medium text-slate-900">{{ batch.file_name }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ t(batch.status) }} | {{ batch.imported_rows }}/{{ batch.total_rows }}</div>
                    </article>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Do-not-call governance') }}</h2>
                    <HelpTooltip :text="t('Keep opt-out, invalid, and blocked numbers visible to the whole operation.')"/>
                </div>
                <form class="mt-5 space-y-4" @submit.prevent="submitDnc">
                    <input v-model="dncForm.phone_number" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Phone number')" />
                    <input v-model="dncForm.reason" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Reason')" />
                    <input v-model="dncForm.source" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Source')" />
                    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save DNC entry') }}</button>
                </form>
                <div class="mt-5 space-y-2">
                    <article v-for="entry in dncEntries" :key="entry.uuid" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ entry.phone_number }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ entry.reason || t('No reason') }} | {{ entry.source }}</div>
                        </div>
                        <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteDnc(entry.uuid)">{{ t('Delete') }}</button>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
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
const leadForm = reactive({ first_name: '', last_name: '', company: '', phone_number: '', email: '', state_code: '', timezone: '', do_not_call: false, campaign_uuids: [] })
const importForm = reactive({ file: null, campaign_uuids: [], default_state_code: '', default_timezone: '' })
const dncForm = reactive({ phone_number: '', reason: '', source: 'manual' })

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const succeedAndReload = (text) => { setMessage('success', text); reloadPage() }
const stateMap = () => Object.fromEntries((props.options?.states || []).map((state) => [state.value, state]))
const syncLeadTimezone = () => { if (stateMap()[leadForm.state_code]) leadForm.timezone = stateMap()[leadForm.state_code].timezone }
const syncImportTimezone = () => { if (stateMap()[importForm.default_state_code]) importForm.default_timezone = stateMap()[importForm.default_state_code].timezone }
const submitLead = async () => { try { const response = await axios.post(props.routes.storeLead || '/dialer/leads', leadForm); succeedAndReload(response.data?.messages?.success?.[0] || t('Lead created successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the lead right now.')) } }
const handleImportFile = (event) => { importForm.file = event.target.files?.[0] || null }
const submitImport = async () => { try { const formData = new FormData(); if (importForm.file) formData.append('file', importForm.file); importForm.campaign_uuids.forEach((uuid) => formData.append('campaign_uuids[]', uuid)); if (importForm.default_state_code) formData.append('default_state_code', importForm.default_state_code); if (importForm.default_timezone) formData.append('default_timezone', importForm.default_timezone); const response = await axios.post(props.routes.importLeads || '/dialer/imports', formData, { headers: { 'Content-Type': 'multipart/form-data' } }); succeedAndReload(response.data?.messages?.success?.[0] || t('Lead import queued successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to queue the import right now.')) } }
const submitDnc = async () => { try { const response = await axios.post(props.routes.storeDnc || '/dialer/dnc', dncForm); succeedAndReload(response.data?.messages?.success?.[0] || t('Do-not-call entry saved successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the do-not-call entry right now.')) } }
const deleteDnc = async (uuid) => { try { const response = await axios.delete(routeFor(props.routes.destroyDnc || '/dialer/dnc/__ENTRY__', '__ENTRY__', uuid)); succeedAndReload(response.data?.messages?.success?.[0] || t('Do-not-call entry deleted successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the do-not-call entry right now.')) } }
</script>

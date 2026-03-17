<template>
    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Outcome desk') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Treat dispositions as an operational model: they define reporting, recycling, callbacks, voicemail handling, and do-not-call governance.') }}</p>
                </div>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700">{{ attempts.length }} {{ t('recent attempts') }}</div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
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

        <div class="grid gap-6 xl:grid-cols-[420px,minmax(0,1fr)]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Disposition design') }}</h2>
                        <HelpTooltip :text="t('Outcomes drive retries, callbacks, DNC handling, and reporting quality.')"/>
                    </div>
                    <form class="mt-5 space-y-4" @submit.prevent="submitDisposition">
                        <input v-model="dispositionForm.name" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Disposition name')" />
                        <input v-model="dispositionForm.code" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Machine code')" />
                        <textarea v-model="dispositionForm.description" rows="3" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Describe when the agent should choose this outcome.')" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.is_final" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Final outcome') }}</span></label>
                            <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.is_callback" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Creates callback') }}</span></label>
                            <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200"><input v-model="dispositionForm.mark_dnc" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" /><span>{{ t('Marks DNC') }}</span></label>
                            <input v-model.number="dispositionForm.default_callback_offset_minutes" type="number" min="1" max="43200" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Default callback offset (minutes)')" />
                        </div>
                        <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save disposition') }}</button>
                    </form>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Outcome catalog') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Each disposition should tell the floor whether the lead is final, recyclable, callback-driven, or no-longer-callable.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ dispositions.length }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="disposition in dispositions" :key="disposition.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ disposition.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ disposition.code }} | {{ disposition.description || t('No description') }}</div>
                                </div>
                                <div class="flex flex-wrap justify-end gap-2 text-[11px] font-semibold">
                                    <span v-if="disposition.is_final" class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ t('Final outcome') }}</span>
                                    <span v-if="disposition.is_callback" class="rounded-full bg-cyan-100 px-3 py-1 text-cyan-700">{{ t('Creates callback') }}</span>
                                    <span v-if="disposition.mark_dnc" class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">{{ t('Marks DNC') }}</span>
                                </div>
                            </div>
                            <div v-if="disposition.default_callback_offset_minutes" class="mt-3 text-xs text-slate-500">{{ t('Default callback offset (minutes)') }}: {{ disposition.default_callback_offset_minutes }}</div>
                        </article>
                    </div>
                </section>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Attempt result queue') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Apply the right disposition to recent attempts without losing AMD, hangup, or callback context.') }}</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                    <div class="grid grid-cols-[minmax(0,1.1fr),100px,100px,120px,220px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                        <div>{{ t('Attempt') }}</div>
                        <div>{{ t('AMD') }}</div>
                        <div>{{ t('Hangup') }}</div>
                        <div>{{ t('Current') }}</div>
                        <div>{{ t('Supervisor action') }}</div>
                    </div>
                    <div v-if="attempts.length" class="divide-y divide-slate-200">
                        <div v-for="attempt in attempts" :key="attempt.uuid" class="grid grid-cols-[minmax(0,1.1fr),100px,100px,120px,220px] gap-3 px-4 py-4 text-sm">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-slate-900">{{ attempt.lead_name || attempt.destination_number }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">{{ attempt.campaign_name || '-' }} | {{ attempt.destination_number }} | {{ t(attempt.mode) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ t('Talk') }}: {{ attempt.talk_seconds || 0 }}s | {{ t('Waiting') }}: {{ attempt.wait_seconds || 0 }}s</div>
                            </div>
                            <div class="text-slate-600">{{ attempt.amd_result || '-' }}</div>
                            <div class="text-slate-600">{{ attempt.hangup_cause || '-' }}</div>
                            <div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ attempt.disposition || '-' }}</span>
                            </div>
                            <div class="space-y-2">
                                <select v-model="attemptForms[attempt.uuid].disposition_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                                    <option value="">{{ t('Choose disposition') }}</option>
                                    <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.uuid">{{ disposition.name }} ({{ disposition.code }})</option>
                                </select>
                                <input v-model="attemptForms[attempt.uuid].preferred_callback_at" type="datetime-local" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                                <input v-model="attemptForms[attempt.uuid].notes" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Disposition notes')" />
                                <button type="button" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="saveAttemptDisposition(attempt.uuid)">{{ t('Save disposition') }}</button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No recent attempts are waiting for supervisor outcome work.') }}</div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    dispositions: { type: Array, default: () => [] },
    attempts: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const dispositionForm = reactive({ name: '', code: '', is_final: false, is_callback: false, mark_dnc: false, default_callback_offset_minutes: '', description: '' })
const attemptForms = reactive(Object.fromEntries(props.attempts.map((attempt) => [attempt.uuid, { disposition_uuid: '', notes: '', preferred_callback_at: '' }])))

const summaryCards = computed(() => ([
    {
        label: 'Dispositions',
        value: props.dispositions.length,
        help: 'Total outcomes available to the floor.',
        tone: 'border-slate-200 bg-slate-50',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Final outcomes',
        value: props.dispositions.filter((disposition) => disposition.is_final).length,
        help: 'Results that close the lead lifecycle.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Callback outcomes',
        value: props.dispositions.filter((disposition) => disposition.is_callback).length,
        help: 'Results that should generate a return task.',
        tone: 'border-cyan-100 bg-cyan-50',
        kickerTone: 'text-cyan-700',
        valueTone: 'text-cyan-700',
        helpTone: 'text-cyan-700/80',
    },
    {
        label: 'DNC outcomes',
        value: props.dispositions.filter((disposition) => disposition.mark_dnc).length,
        help: 'Results that immediately protect the number.',
        tone: 'border-rose-100 bg-rose-50',
        kickerTone: 'text-rose-700',
        valueTone: 'text-rose-700',
        helpTone: 'text-rose-700/80',
    },
    {
        label: 'Voicemail attempts',
        value: props.attempts.filter((attempt) => (attempt.amd_result || '').toLowerCase() === 'voicemail' || (attempt.disposition || '').toLowerCase() === 'voicemail').length,
        help: 'Recent attempts classified as voicemail or machine.',
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

const submitDisposition = async () => {
    try {
        const response = await axios.post(props.routes.storeDisposition || '/dialer/dispositions', dispositionForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Disposition saved successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.'))
    }
}

const routeFor = (template, token, value) => (template || '').replace(token, value)

const saveAttemptDisposition = async (attemptUuid) => {
    try {
        const response = await axios.post(routeFor(props.routes.saveAttemptDisposition || '/dialer/attempts/__ATTEMPT__/disposition', '__ATTEMPT__', attemptUuid), attemptForms[attemptUuid])
        succeedAndReload(response.data?.messages?.success?.[0] || t('Attempt disposition saved successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.'))
    }
}
</script>

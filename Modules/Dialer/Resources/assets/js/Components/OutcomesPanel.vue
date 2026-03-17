<template>
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
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Outcome catalog') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="disposition in dispositions" :key="disposition.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="font-semibold text-slate-900">{{ disposition.name }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ disposition.code }} | {{ disposition.description || t('No description') }}</div>
                    </article>
                </div>
            </section>
        </section>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Outcome desk') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Apply dispositions and callback dates to recent attempts without losing operational context.') }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700">{{ attempts.length }} {{ t('recent attempts') }}</span>
            </div>

            <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                {{ message.text }}
            </div>

            <div class="mt-5 space-y-3">
                <article v-for="attempt in attempts" :key="attempt.uuid" class="rounded-3xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
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
                        <select v-model="attemptForms[attempt.uuid].disposition_uuid" class="rounded-2xl border-slate-300 shadow-sm">
                            <option value="">{{ t('Choose disposition') }}</option>
                            <option v-for="disposition in dispositions" :key="disposition.uuid" :value="disposition.uuid">{{ disposition.name }} ({{ disposition.code }})</option>
                        </select>
                        <input v-model="attemptForms[attempt.uuid].preferred_callback_at" type="datetime-local" class="rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="attemptForms[attempt.uuid].notes" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Disposition notes')" />
                        <button type="button" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="saveAttemptDisposition(attempt.uuid)">{{ t('Save disposition') }}</button>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
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

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const succeedAndReload = (text) => { setMessage('success', text); reloadPage() }
const submitDisposition = async () => { try { const response = await axios.post(props.routes.storeDisposition || '/dialer/dispositions', dispositionForm); succeedAndReload(response.data?.messages?.success?.[0] || t('Disposition saved successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.')) } }
const saveAttemptDisposition = async (attemptUuid) => { try { const response = await axios.post(routeFor(props.routes.saveAttemptDisposition || '/dialer/attempts/__ATTEMPT__/disposition', '__ATTEMPT__', attemptUuid), attemptForms[attemptUuid]); succeedAndReload(response.data?.messages?.success?.[0] || t('Attempt disposition saved successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the disposition right now.')) } }
</script>

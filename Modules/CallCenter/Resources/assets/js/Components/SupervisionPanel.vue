<template>
    <div class="grid gap-6 xl:grid-cols-[1fr,0.95fr]">
        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Agent supervision') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('Pause, resume, and review the live posture of every agent in the operation.') }}</p>
                    </div>
                </div>

                <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                    {{ message.text }}
                </div>

                <div class="mt-5 space-y-3">
                    <article v-for="agent in agents" :key="agent.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="font-medium text-slate-900">{{ agent.extension }} - {{ agent.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ t(agent.status) }} | {{ t(agent.live_status) }} <span v-if="agent.pause_reason">| {{ agent.pause_reason }}</span></div>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ agent.call_timeout }}s / {{ agent.wrap_up_time }}s / {{ agent.reject_delay_time }}s</div>
                        </div>
                        <div class="mt-3 grid gap-3 md:grid-cols-[1fr,1fr,auto,auto]">
                            <select v-model="pauseReasonForms[agent.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm">
                                <option value="">{{ t('Pause reason') }}</option>
                                <option v-for="reason in pauseReasons" :key="reason.uuid" :value="reason.uuid">{{ reason.name }}</option>
                            </select>
                            <input v-model="pauseNoteForms[agent.uuid]" type="text" :placeholder="t('Pause note')" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm" />
                            <button type="button" class="rounded-full bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-700" @click="pauseAgent(agent.uuid)">{{ t('Pause') }}</button>
                            <button type="button" class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700" @click="resumeAgent(agent.uuid)">{{ t('Resume') }}</button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Callback board') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="callback in callbacks" :key="callback.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-medium text-slate-900">{{ callback.contact_name || callback.phone_number }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ callback.phone_number }} | {{ callback.queue_label || t('No queue') }} | {{ t(callback.status) }}</div>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ callback.preferred_callback_at || callback.requested_at }}</div>
                        </div>
                        <div class="mt-3 grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                            <select v-model="callbackAgentForms[callback.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm">
                                <option value="">{{ t('Assign agent') }}</option>
                                <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                            </select>
                            <select v-model="callbackStatusForms[callback.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm">
                                <option value="pending">{{ t('Pending') }}</option>
                                <option value="assigned">{{ t('Assigned') }}</option>
                                <option value="completed">{{ t('Completed') }}</option>
                                <option value="canceled">{{ t('Canceled') }}</option>
                            </select>
                            <button type="button" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white" @click="saveCallback(callback.uuid)">{{ t('Save') }}</button>
                        </div>
                    </article>
                </div>
            </section>
        </section>

        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Live monitoring') }}</h2>
                <form class="mt-5 space-y-3" @submit.prevent="startMonitoring">
                    <select v-model="monitorForm.call_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Select active call') }}</option>
                        <option v-for="call in wallboard.active_calls || []" :key="call.call_uuid" :value="call.call_uuid">{{ call.agent_extension }} - {{ call.destination }}</option>
                    </select>
                    <select v-model="monitorForm.call_center_agent_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Agent on the call') }}</option>
                        <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                    </select>
                    <select v-model="monitorForm.supervisor_extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Select supervisor extension') }}</option>
                        <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                    </select>
                    <select v-model="monitorForm.mode" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="mode in options.monitoring_modes || []" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option>
                    </select>
                    <textarea v-model="monitorForm.notes" rows="3" :placeholder="t('Monitoring notes')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Start monitoring') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Active calls') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="call in wallboard.active_calls || []" :key="call.call_uuid" class="rounded-3xl border border-slate-200 p-4 text-sm">
                        <div class="font-medium text-slate-900">{{ call.agent_extension }} - {{ call.destination }}</div>
                        <div class="text-slate-500">{{ call.direction }} | {{ call.state }} | {{ call.duration_seconds }}s</div>
                    </article>
                </div>
            </section>
        </section>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    agents: { type: Array, default: () => [] },
    callbacks: { type: Array, default: () => [] },
    pauseReasons: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    wallboard: { type: Object, default: () => ({ active_calls: [] }) },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const pauseReasonForms = reactive(Object.fromEntries(props.agents.map((agent) => [agent.uuid, ''])))
const pauseNoteForms = reactive(Object.fromEntries(props.agents.map((agent) => [agent.uuid, ''])))
const callbackStatusForms = reactive(Object.fromEntries(props.callbacks.map((callback) => [callback.uuid, callback.status])))
const callbackAgentForms = reactive(Object.fromEntries(props.callbacks.map((callback) => [callback.uuid, callback.call_center_agent_uuid || ''])))
const monitorForm = reactive({ call_uuid: '', call_center_agent_uuid: '', supervisor_extension_uuid: '', mode: 'listen', notes: '' })

const setMessage = (type, text) => { message.type = type; message.text = text }
const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = () => window.location.reload()
const pauseAgent = async (agentUuid) => { try { await axios.post(routeFor(props.routes.pauseAgent || '/contact-center/agents/__AGENT__/pause', '__AGENT__', agentUuid), { pause_reason_uuid: pauseReasonForms[agentUuid] || null, note: pauseNoteForms[agentUuid] || null }); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to pause the agent right now.')) } }
const resumeAgent = async (agentUuid) => { try { await axios.post(routeFor(props.routes.resumeAgent || '/contact-center/agents/__AGENT__/resume', '__AGENT__', agentUuid)); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to resume the agent right now.')) } }
const saveCallback = async (callbackUuid) => { try { await axios.put(routeFor(props.routes.updateCallback || '/contact-center/callbacks/__CALLBACK__', '__CALLBACK__', callbackUuid), { status: callbackStatusForms[callbackUuid], call_center_agent_uuid: callbackAgentForms[callbackUuid] || null }); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to update the callback right now.')) } }
const startMonitoring = async () => { try { await axios.post(props.routes.startMonitoring || '/contact-center/monitoring', monitorForm); setMessage('success', t('Monitoring session started successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start monitoring right now.')) } }
</script>

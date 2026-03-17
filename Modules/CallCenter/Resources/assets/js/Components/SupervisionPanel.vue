<template>
    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
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

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),420px]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
                                <span>{{ t('Agent supervision') }}</span>
                                <HelpTooltip :text="t('Pause, resume, and review the live posture of every agent in the operation.')"/>
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Use one roster to understand who is online, who is busy, which queues they cover, and who needs immediate supervisor action.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ agents.length }} {{ t('agents') }}</div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                        <div class="grid grid-cols-[120px,minmax(0,1.2fr),110px,110px,120px,220px] gap-3 bg-slate-950 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-300">
                            <div>{{ t('Extension') }}</div>
                            <div>{{ t('Agent') }}</div>
                            <div>{{ t('Live') }}</div>
                            <div>{{ t('Queues') }}</div>
                            <div>{{ t('Call') }}</div>
                            <div>{{ t('Supervisor action') }}</div>
                        </div>
                        <div v-if="agents.length" class="divide-y divide-slate-200">
                            <div v-for="agent in agents" :key="agent.uuid" class="grid grid-cols-[120px,minmax(0,1.2fr),110px,110px,120px,220px] gap-3 px-4 py-4 text-sm">
                                <div class="font-semibold text-slate-900">{{ agent.extension }}</div>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ agent.name }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">
                                        {{ agent.pause_reason || t(agent.status) }}
                                        <span v-if="agent.active_call_destination">| {{ agent.active_call_destination }}</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="agent.live_status === 'Busy' ? 'bg-cyan-100 text-cyan-700' : (agent.live_status === 'Online' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')">
                                        {{ t(agent.live_status) }}
                                    </span>
                                </div>
                                <div class="font-semibold text-slate-900">{{ agent.queue_count }}</div>
                                <div class="text-slate-600">{{ agent.active_call_state || '-' }}</div>
                                <div class="space-y-2">
                                    <div class="grid grid-cols-[1fr,1fr] gap-2">
                                        <select v-model="pauseReasonForms[agent.uuid]" class="rounded-2xl border-slate-300 text-sm shadow-sm">
                                            <option value="">{{ t('Pause reason') }}</option>
                                            <option v-for="reason in pauseReasons" :key="reason.uuid" :value="reason.uuid">{{ reason.name }}</option>
                                        </select>
                                        <input v-model="pauseNoteForms[agent.uuid]" type="text" :placeholder="t('Pause note')" class="rounded-2xl border-slate-300 text-sm shadow-sm" />
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="rounded-full bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-700" @click="pauseAgent(agent.uuid)">{{ t('Pause') }}</button>
                                        <button type="button" class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700" @click="resumeAgent(agent.uuid)">{{ t('Resume') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No agents are provisioned for supervision yet.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
                                <span>{{ t('Callback board') }}</span>
                                <HelpTooltip :text="t('Track callback ownership, due dates, and resolution status without leaving the wallboard flow.')"/>
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Callbacks should be visible with queue, owner, and due time so the supervisor can rebalance work before SLAs slip.') }}</p>
                        </div>
                        <div class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ callbackRows.length }} {{ t('callbacks') }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="callback in callbackRows" :key="callback.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ callback.contact_name || callback.phone_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ callback.phone_number }} | {{ callback.queue_label || t('No queue') }}</div>
                                </div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>{{ callback.preferred_callback_at || callback.requested_at }}</div>
                                    <div class="mt-1">{{ callback.agent_label || t('Unassigned') }}</div>
                                </div>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                                <select v-model="callbackAgentForms[callback.uuid]" class="rounded-2xl border-slate-300 text-sm shadow-sm">
                                    <option value="">{{ t('Assign agent') }}</option>
                                    <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                                </select>
                                <select v-model="callbackStatusForms[callback.uuid]" class="rounded-2xl border-slate-300 text-sm shadow-sm">
                                    <option value="pending">{{ t('Pending') }}</option>
                                    <option value="assigned">{{ t('Assigned') }}</option>
                                    <option value="completed">{{ t('Completed') }}</option>
                                    <option value="canceled">{{ t('Canceled') }}</option>
                                </select>
                                <button type="button" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="saveCallback(callback.uuid)">{{ t('Save') }}</button>
                            </div>
                        </article>
                        <div v-if="!callbackRows.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No callbacks are pending in supervision right now.') }}</div>
                    </div>
                </section>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
                        <span>{{ t('Live monitoring') }}</span>
                        <HelpTooltip :text="t('Start listen, whisper, or barge sessions from the supervisor console once the active call and supervisor extension are selected.')"/>
                    </h2>
                    <form class="mt-5 space-y-3" @submit.prevent="startMonitoring">
                        <select v-model="monitorForm.call_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option value="">{{ t('Select active call') }}</option>
                            <option v-for="call in activeCalls" :key="call.call_uuid" :value="call.call_uuid">{{ call.agent_extension }} - {{ call.destination }}</option>
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
                        <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Start monitoring') }}</button>
                    </form>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Active calls') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Supervisor-ready list of current conversations.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ activeCalls.length }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="call in activeCalls" :key="call.call_uuid" class="rounded-3xl border border-slate-200 p-4 text-sm">
                            <div class="font-semibold text-slate-900">{{ call.agent_extension }} - {{ call.destination }}</div>
                            <div class="mt-1 text-slate-500">{{ call.direction }} | {{ call.state }} | {{ call.duration_seconds }}s</div>
                        </article>
                        <div v-if="!activeCalls.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No active calls on the floor right now.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-[#fffaf0] p-6 shadow-sm ring-1 ring-amber-200">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Supervisor checklist') }}</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Resolve callbacks before they age silently.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('Pending callbacks should always have a queue or owner before the next dialer cycle increases pressure.') }}</div>
                        </div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Pause reasons need discipline.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('A supervisor should be able to explain why every paused agent is parked and when they return.') }}</div>
                        </div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Monitor only with clear intent.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('Listen, whisper, and barge actions should be deliberate and documented in the session notes.') }}</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
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

const summaryCards = computed(() => {
    const available = props.agents.filter((agent) => ['online', 'available'].includes((agent.live_status || '').toLowerCase())).length
    const paused = props.agents.filter((agent) => Boolean(agent.is_paused)).length
    const talking = props.agents.filter((agent) => (agent.live_status || '').toLowerCase() === 'busy').length
    const pendingCallbacks = props.callbacks.filter((callback) => ['pending', 'assigned'].includes((callback.status || '').toLowerCase())).length

    return [
        {
            label: 'Available agents',
            value: available,
            help: 'Agents online and not parked or busy.',
            tone: 'border-slate-200 bg-slate-50',
            kickerTone: 'text-slate-500',
            valueTone: 'text-slate-900',
            helpTone: 'text-slate-500',
        },
        {
            label: 'Paused',
            value: paused,
            help: 'Agents with an open pause record.',
            tone: 'border-amber-100 bg-amber-50',
            kickerTone: 'text-amber-700',
            valueTone: 'text-amber-700',
            helpTone: 'text-amber-700/80',
        },
        {
            label: 'Talking',
            value: talking,
            help: 'Agents currently committed to a live call.',
            tone: 'border-cyan-100 bg-cyan-50',
            kickerTone: 'text-cyan-700',
            valueTone: 'text-cyan-700',
            helpTone: 'text-cyan-700/80',
        },
        {
            label: 'Pending callbacks',
            value: pendingCallbacks,
            help: 'Callbacks still waiting for completion.',
            tone: 'border-rose-100 bg-rose-50',
            kickerTone: 'text-rose-700',
            valueTone: 'text-rose-700',
            helpTone: 'text-rose-700/80',
        },
    ]
})

const callbackRows = computed(() => [...(props.callbacks || [])].sort((left, right) => {
    const leftTimestamp = new Date(left.preferred_callback_at || left.requested_at || 0).getTime()
    const rightTimestamp = new Date(right.preferred_callback_at || right.requested_at || 0).getTime()
    return leftTimestamp - rightTimestamp
}))

const activeCalls = computed(() => props.wallboard?.active_calls || [])

const setMessage = (type, text) => {
    message.type = type
    message.text = text
}

const routeFor = (template, token, value) => (template || '').replace(token, value)
const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const succeedAndReload = (text) => {
    setMessage('success', text)
    reloadPage()
}

const pauseAgent = async (agentUuid) => {
    try {
        const response = await axios.post(routeFor(props.routes.pauseAgent || '/contact-center/agents/__AGENT__/pause', '__AGENT__', agentUuid), {
            pause_reason_uuid: pauseReasonForms[agentUuid] || null,
            note: pauseNoteForms[agentUuid] || null,
        })
        succeedAndReload(response.data?.messages?.success?.[0] || t('Agent paused successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to pause the agent right now.'))
    }
}

const resumeAgent = async (agentUuid) => {
    try {
        const response = await axios.post(routeFor(props.routes.resumeAgent || '/contact-center/agents/__AGENT__/resume', '__AGENT__', agentUuid))
        succeedAndReload(response.data?.messages?.success?.[0] || t('Agent resumed successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to resume the agent right now.'))
    }
}

const saveCallback = async (callbackUuid) => {
    try {
        const response = await axios.put(routeFor(props.routes.updateCallback || '/contact-center/callbacks/__CALLBACK__', '__CALLBACK__', callbackUuid), {
            status: callbackStatusForms[callbackUuid],
            call_center_agent_uuid: callbackAgentForms[callbackUuid] || null,
        })
        succeedAndReload(response.data?.messages?.success?.[0] || t('Callback updated successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to update the callback right now.'))
    }
}

const startMonitoring = async () => {
    try {
        const response = await axios.post(props.routes.startMonitoring || '/contact-center/monitoring', monitorForm)
        setMessage('success', response.data?.messages?.success?.[0] || t('Monitoring session started successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start monitoring right now.'))
    }
}
</script>

<template>
    <div class="space-y-6">
        <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            {{ message.text }}
        </div>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Configuration ledger') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Build queues, agents, pause reasons, callbacks, and supervisor access from one denser setup surface.') }}</p>
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

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Create queue') }}</h2>
                    <HelpTooltip :text="t('Create the queue here, then attach the right agents and strategy before activating campaigns or callbacks.')"/>
                </div>
                <form class="mt-4 space-y-3" @submit.prevent="submitQueue">
                    <input v-model="queueForm.queue_name" type="text" :placeholder="t('Queue name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="queueForm.queue_extension" type="text" :placeholder="t('Queue extension')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <select v-model="queueForm.queue_strategy" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option v-for="strategy in options.strategies || []" :key="strategy.value" :value="strategy.value">{{ t(strategy.label) }}</option>
                        </select>
                    </div>
                    <input v-model.number="queueForm.queue_max_wait_time" type="number" min="0" :placeholder="t('Max wait')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <textarea v-model="queueForm.queue_description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                    <select v-model="queueForm.agent_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                    </select>
                    <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create queue') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Create agent') }}</h2>
                    <HelpTooltip :text="t('Use a registered extension and define timeout, wrap-up, and reject delay before putting the agent into production.')"/>
                </div>
                <form class="mt-4 space-y-3" @submit.prevent="submitAgent">
                    <select v-model="agentForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Select an extension') }}</option>
                        <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                    </select>
                    <input v-model="agentForm.agent_name" type="text" :placeholder="t('Agent label')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <div class="grid gap-3 md:grid-cols-3">
                        <input v-model.number="agentForm.agent_call_timeout" type="number" min="5" :placeholder="t('Call timeout')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model.number="agentForm.agent_wrap_up_time" type="number" min="0" :placeholder="t('Wrap up')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model.number="agentForm.agent_reject_delay_time" type="number" min="0" :placeholder="t('Reject delay')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    </div>
                    <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create agent') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Provision user') }}</h2>
                    <HelpTooltip :text="t('Provisioning creates or reuses the portal user so the extension can operate as an agent or supervisor.')"/>
                </div>
                <form class="mt-4 space-y-3" @submit.prevent="submitUser">
                    <select v-model="userForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Select an extension') }}</option>
                        <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                    </select>
                    <select v-model="userForm.role" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="agent">{{ t('Agent') }}</option>
                        <option value="admin">{{ t('Supervisor') }}</option>
                    </select>
                    <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Provision access') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Pause reasons and callbacks') }}</h2>
                    <HelpTooltip :text="t('Define structured pause reasons and register callbacks with queue, agent, and preferred local time.')"/>
                </div>
                <form class="mt-4 space-y-3" @submit.prevent="submitPauseReason">
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="pauseReasonForm.code" type="text" :placeholder="t('Code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="pauseReasonForm.name" type="text" :placeholder="t('Name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    </div>
                    <input v-model.number="pauseReasonForm.auto_resume_minutes" type="number" min="1" :placeholder="t('Auto resume (minutes)')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save pause reason') }}</button>
                </form>

                <form class="mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submitCallback">
                    <input v-model="callbackForm.contact_name" type="text" :placeholder="t('Contact name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model="callbackForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <select v-model="callbackForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Queue') }}</option>
                        <option v-for="queue in queues" :key="queue.uuid" :value="queue.uuid">{{ queue.extension }} - {{ queue.name }}</option>
                    </select>
                    <select v-model="callbackForm.call_center_agent_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Assign agent') }}</option>
                        <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                    </select>
                    <input v-model="callbackForm.state_code" type="text" :placeholder="t('State code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model="callbackForm.timezone" type="text" :placeholder="t('Timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model="callbackForm.preferred_callback_at" type="datetime-local" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model="callbackForm.notes" type="text" :placeholder="t('Notes')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save callback') }}</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr),0.85fr]">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Current queue portfolio') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Keep queue strategy, agent coverage, and wait targets visible while you configure the floor.') }}</p>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ queues.length }} {{ t('queues') }}</div>
                </div>

                <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                    <div class="grid grid-cols-[120px,minmax(0,1fr),120px,110px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                        <div>{{ t('Extension') }}</div>
                        <div>{{ t('Queue') }}</div>
                        <div>{{ t('Strategy') }}</div>
                        <div>{{ t('Ready agents') }}</div>
                    </div>
                    <div v-if="queues.length" class="divide-y divide-slate-200">
                        <div v-for="queue in queues" :key="queue.uuid" class="grid grid-cols-[120px,minmax(0,1fr),120px,110px] gap-3 px-4 py-4 text-sm">
                            <div class="font-semibold text-slate-900">{{ queue.extension }}</div>
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-slate-900">{{ queue.name }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">{{ queue.description || t('No description') }}</div>
                            </div>
                            <div class="text-slate-600">{{ t(queue.strategy) }}</div>
                            <div class="font-semibold text-slate-900">{{ queue.available_agents }}</div>
                        </div>
                    </div>
                    <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No call center queues are configured yet.') }}</div>
                </div>
            </section>

            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Agent roster') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Review the current estate of agents while adding or provisioning new ones.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ agents.length }} {{ t('agents') }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="agent in agents" :key="agent.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ agent.extension }} - {{ agent.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ t(agent.live_status) }} | {{ agent.queue_count }} {{ t('queues') }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="agent.live_status === 'Busy' ? 'bg-cyan-100 text-cyan-700' : (agent.live_status === 'Online' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')">
                                    {{ t(agent.live_status) }}
                                </span>
                            </div>
                        </article>
                        <div v-if="!agents.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No agents are provisioned for supervision yet.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Pause reason catalog') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('A structured pause catalog keeps reporting and supervision consistent.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ pauseReasons.length }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="reason in pauseReasons" :key="reason.uuid" class="flex items-center justify-between rounded-3xl border border-slate-200 p-4">
                            <div>
                                <div class="font-semibold text-slate-900">{{ reason.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ reason.code }}</div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ reason.auto_resume_minutes || '-' }}</span>
                        </article>
                        <div v-if="!pauseReasons.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No pause reasons are configured yet.') }}</div>
                    </div>
                </section>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    queues: { type: Array, default: () => [] },
    agents: { type: Array, default: () => [] },
    pauseReasons: { type: Array, default: () => [] },
    callbacks: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const queueForm = reactive({ queue_name: '', queue_extension: '', queue_strategy: 'ring-all', queue_description: '', queue_max_wait_time: 20, agent_uuids: [] })
const agentForm = reactive({ extension_uuid: '', agent_name: '', agent_status: 'Available', agent_call_timeout: 20, agent_wrap_up_time: 10, agent_reject_delay_time: 10 })
const userForm = reactive({ extension_uuid: '', role: 'agent' })
const pauseReasonForm = reactive({ code: '', name: '', auto_resume_minutes: '', is_active: true })
const callbackForm = reactive({ call_center_queue_uuid: '', call_center_agent_uuid: '', contact_name: '', phone_number: '', state_code: '', timezone: '', preferred_callback_at: '', notes: '' })

const summaryCards = computed(() => ([
    {
        label: 'Queues',
        value: props.queues.length,
        help: 'Published queue objects ready for routing.',
        tone: 'border-slate-200 bg-slate-50',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Agents',
        value: props.agents.length,
        help: 'Provisioned agents in the current domain.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Pause reasons',
        value: props.pauseReasons.length,
        help: 'Structured pause catalog for supervision.',
        tone: 'border-amber-100 bg-amber-50',
        kickerTone: 'text-amber-700',
        valueTone: 'text-amber-700',
        helpTone: 'text-amber-700/80',
    },
    {
        label: 'Pending callbacks',
        value: props.callbacks.filter((callback) => ['pending', 'assigned'].includes((callback.status || '').toLowerCase())).length,
        help: 'Callbacks still waiting for an owner or completion.',
        tone: 'border-rose-100 bg-rose-50',
        kickerTone: 'text-rose-700',
        valueTone: 'text-rose-700',
        helpTone: 'text-rose-700/80',
    },
    {
        label: 'Online agents',
        value: props.agents.filter((agent) => (agent.live_status || '').toLowerCase() === 'online').length,
        help: 'Agents already reachable in the live estate.',
        tone: 'border-emerald-100 bg-emerald-50',
        kickerTone: 'text-emerald-700',
        valueTone: 'text-emerald-700',
        helpTone: 'text-emerald-700/80',
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

const submitQueue = async () => {
    try {
        const response = await axios.post(props.routes.storeQueue || '/contact-center/queues', queueForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Queue created successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the queue right now.'))
    }
}

const submitAgent = async () => {
    try {
        const response = await axios.post(props.routes.storeAgent || '/contact-center/agents', agentForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Agent created successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the agent right now.'))
    }
}

const submitUser = async () => {
    try {
        const response = await axios.post(props.routes.storeUser || '/contact-center/users', userForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Contact center agent provisioned successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to provision access right now.'))
    }
}

const submitPauseReason = async () => {
    try {
        const response = await axios.post(props.routes.storePauseReason || '/contact-center/pause-reasons', pauseReasonForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Pause reason saved successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the pause reason right now.'))
    }
}

const submitCallback = async () => {
    try {
        const response = await axios.post(props.routes.storeCallback || '/contact-center/callbacks', callbackForm)
        succeedAndReload(response.data?.messages?.success?.[0] || t('Callback saved successfully.'))
    } catch (error) {
        setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the callback right now.'))
    }
}
</script>

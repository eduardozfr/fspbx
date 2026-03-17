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
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Configuration ledger') }}</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Run setup from an executive surface: inventory stays visible on the page, while creation flows open in focused modals.') }}</p>
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
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Configuration launchpad') }}</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Open only the action you need: queue routing, staffing, access, pause governance, or callback scheduling.') }}</p>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-5">
                <article v-for="action in actions" :key="action.key" class="rounded-[1.75rem] border p-5" :class="action.tone">
                    <div class="text-[11px] uppercase tracking-[0.22em]" :class="action.kickerTone">{{ t(action.kicker) }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ t(action.title) }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ t(action.description) }}</p>
                    <button type="button" class="mt-5 rounded-full px-4 py-2 text-sm font-semibold text-white" :class="action.buttonTone" @click="openAction(action.key)">
                        {{ t(action.button) }}
                    </button>
                </article>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr),380px]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Queue portfolio') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Keep extension, strategy, and live staffing visible while shaping the floor.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ queues.length }} {{ t('queues') }}</div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                        <div class="grid grid-cols-[110px,minmax(0,1.2fr),120px,90px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <div>{{ t('Extension') }}</div>
                            <div>{{ t('Queue') }}</div>
                            <div>{{ t('Strategy') }}</div>
                            <div>{{ t('Ready') }}</div>
                        </div>
                        <div v-if="queues.length" class="divide-y divide-slate-200">
                            <div v-for="queue in queues" :key="queue.uuid" class="grid grid-cols-[110px,minmax(0,1.2fr),120px,90px] gap-3 px-4 py-4 text-sm">
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

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Callback board') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Callbacks stay visible with owner, queue, and local time instead of being buried in free-text notes.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ callbacks.length }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article v-for="callback in callbacks" :key="callback.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-slate-900">{{ callback.contact_name || callback.phone_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ callback.phone_number }} | {{ callback.queue_name || t('No queue') }} | {{ callback.agent_name || t('Unassigned') }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="callbackTone(callback.status)">{{ t(callback.status || 'pending') }}</span>
                            </div>
                            <div class="mt-3 text-xs text-slate-500">{{ callback.preferred_callback_at || '-' }} | {{ callback.timezone || '-' }}</div>
                        </article>
                        <div v-if="!callbacks.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No callbacks are waiting in this board right now.') }}</div>
                    </div>
                </section>
            </section>

            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Agent roster') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('See which extensions are already provisioned and how they appear to the floor.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ agents.length }} {{ t('agents') }}</div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <article v-for="agent in agents" :key="agent.uuid" class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ agent.extension }} - {{ agent.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ agent.queue_count }} {{ t('queues') }}</div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="agentTone(agent.live_status)">{{ t(agent.live_status) }}</span>
                            </div>
                        </article>
                        <div v-if="!agents.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">{{ t('No agents are provisioned yet.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Pause governance') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Structured pause reasons make supervision and reporting look intentional, not improvised.') }}</p>
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

        <ExecutiveModal :show="showQueueModal" :title="t('Create queue')" :description="t('Publish a routing object with extension, strategy, wait posture, and initial staffing in one guided step.')" :kicker="t('Queue routing')" custom-class="sm:max-w-4xl" @close="showQueueModal = false">
            <form class="space-y-5" @submit.prevent="submitQueue">
                <div class="grid gap-4 md:grid-cols-2">
                    <input v-model="queueForm.queue_name" type="text" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Queue name')" />
                    <input v-model="queueForm.queue_extension" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Queue extension')" />
                    <select v-model="queueForm.queue_strategy" class="rounded-2xl border-slate-300 shadow-sm">
                        <option v-for="strategy in options.strategies || []" :key="strategy.value" :value="strategy.value">{{ t(strategy.label) }}</option>
                    </select>
                    <input v-model.number="queueForm.queue_max_wait_time" type="number" min="0" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Max wait')" />
                    <textarea v-model="queueForm.queue_description" rows="3" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Description')" />
                </div>
                <select v-model="queueForm.agent_uuids" multiple class="h-40 w-full rounded-2xl border-slate-300 shadow-sm">
                    <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                </select>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showQueueModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white">{{ t('Create queue') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal :show="showAgentModal" :title="t('Create agent')" :description="t('Turn an extension into a call-center agent with production-ready timeout and wrap-up controls.')" :kicker="t('Staffing')" custom-class="sm:max-w-3xl" @close="showAgentModal = false">
            <form class="space-y-5" @submit.prevent="submitAgent">
                <select v-model="agentForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                    <option value="">{{ t('Select an extension') }}</option>
                    <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                </select>
                <input v-model="agentForm.agent_name" type="text" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Agent label')" />
                <div class="grid gap-4 md:grid-cols-3">
                    <input v-model.number="agentForm.agent_call_timeout" type="number" min="5" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Call timeout')" />
                    <input v-model.number="agentForm.agent_wrap_up_time" type="number" min="0" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Wrap up')" />
                    <input v-model.number="agentForm.agent_reject_delay_time" type="number" min="0" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Reject delay')" />
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showAgentModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Create agent') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal :show="showUserModal" :title="t('Provision portal user')" :description="t('Create the portal access that makes the extension an agent or supervisor in the contact-center workspace.')" :kicker="t('Access')" custom-class="sm:max-w-2xl" @close="showUserModal = false">
            <form class="space-y-5" @submit.prevent="submitUser">
                <select v-model="userForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                    <option value="">{{ t('Select an extension') }}</option>
                    <option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                </select>
                <select v-model="userForm.role" class="w-full rounded-2xl border-slate-300 shadow-sm">
                    <option value="agent">{{ t('Agent') }}</option>
                    <option value="admin">{{ t('Supervisor') }}</option>
                </select>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showUserModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Provision access') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal :show="showPauseModal" :title="t('Create pause reason')" :description="t('Define the code and business label supervisors will use to explain occupancy and adherence clearly.')" :kicker="t('Discipline')" custom-class="sm:max-w-2xl" @close="showPauseModal = false">
            <form class="space-y-5" @submit.prevent="submitPauseReason">
                <div class="grid gap-4 md:grid-cols-2">
                    <input v-model="pauseReasonForm.code" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Code')" />
                    <input v-model="pauseReasonForm.name" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Name')" />
                </div>
                <input v-model.number="pauseReasonForm.auto_resume_minutes" type="number" min="1" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Auto resume (minutes)')" />
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showPauseModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Save pause reason') }}</button>
                </div>
            </form>
        </ExecutiveModal>

        <ExecutiveModal :show="showCallbackModal" :title="t('Schedule callback')" :description="t('Place the callback in the right queue, owner, and local-time context so recovery work is visible to the floor.')" :kicker="t('Recovery')" custom-class="sm:max-w-4xl" @close="showCallbackModal = false">
            <form class="space-y-5" @submit.prevent="submitCallback">
                <div class="grid gap-4 md:grid-cols-2">
                    <input v-model="callbackForm.contact_name" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Contact name')" />
                    <input v-model="callbackForm.phone_number" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Phone number')" />
                    <select v-model="callbackForm.call_center_queue_uuid" class="rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Queue') }}</option>
                        <option v-for="queue in queues" :key="queue.uuid" :value="queue.uuid">{{ queue.extension }} - {{ queue.name }}</option>
                    </select>
                    <select v-model="callbackForm.call_center_agent_uuid" class="rounded-2xl border-slate-300 shadow-sm">
                        <option value="">{{ t('Assign agent') }}</option>
                        <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                    </select>
                    <input v-model="callbackForm.state_code" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('State code')" />
                    <input v-model="callbackForm.timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Timezone')" />
                    <input v-model="callbackForm.preferred_callback_at" type="datetime-local" class="rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model="callbackForm.notes" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Notes')" />
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700" @click="showCallbackModal = false">{{ t('Cancel') }}</button>
                    <button type="submit" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white">{{ t('Save callback') }}</button>
                </div>
            </form>
        </ExecutiveModal>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import ExecutiveModal from '@pages/components/modal/ExecutiveModal.vue'
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
const showQueueModal = ref(false)
const showAgentModal = ref(false)
const showUserModal = ref(false)
const showPauseModal = ref(false)
const showCallbackModal = ref(false)

const queueForm = reactive({ queue_name: '', queue_extension: '', queue_strategy: 'ring-all', queue_description: '', queue_max_wait_time: 20, agent_uuids: [] })
const agentForm = reactive({ extension_uuid: '', agent_name: '', agent_status: 'Available', agent_call_timeout: 20, agent_wrap_up_time: 10, agent_reject_delay_time: 10 })
const userForm = reactive({ extension_uuid: '', role: 'agent' })
const pauseReasonForm = reactive({ code: '', name: '', auto_resume_minutes: '', is_active: true })
const callbackForm = reactive({ call_center_queue_uuid: '', call_center_agent_uuid: '', contact_name: '', phone_number: '', state_code: '', timezone: '', preferred_callback_at: '', notes: '' })

const summaryCards = computed(() => ([
    { label: 'Queues', value: props.queues.length, help: 'Routing objects published for the floor.', tone: 'border-slate-200 bg-slate-50', kickerTone: 'text-slate-500', valueTone: 'text-slate-900', helpTone: 'text-slate-500' },
    { label: 'Agents', value: props.agents.length, help: 'Extensions already provisioned for the contact-center estate.', tone: 'border-cyan-100 bg-cyan-50', kickerTone: 'text-cyan-700', valueTone: 'text-cyan-700', helpTone: 'text-cyan-700/80' },
    { label: 'Pause reasons', value: props.pauseReasons.length, help: 'Structured pause catalog available to supervision.', tone: 'border-amber-100 bg-amber-50', kickerTone: 'text-amber-700', valueTone: 'text-amber-700', helpTone: 'text-amber-700/80' },
    { label: 'Pending callbacks', value: props.callbacks.filter((callback) => ['pending', 'assigned'].includes((callback.status || '').toLowerCase())).length, help: 'Recovery work still waiting for action.', tone: 'border-rose-100 bg-rose-50', kickerTone: 'text-rose-700', valueTone: 'text-rose-700', helpTone: 'text-rose-700/80' },
    { label: 'Online agents', value: props.agents.filter((agent) => (agent.live_status || '').toLowerCase() === 'online').length, help: 'Agents currently reachable in the live estate.', tone: 'border-emerald-100 bg-emerald-50', kickerTone: 'text-emerald-700', valueTone: 'text-emerald-700', helpTone: 'text-emerald-700/80' },
]))

const actions = [
    { key: 'queue', kicker: 'Queue routing', title: 'Create queue', description: 'Publish the extension, strategy, and initial staffing in one controlled flow.', button: 'New queue', tone: 'border-slate-200 bg-slate-50', kickerTone: 'text-slate-400', buttonTone: 'bg-slate-950' },
    { key: 'agent', kicker: 'Staffing', title: 'Create agent', description: 'Bind an extension to call-center timing and live behavior.', button: 'New agent', tone: 'border-cyan-100 bg-cyan-50', kickerTone: 'text-cyan-700', buttonTone: 'bg-cyan-600' },
    { key: 'user', kicker: 'Access', title: 'Provision user', description: 'Give the extension agent or supervisor access to the workspace.', button: 'Provision', tone: 'border-emerald-100 bg-emerald-50', kickerTone: 'text-emerald-700', buttonTone: 'bg-emerald-600' },
    { key: 'pause', kicker: 'Discipline', title: 'Pause reason', description: 'Keep pause classification structured and reportable.', button: 'New reason', tone: 'border-amber-100 bg-amber-50', kickerTone: 'text-amber-700', buttonTone: 'bg-amber-600' },
    { key: 'callback', kicker: 'Recovery', title: 'Schedule callback', description: 'Place follow-up work into the right queue and owner context.', button: 'New callback', tone: 'border-rose-100 bg-rose-50', kickerTone: 'text-rose-700', buttonTone: 'bg-rose-600' },
]

const openAction = (key) => {
    if (key === 'queue') showQueueModal.value = true
    if (key === 'agent') showAgentModal.value = true
    if (key === 'user') showUserModal.value = true
    if (key === 'pause') showPauseModal.value = true
    if (key === 'callback') showCallbackModal.value = true
}

const setMessage = (type, text) => {
    message.type = type
    message.text = text
}

const reloadPage = (delay = 1100) => window.setTimeout(() => window.location.reload(), delay)
const succeedAndReload = (text) => {
    setMessage('success', text)
    reloadPage()
}

const callbackTone = (status) => {
    const value = (status || '').toLowerCase()
    if (value === 'completed') return 'bg-emerald-100 text-emerald-700'
    if (value === 'assigned') return 'bg-cyan-100 text-cyan-700'
    if (value === 'canceled') return 'bg-slate-100 text-slate-700'
    return 'bg-rose-100 text-rose-700'
}

const agentTone = (status) => {
    const value = (status || '').toLowerCase()
    if (value === 'busy') return 'bg-cyan-100 text-cyan-700'
    if (value === 'online') return 'bg-emerald-100 text-emerald-700'
    return 'bg-slate-100 text-slate-700'
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

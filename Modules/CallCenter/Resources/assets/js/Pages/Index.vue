<template>
    <MainLayout>
        <main class="mx-auto max-w-8xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.3em] text-slate-300">{{ t('Call Center') }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ t('Live queue operations, callbacks, supervision, and monitoring') }}</h1>
                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-6">
                    <div class="rounded-2xl bg-white/10 px-4 py-3"><div class="text-xs uppercase tracking-wide text-slate-300">{{ t('Queues') }}</div><div class="mt-2 text-2xl font-semibold">{{ summary.queues }}</div></div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3"><div class="text-xs uppercase tracking-wide text-slate-300">{{ t('Agents') }}</div><div class="mt-2 text-2xl font-semibold">{{ summary.agents }}</div></div>
                    <div class="rounded-2xl bg-emerald-400/15 px-4 py-3"><div class="text-xs uppercase tracking-wide text-emerald-100">{{ t('Online') }}</div><div class="mt-2 text-2xl font-semibold text-emerald-200">{{ summary.online_agents }}</div></div>
                    <div class="rounded-2xl bg-amber-400/15 px-4 py-3"><div class="text-xs uppercase tracking-wide text-amber-100">{{ t('Busy') }}</div><div class="mt-2 text-2xl font-semibold text-amber-200">{{ summary.busy_agents }}</div></div>
                    <div class="rounded-2xl bg-cyan-400/15 px-4 py-3"><div class="text-xs uppercase tracking-wide text-cyan-100">{{ t('Paused') }}</div><div class="mt-2 text-2xl font-semibold text-cyan-200">{{ summary.paused_agents }}</div></div>
                    <div class="rounded-2xl bg-rose-400/15 px-4 py-3"><div class="text-xs uppercase tracking-wide text-rose-100">{{ t('Callbacks') }}</div><div class="mt-2 text-2xl font-semibold text-rose-200">{{ summary.pending_callbacks }}</div></div>
                </div>
            </section>

            <div v-if="message.text" class="rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">{{ message.text }}</div>

            <div class="flex flex-wrap gap-2">
                <button v-for="tab in tabs" :key="tab.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeTab === tab.value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow'" @click="activeTab = tab.value">{{ t(tab.label) }}</button>
            </div>

            <div v-if="activeTab === 'overview'" class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Queues') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Monitor queue pressure, agent coverage, and service levels in one place.') }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="queue in queues" :key="queue.uuid" class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ queue.extension }} - {{ queue.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ queue.strategy }} | {{ t('Waiting') }}: {{ queue.live_waiting_members }} | SLA: {{ queue.service_level_percent }}%</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ t('Abandonment') }}: {{ queue.abandonment_rate }}% | {{ t('Average wait') }}: {{ queue.avg_wait_seconds }}s</div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ t('Max wait') }}: {{ queue.max_wait_time }}s</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="agent in queue.agents" :key="agent.uuid" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ agent.extension }} - {{ agent.name }}</span>
                                <span v-if="!queue.agents.length" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">{{ t('No agents assigned') }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Agents') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Pause or resume agents with explicit reasons for operational visibility.') }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="agent in agents" :key="agent.uuid" class="rounded-2xl border border-slate-200 p-4">
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
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Pending callbacks') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Track callback ownership, due dates, and resolution status without leaving the wallboard flow.') }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="callback in callbacks" :key="callback.uuid" class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ callback.contact_name || callback.phone_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ callback.phone_number }} | {{ callback.queue_label || t('No queue') }} | {{ t(callback.status) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ callback.state_code || t('No state') }} | {{ callback.timezone || t('No timezone') }}</div>
                                </div>
                                <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ callback.preferred_callback_at || callback.requested_at }}</div>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                                <select v-model="callbackAgentForms[callback.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm">
                                    <option value="">{{ t('Assign agent') }}</option>
                                    <option v-for="agent in options.agents" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                                </select>
                                <select v-model="callbackStatusForms[callback.uuid]" class="w-full rounded-2xl border-slate-300 text-sm shadow-sm">
                                    <option value="pending">{{ t('Pending') }}</option>
                                    <option value="assigned">{{ t('Assigned') }}</option>
                                    <option value="completed">{{ t('Completed') }}</option>
                                    <option value="canceled">{{ t('Canceled') }}</option>
                                </select>
                                <button type="button" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white" @click="saveCallback(callback.uuid)">{{ t('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div v-else-if="activeTab === 'wallboard'" class="grid gap-6 xl:grid-cols-[0.8fr,1.2fr]">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Wallboard summary') }}</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-2xl border border-slate-200 p-4">{{ t('Active calls') }}: {{ wallboard.summary.active_calls }}</div>
                        <div class="rounded-2xl border border-slate-200 p-4">{{ t('Waiting members') }}: {{ wallboard.summary.waiting_members }}</div>
                        <div class="rounded-2xl border border-slate-200 p-4">{{ t('Available agents') }}: {{ wallboard.summary.available_agents }}</div>
                        <div class="rounded-2xl border border-slate-200 p-4">SLA: {{ wallboard.summary.service_level_percent }}%</div>
                        <div class="rounded-2xl border border-slate-200 p-4">{{ t('Abandonment') }}: {{ wallboard.summary.abandonment_rate }}%</div>
                        <div class="rounded-2xl border border-slate-200 p-4">{{ t('Average wait') }}: {{ wallboard.summary.avg_wait_seconds }}s</div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Live monitoring') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="startMonitoring">
                        <select v-model="monitorForm.call_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option value="">{{ t('Select active call') }}</option>
                            <option v-for="call in wallboard.active_calls" :key="call.call_uuid" :value="call.call_uuid">{{ call.agent_extension }} - {{ call.destination }}</option>
                        </select>
                        <select v-model="monitorForm.call_center_agent_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option value="">{{ t('Agent on the call') }}</option>
                            <option v-for="agent in options.agents" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                        </select>
                        <select v-model="monitorForm.supervisor_extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option value="">{{ t('Select supervisor extension') }}</option>
                            <option v-for="extension in options.extensions" :key="extension.value" :value="extension.value">{{ extension.label }}</option>
                        </select>
                        <select v-model="monitorForm.mode" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option v-for="mode in options.monitoring_modes" :key="mode.value" :value="mode.value">{{ t(mode.label) }}</option>
                        </select>
                        <textarea v-model="monitorForm.notes" rows="3" :placeholder="t('Monitoring notes')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Start monitoring') }}</button>
                    </form>
                    <div class="mt-6 space-y-3">
                        <div v-for="call in wallboard.active_calls" :key="call.call_uuid" class="rounded-2xl border border-slate-200 p-4 text-sm">
                            <div class="font-medium text-slate-900">{{ call.agent_extension }} - {{ call.destination }}</div>
                            <div class="text-slate-500">{{ call.direction }} | {{ call.state }} | {{ call.duration_seconds }}s</div>
                        </div>
                    </div>
                </section>
            </div>

            <div v-else class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Create queue') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitQueue">
                        <input v-model="queueForm.queue_name" type="text" :placeholder="t('Queue name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="queueForm.queue_extension" type="text" :placeholder="t('Queue extension')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <select v-model="queueForm.queue_strategy" class="w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="strategy in options.strategies" :key="strategy.value" :value="strategy.value">{{ t(strategy.label) }}</option></select>
                        </div>
                        <input v-model.number="queueForm.queue_max_wait_time" type="number" min="0" :placeholder="t('Max wait')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <textarea v-model="queueForm.queue_description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                        <select v-model="queueForm.agent_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm">
                            <option v-for="agent in options.agents" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                        </select>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create queue') }}</button>
                    </form>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Create agent') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitAgent">
                        <select v-model="agentForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select an extension') }}</option><option v-for="extension in options.extensions" :key="extension.value" :value="extension.value">{{ extension.label }}</option></select>
                        <input v-model="agentForm.agent_name" type="text" :placeholder="t('Agent label')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model.number="agentForm.agent_call_timeout" type="number" min="5" :placeholder="t('Call timeout')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model.number="agentForm.agent_wrap_up_time" type="number" min="0" :placeholder="t('Wrap up')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <input v-model.number="agentForm.agent_reject_delay_time" type="number" min="0" :placeholder="t('Reject delay')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create agent') }}</button>
                    </form>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Provision user') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitUser">
                        <select v-model="userForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select an extension') }}</option><option v-for="extension in options.extensions" :key="extension.value" :value="extension.value">{{ extension.label }}</option></select>
                        <select v-model="userForm.role" class="w-full rounded-2xl border-slate-300 shadow-sm">
                            <option value="agent">{{ t('Agent') }}</option>
                            <option value="admin">{{ t('Supervisor') }}</option>
                        </select>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Provision access') }}</button>
                    </form>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Pause reasons') }}</h2>
                    <form class="mt-4 space-y-3" @submit.prevent="submitPauseReason">
                        <div class="grid gap-3 md:grid-cols-2">
                            <input v-model="pauseReasonForm.code" type="text" :placeholder="t('Code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                            <input v-model="pauseReasonForm.name" type="text" :placeholder="t('Name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        </div>
                        <input v-model.number="pauseReasonForm.auto_resume_minutes" type="number" min="1" :placeholder="t('Auto resume (minutes)')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input v-model="pauseReasonForm.is_active" type="checkbox" class="rounded border-slate-300" /><span>{{ t('Reason active') }}</span></label>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save pause reason') }}</button>
                    </form>
                    <div class="mt-4 space-y-2 text-sm">
                        <div v-for="reason in pauseReasons" :key="reason.uuid" class="rounded-2xl border border-slate-200 p-3">{{ reason.name }} <span class="text-slate-500">({{ reason.code }})</span></div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ t('Create callback') }}</h2>
                    <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitCallback">
                        <input v-model="callbackForm.contact_name" type="text" :placeholder="t('Contact name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="callbackForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <select v-model="callbackForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Queue') }}</option><option v-for="queue in queues" :key="queue.uuid" :value="queue.uuid">{{ queue.extension }} - {{ queue.name }}</option></select>
                        <select v-model="callbackForm.call_center_agent_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Assign agent') }}</option><option v-for="agent in options.agents" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option></select>
                        <input v-model="callbackForm.state_code" type="text" :placeholder="t('State code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="callbackForm.timezone" type="text" :placeholder="t('Timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="callbackForm.preferred_callback_at" type="datetime-local" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <input v-model="callbackForm.notes" type="text" :placeholder="t('Notes')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save callback') }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </MainLayout>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@layouts/MainLayout.vue'
import { useLocale } from '../../../../../resources/js/composables/useLocale'

const props = defineProps({ summary: Object, queues: Array, agents: Array, wallboard: Object, callbacks: Array, pauseReasons: Array, options: Object, initialTab: String, routes: Object })
const page = usePage()
const { t } = useLocale()
const tabs = [{ value: 'overview', label: 'Overview' }, { value: 'wallboard', label: 'Wallboard' }, { value: 'settings', label: 'Settings' }]
const activeTab = ref(props.initialTab || 'overview')
const message = reactive({ type: 'success', text: '' })
const queueForm = reactive({ queue_name: '', queue_extension: '', queue_strategy: 'ring-all', queue_description: '', queue_max_wait_time: 20, agent_uuids: [] })
const agentForm = reactive({ extension_uuid: '', agent_name: '', agent_status: 'Available', agent_call_timeout: 20, agent_wrap_up_time: 10, agent_reject_delay_time: 10 })
const userForm = reactive({ extension_uuid: '', role: 'agent' })
const pauseReasonForm = reactive({ code: '', name: '', auto_resume_minutes: '', is_active: true })
const callbackForm = reactive({ call_center_queue_uuid: '', call_center_agent_uuid: '', contact_name: '', phone_number: '', state_code: '', timezone: '', preferred_callback_at: '', notes: '' })
const monitorForm = reactive({ call_uuid: '', call_center_agent_uuid: '', supervisor_extension_uuid: '', mode: 'listen', notes: '' })
const callbackStatusForms = reactive(Object.fromEntries(props.callbacks.map((callback) => [callback.uuid, callback.status])))
const callbackAgentForms = reactive(Object.fromEntries(props.callbacks.map((callback) => [callback.uuid, callback.call_center_agent_uuid || ''])))
const pauseReasonForms = reactive(Object.fromEntries(props.agents.map((agent) => [agent.uuid, ''])))
const pauseNoteForms = reactive(Object.fromEntries(props.agents.map((agent) => [agent.uuid, ''])))

const setMessage = (type, text) => { message.type = type; message.text = text }
const reloadPage = () => window.location.reload()
const submitQueue = async () => { try { await axios.post(props.routes.storeQueue, queueForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the queue right now.')) } }
const submitAgent = async () => { try { await axios.post(props.routes.storeAgent, agentForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the agent right now.')) } }
const submitUser = async () => { try { await axios.post(props.routes.storeUser, userForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to provision the user right now.')) } }
const submitPauseReason = async () => { try { await axios.post(props.routes.storePauseReason, pauseReasonForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the pause reason right now.')) } }
const submitCallback = async () => { try { await axios.post(props.routes.storeCallback, callbackForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the callback right now.')) } }
const pauseAgent = async (agentUuid) => { try { await axios.post(`/contact-center/agents/${agentUuid}/pause`, { pause_reason_uuid: pauseReasonForms[agentUuid] || null, note: pauseNoteForms[agentUuid] || null }); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to pause the agent right now.')) } }
const resumeAgent = async (agentUuid) => { try { await axios.post(`/contact-center/agents/${agentUuid}/resume`); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to resume the agent right now.')) } }
const saveCallback = async (callbackUuid) => { try { await axios.put(`/contact-center/callbacks/${callbackUuid}`, { status: callbackStatusForms[callbackUuid], call_center_agent_uuid: callbackAgentForms[callbackUuid] || null }); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to update the callback right now.')) } }
const startMonitoring = async () => { try { await axios.post(props.routes.startMonitoring, monitorForm); setMessage('success', t('Monitoring session started successfully.')) } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to start monitoring right now.')) } }

onMounted(() => {
    const domainUuid = page.props.selectedDomainUuid
    if (!domainUuid || !window.Echo) return
    window.Echo.private(`call-center.domain.${domainUuid}`).listen('.call-center.wallboard.updated', () => setMessage('success', t('Wallboard updated in real time.')))
})

onBeforeUnmount(() => {
    const domainUuid = page.props.selectedDomainUuid
    if (domainUuid && window.Echo) {
        window.Echo.leave(`call-center.domain.${domainUuid}`)
    }
})
</script>

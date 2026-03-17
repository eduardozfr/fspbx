<template>
    <div class="grid gap-6 xl:grid-cols-2">
        <div v-if="message.text" class="xl:col-span-2 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            {{ message.text }}
        </div>
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Create queue') }}</h2>
                <HelpTooltip :text="t('Create the queue here, then attach the right agents and strategy before activating campaigns or callbacks.')"/>
            </div>
            <form class="mt-4 space-y-3" @submit.prevent="submitQueue">
                <input v-model="queueForm.queue_name" type="text" :placeholder="t('Queue name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <div class="grid gap-3 md:grid-cols-2">
                    <input v-model="queueForm.queue_extension" type="text" :placeholder="t('Queue extension')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <select v-model="queueForm.queue_strategy" class="w-full rounded-2xl border-slate-300 shadow-sm"><option v-for="strategy in options.strategies || []" :key="strategy.value" :value="strategy.value">{{ t(strategy.label) }}</option></select>
                </div>
                <input v-model.number="queueForm.queue_max_wait_time" type="number" min="0" :placeholder="t('Max wait')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <textarea v-model="queueForm.queue_description" rows="3" :placeholder="t('Description')" class="w-full rounded-2xl border-slate-300 shadow-sm"></textarea>
                <select v-model="queueForm.agent_uuids" multiple class="h-32 w-full rounded-2xl border-slate-300 shadow-sm">
                    <option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option>
                </select>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create queue') }}</button>
            </form>
        </section>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Create agent') }}</h2>
                <HelpTooltip :text="t('Use a registered extension and define timeout, wrap-up, and reject delay before putting the agent into production.')"/>
            </div>
            <form class="mt-4 space-y-3" @submit.prevent="submitAgent">
                <select v-model="agentForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select an extension') }}</option><option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option></select>
                <input v-model="agentForm.agent_name" type="text" :placeholder="t('Agent label')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <div class="grid gap-3 md:grid-cols-2">
                    <input v-model.number="agentForm.agent_call_timeout" type="number" min="5" :placeholder="t('Call timeout')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                    <input v-model.number="agentForm.agent_wrap_up_time" type="number" min="0" :placeholder="t('Wrap up')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                </div>
                <input v-model.number="agentForm.agent_reject_delay_time" type="number" min="0" :placeholder="t('Reject delay')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Create agent') }}</button>
            </form>
        </section>

        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ t('Provision user') }}</h2>
                <HelpTooltip :text="t('Provisioning creates or reuses the portal user so the extension can operate as an agent or supervisor.')"/>
            </div>
            <form class="mt-4 space-y-3" @submit.prevent="submitUser">
                <select v-model="userForm.extension_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Select an extension') }}</option><option v-for="extension in options.extensions || []" :key="extension.value" :value="extension.value">{{ extension.label }}</option></select>
                <select v-model="userForm.role" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="agent">{{ t('Agent') }}</option><option value="admin">{{ t('Supervisor') }}</option></select>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Provision access') }}</button>
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
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save pause reason') }}</button>
            </form>

            <form class="mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submitCallback">
                <input v-model="callbackForm.contact_name" type="text" :placeholder="t('Contact name')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <input v-model="callbackForm.phone_number" type="text" :placeholder="t('Phone number')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <select v-model="callbackForm.call_center_queue_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Queue') }}</option><option v-for="queue in queues" :key="queue.uuid" :value="queue.uuid">{{ queue.extension }} - {{ queue.name }}</option></select>
                <select v-model="callbackForm.call_center_agent_uuid" class="w-full rounded-2xl border-slate-300 shadow-sm"><option value="">{{ t('Assign agent') }}</option><option v-for="agent in options.agents || []" :key="agent.value" :value="agent.value">{{ agent.extension }} - {{ agent.label }}</option></select>
                <input v-model="callbackForm.state_code" type="text" :placeholder="t('State code')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <input v-model="callbackForm.timezone" type="text" :placeholder="t('Timezone')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <input v-model="callbackForm.preferred_callback_at" type="datetime-local" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <input v-model="callbackForm.notes" type="text" :placeholder="t('Notes')" class="w-full rounded-2xl border-slate-300 shadow-sm" />
                <div class="md:col-span-2"><button type="submit" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">{{ t('Save callback') }}</button></div>
            </form>
        </section>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    queues: { type: Array, default: () => [] },
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

const setMessage = (type, text) => { message.type = type; message.text = text }
const reloadPage = () => window.location.reload()
const submitQueue = async () => { try { await axios.post(props.routes.storeQueue || '/contact-center/queues', queueForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the queue right now.')) } }
const submitAgent = async () => { try { await axios.post(props.routes.storeAgent || '/contact-center/agents', agentForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the agent right now.')) } }
const submitUser = async () => { try { await axios.post(props.routes.storeUser || '/contact-center/users', userForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to provision access right now.')) } }
const submitPauseReason = async () => { try { await axios.post(props.routes.storePauseReason || '/contact-center/pause-reasons', pauseReasonForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the pause reason right now.')) } }
const submitCallback = async () => { try { await axios.post(props.routes.storeCallback || '/contact-center/callbacks', callbackForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the callback right now.')) } }
</script>

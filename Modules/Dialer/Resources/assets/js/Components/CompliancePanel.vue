<template>
    <div class="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Custom compliance profiles') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('Create your own dialing profiles instead of relying only on state editing.') }}</p>
                    </div>
                    <button v-if="editingComplianceProfileUuid" type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="resetComplianceProfileForm">{{ t('New profile') }}</button>
                </div>

                <div v-if="message.text" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="message.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                    {{ message.text }}
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitComplianceProfile">
                    <div class="grid gap-4 md:grid-cols-2">
                        <input v-model="profileForm.name" type="text" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Profile name')" />
                        <textarea v-model="profileForm.description" rows="3" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Describe when this profile is allowed to be used.')" />
                        <input v-model="profileForm.timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Timezone override')" />
                        <select v-model="profileForm.state_codes" multiple class="h-32 rounded-2xl border-slate-300 shadow-sm">
                            <option v-for="state in options.states || []" :key="state.value" :value="state.value">{{ state.label }}</option>
                        </select>
                    </div>
                    <ScheduleMatrix :schedule="profileForm.schedule" :days="scheduleDays" />
                    <div class="grid gap-4 md:grid-cols-2">
                        <input v-model="profileForm.legal_reference_url" type="url" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Legal reference URL')" />
                        <textarea v-model="profileForm.notes" rows="2" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Notes')" />
                    </div>
                    <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200">
                        <input v-model="profileForm.is_active" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" />
                        <span>{{ t('Profile active') }}</span>
                    </label>
                    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ editingComplianceProfileUuid ? t('Update compliance profile') : t('Create compliance profile') }}</button>
                </form>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Profile catalog') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="profile in complianceProfiles" :key="profile.uuid" class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-slate-900">{{ profile.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ profile.schedule_summary }}</div>
                            </div>
                            <div class="flex gap-2">
                                <span v-if="profile.is_system" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ t('System') }}</span>
                                <template v-else>
                                    <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="loadComplianceProfile(profile)">{{ t('Edit') }}</button>
                                    <button type="button" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" @click="deleteComplianceProfile(profile.uuid)">{{ t('Delete') }}</button>
                                </template>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </section>

        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('UF baseline rules') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="rule in stateRules" :key="rule.uuid" class="cursor-pointer rounded-3xl border p-4 transition" :class="selectedStateRuleUuid === rule.uuid ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50'" @click="loadSelectedStateRule(rule)">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ rule.state_code }} - {{ rule.state_name }}</div>
                                <div class="mt-1 text-xs" :class="selectedStateRuleUuid === rule.uuid ? 'text-slate-300' : 'text-slate-500'">{{ rule.schedule_summary }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="selectedStateRuleUuid === rule.uuid ? 'bg-white/10 text-slate-100' : 'bg-white text-slate-700 ring-1 ring-slate-200'">{{ rule.timezone }}</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-[2rem] bg-[#fffaf0] p-6 shadow-sm ring-1 ring-amber-200">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('UF rule editor') }}</h2>
                    <HelpTooltip :text="t('Use this editor to refine the baseline by state. For campaign governance, prefer named compliance profiles.')"/>
                </div>
                <form class="mt-5 space-y-4" @submit.prevent="submitStateRule">
                    <div class="grid gap-4 md:grid-cols-2">
                        <input v-model="stateRuleForm.state_code" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('State code')" />
                        <input v-model="stateRuleForm.state_name" type="text" class="rounded-2xl border-slate-300 shadow-sm" :placeholder="t('State name')" />
                        <input v-model="stateRuleForm.timezone" type="text" class="rounded-2xl border-slate-300 shadow-sm md:col-span-2" :placeholder="t('Timezone')" />
                    </div>
                    <ScheduleMatrix :schedule="stateRuleForm.schedule" :days="scheduleDays" />
                    <textarea v-model="stateRuleForm.notes" rows="3" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Document the legal or operational note behind this window.')" />
                    <input v-model="stateRuleForm.legal_reference_url" type="url" class="w-full rounded-2xl border-slate-300 shadow-sm" :placeholder="t('Legal reference URL')" />
                    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">{{ t('Save state dialing rule') }}</button>
                </form>
            </section>
        </section>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import HelpTooltip from '@generalComponents/HelpTooltip.vue'
import { useLocale } from '@composables/useLocale'
import ScheduleMatrix from './ScheduleMatrix.vue'

const props = defineProps({
    complianceProfiles: { type: Array, default: () => [] },
    stateRules: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const message = reactive({ type: 'success', text: '' })
const selectedStateRuleUuid = ref(props.stateRules?.[0]?.uuid || '')
const editingComplianceProfileUuid = ref(null)
const scheduleDays = [
    { value: 'monday', label: 'Monday' },
    { value: 'tuesday', label: 'Tuesday' },
    { value: 'wednesday', label: 'Wednesday' },
    { value: 'thursday', label: 'Thursday' },
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' },
]
const baseline = () => ({ monday: { enabled: true, start: '09:00', end: '21:00' }, tuesday: { enabled: true, start: '09:00', end: '21:00' }, wednesday: { enabled: true, start: '09:00', end: '21:00' }, thursday: { enabled: true, start: '09:00', end: '21:00' }, friday: { enabled: true, start: '09:00', end: '21:00' }, saturday: { enabled: true, start: '10:00', end: '16:00' }, sunday: { enabled: false, start: null, end: null } })
const profileForm = reactive({ name: '', description: '', timezone: '', state_codes: [], schedule: baseline(), notes: '', legal_reference_url: '', is_active: true })
const stateRuleForm = reactive({ state_code: '', state_name: '', timezone: 'America/Sao_Paulo', schedule: baseline(), notes: '', legal_reference_url: '' })

const setMessage = (type, text) => { message.type = type; message.text = text }
const reloadPage = () => window.location.reload()
const interpolateRoute = (template, token, value) => (template || '').replace(token, value)
const resetComplianceProfileForm = () => { editingComplianceProfileUuid.value = null; Object.assign(profileForm, { name: '', description: '', timezone: '', state_codes: [], schedule: baseline(), notes: '', legal_reference_url: '', is_active: true }) }
const loadComplianceProfile = (profile) => { editingComplianceProfileUuid.value = profile.uuid; Object.assign(profileForm, { name: profile.name, description: profile.description || '', timezone: profile.timezone || '', state_codes: [...(profile.state_codes || [])], schedule: JSON.parse(JSON.stringify(profile.schedule || baseline())), notes: profile.notes || '', legal_reference_url: profile.legal_reference_url || '', is_active: true }) }
const submitComplianceProfile = async () => { try { await axios[editingComplianceProfileUuid.value ? 'put' : 'post'](editingComplianceProfileUuid.value ? interpolateRoute(props.routes.updateComplianceProfile || '/dialer/compliance-profiles/__PROFILE__', '__PROFILE__', editingComplianceProfileUuid.value) : (props.routes.storeComplianceProfile || '/dialer/compliance-profiles'), profileForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the compliance profile right now.')) } }
const deleteComplianceProfile = async (uuid) => { if (!window.confirm(t('Delete this compliance profile?'))) return; try { await axios.delete(interpolateRoute(props.routes.destroyComplianceProfile || '/dialer/compliance-profiles/__PROFILE__', '__PROFILE__', uuid)); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to delete the compliance profile right now.')) } }
const loadSelectedStateRule = (rule) => { selectedStateRuleUuid.value = rule.uuid; Object.assign(stateRuleForm, { state_code: rule.state_code, state_name: rule.state_name, timezone: rule.timezone, schedule: JSON.parse(JSON.stringify(rule.schedule || baseline())), notes: rule.notes || '', legal_reference_url: rule.legal_reference_url || '' }) }
const submitStateRule = async () => { try { await axios[selectedStateRuleUuid.value ? 'put' : 'post'](selectedStateRuleUuid.value ? interpolateRoute(props.routes.updateStateRule || '/dialer/state-rules/__STATE_RULE__', '__STATE_RULE__', selectedStateRuleUuid.value) : (props.routes.storeStateRule || '/dialer/state-rules'), stateRuleForm); reloadPage() } catch (error) { setMessage('error', error.response?.data?.messages?.error?.[0] || t('Unable to save the state dialing rule right now.')) } }
</script>

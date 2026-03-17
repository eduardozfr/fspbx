<template>
    <MainLayout>
        <main class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#edf4ff_32%,#fff9ef_100%)] px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1650px] space-y-6">
                <section class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl lg:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-300">{{ t('Call Center') }}</p>
                            <h1 class="mt-2 text-3xl font-semibold">{{ t('Professional call center floor') }}</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ t('Monitor wallboard KPIs, supervise agents, resolve callbacks, and manage queue operations from a denser floor-control workspace.') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                            <article class="rounded-3xl bg-white/10 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-slate-300">{{ t('Queues') }}</div><div class="mt-3 text-3xl font-semibold">{{ summary.queues }}</div></article>
                            <article class="rounded-3xl bg-white/10 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-slate-300">{{ t('Agents') }}</div><div class="mt-3 text-3xl font-semibold">{{ summary.agents }}</div></article>
                            <article class="rounded-3xl bg-emerald-400/15 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-emerald-100">{{ t('Service level') }}</div><div class="mt-3 text-3xl font-semibold text-emerald-200">{{ summary.service_level_percent }}%</div></article>
                            <article class="rounded-3xl bg-amber-400/15 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-amber-100">{{ t('Occupancy') }}</div><div class="mt-3 text-3xl font-semibold text-amber-200">{{ summary.occupancy_percent }}%</div></article>
                            <article class="rounded-3xl bg-cyan-400/15 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-cyan-100">{{ t('Waiting members') }}</div><div class="mt-3 text-3xl font-semibold text-cyan-100">{{ summary.waiting_members }}</div></article>
                            <article class="rounded-3xl bg-rose-400/15 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-rose-100">{{ t('Callbacks due') }}</div><div class="mt-3 text-3xl font-semibold text-rose-100">{{ summary.callbacks_due_now }}</div></article>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <article v-for="lane in navigationLanes" :key="lane.value" class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t(lane.kicker) }}</div>
                        <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ t(lane.title) }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ t(lane.description) }}</p>
                        <button type="button" class="mt-4 rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold text-white" @click="activeTab = lane.value">{{ t(lane.action) }}</button>
                    </article>
                </section>

                <div class="flex flex-wrap gap-2 rounded-[1.5rem] bg-white p-3 shadow-sm ring-1 ring-slate-200">
                    <button v-for="tab in tabs" :key="tab.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeTab === tab.value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow'" @click="activeTab = tab.value">{{ t(tab.label) }}</button>
                </div>

                <OperationsPanel v-if="activeTab === 'operations'" :summary="summary" :queues="queues" :wallboard="wallboard" :alerts="alerts" :pause-breakdown="pauseBreakdown" :callbacks="callbacks" @openConfig="activeTab = 'configuration'" />
                <SupervisionPanel v-else-if="activeTab === 'supervision'" :agents="agents" :callbacks="callbacks" :pause-reasons="pauseReasons" :options="options" :wallboard="wallboard" :routes="routes" />
                <ConfigurationPanel v-else :queues="queues" :agents="agents" :pause-reasons="pauseReasons" :callbacks="callbacks" :options="options" :routes="routes" />
            </div>
        </main>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import MainLayout from '@layouts/MainLayout.vue'
import { useLocale } from '@composables/useLocale'
import OperationsPanel from '../Components/OperationsPanel.vue'
import SupervisionPanel from '../Components/SupervisionPanel.vue'
import ConfigurationPanel from '../Components/ConfigurationPanel.vue'

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    queues: { type: Array, default: () => [] },
    agents: { type: Array, default: () => [] },
    wallboard: { type: Object, default: () => ({ summary: {}, active_calls: [] }) },
    callbacks: { type: Array, default: () => [] },
    pauseReasons: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    pauseBreakdown: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    initialTab: { type: String, default: 'operations' },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const tabs = [
    { value: 'operations', label: 'Operations' },
    { value: 'supervision', label: 'Supervision' },
    { value: 'configuration', label: 'Configuration' },
]
const navigationLanes = [
    { value: 'operations', kicker: 'Wallboard', title: 'Run the floor', description: 'Keep queue pressure, service level, active conversations, and live alerts in one place.', action: 'Open wallboard' },
    { value: 'supervision', kicker: 'People', title: 'Supervise agents', description: 'Manage pauses, callbacks, and monitoring tasks from a dedicated supervisor lane.', action: 'Open supervision' },
    { value: 'configuration', kicker: 'Setup', title: 'Configure queues', description: 'Adjust queue routing, agent provisioning, pause reasons, and callback defaults without leaving the module.', action: 'Open configuration' },
]
const initialTab = tabs.some((tab) => tab.value === props.initialTab) ? props.initialTab : 'operations'
const activeTab = ref(initialTab)
</script>

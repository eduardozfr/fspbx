<template>
    <MainLayout>
        <main class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#eef4f7_100%)] px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1650px] space-y-6">
                <section class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl lg:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-300">{{ t('Call Center') }}</p>
                            <h1 class="mt-2 text-3xl font-semibold">{{ t('Contact center operations hub') }}</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ t('Monitor wallboard KPIs, supervise agents, manage callbacks, and configure queue operations from a more professional control panel.') }}</p>
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

                <div class="flex flex-wrap gap-2">
                    <button v-for="tab in tabs" :key="tab.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeTab === tab.value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow'" @click="activeTab = tab.value">{{ t(tab.label) }}</button>
                </div>

                <OperationsPanel v-if="activeTab === 'operations'" :summary="summary" :queues="queues" :wallboard="wallboard" :alerts="alerts" :pause-breakdown="pauseBreakdown" @openConfig="activeTab = 'configuration'" />
                <SupervisionPanel v-else-if="activeTab === 'supervision'" :agents="agents" :callbacks="callbacks" :pause-reasons="pauseReasons" :options="options" :wallboard="wallboard" :routes="routes" />
                <ConfigurationPanel v-else :queues="queues" :options="options" :routes="routes" />
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
const initialTab = tabs.some((tab) => tab.value === props.initialTab) ? props.initialTab : 'operations'
const activeTab = ref(initialTab)
</script>

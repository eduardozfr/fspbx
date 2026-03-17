<template>
    <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
        <section class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Wallboard overview') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('Bring SLA, waiting members, occupancy, and queue pressure into one supervision surface.') }}</p>
                    </div>
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700" @click="$emit('openConfig')">{{ t('Open setup') }}</button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <article class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200"><div class="text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ t('Active calls') }}</div><div class="mt-3 text-3xl font-semibold text-slate-900">{{ summary.active_calls || wallboard.summary.active_calls }}</div></article>
                    <article class="rounded-3xl bg-cyan-50 p-4 ring-1 ring-cyan-100"><div class="text-[11px] uppercase tracking-[0.2em] text-cyan-700">{{ t('Waiting members') }}</div><div class="mt-3 text-3xl font-semibold text-cyan-700">{{ summary.waiting_members }}</div></article>
                    <article class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100"><div class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">{{ t('Service level') }}</div><div class="mt-3 text-3xl font-semibold text-emerald-700">{{ summary.service_level_percent }}%</div></article>
                    <article class="rounded-3xl bg-amber-50 p-4 ring-1 ring-amber-100"><div class="text-[11px] uppercase tracking-[0.2em] text-amber-700">{{ t('Occupancy') }}</div><div class="mt-3 text-3xl font-semibold text-amber-700">{{ summary.occupancy_percent }}%</div></article>
                    <article class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100"><div class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">{{ t('Answered today') }}</div><div class="mt-3 text-3xl font-semibold text-emerald-700">{{ summary.answered_today }}</div></article>
                    <article class="rounded-3xl bg-rose-50 p-4 ring-1 ring-rose-100"><div class="text-[11px] uppercase tracking-[0.2em] text-rose-700">{{ t('Abandoned today') }}</div><div class="mt-3 text-3xl font-semibold text-rose-700">{{ summary.abandoned_today }}</div></article>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ t('Queue pressure board') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('Focus on the queues that are stretching service level, coverage, or wait targets.') }}</p>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-[0.2em] text-slate-500">
                                <th class="pb-3 pr-4">{{ t('Queue') }}</th>
                                <th class="pb-3 pr-4">{{ t('Waiting') }}</th>
                                <th class="pb-3 pr-4">{{ t('Available') }}</th>
                                <th class="pb-3 pr-4">{{ t('Coverage gap') }}</th>
                                <th class="pb-3 pr-4">SLA</th>
                                <th class="pb-3 pr-4">{{ t('Abandonment') }}</th>
                                <th class="pb-3">{{ t('Average wait') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="queue in queues" :key="queue.uuid">
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ queue.extension }} - {{ queue.name }}</td>
                                <td class="py-3 pr-4">{{ queue.live_waiting_members }}</td>
                                <td class="py-3 pr-4">{{ queue.available_agents }}</td>
                                <td class="py-3 pr-4">{{ queue.coverage_gap }}</td>
                                <td class="py-3 pr-4">{{ queue.service_level_percent }}%</td>
                                <td class="py-3 pr-4">{{ queue.abandonment_rate }}%</td>
                                <td class="py-3">{{ queue.avg_wait_seconds }}s</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <div class="space-y-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Live floor view') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('Track active conversations, alerts, and pause concentration without leaving the wallboard lane.') }}</p>
                </div>
            </div>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Active conversations') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="call in wallboard.active_calls || []" :key="call.call_uuid" class="rounded-3xl border border-slate-200 p-4 text-sm">
                        <div class="font-medium text-slate-900">{{ call.agent_extension }} - {{ call.destination }}</div>
                        <div class="mt-1 text-slate-500">{{ call.direction }} | {{ call.state }} | {{ call.duration_seconds }}s</div>
                    </article>
                    <div v-if="!(wallboard.active_calls || []).length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">{{ t('No active calls on the floor right now.') }}</div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Operational alerts') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="alert in alerts" :key="alert.title + alert.description" class="rounded-3xl border px-4 py-4" :class="alert.severity === 'critical' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
                        <div class="text-sm font-semibold" :class="alert.severity === 'critical' ? 'text-rose-700' : 'text-amber-700'">{{ t(alert.title) }}</div>
                        <div class="mt-1 text-xs leading-5" :class="alert.severity === 'critical' ? 'text-rose-700/80' : 'text-amber-700/80'">{{ t(alert.description) }}</div>
                    </article>
                    <div v-if="!alerts.length" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-6 text-sm text-emerald-700">{{ t('No contact center alerts detected right now.') }}</div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">{{ t('Pause breakdown') }}</h2>
                <div class="mt-5 space-y-3">
                    <article v-for="pause in pauseBreakdown" :key="pause.reason" class="flex items-center justify-between rounded-3xl border border-slate-200 p-4">
                        <div class="font-medium text-slate-900">{{ pause.reason }}</div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ pause.count }}</span>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { useLocale } from '@composables/useLocale'

defineProps({
    summary: { type: Object, default: () => ({}) },
    queues: { type: Array, default: () => [] },
    wallboard: { type: Object, default: () => ({ summary: {} }) },
    alerts: { type: Array, default: () => [] },
    pauseBreakdown: { type: Array, default: () => [] },
})

defineEmits(['openConfig'])

const { t } = useLocale()
</script>

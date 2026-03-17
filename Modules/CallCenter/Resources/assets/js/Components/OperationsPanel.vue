<template>
    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Wallboard overview') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ t('Bring SLA, waiting members, occupancy, callback pressure, and live conversations into one operating layer for the floor.') }}</p>
                </div>
                <button type="button" class="rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold text-white" @click="$emit('openConfig')">{{ t('Open setup') }}</button>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-8">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl border p-4" :class="card.tone">
                    <div class="text-[11px] uppercase tracking-[0.22em]" :class="card.kickerTone">{{ t(card.label) }}</div>
                    <div class="mt-3 text-3xl font-semibold" :class="card.valueTone">{{ card.value }}</div>
                    <div class="mt-2 text-xs leading-5" :class="card.helpTone">{{ t(card.help) }}</div>
                </article>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr),380px]">
            <section class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">{{ t('Queue command board') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Rank queues by pressure so the supervisor sees waiting members, available coverage, abandonment, and SLA on the same line.') }}</p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ queues.length }} {{ t('queues') }}</div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                        <div class="grid grid-cols-[minmax(0,1.5fr),90px,90px,100px,90px,110px,90px] gap-3 bg-slate-950 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-300">
                            <div>{{ t('Queue') }}</div>
                            <div>{{ t('Waiting') }}</div>
                            <div>{{ t('Ready') }}</div>
                            <div>{{ t('Coverage gap') }}</div>
                            <div>SLA</div>
                            <div>{{ t('Abandonment') }}</div>
                            <div>{{ t('Average wait') }}</div>
                        </div>
                        <div v-if="sortedQueues.length" class="divide-y divide-slate-200">
                            <div v-for="queue in sortedQueues" :key="queue.uuid" class="grid grid-cols-[minmax(0,1.5fr),90px,90px,100px,90px,110px,90px] gap-3 px-4 py-4 text-sm">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <div class="truncate font-semibold text-slate-900">{{ queue.extension }} - {{ queue.name }}</div>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="queue.alert_level === 'critical' ? 'bg-rose-100 text-rose-700' : (queue.alert_level === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')">
                                            {{ t(queue.alert_level === 'critical' ? 'Critical' : (queue.alert_level === 'warning' ? 'Watch' : 'Stable')) }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span>{{ t(queue.strategy) }}</span>
                                        <span>{{ t('Max wait') }}: {{ queue.max_wait_time }}s</span>
                                        <span>{{ t('Busy agents') }}: {{ queue.busy_agents }}</span>
                                        <span>{{ t('Offline agents') }}: {{ queue.offline_agents }}</span>
                                    </div>
                                </div>
                                <div class="font-semibold text-slate-900">{{ queue.live_waiting_members }}</div>
                                <div class="font-semibold text-slate-900">{{ queue.available_agents }}</div>
                                <div class="font-semibold" :class="queue.coverage_gap > 0 ? 'text-rose-700' : 'text-emerald-700'">{{ queue.coverage_gap }}</div>
                                <div class="font-semibold text-slate-900">{{ queue.service_level_percent }}%</div>
                                <div class="font-semibold" :class="queue.abandonment_rate >= 10 ? 'text-rose-700' : 'text-slate-900'">{{ queue.abandonment_rate }}%</div>
                                <div class="font-semibold text-slate-900">{{ queue.avg_wait_seconds }}s</div>
                            </div>
                        </div>
                        <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No call center queues are configured yet.') }}</div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ t('Active conversations') }}</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Track live interactions with duration, state, and agent extension without leaving the wallboard.') }}</p>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ activeCalls.length }} {{ t('live') }}</div>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                            <div class="grid grid-cols-[110px,minmax(0,1fr),120px,90px] gap-3 bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                <div>{{ t('Agent') }}</div>
                                <div>{{ t('Destination') }}</div>
                                <div>{{ t('State') }}</div>
                                <div>{{ t('Duration') }}</div>
                            </div>
                            <div v-if="activeCalls.length" class="divide-y divide-slate-200">
                                <div v-for="call in activeCalls" :key="call.call_uuid" class="grid grid-cols-[110px,minmax(0,1fr),120px,90px] gap-3 px-4 py-4 text-sm">
                                    <div class="font-semibold text-slate-900">{{ call.agent_extension || '-' }}</div>
                                    <div class="truncate text-slate-700">{{ call.destination || '-' }}</div>
                                    <div class="text-slate-600">{{ call.state || '-' }}</div>
                                    <div class="font-semibold text-slate-900">{{ call.duration_seconds }}s</div>
                                </div>
                            </div>
                            <div v-else class="px-4 py-10 text-sm text-slate-500">{{ t('No active calls on the floor right now.') }}</div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ t('Callbacks due now') }}</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ t('Keep the callback queue visible so overdue returns never disappear behind queue traffic.') }}</p>
                            </div>
                            <div class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ callbacksDueNow.length }} {{ t('due') }}</div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <article v-for="callback in callbacksDueNow" :key="callback.uuid" class="rounded-3xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ callback.contact_name || callback.phone_number }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ callback.phone_number }} | {{ callback.queue_label || t('No queue') }}</div>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ callback.preferred_callback_at || callback.requested_at }}</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ callback.agent_label || t('Unassigned') }}</span>
                                    <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ t(callback.status) }}</span>
                                    <span v-if="callback.state_code" class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ callback.state_code }}</span>
                                </div>
                            </article>
                            <div v-if="!callbacksDueNow.length" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-sm text-emerald-700">{{ t('No callbacks are overdue right now.') }}</div>
                        </div>
                    </section>
                </section>
            </section>

            <aside class="space-y-6">
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
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-slate-900">{{ pause.reason }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ t('Agents currently parked in this reason.') }}</div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ pause.count }}</span>
                        </article>
                        <div v-if="!pauseBreakdown.length" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">{{ t('No active pauses are open right now.') }}</div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-[#fffaf0] p-6 shadow-sm ring-1 ring-amber-200">
                    <h2 class="text-xl font-semibold text-slate-900">{{ t('Floor checklist') }}</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Protect queue coverage before speed.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('If waiting members are above ready coverage, rebalance staffing before increasing outbound pressure.') }}</div>
                        </div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Callbacks deserve their own lane.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('Overdue callbacks should be assigned with the same priority as live queue recovery.') }}</div>
                        </div>
                        <div class="rounded-3xl bg-white p-4 ring-1 ring-amber-100">
                            <div class="text-sm font-semibold text-slate-900">{{ t('Watch abandonment and occupancy together.') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ t('High occupancy can be healthy, but rising abandonment means the floor is outrunning available answer capacity.') }}</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    queues: { type: Array, default: () => [] },
    wallboard: { type: Object, default: () => ({ summary: {}, active_calls: [] }) },
    alerts: { type: Array, default: () => [] },
    pauseBreakdown: { type: Array, default: () => [] },
    callbacks: { type: Array, default: () => [] },
})

defineEmits(['openConfig'])

const { t } = useLocale()

const summaryCards = computed(() => ([
    {
        label: 'Active calls',
        value: props.summary.active_calls || props.wallboard?.summary?.active_calls || 0,
        help: 'Current live interactions on the floor.',
        tone: 'border-slate-200 bg-slate-50',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Waiting members',
        value: props.summary.waiting_members || 0,
        help: 'Members currently waiting across queues.',
        tone: 'border-cyan-100 bg-cyan-50',
        kickerTone: 'text-cyan-700',
        valueTone: 'text-cyan-700',
        helpTone: 'text-cyan-700/80',
    },
    {
        label: 'Service level',
        value: `${props.summary.service_level_percent || 0}%`,
        help: 'Share answered inside the SLA target.',
        tone: 'border-emerald-100 bg-emerald-50',
        kickerTone: 'text-emerald-700',
        valueTone: 'text-emerald-700',
        helpTone: 'text-emerald-700/80',
    },
    {
        label: 'Occupancy',
        value: `${props.summary.occupancy_percent || 0}%`,
        help: 'Busy agents compared with the online pool.',
        tone: 'border-amber-100 bg-amber-50',
        kickerTone: 'text-amber-700',
        valueTone: 'text-amber-700',
        helpTone: 'text-amber-700/80',
    },
    {
        label: 'Online agents',
        value: props.summary.online_agents || 0,
        help: 'Agents online and ready for routing.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Busy agents',
        value: props.summary.busy_agents || 0,
        help: 'Agents already committed to a conversation.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
    {
        label: 'Callbacks due',
        value: props.summary.callbacks_due_now || 0,
        help: 'Callbacks that should already be owned.',
        tone: 'border-rose-100 bg-rose-50',
        kickerTone: 'text-rose-700',
        valueTone: 'text-rose-700',
        helpTone: 'text-rose-700/80',
    },
    {
        label: 'Abandonment',
        value: `${props.summary.abandonment_rate || 0}%`,
        help: 'Daily abandonment rate across the floor.',
        tone: 'border-slate-200 bg-white',
        kickerTone: 'text-slate-500',
        valueTone: 'text-slate-900',
        helpTone: 'text-slate-500',
    },
]))

const sortedQueues = computed(() => [...(props.queues || [])].sort((left, right) => {
    const leftScore = (Number(left.coverage_gap || 0) * 100) + Number(left.live_waiting_members || 0) + Number(left.abandonment_rate || 0)
    const rightScore = (Number(right.coverage_gap || 0) * 100) + Number(right.live_waiting_members || 0) + Number(right.abandonment_rate || 0)

    return rightScore - leftScore
}))

const activeCalls = computed(() => props.wallboard?.active_calls || [])

const callbacksDueNow = computed(() => {
    const now = Date.now()

    return (props.callbacks || [])
        .filter((callback) => ['pending', 'assigned'].includes((callback.status || '').toLowerCase()))
        .filter((callback) => {
            const dueAt = callback.preferred_callback_at || callback.requested_at

            if (!dueAt) {
                return false
            }

            const timestamp = new Date(dueAt).getTime()
            return !Number.isNaN(timestamp) && timestamp <= now
        })
        .slice(0, 8)
})
</script>

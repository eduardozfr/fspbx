<template>
    <div class="grid gap-3">
        <div
            v-for="day in days"
            :key="day.value"
            class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[140px,120px,1fr,1fr] md:items-center"
        >
            <div class="font-medium text-slate-900">{{ t(day.label) }}</div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input v-model="schedule[day.value].enabled" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-950" />
                <span>{{ t('Enabled') }}</span>
            </label>
            <input v-model="schedule[day.value].start" type="time" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :disabled="!schedule[day.value].enabled" />
            <input v-model="schedule[day.value].end" type="time" class="rounded-2xl border-slate-300 shadow-sm focus:border-slate-950 focus:ring-slate-950" :disabled="!schedule[day.value].enabled" />
        </div>
    </div>
</template>

<script setup>
import { useLocale } from '@composables/useLocale'

defineProps({
    schedule: {
        type: Object,
        required: true,
    },
    days: {
        type: Array,
        required: true,
    },
})

const { t } = useLocale()
</script>

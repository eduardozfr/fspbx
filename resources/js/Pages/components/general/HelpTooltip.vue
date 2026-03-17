<template>
    <span class="group relative inline-flex items-center">
        <button
            type="button"
            tabindex="-1"
            class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-[11px] font-semibold text-slate-500 shadow-sm transition group-hover:border-slate-900 group-hover:text-slate-900"
        >
            ?
        </button>
        <span class="pointer-events-none absolute left-1/2 top-full z-30 hidden w-64 -translate-x-1/2 rounded-2xl bg-slate-950 px-3 py-2 text-left text-xs leading-5 text-white shadow-2xl group-hover:block">
            {{ text }}
        </span>
    </span>
</template>

<script setup>
defineProps({
    text: {
        type: String,
        required: true,
    },
})
</script>

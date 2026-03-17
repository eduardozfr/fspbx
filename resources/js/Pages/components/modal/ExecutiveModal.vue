<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-[120]" @close="emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            :class="[
                                'relative w-full overflow-hidden rounded-[2rem] bg-white text-left shadow-2xl ring-1 ring-slate-200',
                                customClass,
                            ]"
                        >
                            <div class="border-b border-slate-200 bg-slate-950 px-6 py-5 text-white sm:px-8">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div v-if="kicker" class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-300">
                                            {{ kicker }}
                                        </div>
                                        <DialogTitle class="mt-2 text-2xl font-semibold tracking-tight">
                                            {{ title }}
                                        </DialogTitle>
                                        <p v-if="description" class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                                            {{ description }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-white"
                                        @click="emit('close')"
                                    >
                                        <span class="sr-only">Close</span>
                                        <XMarkIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="max-h-[78vh] overflow-y-auto px-6 py-6 sm:px-8">
                                <slot />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    kicker: { type: String, default: '' },
    customClass: { type: String, default: 'sm:max-w-5xl' },
})

const emit = defineEmits(['close'])
</script>

<template>
    <MainLayout>
        <main class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#edf4ff_35%,#fff7ed_100%)] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1650px] space-y-6">
                <section class="overflow-hidden rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl lg:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.1fr,0.9fr]">
                        <div>
                            <div class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-cyan-100">
                                {{ t('Web Phone') }}
                            </div>
                            <h1 class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl">
                                {{ t('Professional browser calling console') }}
                            </h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
                                {{ t('Run secure PBX calls from the browser with live registration status, dialpad actions, and audio controls in one workspace.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <article class="rounded-3xl bg-white/5 p-4">
                                <div class="text-[11px] uppercase tracking-[0.24em] text-slate-300">{{ t('Assigned extension') }}</div>
                                <div class="mt-3 text-3xl font-semibold">{{ profile.extension || '--' }}</div>
                            </article>
                            <article class="rounded-3xl bg-cyan-400/10 p-4">
                                <div class="text-[11px] uppercase tracking-[0.24em] text-cyan-100">{{ t('Transport') }}</div>
                                <div class="mt-3 text-sm font-semibold text-cyan-100">{{ transportLabel }}</div>
                            </article>
                            <article class="rounded-3xl p-4" :class="registrationCardClass">
                                <div class="text-[11px] uppercase tracking-[0.24em]" :class="registrationTextClass">{{ t('Registration') }}</div>
                                <div class="mt-3 text-lg font-semibold" :class="registrationTextClass">{{ registrationLabel }}</div>
                            </article>
                            <article class="rounded-3xl p-4" :class="callCardClass">
                                <div class="text-[11px] uppercase tracking-[0.24em]" :class="callTextClass">{{ t('Call state') }}</div>
                                <div class="mt-3 text-lg font-semibold" :class="callTextClass">{{ callLabel }}</div>
                                <div class="mt-1 text-xs" :class="callTextClass">{{ callDurationLabel }}</div>
                            </article>
                        </div>
                    </div>
                </section>

                <div
                    v-if="flash.message"
                    class="rounded-[1.5rem] border px-5 py-4 shadow-sm"
                    :class="flash.type === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : flash.type === 'warning'
                            ? 'border-amber-200 bg-amber-50 text-amber-800'
                            : 'border-rose-200 bg-rose-50 text-rose-800'"
                >
                    <div class="text-sm font-semibold">{{ flash.message }}</div>
                </div>

                <section
                    v-if="!profile.has_assigned_extension"
                    class="rounded-[1.75rem] bg-white p-8 shadow-sm ring-1 ring-slate-200"
                >
                    <h2 class="text-2xl font-semibold text-slate-900">{{ t('Web Phone') }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                        {{ t('No extension is assigned to your user. Ask an administrator to link a PBX extension before using the Web Phone.') }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                            @click="loadConfig()"
                        >
                            {{ t('Load configuration') }}
                        </button>
                    </div>
                </section>

                <section v-else class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <div class="space-y-6">
                        <article class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Call controls') }}</div>
                                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ t('Run the browser softphone') }}</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                        {{ readyHint }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                        :disabled="loadingConfig || actionLoading"
                                        @click="loadConfig()"
                                    >
                                        {{ t('Load configuration') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="isConnectedOrRegistered ? 'bg-rose-600 hover:bg-rose-500' : 'bg-slate-950 hover:bg-slate-800'"
                                        :disabled="!phoneConfig || actionLoading"
                                        @click="isConnectedOrRegistered ? disconnectPhone() : connectPhone()"
                                    >
                                        {{ isConnectedOrRegistered ? t('Disconnect phone') : t('Connect phone') }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr,18rem]">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">{{ t('Destination') }}</label>
                                        <div class="mt-2 flex flex-col gap-3 xl:flex-row">
                                            <input
                                                v-model="dialTarget"
                                                type="text"
                                                class="min-h-[3.25rem] flex-1 rounded-2xl border border-slate-200 px-4 text-base text-slate-900 outline-none transition focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                                                :placeholder="t('Enter extension or full number')"
                                                @keydown.enter.prevent="placeCall"
                                            />
                                            <button
                                                type="button"
                                                class="rounded-2xl bg-cyan-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="!canPlaceCall || actionLoading"
                                                @click="placeCall"
                                            >
                                                {{ t('Call now') }}
                                            </button>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">
                                                {{ t('Outbound prefix') }}: <strong>{{ phoneConfig?.dialing?.prefix || '--' }}</strong>
                                            </span>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">
                                                {{ t('Identity') }}: <strong>{{ phoneConfig?.identity?.display_name || profile.display_name || '--' }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!incomingCall || actionLoading" @click="answerCall">
                                            {{ t('Answer') }}
                                        </button>
                                        <button type="button" class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!incomingCall || actionLoading" @click="declineCall">
                                            {{ t('Decline') }}
                                        </button>
                                        <button type="button" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!isInCall || actionLoading" @click="hangupCall">
                                            {{ t('Hang up') }}
                                        </button>
                                        <button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!isEstablishedCall || actionLoading" @click="toggleMute">
                                            {{ isMuted ? t('Unmute') : t('Mute') }}
                                        </button>
                                        <button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="!isEstablishedCall || actionLoading" @click="toggleHold">
                                            {{ isHeld ? t('Resume') : t('Hold') }}
                                        </button>
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">{{ t('Send DTMF') }}</div>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    {{ t('Use the keypad during a live call to send tones, or compose the destination before dialing.') }}
                                                </p>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white" :disabled="!dialTarget" @click="backspaceDialTarget">
                                                    {{ t('Backspace') }}
                                                </button>
                                                <button type="button" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white" :disabled="!dialTarget" @click="clearDialTarget">
                                                    {{ t('Clear') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Dialpad') }}</div>
                                    <div class="mt-4 grid grid-cols-3 gap-3">
                                        <button
                                            v-for="tone in dialpad"
                                            :key="tone"
                                            type="button"
                                            class="flex h-16 items-center justify-center rounded-2xl bg-white text-xl font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-900 hover:text-white"
                                            @click="handleDialpadPress(tone)"
                                        >
                                            {{ tone }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Timeline') }}</div>
                                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ t('Recent events') }}</h2>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                    {{ recentEvents.length }} {{ t('events') }}
                                </span>
                            </div>

                            <div v-if="recentEvents.length" class="mt-5 space-y-3">
                                <article v-for="event in recentEvents" :key="event.id" class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ event.title }}</div>
                                            <p v-if="event.detail" class="mt-1 text-sm text-slate-500">{{ event.detail }}</p>
                                        </div>
                                        <span class="text-xs font-medium text-slate-400">{{ formatEventTime(event.at) }}</span>
                                    </div>
                                </article>
                            </div>
                            <div v-else class="mt-5 rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                                {{ t('No events yet. Connect the browser phone to start the signaling timeline.') }}
                            </div>
                        </article>
                    </div>

                    <div class="space-y-6">
                        <article class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Runtime status') }}</div>
                            <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ t('Extension identity') }}</h2>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <article class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ t('Display name') }}</div>
                                    <div class="mt-2 text-lg font-semibold text-slate-900">{{ phoneConfig?.identity?.display_name || profile.display_name || '--' }}</div>
                                </article>
                                <article class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ t('SIP address') }}</div>
                                    <div class="mt-2 break-all text-sm font-semibold text-slate-900">{{ phoneConfig?.aor || '--' }}</div>
                                </article>
                                <article class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ t('Signaling') }}</div>
                                    <div class="mt-2 break-all text-sm font-semibold text-slate-900">{{ phoneConfig?.transport?.server || '--' }}</div>
                                </article>
                                <article class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ t('Registration') }}</div>
                                    <div class="mt-2 text-lg font-semibold text-slate-900">{{ registrationLabel }}</div>
                                </article>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Audio') }}</div>
                                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ t('Device preferences') }}</h2>
                                </div>
                                <button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="deviceRefreshLoading" @click="hydrateDevices()">
                                    {{ t('Refresh devices') }}
                                </button>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="text-sm font-semibold text-slate-700">{{ t('Microphone') }}</label>
                                    <select v-model="selectedInputDeviceId" class="mt-2 min-h-[3rem] w-full rounded-2xl border border-slate-200 px-4 text-sm text-slate-900 outline-none transition focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                        <option value="">{{ t('System default microphone') }}</option>
                                        <option v-for="device in inputDevices" :key="device.value" :value="device.value">
                                            {{ device.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">{{ t('Speaker output') }}</label>
                                    <select v-model="selectedOutputDeviceId" class="mt-2 min-h-[3rem] w-full rounded-2xl border border-slate-200 px-4 text-sm text-slate-900 outline-none transition focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" :disabled="!supportsSinkSelection">
                                        <option value="">{{ t('System default speaker') }}</option>
                                        <option v-for="device in outputDevices" :key="device.value" :value="device.value">
                                            {{ device.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                    {{ t('Reconnect the browser phone after changing the microphone device so the next call starts with the selected input.') }}
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.26em] text-slate-400">{{ t('Governance') }}</div>
                            <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ t('Softphone checklist') }}</h2>

                            <div class="mt-5 space-y-3">
                                <article v-for="item in checklist" :key="item.title" class="rounded-[1.25rem] border px-4 py-3" :class="item.ok ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold" :class="item.ok ? 'text-emerald-900' : 'text-amber-900'">{{ item.title }}</div>
                                            <p class="mt-1 text-sm" :class="item.ok ? 'text-emerald-700' : 'text-amber-700'">{{ item.detail }}</p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="item.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                            {{ item.ok ? t('Ready') : t('Attention') }}
                                        </span>
                                    </div>
                                </article>
                            </div>
                        </article>
                    </div>
                </section>

                <audio ref="remoteAudio" autoplay playsinline class="hidden" />
                <audio ref="localAudio" autoplay muted playsinline class="hidden" />
            </div>
        </main>
    </MainLayout>
</template>

<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue'
import { SimpleUser } from 'sip.js/lib/platform/web'
import MainLayout from '@layouts/MainLayout.vue'
import { useLocale } from '@composables/useLocale'

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const { t } = useLocale()
const remoteAudio = ref(null)
const localAudio = ref(null)
const phone = shallowRef(null)
const phoneConfig = ref(null)
const dialTarget = ref('')
const recentEvents = ref([])
const loadingConfig = ref(false)
const actionLoading = ref(false)
const deviceRefreshLoading = ref(false)
const flash = ref({ type: 'success', message: '' })
const connectionState = ref('offline')
const registrationState = ref('unregistered')
const callState = ref('idle')
const incomingCall = ref(false)
const isMuted = ref(false)
const isHeld = ref(false)
const durationSeconds = ref(0)
const connectedAt = ref(null)
const inputDevices = ref([])
const outputDevices = ref([])
const selectedInputDeviceId = ref('')
const selectedOutputDeviceId = ref('')

let durationTimer = null

const dialpad = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#']
const supportsSinkSelection = computed(() => typeof remoteAudio.value?.setSinkId === 'function')
const isConnectedOrRegistered = computed(() => ['connected', 'connecting'].includes(connectionState.value) || ['registered', 'registering'].includes(registrationState.value))
const isInCall = computed(() => ['dialing', 'ringing', 'active', 'held'].includes(callState.value))
const isEstablishedCall = computed(() => ['active', 'held'].includes(callState.value))
const canPlaceCall = computed(() => Boolean(props.profile.has_assigned_extension && dialTarget.value.trim() && phoneConfig.value) && !incomingCall.value)
const transportLabel = computed(() => {
    if (!phoneConfig.value?.transport) {
        return '--'
    }

    return `${phoneConfig.value.transport.secure ? 'WSS' : 'WS'}:${phoneConfig.value.transport.port}`
})
const registrationLabel = computed(() => {
    if (registrationState.value === 'registered') return t('Registered')
    if (registrationState.value === 'registering') return t('Registering')
    return t('Offline')
})
const callLabel = computed(() => {
    if (incomingCall.value) return t('Incoming')

    switch (callState.value) {
        case 'dialing':
            return t('Calling')
        case 'ringing':
            return t('Ringing')
        case 'active':
            return t('In conversation')
        case 'held':
            return t('On hold')
        default:
            return t('Idle')
    }
})
const callDurationLabel = computed(() => isEstablishedCall.value ? formatDuration(durationSeconds.value) : t('Not in call'))
const readyHint = computed(() => {
    if (!phoneConfig.value) {
        return t('Load the signaling profile first, then connect the browser phone and place the call from this console.')
    }

    if (!isConnectedOrRegistered.value) {
        return t('The PBX profile is loaded. Connect and register the browser phone before placing or receiving calls.')
    }

    if (!dialTarget.value.trim()) {
        return t('The browser phone is ready. Enter an extension or full number to start the next call.')
    }

    return t('The browser phone is connected and ready to place the current destination.')
})
const registrationCardClass = computed(() => registrationState.value === 'registered' ? 'bg-emerald-400/15' : 'bg-amber-400/15')
const registrationTextClass = computed(() => registrationState.value === 'registered' ? 'text-emerald-100' : 'text-amber-100')
const callCardClass = computed(() => isInCall.value ? 'bg-cyan-400/15' : 'bg-white/5')
const callTextClass = computed(() => isInCall.value ? 'text-cyan-100' : 'text-slate-300')
const checklist = computed(() => ([
    {
        title: t('Assigned extension'),
        ok: Boolean(props.profile.has_assigned_extension && phoneConfig.value?.identity?.extension),
        detail: phoneConfig.value?.identity?.extension
            ? `${phoneConfig.value.identity.extension}@${phoneConfig.value.identity.domain}`
            : t('An administrator still needs to link an extension to this user before browser registration can happen.'),
    },
    {
        title: t('Browser secure context'),
        ok: !phoneConfig.value?.transport?.secure || (typeof window !== 'undefined' && window.isSecureContext),
        detail: !phoneConfig.value?.transport?.secure || (typeof window !== 'undefined' && window.isSecureContext)
            ? t('The current browser context is compatible with the configured signaling transport.')
            : t('WSS is enabled for signaling, so the portal itself also needs to run over HTTPS in the browser.'),
    },
    {
        title: t('Microphone devices'),
        ok: inputDevices.value.length > 0 || Boolean(selectedInputDeviceId.value),
        detail: inputDevices.value.length > 0
            ? t('Audio inputs are visible to the browser and can be selected for the next call.')
            : t('Allow microphone access in the browser so the softphone can capture local audio.'),
    },
    {
        title: t('Speaker routing'),
        ok: supportsSinkSelection.value ? outputDevices.value.length > 0 : true,
        detail: supportsSinkSelection.value
            ? t('This browser supports output routing, so you can pin the call audio to a specific speaker device.')
            : t('This browser will use the system default speaker output because device-level sink selection is unavailable.'),
    },
]))

const setFlash = (type, message) => {
    flash.value = { type, message }
}

const pushEvent = (title, detail = '') => {
    recentEvents.value = [
        {
            id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
            title,
            detail,
            at: new Date().toISOString(),
        },
        ...recentEvents.value,
    ].slice(0, 15)
}

const startDurationTimer = () => {
    stopDurationTimer()
    connectedAt.value = Date.now()
    durationTimer = window.setInterval(() => {
        durationSeconds.value = Math.max(0, Math.floor((Date.now() - connectedAt.value) / 1000))
    }, 1000)
}

const stopDurationTimer = () => {
    if (durationTimer) {
        window.clearInterval(durationTimer)
        durationTimer = null
    }

    connectedAt.value = null
    durationSeconds.value = 0
}

const resetCallFlags = () => {
    incomingCall.value = false
    isMuted.value = false
    isHeld.value = false
    callState.value = 'idle'
    stopDurationTimer()
}

const buildPhoneDelegate = () => ({
    onCallCreated: () => {
        callState.value = 'dialing'
        pushEvent(t('Call session created'))
    },
    onCallReceived: () => {
        incomingCall.value = true
        callState.value = 'ringing'
        setFlash('warning', t('Incoming call ringing.'))
        pushEvent(t('Incoming call ringing.'))
    },
    onCallAnswered: () => {
        incomingCall.value = false
        callState.value = isHeld.value ? 'held' : 'active'
        startDurationTimer()
        setFlash('success', t('Call answered.'))
        pushEvent(t('Call answered.'))
    },
    onCallHangup: () => {
        resetCallFlags()
        setFlash('success', t('Call finished.'))
        pushEvent(t('Call finished.'))
    },
    onRegistered: () => {
        registrationState.value = 'registered'
        pushEvent(t('Browser phone registered.'))
    },
    onUnregistered: () => {
        registrationState.value = 'unregistered'
        pushEvent(t('Browser phone unregistered.'))
    },
    onServerConnect: () => {
        connectionState.value = 'connected'
        pushEvent(t('Signaling connected.'))
    },
    onServerDisconnect: () => {
        connectionState.value = 'offline'
        registrationState.value = 'unregistered'
        resetCallFlags()
        pushEvent(t('Signaling disconnected.'))
    },
})

const getMediaConstraints = () => {
    if (!selectedInputDeviceId.value) {
        return { audio: true, video: false }
    }

    return {
        audio: {
            deviceId: {
                exact: selectedInputDeviceId.value,
            },
        },
        video: false,
    }
}

const buildPhone = () => {
    if (!phoneConfig.value) {
        throw new Error('Web phone configuration is not available.')
    }

    phone.value = new SimpleUser(phoneConfig.value.transport.server, {
        aor: phoneConfig.value.aor,
        delegate: buildPhoneDelegate(),
        media: {
            constraints: getMediaConstraints(),
            local: { audio: localAudio.value },
            remote: { audio: remoteAudio.value },
        },
        userAgentOptions: {
            authorizationUsername: phoneConfig.value.auth.username,
            authorizationPassword: phoneConfig.value.auth.password,
            displayName: phoneConfig.value.identity.display_name,
            sessionDescriptionHandlerFactoryOptions: {
                peerConnectionConfiguration: {
                    iceServers: phoneConfig.value.media?.ice_servers || [],
                },
            },
        },
    })
}

const destroyPhone = async () => {
    if (!phone.value) {
        return
    }

    try {
        await phone.value.unregister()
    } catch (error) {
        // noop
    }

    try {
        await phone.value.disconnect()
    } catch (error) {
        // noop
    }

    phone.value = null
    connectionState.value = 'offline'
    registrationState.value = 'unregistered'
    resetCallFlags()
}

const requestAudioPermission = async () => {
    if (!navigator.mediaDevices?.getUserMedia) {
        return
    }

    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    stream.getTracks().forEach((track) => track.stop())
}

const hydrateDevices = async () => {
    if (!navigator.mediaDevices?.enumerateDevices) {
        return
    }

    deviceRefreshLoading.value = true

    try {
        const devices = await navigator.mediaDevices.enumerateDevices()

        inputDevices.value = devices
            .filter((device) => device.kind === 'audioinput')
            .map((device, index) => ({
                value: device.deviceId,
                label: device.label || `${t('Microphone')} ${index + 1}`,
            }))

        outputDevices.value = devices
            .filter((device) => device.kind === 'audiooutput')
            .map((device, index) => ({
                value: device.deviceId,
                label: device.label || `${t('Speaker output')} ${index + 1}`,
            }))

        if (!selectedInputDeviceId.value && inputDevices.value.length === 1) {
            selectedInputDeviceId.value = inputDevices.value[0].value
        }

        if (!selectedOutputDeviceId.value && outputDevices.value.length === 1) {
            selectedOutputDeviceId.value = outputDevices.value[0].value
        }

        await applyOutputDevice()
    } finally {
        deviceRefreshLoading.value = false
    }
}

const applyOutputDevice = async () => {
    if (!supportsSinkSelection.value || !selectedOutputDeviceId.value) {
        return
    }

    try {
        await remoteAudio.value.setSinkId(selectedOutputDeviceId.value)
    } catch (error) {
        setFlash('warning', t('The browser blocked the selected speaker output. The system default output will be used instead.'))
    }
}

const loadConfig = async () => {
    if (!props.routes.config) {
        return
    }

    loadingConfig.value = true

    try {
        const response = await axios.get(props.routes.config)
        phoneConfig.value = response.data.data
        setFlash('success', t('Browser phone ready.'))
        pushEvent(t('Browser phone configuration loaded.'), phoneConfig.value.transport.server)
        await hydrateDevices()
    } catch (error) {
        setFlash('error', error?.response?.data?.messages?.error?.[0] || t('Failed to load browser phone configuration.'))
    } finally {
        loadingConfig.value = false
    }
}

const ensurePhoneSession = async (forceRebuild = false) => {
    if (!phoneConfig.value) {
        await loadConfig()
    }

    if (!phoneConfig.value) {
        throw new Error('Browser phone configuration is unavailable.')
    }

    await requestAudioPermission()
    await hydrateDevices()

    if (forceRebuild || !phone.value) {
        await destroyPhone()
        buildPhone()
    }

    if (connectionState.value !== 'connected') {
        connectionState.value = 'connecting'
        await phone.value.connect()
    }

    if (registrationState.value !== 'registered') {
        registrationState.value = 'registering'
        await phone.value.register()
    }

    await applyOutputDevice()
}

const connectPhone = async () => {
    actionLoading.value = true

    try {
        await ensurePhoneSession(true)
        setFlash('success', t('Browser phone connected and registered.'))
        pushEvent(t('Browser phone connected and registered.'))
    } catch (error) {
        connectionState.value = 'offline'
        registrationState.value = 'unregistered'
        setFlash('error', error?.message || t('Failed to connect the browser phone.'))
    } finally {
        actionLoading.value = false
    }
}

const disconnectPhone = async () => {
    actionLoading.value = true

    try {
        await destroyPhone()
        setFlash('success', t('Browser phone disconnected.'))
        pushEvent(t('Browser phone disconnected.'))
    } finally {
        actionLoading.value = false
    }
}

const normalizeDestination = () => {
    const target = dialTarget.value.trim()

    if (!target || !phoneConfig.value) {
        return null
    }

    if (target.startsWith('sip:')) {
        return target
    }

    if (target.includes('@')) {
        return `sip:${target}`
    }

    const prefix = phoneConfig.value.dialing?.prefix || ''
    return `sip:${prefix}${target}@${phoneConfig.value.identity.domain}`
}

const placeCall = async () => {
    const destination = normalizeDestination()

    if (!destination) {
        return
    }

    actionLoading.value = true

    try {
        await ensurePhoneSession()
        callState.value = 'dialing'
        await phone.value.call(destination)
        setFlash('success', t('Outbound call started.'))
        pushEvent(t('Outbound call started.'), destination)
    } catch (error) {
        callState.value = 'idle'
        setFlash('error', error?.message || t('Failed to start the outbound call.'))
    } finally {
        actionLoading.value = false
    }
}

const answerCall = async () => {
    if (!incomingCall.value || !phone.value) {
        return
    }

    actionLoading.value = true

    try {
        await phone.value.answer()
        incomingCall.value = false
        callState.value = 'active'
        startDurationTimer()
    } catch (error) {
        setFlash('error', error?.message || t('Failed to answer the incoming call.'))
    } finally {
        actionLoading.value = false
    }
}

const declineCall = async () => {
    if (!incomingCall.value || !phone.value) {
        return
    }

    actionLoading.value = true

    try {
        await phone.value.decline()
        resetCallFlags()
        setFlash('success', t('Incoming call declined.'))
        pushEvent(t('Incoming call declined.'))
    } catch (error) {
        setFlash('error', error?.message || t('Failed to decline the incoming call.'))
    } finally {
        actionLoading.value = false
    }
}

const hangupCall = async () => {
    if (!phone.value || !isInCall.value) {
        return
    }

    actionLoading.value = true

    try {
        await phone.value.hangup()
        resetCallFlags()
        setFlash('success', t('Call finished.'))
        pushEvent(t('Call finished.'))
    } catch (error) {
        setFlash('error', error?.message || t('Failed to finish the current call.'))
    } finally {
        actionLoading.value = false
    }
}

const toggleMute = async () => {
    if (!phone.value || !isEstablishedCall.value) {
        return
    }

    actionLoading.value = true

    try {
        if (typeof phone.value.mute !== 'function' || typeof phone.value.unmute !== 'function') {
            throw new Error(t('This SIP session does not expose browser mute controls.'))
        }

        if (isMuted.value) {
            await phone.value.unmute()
            isMuted.value = false
            setFlash('success', t('Microphone unmuted.'))
            pushEvent(t('Microphone unmuted.'))
        } else {
            await phone.value.mute()
            isMuted.value = true
            setFlash('success', t('Microphone muted.'))
            pushEvent(t('Microphone muted.'))
        }
    } catch (error) {
        setFlash('error', error?.message || t('Failed to change the microphone state.'))
    } finally {
        actionLoading.value = false
    }
}

const toggleHold = async () => {
    if (!phone.value || !isEstablishedCall.value) {
        return
    }

    actionLoading.value = true

    try {
        if (typeof phone.value.hold !== 'function' || typeof phone.value.unhold !== 'function') {
            throw new Error(t('This SIP session does not expose browser hold controls.'))
        }

        if (isHeld.value) {
            await phone.value.unhold()
            isHeld.value = false
            callState.value = 'active'
            setFlash('success', t('Call resumed.'))
            pushEvent(t('Call resumed.'))
        } else {
            await phone.value.hold()
            isHeld.value = true
            callState.value = 'held'
            setFlash('success', t('Call placed on hold.'))
            pushEvent(t('Call placed on hold.'))
        }
    } catch (error) {
        setFlash('error', error?.message || t('Failed to change the hold state.'))
    } finally {
        actionLoading.value = false
    }
}

const sendDtmfTone = async (tone) => {
    if (!phone.value || !isEstablishedCall.value || typeof phone.value.sendDTMF !== 'function') {
        return
    }

    try {
        await phone.value.sendDTMF(tone)
        setFlash('success', t('DTMF tone sent.'))
        pushEvent(t('DTMF tone sent.'), tone)
    } catch (error) {
        setFlash('error', error?.message || t('Failed to send DTMF.'))
    }
}

const handleDialpadPress = async (tone) => {
    if (isEstablishedCall.value) {
        await sendDtmfTone(tone)
        return
    }

    dialTarget.value = `${dialTarget.value}${tone}`
}

const backspaceDialTarget = () => {
    dialTarget.value = dialTarget.value.slice(0, -1)
}

const clearDialTarget = () => {
    dialTarget.value = ''
}

const formatDuration = (value) => {
    const hours = Math.floor(value / 3600)
    const minutes = Math.floor((value % 3600) / 60)
    const seconds = value % 60

    if (hours > 0) {
        return [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':')
    }

    return [minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':')
}

const formatEventTime = (value) => new Date(value).toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
})

watch(selectedOutputDeviceId, async () => {
    await applyOutputDevice()
})

onMounted(async () => {
    if (props.profile.has_assigned_extension) {
        await loadConfig()
    }
})

onBeforeUnmount(() => {
    stopDurationTimer()
    destroyPhone()
})
</script>

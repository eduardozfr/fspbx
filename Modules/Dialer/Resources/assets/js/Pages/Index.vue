<template>
    <MainLayout>
        <main class="min-h-screen bg-[radial-gradient(circle_at_top,#fef3c7_0,#fff7ed_18%,#f8fafc_58%,#eef2ff_100%)] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1650px] space-y-6">
                <section class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl lg:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                        <div>
                            <div class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-100">{{ t('Dialer') }}</div>
                            <h1 class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl">{{ t('Outbound command center') }}</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ t('Operate manual, preview, progressive, and power dialing from one guided workspace with better compliance, pacing, and outcome control.') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <article class="rounded-3xl bg-white/5 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-slate-300">{{ t('Campaigns') }}</div><div class="mt-3 text-3xl font-semibold">{{ formatNumber(metrics.campaigns) }}</div></article>
                            <article class="rounded-3xl bg-emerald-400/10 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-emerald-100">{{ t('Active') }}</div><div class="mt-3 text-3xl font-semibold text-emerald-100">{{ formatNumber(metrics.activeCampaigns) }}</div></article>
                            <article class="rounded-3xl bg-cyan-400/10 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-cyan-100">{{ t('Queued leads') }}</div><div class="mt-3 text-3xl font-semibold text-cyan-100">{{ formatNumber(metrics.queuedLeads) }}</div></article>
                            <article class="rounded-3xl bg-rose-400/10 p-4"><div class="text-[11px] uppercase tracking-[0.24em] text-rose-100">{{ t('Alerts') }}</div><div class="mt-3 text-3xl font-semibold text-rose-100">{{ formatNumber(alerts.length) }}</div></article>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap gap-2">
                    <button v-for="tab in tabs" :key="tab.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeTab === tab.value ? 'bg-slate-950 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'" @click="activeTab = tab.value">{{ t(tab.label) }}</button>
                </div>

                <WorkspacePanel
                    v-if="activeTab === 'workspace'"
                    :campaigns="campaigns"
                    :leads="leads"
                    :attempts="attempts"
                    :options="options"
                    :alerts="alerts"
                    :routes="routes"
                    @openStudio="openStudio"
                />
                <StudioPanel
                    v-else-if="activeTab === 'studio'"
                    :campaigns="campaigns"
                    :compliance-profiles="complianceProfiles"
                    :options="options"
                    :routes="routes"
                    :selected-campaign-uuid="studioCampaignUuid"
                />
                <ListsPanel
                    v-else-if="activeTab === 'leads'"
                    :campaigns="campaigns"
                    :leads="leads"
                    :dnc-entries="dncEntries"
                    :import-batches="importBatches"
                    :options="options"
                    :routes="routes"
                />
                <CompliancePanel
                    v-else-if="activeTab === 'compliance'"
                    :compliance-profiles="complianceProfiles"
                    :state-rules="stateRules"
                    :options="options"
                    :routes="routes"
                />
                <OutcomesPanel
                    v-else
                    :dispositions="dispositions"
                    :attempts="attempts"
                    :routes="routes"
                />
            </div>
        </main>
    </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import MainLayout from '@layouts/MainLayout.vue'
import { useLocale } from '@composables/useLocale'
import WorkspacePanel from '../Components/WorkspacePanel.vue'
import StudioPanel from '../Components/StudioPanel.vue'
import ListsPanel from '../Components/ListsPanel.vue'
import CompliancePanel from '../Components/CompliancePanel.vue'
import OutcomesPanel from '../Components/OutcomesPanel.vue'

const props = defineProps({
    campaigns: { type: Array, default: () => [] },
    leads: { type: Array, default: () => [] },
    attempts: { type: Array, default: () => [] },
    dispositions: { type: Array, default: () => [] },
    dncEntries: { type: Array, default: () => [] },
    stateRules: { type: Array, default: () => [] },
    complianceProfiles: { type: Array, default: () => [] },
    importBatches: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
})

const { t, formatNumber } = useLocale()
const tabs = [
    { value: 'workspace', label: 'Workspace' },
    { value: 'studio', label: 'Campaign studio' },
    { value: 'leads', label: 'Lists and contacts' },
    { value: 'compliance', label: 'Compliance' },
    { value: 'outcomes', label: 'Outcomes' },
]
const activeTab = ref('workspace')
const studioCampaignUuid = ref('')
const metrics = computed(() => ({
    campaigns: props.campaigns.length,
    activeCampaigns: props.campaigns.filter((campaign) => campaign.status === 'active').length,
    queuedLeads: props.campaigns.reduce((total, campaign) => total + Number(campaign.queued_leads_count || 0), 0),
}))
const alerts = computed(() => {
    const items = []
    props.campaigns.forEach((campaign) => {
        if (['progressive', 'power'].includes(campaign.mode) && !campaign.call_center_queue_uuid) items.push({ severity: 'critical', title: 'Automated campaign without queue handoff', description: `${campaign.name} is configured for ${campaign.mode} dialing but has no queue for answered calls.` })
        if (campaign.status === 'active' && !campaign.respect_dnc) items.push({ severity: 'warning', title: 'Campaign bypassing DNC protection', description: `${campaign.name} is active without enforcing the do-not-call list.` })
    })
    if (!props.complianceProfiles.some((profile) => !profile.is_system)) items.push({ severity: 'warning', title: 'Custom compliance profiles not configured', description: 'Create at least one operation-specific profile so campaigns carry explicit governance.' })
    return items
})
const openStudio = (campaignUuid = '') => {
    studioCampaignUuid.value = campaignUuid || ''
    activeTab.value = 'studio'
}
</script>

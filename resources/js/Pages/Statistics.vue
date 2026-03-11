<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../Layouts/MainLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    date: String,
    month: String,
    journal_id: [String, Number],
    journeeSessions: Array,
    journeeTotaux: Object,
    journalLignes: Array,
    journalEntete: Object,
    rapportMensuel: Object,
    rapportParCaisse: Array,
    caisses: Array,
});

const activeTab = ref(props.journal_id ? 'journal' : 'journee');
const date = ref(props.date || new Date().toISOString().slice(0, 10));
const month = ref(props.month || new Date().toISOString().slice(0, 7));

// Synchroniser les refs quand les props changent (après une requête Inertia)
watch(() => [props.date, props.month], ([newDate, newMonth]) => {
    if (newDate) date.value = newDate;
    if (newMonth) month.value = newMonth;
});

// Quand on arrive avec un journal_id (clic "Voir journal"), afficher l'onglet Journal caisse
watch(() => props.journal_id, (id) => {
    if (id) activeTab.value = 'journal';
}, { immediate: true });

const formatCurrency = (val) => {
    if (val == null) return '0,000';
    return new Intl.NumberFormat('fr-TN', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(Number(val));
};

const formatDate = (d) => {
    if (!d) return '—';
    const x = new Date(d);
    return x.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const loadWithDate = () => {
    router.get('/statistiques', { date: date.value, month: month.value }, { preserveState: true });
};

const loadWithMonth = () => {
    router.get('/statistiques', { date: date.value, month: month.value }, { preserveState: true });
};

const applyDate = () => loadWithDate();
const applyMonth = () => loadWithMonth();

const selectJournal = (id) => {
    router.get('/statistiques', { date: date.value, month: month.value, journal_id: id || undefined }, { preserveState: true });
};

// Actualisation automatique quand la date ou le mois change (dynamique)
watch(date, () => { if (activeTab.value === 'journee' || activeTab.value === 'journal') debouncedLoadDate(); });
watch(month, () => { if (activeTab.value === 'mensuel') debouncedLoadMonth(); });

const debouncedLoadDate = debounce(loadWithDate, 400);
const debouncedLoadMonth = debounce(loadWithMonth, 400);
</script>

<template>
    <MainLayout>
        <Head title="Statistiques" />
        <div class="p-4 sm:p-6 md:p-8 lg:p-12 pb-8 safe-bottom">
            <header class="mb-8">
                <h2 class="text-2xl md:text-3xl font-extrabold text-brand-charcoal tracking-tight mb-1">Statistiques</h2>
                <p class="text-sm md:text-base text-[#706f6c] font-medium">État de journée, journal de caisse et rapport mensuel</p>
            </header>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 mb-6 border-b border-[#e3e3e0] pb-2">
                <button
                    @click="activeTab = 'journee'"
                    :class="[activeTab === 'journee' ? 'bg-brand-gold/10 text-brand-gold border-brand-gold' : 'text-[#706f6c] border-transparent hover:bg-[#f8f8f7]', 'px-4 py-2 rounded-xl font-semibold border transition-all']"
                >
                    État de journée
                </button>
                <button
                    @click="activeTab = 'journal'"
                    :class="[activeTab === 'journal' ? 'bg-brand-gold/10 text-brand-gold border-brand-gold' : 'text-[#706f6c] border-transparent hover:bg-[#f8f8f7]', 'px-4 py-2 rounded-xl font-semibold border transition-all']"
                >
                    Journal caisse
                </button>
                <button
                    @click="activeTab = 'mensuel'"
                    :class="[activeTab === 'mensuel' ? 'bg-brand-gold/10 text-brand-gold border-brand-gold' : 'text-[#706f6c] border-transparent hover:bg-[#f8f8f7]', 'px-4 py-2 rounded-xl font-semibold border transition-all']"
                >
                    Rapport mensuel
                </button>
            </div>

            <!-- État de journée -->
            <div v-show="activeTab === 'journee'" class="space-y-6">
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <label class="text-sm font-bold text-[#706f6c]">Date</label>
                    <input v-model="date" type="date" class="rounded-xl border border-[#e3e3e0] px-4 py-2 text-sm focus:border-brand-gold focus:ring-0" />
                    <button @click="applyDate" class="px-4 py-2 bg-brand-gold text-white rounded-xl font-bold text-sm hover:opacity-90">Actualiser</button>
                </div>
                <div class="bg-white rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-[#f0f0f0]">
                        <h3 class="font-bold text-brand-charcoal">Résumé du {{ date }}</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Sessions</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ journeeTotaux?.nombre_sessions ?? 0 }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Fond de caisse</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(journeeTotaux?.total_fondcaisse) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Montant clôture</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(journeeTotaux?.total_cloture) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Montant théorique</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(journeeTotaux?.total_theorique) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Dépenses</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(journeeTotaux?.total_depense) }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left min-w-[520px]">
                            <thead>
                                <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-[#fdfdfc] border-t border-[#f0f0f0]">
                                    <th class="px-3 sm:px-6 py-4">Caisse / Site</th>
                                    <th class="px-3 sm:px-6 py-4 hidden sm:table-cell">Ouverture</th>
                                    <th class="px-3 sm:px-6 py-4 hidden md:table-cell">Clôture</th>
                                    <th class="px-3 sm:px-6 py-4 text-right">Fond</th>
                                    <th class="px-3 sm:px-6 py-4 text-right">Clôture</th>
                                    <th class="px-3 sm:px-6 py-4 text-right hidden lg:table-cell">Théorique</th>
                                    <th class="px-3 sm:px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0f0f0]">
                                <tr v-for="s in journeeSessions" :key="s.journalcaisseid" class="hover:bg-[#fcfcfb]">
                                    <td class="px-3 sm:px-6 py-4">
                                        <span class="font-bold text-brand-charcoal">{{ s.caisse_libelle || '—' }}</span>
                                        <span class="block text-[10px] text-[#706f6c]">{{ s.site_libelle || '—' }}</span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm hidden sm:table-cell">{{ formatDate(s.dateouverture) }}</td>
                                    <td class="px-3 sm:px-6 py-4 text-xs hidden md:table-cell">{{ formatDate(s.datecloture) }}</td>
                                    <td class="px-3 sm:px-6 py-4 text-right font-mono text-xs sm:text-sm">{{ formatCurrency(s.fondcaisse) }}</td>
                                    <td class="px-3 sm:px-6 py-4 text-right font-mono text-xs sm:text-sm">{{ formatCurrency(s.montantcloture) }}</td>
                                    <td class="px-3 sm:px-6 py-4 text-right font-mono text-xs hidden lg:table-cell">{{ formatCurrency(s.montanttheorique) }}</td>
                                    <td class="px-3 sm:px-6 py-4">
                                        <button @click="selectJournal(s.journalcaisseid)" class="text-brand-gold font-bold text-xs sm:text-sm hover:underline whitespace-nowrap">Voir journal</button>
                                    </td>
                                </tr>
                                <tr v-if="!journeeSessions?.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-[#706f6c]">Aucune session pour cette date.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Journal caisse -->
            <div v-show="activeTab === 'journal'" class="space-y-6">
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <label class="text-sm font-bold text-[#706f6c]">Date (sessions du jour)</label>
                    <input v-model="date" type="date" class="rounded-xl border border-[#e3e3e0] px-4 py-2 text-sm focus:border-brand-gold focus:ring-0" />
                    <button @click="applyDate()" class="px-4 py-2 bg-brand-gold text-white rounded-xl font-bold text-sm hover:opacity-90">Actualiser</button>
                    <button v-if="journal_id" @click="selectJournal(null)" class="px-4 py-2 border border-[#e3e3e0] rounded-xl font-bold text-sm">Toutes les lignes du jour</button>
                </div>
                <div v-if="journalEntete" class="bg-[#f8f8f7] rounded-xl p-4 mb-4">
                    <p class="text-xs font-bold text-[#706f6c] uppercase">Session : {{ journalEntete.caisse_libelle }} — {{ journalEntete.site_libelle }}</p>
                    <p class="text-sm">Ouverture {{ formatDate(journalEntete.dateouverture) }} — Clôture {{ formatDate(journalEntete.datecloture) }}</p>
                    <p class="text-sm">Fond: {{ formatCurrency(journalEntete.fondcaisse) }} — Clôture: {{ formatCurrency(journalEntete.montantcloture) }}</p>
                </div>
                <div class="bg-white rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-[#f0f0f0]">
                        <h3 class="font-bold text-brand-charcoal">Lignes du journal</h3>
                    </div>
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left min-w-[400px]">
                            <thead>
                                <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-[#fdfdfc] border-b border-[#f0f0f0]">
                                    <th class="px-3 sm:px-4 py-4 min-w-[120px]">Libellé</th>
                                    <th class="px-3 sm:px-4 py-4 min-w-[80px] hidden sm:table-cell">Section</th>
                                    <th class="px-3 sm:px-4 py-4 min-w-[70px] text-right">Valeur</th>
                                    <th class="px-3 sm:px-4 py-4 min-w-[70px] text-right hidden md:table-cell">Achat TTC</th>
                                    <th class="px-3 sm:px-4 py-4 min-w-[70px] text-right hidden md:table-cell">Marge TTC</th>
                                    <th class="px-3 sm:px-4 py-4 min-w-[50px] text-right hidden lg:table-cell">Qte</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0f0f0]">
                                <tr v-for="l in journalLignes" :key="l.journalcaissedetid" class="hover:bg-[#fcfcfb]">
                                    <td class="px-3 sm:px-4 py-3 break-words text-xs sm:text-sm">{{ l.libelle }}</td>
                                    <td class="px-3 sm:px-4 py-3 text-xs hidden sm:table-cell">{{ l.section_libelle || '—' }}</td>
                                    <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs sm:text-sm">{{ formatCurrency(l.valeur) }}</td>
                                    <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs hidden md:table-cell">{{ formatCurrency(l.achatttc) }}</td>
                                    <td class="px-3 sm:px-4 py-3 text-right font-mono text-xs hidden md:table-cell">{{ formatCurrency(l.margettc) }}</td>
                                    <td class="px-3 sm:px-4 py-3 text-right text-xs hidden lg:table-cell">{{ l.qte != null ? l.qte : '—' }}</td>
                                </tr>
                                <tr v-if="journalEntete && !journalLignes?.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-[#706f6c]">Aucune ligne pour cette session.</td>
                                </tr>
                                <tr v-if="!journalEntete && !journalLignes?.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-[#706f6c]">Choisir une date et cliquer sur « Voir journal » pour une session, ou aucune donnée pour ce jour.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rapport mensuel -->
            <div v-show="activeTab === 'mensuel'" class="space-y-6">
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <label class="text-sm font-bold text-[#706f6c]">Mois</label>
                    <input v-model="month" type="month" class="rounded-xl border border-[#e3e3e0] px-4 py-2 text-sm focus:border-brand-gold focus:ring-0" />
                    <button @click="applyMonth" class="px-4 py-2 bg-brand-gold text-white rounded-xl font-bold text-sm hover:opacity-90">Actualiser</button>
                </div>
                <div class="bg-white rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-[#f0f0f0]">
                        <h3 class="font-bold text-brand-charcoal">Synthèse du mois {{ month }}</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Sessions</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ rapportMensuel?.nb_sessions ?? 0 }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Total fond</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(rapportMensuel?.total_fond) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Total clôture</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(rapportMensuel?.total_cloture) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Total théorique</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(rapportMensuel?.total_theorique) }}</p>
                        </div>
                        <div class="bg-[#f8f8f7] rounded-xl p-4">
                            <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider">Total dépenses</p>
                            <p class="text-xl font-black text-brand-charcoal">{{ formatCurrency(rapportMensuel?.total_depense) }}</p>
                        </div>
                    </div>
                    <div class="p-6 border-t border-[#f0f0f0]">
                        <h4 class="text-sm font-bold text-brand-charcoal mb-3">Par caisse</h4>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest">
                                    <th class="px-4 py-2">Caisse</th>
                                    <th class="px-4 py-2 text-right">Sessions</th>
                                    <th class="px-4 py-2 text-right">Total clôture</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0f0f0]">
                                <tr v-for="r in rapportParCaisse" :key="r.caisse_libelle" class="hover:bg-[#fcfcfb]">
                                    <td class="px-4 py-3 font-medium">{{ r.caisse_libelle || '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ r.nb_sessions }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency(r.total_cloture) }}</td>
                                </tr>
                                <tr v-if="!rapportParCaisse?.length">
                                    <td colspan="3" class="px-6 py-6 text-center text-[#706f6c]">Aucune donnée pour ce mois.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

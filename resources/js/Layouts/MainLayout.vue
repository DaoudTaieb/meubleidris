<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const isSidebarOpen = ref(false);

const logoutForm = useForm({});
const logout = () => logoutForm.post('/logout');

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-[#fdfdfc] flex">
        <!-- Sidebar Backdrop (Mobile only) -->
        <div 
            v-if="isSidebarOpen" 
            @click="isSidebarOpen = false"
            class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 lg:hidden transition-all duration-300"
        ></div>

        <!-- Sidebar -->
        <aside 
            :class="[
                'w-72 border-r border-[#e3e3e0] flex flex-col fixed h-full bg-white z-50 transition-transform duration-300 lg:translate-x-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="p-8 h-full flex flex-col">
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center gap-3">
                        <img :src="'/logo.jpg'" alt="MOLKA MODEN" class="w-12 h-12 object-contain rounded-lg shadow-sm" />
                        <div class="flex flex-col">
                            <span class="font-bold text-lg tracking-tighter leading-none text-brand-charcoal">MEUBLE</span>
                            <span class="text-[10px] font-bold text-brand-gold tracking-[0.2em]">DRISS</span>
                        </div>
                    </div>
                    <!-- Close button for mobile -->
                    <button @click="isSidebarOpen = false" class="lg:hidden p-2 text-[#706f6c]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <nav class="space-y-1">
                    <div class="pb-2 px-4 text-[10px] font-bold text-[#706f6c] uppercase tracking-widest">Opérations</div>

                    <Link 
                        href="/" 
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition-all',
                            $page.component === 'Dashboard' ? 'bg-brand-gold/10 text-brand-gold' : 'text-[#706f6c] hover:bg-[#f8f8f7]'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>Fournisseurs</span>
                    </Link>
                    
                    <Link 
                        href="/achats" 
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition-all',
                            $page.component === 'Purchases' ? 'bg-brand-gold/10 text-brand-gold' : 'text-[#706f6c] hover:bg-[#f8f8f7]'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                        <span>Achats</span>
                    </Link>

                    <Link 
                        href="/stock" 
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition-all',
                            $page.component === 'Stock' ? 'bg-brand-gold/10 text-brand-gold' : 'text-[#706f6c] hover:bg-[#f8f8f7]'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        <span>Stock</span>
                    </Link>
                </nav>

                <div class="mt-auto pt-8 border-t border-[#f0f0f0]">
                    <button @click="logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[#ef4444] hover:bg-[#ef4444]/5 font-semibold transition-all w-full leading-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        <span>Déconnexion</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header -->
            <header class="lg:hidden flex items-center justify-between px-6 py-4 bg-white border-b border-[#e3e3e0] sticky top-0 z-30 shadow-sm">
                <div class="flex items-center gap-3">
                    <img :src="'/logo.jpg'" alt="Logo" class="w-10 h-10 object-contain rounded-xl shadow-sm" />
                    <div class="flex flex-col">
                        <span class="text-xs font-black tracking-tighter text-brand-charcoal leading-none">MEUBLE</span>
                        <span class="text-[8px] font-bold text-brand-gold tracking-[0.2em]">DRISS</span>
                    </div>
                </div>
                <button @click="toggleSidebar" class="p-2.5 bg-[#f8f8f7] rounded-xl text-brand-charcoal hover:bg-brand-gold/10 hover:text-brand-gold transition-all border border-transparent active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
            </header>

            <!-- Page Content -->
            <main class="flex-1 lg:ml-72 min-h-screen">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="border-b border-gray-100 bg-white print:hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link :href="route('resumes.index')" class="flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white">
                            R
                        </div>
                        <span class="text-sm font-semibold text-gray-900">ResumeAI</span>
                    </Link>

                    <Link
                        :href="route('resumes.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zm8 0a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM3 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zm10-2a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Meus Currículos
                    </Link>

                    <div class="flex items-center gap-3">
                        <Dropdown align="right" width="64">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600"
                                    aria-label="Menu do usuário"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 2a5 5 0 00-3.173 8.87A7.002 7.002 0 003 17a1 1 0 102 0 5 5 0 0110 0 1 1 0 102 0 7.002 7.002 0 00-3.827-6.13A5 5 0 0010 2zm-3 5a3 3 0 116 0 3 3 0 01-6 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-2 text-sm text-gray-700">
                                    <div class="font-medium truncate">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.email }}</div>
                                </div>
                                <div class="border-t border-gray-100" />
                                <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="title" class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-gray-900">{{ title }}</h1>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>

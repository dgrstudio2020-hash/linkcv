<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';

defineProps({
    resumes: {
        type: Array,
        required: true,
    },
});

const createForm = useForm({});

const createResume = () => {
    createForm.post(route('resumes.store'));
};

const deleteResume = (resumeId) => {
    if (!confirm('Excluir este currículo?')) return;

    router.delete(route('resumes.destroy', resumeId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">Seus Currículos</h1>
                    <p class="mt-1 text-sm text-gray-500">Gerencie seus perfis profissionais</p>
                </div>

                <button
                    type="button"
                    @click="createResume"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                    :disabled="createForm.processing"
                >
                    + Novo Currículo
                </button>
            </div>

            <div class="mt-8">
                <div v-if="resumes.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
                    <div class="text-sm text-gray-600">Você ainda não criou nenhum currículo.</div>
                    <button
                        type="button"
                        @click="createResume"
                        class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                        :disabled="createForm.processing"
                    >
                        + Novo Currículo
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="resume in resumes"
                        :key="resume.id"
                        class="relative rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <button
                            type="button"
                            class="absolute right-4 top-4 rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                            @click="deleteResume(resume.id)"
                            aria-label="Excluir"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path
                                    fill-rule="evenodd"
                                    d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v9a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v9a1 1 0 11-2 0V6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>

                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path
                                        fill-rule="evenodd"
                                        d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-3.414-3.414A2 2 0 0013.586 2H4zm9 1.5V6a1 1 0 001 1h2.5L13 3.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-gray-900">{{ resume.name }}</div>
                                <div class="mt-1 truncate text-sm text-gray-500">
                                    {{ resume.headline || '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <Link
                                :href="route('resumes.edit', resume.id)"
                                class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            >
                                Editar Currículo
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

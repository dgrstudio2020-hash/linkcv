<script setup>
import { computed } from 'vue';

const props = defineProps({
    preview: {
        type: Object,
        required: true,
    },
    resumeId: {
        type: [Number, String],
        default: null,
    },
    minimalContactLine: {
        type: String,
        default: '',
    },
    mode: {
        type: String,
        default: 'screen',
        validator: (v) => ['screen', 'pdf'].includes(v),
    },
});

const isPdf = computed(() => props.mode === 'pdf');

const experiencesToShow = computed(() => (isPdf.value ? props.preview.experiences : props.preview.experiences.slice(0, 6)));
const skillsToShow = computed(() => (isPdf.value ? props.preview.skills : props.preview.skills.slice(0, 10)));
const languagesToShow = computed(() => (isPdf.value ? props.preview.languages : props.preview.languages.slice(0, 6)));
const educationToShow = computed(() => (isPdf.value ? props.preview.education : props.preview.education.slice(0, 6)));
const additionalInfoToShow = computed(() => (isPdf.value ? props.preview.additional_info : props.preview.additional_info.slice(0, 10)));

const resolvePhotoSrc = (value) => {
    const raw = (value ?? '').toString().trim();
    if (!raw) return '';

    if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) {
        return raw;
    }

    if (!props.resumeId) return raw;

    return `/storage/resume-photos/${encodeURIComponent(props.resumeId)}/${encodeURIComponent(raw)}`;
};

const photoSrc = computed(() => resolvePhotoSrc(props.preview?.photo_url));

const modernPhotoBgStyle = computed(() => {
    if (!photoSrc.value) return {};
    return {
        backgroundImage: `url('${photoSrc.value}')`,
    };
});
</script>

<template>
    <div
        class="bg-white"
        :class="isPdf ? 'w-[210mm] min-h-[297mm]' : 'h-full w-full'"
    >
        <!-- Modern template -->
        <div v-if="preview.template === 'modern'" class="flex" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <!-- Sidebar -->
            <div class="w-[36%] bg-slate-700 text-white">
                <div v-if="preview.photo_url" class="p-5">
                    <div class="relative w-full overflow-hidden rounded-sm bg-slate-200">
                        <div class="pb-[100%]" />
                        <div
                            v-if="isPdf"
                            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                            :style="modernPhotoBgStyle"
                        />
                        <img
                            v-else
                            :src="photoSrc"
                            alt="Foto"
                            class="absolute inset-0 h-full w-full object-cover"
                            crossorigin="anonymous"
                            referrerpolicy="no-referrer"
                        />
                    </div>
                </div>

                <div class="px-5 pb-6">
                    <div v-if="preview.location || preview.phone || preview.email || preview.linkedin" class="border-t border-slate-500 pt-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Contato</div>
                        <div class="mt-3 space-y-2 text-[11px] leading-4 text-slate-100">
                            <div v-if="preview.location" class="flex gap-2">
                                <span class="mt-0.5 h-3 w-3 shrink-0">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span class="break-words">{{ preview.location }}</span>
                            </div>
                            <div v-if="preview.phone" class="flex gap-2">
                                <span class="mt-0.5 h-3 w-3 shrink-0">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                        <path d="M2 3.5A1.5 1.5 0 013.5 2h1A1.5 1.5 0 016 3.5v1a1.5 1.5 0 01-.44 1.06l-1.1 1.1a11.04 11.04 0 005.88 5.88l1.1-1.1A1.5 1.5 0 0112.5 11h1A1.5 1.5 0 0115 12.5v1A1.5 1.5 0 0113.5 15h-.5C7.48 15 3 10.52 3 5v-.5z" />
                                    </svg>
                                </span>
                                <span class="break-words">{{ preview.phone }}</span>
                            </div>
                            <div v-if="preview.email" class="flex min-w-0 gap-2">
                                <span class="mt-0.5 h-3 w-3 shrink-0">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                        <path d="M2.94 6.34A2 2 0 014.56 5h10.88a2 2 0 011.62 1.34L10 10.5 2.94 6.34z" />
                                        <path d="M18 8.12V14a2 2 0 01-2 2H4a2 2 0 01-2-2V8.12l7.4 4.45a1 1 0 001.2 0L18 8.12z" />
                                    </svg>
                                </span>
                                <span class="min-w-0 break-all">{{ preview.email }}</span>
                            </div>
                            <div v-if="preview.linkedin" class="flex gap-2">
                                <span class="mt-0.5 h-3 w-3 shrink-0">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3">
                                        <path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM0.22 23.5h4.56V7.98H0.22V23.5zM8.22 7.98H12.6v2.12h.06c.61-1.16 2.1-2.38 4.33-2.38 4.63 0 5.49 3.05 5.49 7.01v8.77h-4.56v-7.78c0-1.85-.03-4.23-2.58-4.23-2.58 0-2.98 2.01-2.98 4.1v7.91H8.22V7.98z" />
                                    </svg>
                                </span>
                                <span class="break-words">{{ preview.linkedin }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="skillsToShow.length" class="mt-6 border-t border-slate-500 pt-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Habilidades e Competências</div>
                        <div class="mt-3 space-y-3">
                            <div v-for="(skill, idx) in skillsToShow" :key="`${skill?.name || 'skill'}-${idx}`" class="text-[11px]">
                                <div class="min-w-0 font-semibold">
                                    <span class="break-words">{{ skill.name }}</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full bg-slate-600">
                                    <div class="h-1.5 bg-white" :style="{ width: `${Number.isFinite(skill.percent) ? skill.percent : 70}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="languagesToShow.length" class="mt-6 border-t border-slate-500 pt-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Idiomas</div>
                        <div class="mt-3 space-y-3">
                            <div v-for="(lang, idx) in languagesToShow" :key="idx" class="text-[11px]">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold">{{ lang.name }}</div>
                                    <div class="text-slate-200">{{ lang.level || '' }}</div>
                                </div>
                                <div class="mt-2 h-1.5 w-full bg-slate-600">
                                    <div class="h-1.5 bg-white" :style="{ width: `${Number.isFinite(lang.percent) ? lang.percent : 70}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="additionalInfoToShow.length" class="mt-6 border-t border-slate-500 pt-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Informações Adicionais</div>
                        <div class="mt-3 space-y-2 text-[11px] leading-4">
                            <div v-for="(item, idx) in additionalInfoToShow" :key="idx" class="break-words">
                                <span class="font-semibold">{{ item.label || '' }}</span>
                                <span v-if="item.label && item.value">: </span>
                                <span class="text-slate-100">{{ item.value || '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main -->
            <div class="w-[64%] px-8 py-6 text-gray-700">
                <div class="font-archivo-black text-1xl font-black uppercase tracking-wide">{{ preview.name }}</div>
                <div class="mt-2 border-b-4 border-gray-900"></div>

                <div v-if="(preview.summary || '').trim()" class="mt-5">
                    <div class="font-archivo-black text-xs font-extrabold uppercase">Resumo</div>
                    <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                </div>

                <div v-if="experiencesToShow.length" class="mt-6">
                    <div class="font-archivo-black text-xs font-extrabold uppercase">Experiência Profissional</div>
                    <div class="mt-2 border-b border-gray-900"></div>

                    <div class="mt-4 space-y-5">
                        <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div v-if="exp.role" class="font-archivo-black font-extrabold">{{ exp.role }}</div>
                                    <div v-if="exp.company" class="font-archivo-black font-semibold">{{ exp.company }}</div>
                                </div>
                                <div class="shrink-0 text-[10px] font-semibold text-gray-700">
                                    {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                </div>
                            </div>

                            <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                <div
                                    v-for="(b, bIdx) in (isPdf ? exp.bullets : exp.bullets.slice(0, 6))"
                                    :key="bIdx"
                                    class="grid grid-cols-[10px_1fr] gap-x-2"
                                >
                                    <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                    <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="educationToShow.length" class="mt-6">
                    <div class="font-archivo-black text-xs font-extrabold uppercase">Formação Acadêmica</div>
                    <div class="mt-2 border-b border-gray-900"></div>

                    <div class="mt-4 space-y-4 text-[11px]">
                        <div v-for="(edu, idx) in educationToShow" :key="idx">
                            <div v-if="edu.course" class="font-extrabold">{{ edu.course }}</div>
                            <div class="text-gray-700">
                                <span class="font-semibold">{{ edu.institution || '' }}</span>
                                <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                <span>{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                            </div>
                            <div v-if="edu.status" class="text-gray-700"><span class="font-semibold">Status</span> - {{ edu.status }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elegant template -->
        <div v-else-if="preview.template === 'elegant'" class="flex flex-col" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="bg-gradient-to-r from-indigo-700 to-slate-900 px-10 py-8 text-white">
                <div class="flex items-center gap-6">
                    <div v-if="preview.photo_url" class="relative w-20 shrink-0 overflow-hidden rounded-full bg-white/15 border-2 border-white/30">
                        <div class="pb-[100%]" />
                        <img
                            :src="photoSrc"
                            alt="Foto"
                            class="absolute inset-0 h-full w-full object-cover"
                            crossorigin="anonymous"
                            referrerpolicy="no-referrer"
                        />
                    </div>

                    <div class="min-w-0">
                        <div class="font-archivo-black text-2xl font-black uppercase tracking-wide">{{ preview.name }}</div>
                        <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-indigo-100">
                            {{ preview.headline }}
                        </div>

                        <div v-if="preview.location || preview.phone || preview.email || preview.linkedin" class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-100">
                            <span v-if="preview.location" class="break-words">{{ preview.location }}</span>
                            <span v-if="preview.phone" class="break-words">{{ preview.phone }}</span>
                            <span v-if="preview.email" class="break-all">{{ preview.email }}</span>
                            <span v-if="preview.linkedin" class="break-words">{{ preview.linkedin }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-10 py-8 text-gray-700">
                <div class="grid grid-cols-12 gap-7">
                    <div class="col-span-4 space-y-6">
                        <div v-if="skillsToShow.length">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Habilidades</div>
                            <div class="mt-3 space-y-3">
                                <div v-for="(skill, idx) in skillsToShow" :key="`${skill?.name || 'skill'}-${idx}`" class="text-[11px]">
                                    <div class="min-w-0 font-semibold">
                                        <span class="break-words">{{ skill.name }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 w-full rounded bg-gray-200">
                                        <div class="h-1.5 rounded bg-indigo-600" :style="{ width: `${Number.isFinite(skill.percent) ? skill.percent : 70}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="languagesToShow.length">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Idiomas</div>
                            <div class="mt-3 space-y-3">
                                <div v-for="(lang, idx) in languagesToShow" :key="idx" class="text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold">{{ lang.name }}</div>
                                        <div class="text-gray-500">{{ lang.level || '' }}</div>
                                    </div>
                                    <div class="mt-2 h-1.5 w-full rounded bg-gray-200">
                                        <div class="h-1.5 rounded bg-indigo-600" :style="{ width: `${Number.isFinite(lang.percent) ? lang.percent : 70}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="additionalInfoToShow.length">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Extras</div>
                            <div class="mt-3 space-y-2 text-[11px] leading-4 text-gray-700">
                                <div v-for="(item, idx) in additionalInfoToShow" :key="idx" class="break-words">
                                    <span class="font-semibold">{{ item.label || '' }}</span>
                                    <span v-if="item.label && item.value">: </span>
                                    <span class="text-gray-600">{{ item.value || '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-8 space-y-7">
                        <div v-if="(preview.summary || '').trim()">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Resumo</div>
                            <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                        </div>

                        <div v-if="experiencesToShow.length">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Experiência</div>
                            <div class="mt-3 space-y-5">
                                <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div v-if="exp.role" class="font-extrabold text-gray-900">{{ exp.role }}</div>
                                            <div v-if="exp.company" class="font-semibold text-gray-700">{{ exp.company }}</div>
                                        </div>
                                        <div class="shrink-0 text-[10px] font-semibold text-gray-500">
                                            {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                        </div>
                                    </div>

                                    <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                        <template v-if="isPdf">
                                            <div
                                                v-for="(b, bIdx) in exp.bullets"
                                                :key="bIdx"
                                                class="grid grid-cols-[10px_1fr] gap-x-2"
                                            >
                                                <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                                <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                            </div>
                                        </template>

                                        <ul v-else class="list-disc space-y-1 pl-5 text-gray-700">
                                            <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                        </ul>
                                    </div>
                                    <div v-else-if="exp.description" class="mt-2 text-gray-700">{{ exp.description }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="educationToShow.length">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Formação</div>
                            <div class="mt-3 space-y-4 text-[11px]">
                                <div v-for="(edu, idx) in educationToShow" :key="idx">
                                    <div v-if="edu.course" class="font-extrabold text-gray-900">{{ edu.course }}</div>
                                    <div class="text-gray-700">
                                        <span class="font-semibold">{{ edu.institution || '' }}</span>
                                        <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                        <span class="text-gray-600">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                                    </div>
                                    <div v-if="edu.status" class="text-gray-600"><span class="font-semibold">Status</span> - {{ edu.status }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Corporate (ATS-friendly) template -->
        <div v-else-if="preview.template === 'corporate'" class="px-10 py-8 text-gray-800" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="flex items-start justify-between gap-6">
                <div class="min-w-0">
                    <div class="font-archivo-black text-3xl font-black tracking-wide text-gray-900">{{ preview.name }}</div>
                    <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-gray-700">{{ preview.headline }}</div>
                    <div v-if="minimalContactLine" class="mt-3 text-[11px] text-gray-600">{{ minimalContactLine }}</div>
                </div>

                <div v-if="preview.photo_url" class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-gray-100 ring-1 ring-gray-200">
                    <img
                        :src="photoSrc"
                        alt="Foto"
                        class="h-full w-full object-cover"
                        crossorigin="anonymous"
                        referrerpolicy="no-referrer"
                    />
                </div>
            </div>

            <div class="mt-5 border-b border-gray-300" />

            <div class="mt-6 space-y-7">
                <div v-if="(preview.summary || '').trim()">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Resumo</div>
                    <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                </div>

                <div v-if="experiencesToShow.length">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Experiência Profissional</div>
                    <div class="mt-3 space-y-5">
                        <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900">
                                        <span v-if="exp.role" class="break-words">{{ exp.role }}</span>
                                        <span v-if="exp.role && exp.company" class="text-gray-400"> · </span>
                                        <span v-if="exp.company" class="break-words text-gray-700">{{ exp.company }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-[10px] font-semibold text-gray-500">
                                    {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                </div>
                            </div>

                            <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                <template v-if="isPdf">
                                    <div
                                        v-for="(b, bIdx) in exp.bullets"
                                        :key="bIdx"
                                        class="grid grid-cols-[10px_1fr] gap-x-2"
                                    >
                                        <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                        <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                    </div>
                                </template>

                                <ul v-else class="list-disc space-y-1 pl-5 text-gray-700">
                                    <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                </ul>
                            </div>
                            <div v-else-if="exp.description" class="mt-2 text-gray-700">{{ exp.description }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="educationToShow.length">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Formação Acadêmica</div>
                    <div class="mt-3 space-y-4 text-[11px]">
                        <div v-for="(edu, idx) in educationToShow" :key="idx">
                            <div v-if="edu.course" class="font-semibold text-gray-900">{{ edu.course }}</div>
                            <div class="text-gray-700">
                                <span class="font-semibold">{{ edu.institution || '' }}</span>
                                <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                <span class="text-gray-600">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                            </div>
                            <div v-if="edu.status" class="text-gray-600"><span class="font-semibold">Status</span> - {{ edu.status }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-7" v-if="skillsToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Habilidades</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="(skill, idx) in (isPdf ? skillsToShow : skillsToShow.slice(0, 14))"
                                :key="`${skill?.name || 'skill'}-${idx}`"
                                class="rounded border border-gray-200 bg-white px-2.5 py-1 text-[11px] text-gray-700"
                            >
                                {{ skill.name }}
                            </span>
                        </div>
                    </div>

                    <div class="col-span-5" v-if="languagesToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Idiomas</div>
                        <div class="mt-3 space-y-2 text-[11px]">
                            <div v-for="(lang, idx) in languagesToShow" :key="idx" class="flex items-center justify-between gap-3">
                                <div class="font-semibold">{{ lang.name }}</div>
                                <div class="text-gray-600">{{ lang.level || '' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="additionalInfoToShow.length">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Informações Adicionais</div>
                    <div class="mt-2 text-[11px] leading-4 text-gray-700">
                        <div v-for="(item, idx) in additionalInfoToShow" :key="idx" class="break-words">
                            <span class="font-semibold">{{ item.label || '' }}</span>
                            <span v-if="item.label && item.value">: </span>
                            <span class="text-gray-600">{{ item.value || '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar template -->
        <div v-else-if="preview.template === 'sidebar'" class="flex" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="w-[32%] bg-emerald-700 px-6 py-7 text-white">
                <div v-if="preview.photo_url" class="mb-5 flex justify-center">
                    <div class="h-24 w-24 overflow-hidden rounded-full bg-white/15 ring-2 ring-white/30">
                        <img
                            :src="photoSrc"
                            alt="Foto"
                            class="h-full w-full object-cover"
                            crossorigin="anonymous"
                            referrerpolicy="no-referrer"
                        />
                    </div>
                </div>

                <div v-if="preview.location || preview.phone || preview.email || preview.linkedin">
                    <div class="text-xs font-extrabold uppercase tracking-wide">Contato</div>
                    <div class="mt-3 space-y-2 text-[11px] leading-4 text-emerald-50">
                        <div v-if="preview.location" class="break-words">{{ preview.location }}</div>
                        <div v-if="preview.phone" class="break-words">{{ preview.phone }}</div>
                        <div v-if="preview.email" class="break-all">{{ preview.email }}</div>
                        <div v-if="preview.linkedin" class="break-words">{{ preview.linkedin }}</div>
                    </div>
                </div>

                <div v-if="skillsToShow.length" class="mt-6 border-t border-white/30 pt-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide">Habilidades</div>
                    <div class="mt-3 space-y-3">
                        <div v-for="(skill, idx) in skillsToShow" :key="`${skill?.name || 'skill'}-${idx}`" class="text-[11px]">
                            <div class="font-semibold text-white/95">{{ skill.name }}</div>
                            <div class="mt-2 h-1.5 w-full rounded bg-white/25">
                                <div class="h-1.5 rounded bg-white" :style="{ width: `${Number.isFinite(skill.percent) ? skill.percent : 70}%` }" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="languagesToShow.length" class="mt-6 border-t border-white/30 pt-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide">Idiomas</div>
                    <div class="mt-3 space-y-2 text-[11px]">
                        <div v-for="(lang, idx) in languagesToShow" :key="idx" class="flex items-center justify-between gap-3">
                            <div class="font-semibold">{{ lang.name }}</div>
                            <div class="text-emerald-100">{{ lang.level || '' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-[68%] px-8 py-7 text-gray-800">
                <div class="font-archivo-black text-2xl font-black tracking-wide text-gray-900">{{ preview.name }}</div>
                <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-emerald-700">{{ preview.headline }}</div>

                <div v-if="(preview.summary || '').trim()" class="mt-6">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Resumo</div>
                    <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                </div>

                <div v-if="experiencesToShow.length" class="mt-6">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Experiência</div>
                    <div class="mt-3 space-y-5">
                        <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div v-if="exp.role" class="font-extrabold text-gray-900">{{ exp.role }}</div>
                                    <div v-if="exp.company" class="font-semibold text-gray-700">{{ exp.company }}</div>
                                </div>
                                <div class="shrink-0 text-[10px] font-semibold text-gray-500">
                                    {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                </div>
                            </div>
                            <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                <template v-if="isPdf">
                                    <div
                                        v-for="(b, bIdx) in exp.bullets"
                                        :key="bIdx"
                                        class="grid grid-cols-[10px_1fr] gap-x-2"
                                    >
                                        <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                        <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                    </div>
                                </template>

                                <ul v-else class="list-disc space-y-1 pl-5 text-gray-700">
                                    <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                </ul>
                            </div>
                            <div v-else-if="exp.description" class="mt-2 text-gray-700">{{ exp.description }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="educationToShow.length" class="mt-6">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Formação</div>
                    <div class="mt-3 space-y-4 text-[11px]">
                        <div v-for="(edu, idx) in educationToShow" :key="idx">
                            <div v-if="edu.course" class="font-extrabold text-gray-900">{{ edu.course }}</div>
                            <div class="text-gray-700">
                                <span class="font-semibold">{{ edu.institution || '' }}</span>
                                <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                <span class="text-gray-600">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                            </div>
                            <div v-if="edu.status" class="text-gray-600"><span class="font-semibold">Status</span> - {{ edu.status }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classic template -->
        <div v-else-if="preview.template === 'classic'" class="px-10 py-8 text-gray-800" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="text-center">
                <div class="font-archivo-black text-3xl font-black tracking-wide text-gray-900">{{ preview.name }}</div>
                <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-gray-700">{{ preview.headline }}</div>
                <div v-if="minimalContactLine" class="mt-3 text-[11px] text-gray-600">{{ minimalContactLine }}</div>
            </div>

            <div class="mt-6 border-b border-gray-300" />

            <div class="mt-7 grid grid-cols-12 gap-7">
                <div class="col-span-8 space-y-7">
                    <div v-if="(preview.summary || '').trim()">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Resumo</div>
                        <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                    </div>

                    <div v-if="experiencesToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Experiência</div>
                        <div class="mt-3 space-y-5">
                            <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div v-if="exp.role" class="font-extrabold text-gray-900">{{ exp.role }}</div>
                                        <div v-if="exp.company" class="font-semibold text-gray-700">{{ exp.company }}</div>
                                    </div>
                                    <div class="shrink-0 text-[10px] font-semibold text-gray-500">
                                        {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                    </div>
                                </div>
                                <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                    <template v-if="isPdf">
                                        <div
                                            v-for="(b, bIdx) in exp.bullets"
                                            :key="bIdx"
                                            class="grid grid-cols-[10px_1fr] gap-x-2"
                                        >
                                            <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                            <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                        </div>
                                    </template>

                                    <ul v-else class="list-disc space-y-1 pl-5 text-gray-700">
                                        <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                    </ul>
                                </div>
                                <div v-else-if="exp.description" class="mt-2 text-gray-700">{{ exp.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-4 space-y-7">
                    <div v-if="preview.photo_url" class="flex justify-center">
                        <div class="h-24 w-24 overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                            <img
                                :src="photoSrc"
                                alt="Foto"
                                class="h-full w-full object-cover"
                                crossorigin="anonymous"
                                referrerpolicy="no-referrer"
                            />
                        </div>
                    </div>

                    <div v-if="skillsToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Habilidades</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="(skill, idx) in (isPdf ? skillsToShow : skillsToShow.slice(0, 14))"
                                :key="`${skill?.name || 'skill'}-${idx}`"
                                class="rounded bg-gray-100 px-2.5 py-1 text-[11px] text-gray-700"
                            >
                                {{ skill.name }}
                            </span>
                        </div>
                    </div>

                    <div v-if="educationToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Formação</div>
                        <div class="mt-3 space-y-3 text-[11px]">
                            <div v-for="(edu, idx) in educationToShow" :key="idx">
                                <div v-if="edu.course" class="font-semibold text-gray-900">{{ edu.course }}</div>
                                <div class="text-gray-700">
                                    <span class="font-semibold">{{ edu.institution || '' }}</span>
                                    <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                    <span class="text-gray-600">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="languagesToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Idiomas</div>
                        <div class="mt-3 space-y-2 text-[11px]">
                            <div v-for="(lang, idx) in languagesToShow" :key="idx" class="flex items-center justify-between gap-3">
                                <div class="font-semibold">{{ lang.name }}</div>
                                <div class="text-gray-600">{{ lang.level || '' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Creative template -->
        <div v-else-if="preview.template === 'creative'" class="px-10 py-8" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="rounded-2xl bg-gradient-to-r from-fuchsia-600 via-indigo-600 to-cyan-600 px-8 py-7 text-white">
                <div class="flex items-center justify-between gap-6">
                    <div class="min-w-0">
                        <div class="font-archivo-black text-2xl font-black uppercase tracking-wide">{{ preview.name }}</div>
                        <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-white/90">{{ preview.headline }}</div>
                        <div v-if="minimalContactLine" class="mt-3 text-[11px] text-white/90">{{ minimalContactLine }}</div>
                    </div>

                    <div v-if="preview.photo_url" class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-white/15 ring-2 ring-white/30">
                        <img
                            :src="photoSrc"
                            alt="Foto"
                            class="h-full w-full object-cover"
                            crossorigin="anonymous"
                            referrerpolicy="no-referrer"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-12 gap-7 text-gray-800">
                <div class="col-span-7 space-y-7">
                    <div v-if="(preview.summary || '').trim()">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Resumo</div>
                        <div class="mt-2 text-[11px] leading-4 text-gray-700">{{ preview.summary }}</div>
                    </div>

                    <div v-if="experiencesToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Experiência</div>
                        <div class="mt-3 space-y-5">
                            <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div v-if="exp.role" class="font-extrabold text-gray-900">{{ exp.role }}</div>
                                        <div v-if="exp.company" class="font-semibold text-gray-700">{{ exp.company }}</div>
                                    </div>
                                    <div class="shrink-0 text-[10px] font-semibold text-gray-500">
                                        {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                    </div>
                                </div>
                                <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                    <template v-if="isPdf">
                                        <div
                                            v-for="(b, bIdx) in exp.bullets"
                                            :key="bIdx"
                                            class="grid grid-cols-[10px_1fr] gap-x-2"
                                        >
                                            <div class="pt-[1px] text-[12px] leading-4 text-gray-700">•</div>
                                            <div class="min-w-0 break-words text-[11px] leading-4 text-gray-700">{{ b }}</div>
                                        </div>
                                    </template>

                                    <ul v-else class="list-disc space-y-1 pl-5 text-gray-700">
                                        <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                    </ul>
                                </div>
                                <div v-else-if="exp.description" class="mt-2 text-gray-700">{{ exp.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-5 space-y-7">
                    <div v-if="skillsToShow.length" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Habilidades</div>
                        <div class="mt-3 space-y-3">
                            <div v-for="(skill, idx) in skillsToShow" :key="`${skill?.name || 'skill'}-${idx}`" class="text-[11px]">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 font-semibold text-gray-800">
                                        <span class="break-words">{{ skill.name }}</span>
                                    </div>
                                    <div class="shrink-0 text-[10px] font-semibold text-gray-500">{{ Number.isFinite(skill.percent) ? skill.percent : 70 }}%</div>
                                </div>
                                <div class="mt-2 h-1.5 w-full rounded bg-gray-200">
                                    <div class="h-1.5 rounded bg-indigo-600" :style="{ width: `${Number.isFinite(skill.percent) ? skill.percent : 70}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="educationToShow.length" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-gray-900">Formação</div>
                        <div class="mt-3 space-y-3 text-[11px]">
                            <div v-for="(edu, idx) in educationToShow" :key="idx">
                                <div v-if="edu.course" class="font-semibold text-gray-900">{{ edu.course }}</div>
                                <div class="text-gray-700">
                                    <span class="font-semibold">{{ edu.institution || '' }}</span>
                                    <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                    <span class="text-gray-600">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mono template (black/white) -->
        <div v-else-if="preview.template === 'mono'" class="px-10 py-8 text-gray-900" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="flex items-start justify-between gap-6">
                <div class="min-w-0">
                    <div class="font-archivo-black text-3xl font-black tracking-wide">{{ preview.name }}</div>
                    <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-gray-700">{{ preview.headline }}</div>
                    <div v-if="minimalContactLine" class="mt-3 text-[11px] text-gray-700">{{ minimalContactLine }}</div>
                </div>

                <div v-if="preview.photo_url" class="h-20 w-20 shrink-0 overflow-hidden rounded bg-gray-100 ring-1 ring-gray-200">
                    <img
                        :src="photoSrc"
                        alt="Foto"
                        class="h-full w-full object-cover"
                        crossorigin="anonymous"
                        referrerpolicy="no-referrer"
                    />
                </div>
            </div>

            <div class="mt-5 border-b-2 border-gray-900" />

            <div class="mt-6 space-y-7">
                <div v-if="(preview.summary || '').trim()">
                    <div class="text-xs font-extrabold uppercase tracking-wide">Resumo</div>
                    <div class="mt-2 text-[11px] leading-4 text-gray-800">{{ preview.summary }}</div>
                </div>

                <div v-if="experiencesToShow.length">
                    <div class="text-xs font-extrabold uppercase tracking-wide">Experiência</div>
                    <div class="mt-3 space-y-5">
                        <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-[11px]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-extrabold">{{ exp.role }}</div>
                                    <div class="font-semibold text-gray-700">{{ exp.company }}</div>
                                </div>
                                <div class="shrink-0 text-[10px] font-semibold text-gray-700">
                                    {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' - ' : '' }}{{ exp.current ? 'atual' : (exp.end || '') }}
                                </div>
                            </div>

                            <div v-if="exp.bullets && exp.bullets.length" class="mt-2 space-y-1">
                                <template v-if="isPdf">
                                    <div
                                        v-for="(b, bIdx) in exp.bullets"
                                        :key="bIdx"
                                        class="grid grid-cols-[10px_1fr] gap-x-2"
                                    >
                                        <div class="pt-[1px] text-[12px] leading-4 text-gray-800">•</div>
                                        <div class="min-w-0 break-words text-[11px] leading-4 text-gray-800">{{ b }}</div>
                                    </div>
                                </template>

                                <ul v-else class="list-disc space-y-1 pl-5 text-gray-800">
                                    <li v-for="(b, bIdx) in exp.bullets.slice(0, 6)" :key="bIdx">{{ b }}</li>
                                </ul>
                            </div>
                            <div v-else-if="exp.description" class="mt-2 text-gray-800">{{ exp.description }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-6" v-if="skillsToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Habilidades</div>
                        <div class="mt-3 space-y-2 text-[11px]">
                            <div v-for="(skill, idx) in skillsToShow" :key="`${skill?.name || 'skill'}-${idx}`" class="flex items-center justify-between gap-3">
                                <div class="min-w-0 font-semibold text-gray-900">
                                    <span class="break-words">{{ skill.name }}</span>
                                </div>
                                <div class="shrink-0 text-[10px] font-semibold text-gray-700">{{ Number.isFinite(skill.percent) ? skill.percent : 70 }}%</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-6" v-if="educationToShow.length">
                        <div class="text-xs font-extrabold uppercase tracking-wide">Formação</div>
                        <div class="mt-3 space-y-3 text-[11px]">
                            <div v-for="(edu, idx) in educationToShow" :key="idx">
                                <div class="font-semibold">{{ edu.course }}</div>
                                <div class="text-gray-800">
                                    <span class="font-semibold">{{ edu.institution || '' }}</span>
                                    <span v-if="edu.institution && (edu.start || edu.end)">, </span>
                                    <span class="text-gray-700">{{ edu.start || '' }}{{ edu.start && edu.end ? ' - ' : '' }}{{ edu.end || '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Minimal template -->
        <div v-else class="p-10" :class="isPdf ? 'min-h-[297mm]' : 'h-full'">
            <div class="font-archivo-black text-3xl font-black text-gray-900">{{ preview.name }}</div>
            <div v-if="(preview.headline || '').trim()" class="mt-1 text-sm font-semibold text-indigo-600">{{ preview.headline }}</div>
            <div v-if="minimalContactLine" class="mt-3 text-xs text-gray-500">{{ minimalContactLine }}</div>

            <div v-if="(preview.summary || '').trim()" class="mt-8">
                <div class="text-sm font-semibold text-gray-900">Resumo</div>
                <div class="mt-2 text-xs leading-relaxed text-gray-600">{{ preview.summary }}</div>
            </div>

            <div v-if="experiencesToShow.length" class="mt-8">
                <div class="text-sm font-semibold text-gray-900">Experiências</div>
                <div class="mt-3 space-y-3">
                    <div v-for="(exp, idx) in experiencesToShow" :key="idx" class="text-xs">
                        <div v-if="exp.role" class="font-semibold text-gray-900">{{ exp.role }}</div>
                        <div v-if="exp.company || exp.start || exp.end || exp.current" class="text-gray-600">
                            <span v-if="exp.company">{{ exp.company }}</span>
                            <span v-if="exp.company && (exp.start || exp.end || exp.current)" class="text-gray-400">·</span>
                            <span v-if="exp.start || exp.end || exp.current">
                                {{ exp.start || '' }}{{ exp.start && (exp.current || exp.end) ? ' — ' : '' }}{{ exp.current ? 'ATUAL' : (exp.end || '') }}
                            </span>
                        </div>
                        <div v-if="exp.description" class="mt-1 text-gray-600">{{ exp.description }}</div>
                    </div>
                </div>
            </div>

            <div v-if="skillsToShow.length" class="mt-8">
                <div class="text-sm font-semibold text-gray-900">Habilidades</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-for="(skill, idx) in (isPdf ? skillsToShow : skillsToShow.slice(0, 12))"
                        :key="`${skill?.name || 'skill'}-${idx}`"
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700"
                    >
                        {{ skill.name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

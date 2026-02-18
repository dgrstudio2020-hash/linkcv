<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ResumeCanvas from '@/Components/ResumeCanvas.vue';
import { Link, useForm } from '@inertiajs/vue3';
import html2pdf from 'html2pdf.js';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    resume: {
        type: Object,
        required: true,
    },
});

const normalizeSkills = (skills) => {
    if (!Array.isArray(skills)) return [];

    return skills
        .map((skill) => {
            if (typeof skill === 'string') {
                const name = skill.trim();
                if (!name) return null;
                return { name, percent: 70 };
            }

            if (skill && typeof skill === 'object') {
                const name = (skill.name || '').toString().trim();
                if (!name) return null;
                const percentRaw = skill.percent;
                const percent = Number.isFinite(percentRaw) ? percentRaw : Number(percentRaw);
                return { name, percent: Number.isFinite(percent) ? Math.max(0, Math.min(100, Math.trunc(percent))) : 70 };
            }

            return null;
        })
        .filter(Boolean);
};

const form = useForm({
    name: props.resume.name ?? '',
    headline: props.resume.headline ?? '',
    email: props.resume.email ?? '',
    phone: props.resume.phone ?? '',
    location: props.resume.location ?? '',
    linkedin: props.resume.linkedin ?? '',
    summary: props.resume.summary ?? '',
    experiences: Array.isArray(props.resume.experiences) ? props.resume.experiences : [],
    skills: normalizeSkills(props.resume.skills),
    photo_url: props.resume.photo_url ?? '',
    education: Array.isArray(props.resume.education) ? props.resume.education : [],
    languages: Array.isArray(props.resume.languages) ? props.resume.languages : [],
    additional_info: Array.isArray(props.resume.additional_info) ? props.resume.additional_info : [],
    template: props.resume.template ?? 'modern',
});

const newSkill = ref('');
const newAdditionalLabel = ref('');
const newAdditionalValue = ref('');
const isExportingPdf = ref(false);
const isUploadingPhoto = ref(false);
const photoUploadError = ref('');
const photoInputRef = ref(null);
const isPhotoModalOpen = ref(false);
const photoDraftUrl = ref('');
const cropperImageRef = ref(null);
let cropperInstance = null;
const previewMode = ref('html');
const isGeneratingPdfPreview = ref(false);
const pdfPreviewUrl = ref('');
let lastPdfObjectUrl = '';
const editorSections = ref({
    personal: false,
    experience: false,
    skills: false,
    languages: false,
    education: false,
    additional: false,
});
let autoSaveTimeout = null;

const revokePdfPreviewUrl = () => {
    const url = lastPdfObjectUrl || pdfPreviewUrl.value;
    if (url) {
        try {
            URL.revokeObjectURL(url);
        } catch {
            // ignora
        }
    }
    lastPdfObjectUrl = '';
    pdfPreviewUrl.value = '';
};

const pdfSourceElement = () => document.getElementById('resume-pdf');

const sameOriginUrl = (inputUrl) => {
    try {
        const u = new URL(inputUrl, window.location.origin);
        return `${u.pathname}${u.search}`;
    } catch {
        return inputUrl;
    }
};

const resolvePhotoSrc = (value) => {
    const raw = (value ?? '').toString().trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) return raw;
    return `/storage/resume-photos/${encodeURIComponent(props.resume.id)}/${encodeURIComponent(raw)}`;
};

const photoPreviewSrc = computed(() => resolvePhotoSrc(preview.value?.photo_url));

const openPhotoPicker = () => {
    photoUploadError.value = '';
    photoInputRef.value?.click();
};

const closePhotoModal = () => {
    isPhotoModalOpen.value = false;

    if (cropperInstance) {
        try {
            cropperInstance.destroy();
        } catch {
            // ignore
        }
        cropperInstance = null;
    }

    if (photoDraftUrl.value) {
        try {
            URL.revokeObjectURL(photoDraftUrl.value);
        } catch {
            // ignore
        }
    }
    photoDraftUrl.value = '';
};

const onPickedPhoto = async (e) => {
    const file = e?.target?.files?.[0];
    if (!file) return;

    // Allow picking the same file again later.
    if (e?.target) e.target.value = '';

    photoUploadError.value = '';

    const url = URL.createObjectURL(file);
    photoDraftUrl.value = url;
    isPhotoModalOpen.value = true;

    await nextTick();

    if (!cropperImageRef.value) return;

    if (cropperInstance) {
        try {
            cropperInstance.destroy();
        } catch {
            // ignore
        }
    }

    cropperInstance = new Cropper(cropperImageRef.value, {
        aspectRatio: 1,
        viewMode: 1,
        autoCropArea: 1,
        dragMode: 'move',
        background: false,
        guides: false,
        center: true,
        highlight: false,
        toggleDragModeOnDblclick: false,
    });
};

const saveCroppedPhoto = async () => {
    if (!cropperInstance) return;
    photoUploadError.value = '';

    const canvas = cropperInstance.getCroppedCanvas({
        width: 512,
        height: 512,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    await new Promise((resolve) => {
        canvas.toBlob(async (blob) => {
            if (!blob) {
                photoUploadError.value = 'Não foi possível processar a foto.';
                resolve();
                return;
            }

            const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
            await uploadPhotoFile(file);
            closePhotoModal();
            resolve();
        }, 'image/jpeg', 0.92);
    });
};

onBeforeUnmount(() => {
    closePhotoModal();
});

const uploadPhotoFile = async (file) => {
    if (!file) return;
    photoUploadError.value = '';
    isUploadingPhoto.value = true;

    try {
        const fd = new FormData();
        fd.append('photo', file);

        const { data: payload } = await window.axios.post(
            sameOriginUrl(route('resumes.photo.update', props.resume.id)),
            fd,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (payload?.photo_url) {
            form.photo_url = payload.photo_url;
        }

        if (previewMode.value === 'pdf') {
            await refreshPdfPreview();
        }
    } catch {
        photoUploadError.value = 'Não foi possível enviar a foto.';
    } finally {
        isUploadingPhoto.value = false;
    }
};

const removePhoto = async () => {
    photoUploadError.value = '';
    isUploadingPhoto.value = true;

    try {
        const fd = new FormData();
        fd.append('_method', 'DELETE');

        await window.axios.post(
            sameOriginUrl(route('resumes.photo.destroy', props.resume.id)),
            fd,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        form.photo_url = '';

        if (previewMode.value === 'pdf') {
            await refreshPdfPreview();
        }
    } catch {
        photoUploadError.value = 'Não foi possível remover a foto.';
    } finally {
        isUploadingPhoto.value = false;
    }
};

const pdfOptions = computed(() => {
    const safeName = (preview.value?.name || 'curriculo')
        .toString()
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-');

    return {
        margin: 0,
        filename: `${safeName || 'curriculo'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        pagebreak: { mode: ['css', 'legacy'] },
        html2canvas: {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
    };
});

const waitForImages = async (rootEl) => {
    if (!rootEl) return;
    const images = Array.from(rootEl.querySelectorAll('img'));
    if (!images.length) return;

    await Promise.all(
        images.map((img) => {
            if (img.complete && img.naturalWidth > 0) return Promise.resolve();
            return new Promise((resolve) => {
                const done = () => resolve();
                img.addEventListener('load', done, { once: true });
                img.addEventListener('error', done, { once: true });
            });
        }),
    );
};

const generatePdfBlob = async () => {
    const element = pdfSourceElement();
    if (!element) return null;

    await nextTick();

    if (document.fonts?.ready) {
        try {
            await document.fonts.ready;
        } catch {
            // ignora
        }
    }

    try {
        await waitForImages(element);
    } catch {
        // ignora
    }

    const worker = html2pdf().set(pdfOptions.value).from(element).toPdf();
    const pdf = await worker.get('pdf');
    return pdf.output('blob');
};

const refreshPdfPreview = async () => {
    if (isGeneratingPdfPreview.value) return;
    isGeneratingPdfPreview.value = true;

    try {
        const blob = await generatePdfBlob();
        if (!blob) return;

        revokePdfPreviewUrl();
        const url = URL.createObjectURL(blob);
        lastPdfObjectUrl = url;
        pdfPreviewUrl.value = url;
    } finally {
        isGeneratingPdfPreview.value = false;
    }
};

const toggleEditorSection = (key) => {
    editorSections.value[key] = !editorSections.value[key];
};

const addExperience = () => {
    form.experiences.push({
        company: '',
        role: '',
        start: '',
        end: '',
        current: false,
        description: '',
    });
};

const removeExperience = (index) => {
    form.experiences.splice(index, 1);
};

const addSkill = () => {
    const value = (newSkill.value || '').trim();
    if (!value) return;
    if ((form.skills || []).some((s) => (s?.name || '').toLowerCase() === value.toLowerCase())) {
        newSkill.value = '';
        return;
    }

    form.skills.push({ name: value, percent: 70 });
    newSkill.value = '';
};

const removeSkill = (index) => {
    form.skills.splice(index, 1);
};

const addEducation = () => {
    form.education.push({
        course: '',
        institution: '',
        start: '',
        end: '',
        status: '',
    });
};

const removeEducation = (index) => {
    form.education.splice(index, 1);
};

const addLanguage = () => {
    form.languages.push({
        name: '',
        level: '',
        percent: 70,
    });
};

const removeLanguage = (index) => {
    form.languages.splice(index, 1);
};

const addAdditionalInfo = () => {
    const label = (newAdditionalLabel.value || '').trim();
    const value = (newAdditionalValue.value || '').trim();
    if (!label && !value) return;

    form.additional_info.push({ label, value });
    newAdditionalLabel.value = '';
    newAdditionalValue.value = '';
};

const removeAdditionalInfo = (index) => {
    form.additional_info.splice(index, 1);
};

const exportPdf = async () => {
    const element = pdfSourceElement();
    if (!element) {
        window.print();
        return;
    }

    isExportingPdf.value = true;
    await nextTick();

    if (document.fonts?.ready) {
        try {
            await document.fonts.ready;
        } catch {
            // ignora
        }
    }

    try {
        await html2pdf().set(pdfOptions.value).from(element).save();
    } finally {
        isExportingPdf.value = false;
    }
};

const preview = computed(() => {
    const experiences = (form.experiences || []).filter((e) => {
        return (e.company || '').trim() || (e.role || '').trim() || (e.description || '').trim();
    });

    const normalizedExperiences = experiences.map((e) => {
        const raw = (e.description || '').toString();
        const bullets = raw
            .split(/\r?\n/)
            .map((line) => line.trim())
            .filter(Boolean);

        return {
            ...e,
            bullets,
        };
    });

    const education = (form.education || []).filter((e) => {
        return (e.course || '').trim() || (e.institution || '').trim();
    });

    const languages = (form.languages || []).filter((l) => {
        return (l.name || '').trim() || (l.level || '').trim();
    });

    const additionalInfo = (form.additional_info || []).filter((i) => {
        return (i.label || '').trim() || (i.value || '').trim();
    });

    const skills = normalizeSkills(form.skills).filter((s) => (s.name || '').trim());

    return {
        name: form.name,
        headline: form.headline,
        email: form.email,
        phone: form.phone,
        location: form.location,
        linkedin: form.linkedin,
        summary: form.summary,
        skills,
        experiences: normalizedExperiences,
        education,
        languages,
        additional_info: additionalInfo,
        photo_url: form.photo_url,
        template: form.template,
    };
});

const minimalContactLine = computed(() => {
    const p = preview.value;
    return [p.email, p.phone, p.location, p.linkedin].map((v) => (v || '').toString().trim()).filter(Boolean).join(' · ');
});

watch(
    () => previewMode.value,
    async (mode) => {
        if (mode === 'pdf') {
            await refreshPdfPreview();
        } else {
            revokePdfPreviewUrl();
        }
    },
);

watch(
    () => ({
        ...form.data(),
    }),
    () => {
        if (autoSaveTimeout) clearTimeout(autoSaveTimeout);

        autoSaveTimeout = setTimeout(() => {
            form.put(route('resumes.update', props.resume.id), {
                preserveScroll: true,
            });
        }, 800);
    },
    { deep: true },
);
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="print:hidden">
                <div class="flex items-center justify-between gap-6">
                    <div class="flex items-center gap-3">
                        <Link :href="route('resumes.index')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Meus Currículos
                        </Link>

                        <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-2 py-1 text-sm">
                            <span class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 font-semibold text-indigo-700 shadow-sm">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                    <path
                                        fill-rule="evenodd"
                                        d="M2 5a2 2 0 012-2h6a1 1 0 110 2H4v11h11v-6a1 1 0 112 0v6a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Editar
                            </span>
                            <span class="px-2 py-1 font-medium text-gray-500">Visualizar</span>
                        </div>
                    </div>

                    <PrimaryButton type="button" @click="exportPdf">Exportar PDF</PrimaryButton>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Left: Editor -->
                <div class="space-y-6 print:hidden">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('personal')"
                                    :aria-label="editorSections.personal ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.personal ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Informações Pessoais</h2>
                            </div>

                            <div v-show="editorSections.personal" class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'modern' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'modern'"
                                >
                                    Modern
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'elegant' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'elegant'"
                                >
                                    Elegant
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'corporate' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'corporate'"
                                >
                                    Corporate
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'sidebar' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'sidebar'"
                                >
                                    Sidebar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'classic' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'classic'"
                                >
                                    Classic
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'creative' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'creative'"
                                >
                                    Creative
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'mono' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'mono'"
                                >
                                    Mono
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                    :class="form.template === 'minimal' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                    @click="form.template = 'minimal'"
                                >
                                    Minimal
                                </button>
                            </div>
                        </div>

                        <div v-show="editorSections.personal" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Nome" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="headline" value="Cargo" />
                                <TextInput id="headline" v-model="form.headline" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.headline" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Telefone" />
                                <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div>
                                <InputLabel for="location" value="Local" />
                                <TextInput id="location" v-model="form.location" type="text" class="mt-1 block w-full" placeholder="Belo Horizonte - MG" />
                                <InputError class="mt-2" :message="form.errors.location" />
                            </div>

                            <div>
                                <InputLabel for="linkedin" value="LinkedIn" />
                                <TextInput id="linkedin" v-model="form.linkedin" type="text" class="mt-1 block w-full" placeholder="linkedin.com/in/seuuser" />
                                <InputError class="mt-2" :message="form.errors.linkedin" />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel value="Foto" />

                                    <div class="mt-2 flex items-center gap-4">
                                        <div class="relative">
                                            <div class="h-16 w-16 overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200">
                                                <img v-if="photoPreviewSrc" :src="photoPreviewSrc" alt="Foto" class="h-full w-full object-cover" />
                                            </div>
                                            <button
                                                v-if="photoPreviewSrc"
                                                type="button"
                                                class="absolute -right-1 -top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white shadow"
                                                :disabled="isUploadingPhoto"
                                                @click="removePhoto"
                                                aria-label="Remover foto"
                                            >
                                                ×
                                            </button>
                                        </div>

                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                                    :disabled="isUploadingPhoto"
                                                    @click="openPhotoPicker"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                                        <circle cx="12" cy="13" r="4" />
                                                    </svg>
                                                    {{ photoPreviewSrc ? 'Alterar Foto' : 'Escolher Foto' }}
                                                </button>

                                                <input
                                                    ref="photoInputRef"
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="onPickedPhoto"
                                                />
                                            </div>
                                            <div class="text-xs text-gray-500">JPG, PNG ou GIF. Máximo de 2MB.</div>
                                            <div v-if="photoUploadError" class="text-sm text-red-600">{{ photoUploadError }}</div>
                                            <InputError class="mt-1" :message="form.errors.photo_url" />
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div v-show="editorSections.personal" class="mt-4">
                            <div class="flex items-center justify-between">
                                <InputLabel for="summary" value="Resumo Profissional" />
                                <SecondaryButton type="button" class="text-xs">Melhorar com IA</SecondaryButton>
                            </div>
                            <textarea
                                id="summary"
                                v-model="form.summary"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.summary" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('experience')"
                                    :aria-label="editorSections.experience ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.experience ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Experiência Profissional</h2>
                            </div>
                            <button
                                v-show="editorSections.experience"
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                @click="addExperience"
                                aria-label="Adicionar"
                            >
                                +
                            </button>
                        </div>

                        <div v-show="editorSections.experience" class="mt-4 space-y-4">
                            <div v-if="form.experiences.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                Adicione sua primeira experiência.
                            </div>

                            <div v-for="(exp, idx) in form.experiences" :key="idx" class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900">Experiência {{ idx + 1 }}</div>
                                    <button
                                        type="button"
                                        class="rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                                        @click="removeExperience(idx)"
                                        aria-label="Remover"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v9a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v9a1 1 0 11-2 0V6z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`company-${idx}`" value="Empresa" />
                                        <TextInput :id="`company-${idx}`" v-model="exp.company" type="text" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`role-${idx}`" value="Cargo" />
                                        <TextInput :id="`role-${idx}`" v-model="exp.role" type="text" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`start-${idx}`" value="Início" />
                                        <TextInput :id="`start-${idx}`" v-model="exp.start" type="text" class="mt-1 block w-full" placeholder="janeiro de 2020" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`end-${idx}`" value="Fim" />
                                        <TextInput :id="`end-${idx}`" v-model="exp.end" type="text" class="mt-1 block w-full" :disabled="exp.current" placeholder="2024" />
                                        <div class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                            <input :id="`current-${idx}`" v-model="exp.current" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                            <label :for="`current-${idx}`">Atual</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <InputLabel :for="`desc-${idx}`" value="Descrição" />
                                    <textarea
                                        :id="`desc-${idx}`"
                                        v-model="exp.description"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('skills')"
                                    :aria-label="editorSections.skills ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.skills ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Habilidades</h2>
                            </div>
                            <SecondaryButton v-show="editorSections.skills" type="button" class="text-xs">Sugerir com IA</SecondaryButton>
                        </div>

                        <div v-show="editorSections.skills" class="mt-4 space-y-3">
                            <div v-if="form.skills.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                Adicione suas habilidades e defina o nível.
                            </div>

                            <div v-for="(skill, idx) in form.skills" :key="`${skill?.name || 'skill'}-${idx}`" class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <InputLabel :for="`skill-name-${idx}`" value="Habilidade" />
                                        <TextInput :id="`skill-name-${idx}`" v-model="skill.name" type="text" class="mt-1 block w-full" placeholder="Ex.: Laravel" />
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-6 rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                                        @click="removeSkill(idx)"
                                        aria-label="Remover"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v9a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v9a1 1 0 11-2 0V6z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <div class="flex items-center justify-between">
                                        <InputLabel :for="`skill-percent-${idx}`" value="Nível" />
                                        <div class="text-xs font-semibold text-gray-700">{{ Number.isFinite(skill.percent) ? skill.percent : 70 }}%</div>
                                    </div>
                                    <input
                                        :id="`skill-percent-${idx}`"
                                        v-model.number="skill.percent"
                                        type="range"
                                        min="0"
                                        max="100"
                                        class="mt-2 w-full"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-show="editorSections.skills" class="mt-4 flex items-center gap-2">
                            <TextInput
                                v-model="newSkill"
                                type="text"
                                class="block w-full"
                                placeholder="Adicionar habilidade (ex.: React)"
                                @keyup.enter.prevent="addSkill"
                            />
                            <PrimaryButton type="button" @click="addSkill">+</PrimaryButton>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('languages')"
                                    :aria-label="editorSections.languages ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.languages ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Idiomas</h2>
                            </div>
                            <button
                                v-show="editorSections.languages"
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                @click="addLanguage"
                                aria-label="Adicionar"
                            >
                                +
                            </button>
                        </div>

                        <div v-show="editorSections.languages" class="mt-4 space-y-4">
                            <div v-if="form.languages.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                Adicione idiomas e nível.
                            </div>

                            <div v-for="(lang, idx) in form.languages" :key="idx" class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900">Idioma {{ idx + 1 }}</div>
                                    <button
                                        type="button"
                                        class="rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                                        @click="removeLanguage(idx)"
                                        aria-label="Remover"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v9a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v9a1 1 0 11-2 0V6z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`lang-name-${idx}`" value="Idioma" />
                                        <TextInput :id="`lang-name-${idx}`" v-model="lang.name" type="text" class="mt-1 block w-full" placeholder="Português" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`lang-level-${idx}`" value="Nível" />
                                        <TextInput :id="`lang-level-${idx}`" v-model="lang.level" type="text" class="mt-1 block w-full" placeholder="Básico / Intermediário / Avançado" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <InputLabel :for="`lang-percent-${idx}`" value="Progresso (%)" />
                                        <input
                                            :id="`lang-percent-${idx}`"
                                            v-model.number="lang.percent"
                                            type="range"
                                            min="0"
                                            max="100"
                                            class="mt-2 w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('education')"
                                    :aria-label="editorSections.education ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.education ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Formação Acadêmica</h2>
                            </div>
                            <button
                                v-show="editorSections.education"
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                @click="addEducation"
                                aria-label="Adicionar"
                            >
                                +
                            </button>
                        </div>

                        <div v-show="editorSections.education" class="mt-4 space-y-4">
                            <div v-if="form.education.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                Adicione sua formação.
                            </div>

                            <div v-for="(edu, idx) in form.education" :key="idx" class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900">Formação {{ idx + 1 }}</div>
                                    <button
                                        type="button"
                                        class="rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                                        @click="removeEducation(idx)"
                                        aria-label="Remover"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4a1 1 0 012 0v9a1 1 0 11-2 0V6zm4 0a1 1 0 112 0v9a1 1 0 11-2 0V6z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :for="`edu-course-${idx}`" value="Curso" />
                                        <TextInput :id="`edu-course-${idx}`" v-model="edu.course" type="text" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edu-inst-${idx}`" value="Instituição" />
                                        <TextInput :id="`edu-inst-${idx}`" v-model="edu.institution" type="text" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edu-start-${idx}`" value="Início" />
                                        <TextInput :id="`edu-start-${idx}`" v-model="edu.start" type="text" class="mt-1 block w-full" placeholder="02/2023" />
                                    </div>
                                    <div>
                                        <InputLabel :for="`edu-end-${idx}`" value="Fim" />
                                        <TextInput :id="`edu-end-${idx}`" v-model="edu.end" type="text" class="mt-1 block w-full" placeholder="08/2025" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <InputLabel :for="`edu-status-${idx}`" value="Status" />
                                        <TextInput :id="`edu-status-${idx}`" v-model="edu.status" type="text" class="mt-1 block w-full" placeholder="Concluído / Cursando" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-indigo-700 hover:bg-gray-50"
                                    @click="toggleEditorSection('additional')"
                                    :aria-label="editorSections.additional ? 'Recolher seção' : 'Expandir seção'"
                                >
                                    {{ editorSections.additional ? '–' : '+' }}
                                </button>
                                <h2 class="text-base font-semibold text-indigo-700">Informações Adicionais</h2>
                            </div>
                        </div>

                        <div v-show="editorSections.additional" class="mt-4 space-y-3">
                            <div v-for="(item, idx) in form.additional_info" :key="idx" class="flex items-center gap-2">
                                <TextInput v-model="item.label" type="text" class="block w-1/3" placeholder="Label (ex.: LinkedIn)" />
                                <TextInput v-model="item.value" type="text" class="block flex-1" placeholder="Valor" />
                                <button
                                    type="button"
                                    class="rounded-md p-2 text-gray-400 hover:bg-gray-50 hover:text-red-500"
                                    @click="removeAdditionalInfo(idx)"
                                    aria-label="Remover"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="flex items-center gap-2">
                                <TextInput v-model="newAdditionalLabel" type="text" class="block w-1/3" placeholder="Label" />
                                <TextInput v-model="newAdditionalValue" type="text" class="block flex-1" placeholder="Valor" @keyup.enter.prevent="addAdditionalInfo" />
                                <PrimaryButton type="button" @click="addAdditionalInfo">+</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Preview -->
                <div>
                    <div class="flex items-center justify-between print:hidden">
                        <div class="text-xs text-gray-500">Prévia em tempo real (A4)</div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                :class="previewMode === 'html' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                @click="previewMode = 'html'"
                            >
                                HTML
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold"
                                :class="previewMode === 'pdf' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700'"
                                @click="previewMode = 'pdf'"
                            >
                                PDF
                            </button>

                            <SecondaryButton
                                v-if="previewMode === 'pdf'"
                                type="button"
                                class="text-xs"
                                :disabled="isGeneratingPdfPreview"
                                @click="refreshPdfPreview"
                            >
                                {{ isGeneratingPdfPreview ? 'Gerando…' : 'Atualizar prévia' }}
                            </SecondaryButton>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-center lg:justify-end">
                        <div class="w-full max-w-[720px]">
                            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                                <!-- Screen preview (single page) -->
                                <div
                                    v-if="previewMode === 'html'"
                                    id="resume-a4"
                                    class="aspect-[210/297] w-full overflow-hidden bg-white"
                                    :class="isExportingPdf ? '' : 'rounded-lg border border-gray-200'"
                                >
                                    <ResumeCanvas :resume-id="props.resume.id" :preview="preview" :minimal-contact-line="minimalContactLine" mode="screen" class="h-full w-full" />
                                </div>

                                <!-- PDF preview (exact export rendering) -->
                                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <div v-if="!pdfPreviewUrl" class="flex h-[640px] items-center justify-center text-sm text-gray-500">
                                        {{ isGeneratingPdfPreview ? 'Gerando PDF…' : 'Clique em “Atualizar prévia”.' }}
                                    </div>
                                    <iframe
                                        v-else
                                        :src="pdfPreviewUrl"
                                        class="h-[640px] w-full"
                                        title="Prévia do currículo (PDF)"
                                    />
                                </div>
                            </div>
                            <div class="mt-2 text-center text-xs text-gray-400 print:hidden">
                                Dica: no diálogo de impressão, escolha “Salvar como PDF”.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offscreen PDF source (full A4 width, multi-page) -->
        <div class="fixed left-[-99999px] top-0 z-[-1] bg-white">
            <div id="resume-pdf">
                <ResumeCanvas :resume-id="props.resume.id" :preview="preview" :minimal-contact-line="minimalContactLine" mode="pdf" />
            </div>
        </div>

        <!-- Crop modal -->
        <div v-if="isPhotoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closePhotoModal"></div>
            <div class="relative mx-4 w-full max-w-2xl rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div class="text-base font-semibold text-gray-900">Ajustar Foto</div>
                    <button type="button" class="text-gray-500 hover:text-gray-800" @click="closePhotoModal" aria-label="Fechar">×</button>
                </div>

                <div class="px-6 py-5">
                    <div class="flex justify-center">
                        <div class="w-full max-w-[520px] overflow-hidden rounded-xl bg-black">
                            <img
                                v-if="photoDraftUrl"
                                ref="cropperImageRef"
                                :src="photoDraftUrl"
                                alt="Foto para recorte"
                                class="block max-h-[520px] w-full object-contain"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" @click="closePhotoModal">
                        Cancelar
                    </button>
                    <PrimaryButton type="button" :disabled="isUploadingPhoto" @click="saveCroppedPhoto">
                        {{ isUploadingPhoto ? 'Salvando…' : 'Salvar Foto' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

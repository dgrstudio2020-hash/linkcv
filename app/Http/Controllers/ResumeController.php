<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    private function deleteStoredPhotoIfAny(Resume $resume): void
    {
        $value = (string) ($resume->photo_url ?? '');
        if ($value === '') {
            return;
        }

        // Backward compatible: if the DB still contains a public URL, delete that path.
        if (str_starts_with($value, '/storage/')) {
            $path = ltrim(substr($value, strlen('/storage/')), '/');
            if ($path !== '') {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable) {
                    // ignore
                }
            }
            return;
        }

        // New behavior: store only filename under a fixed folder.
        $fileName = basename($value);
        if ($fileName === '') {
            return;
        }

        $path = "resume-photos/{$resume->id}/{$fileName}";
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable) {
            // ignore
        }
    }

    private function normalizeSkills(mixed $skills): array
    {
        if (!is_array($skills)) {
            return [];
        }

        $normalized = [];

        foreach ($skills as $skill) {
            if (is_string($skill)) {
                $name = trim($skill);
                if ($name === '') {
                    continue;
                }

                $normalized[] = [
                    'name' => $name,
                    'percent' => 70,
                ];
                continue;
            }

            if (is_array($skill)) {
                $name = trim((string) ($skill['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $percent = $skill['percent'] ?? 70;
                $percent = is_numeric($percent) ? (int) $percent : 70;
                $percent = max(0, min(100, $percent));

                $normalized[] = [
                    'name' => $name,
                    'percent' => $percent,
                ];
            }
        }

        return array_slice($normalized, 0, 50);
    }

    public function index(Request $request): Response
    {
        $resumes = Resume::query()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get(['id', 'name', 'headline', 'updated_at']);

        return Inertia::render('Resumes/Index', [
            'resumes' => $resumes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resume = Resume::create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'headline' => null,
            'email' => $request->user()->email,
            'phone' => null,
            'location' => null,
            'linkedin' => null,
            'summary' => null,
            'experiences' => [],
            'skills' => [],
            'template' => 'modern',
            'photo_url' => null,
            'education' => [],
            'languages' => [],
            'additional_info' => [],
        ]);

        return Redirect::route('resumes.edit', $resume);
    }

    public function edit(Request $request, Resume $resume): Response
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        return Inertia::render('Resumes/Edit', [
            'resume' => $resume->only([
                'id',
                'name',
                'headline',
                'email',
                'phone',
                'location',
                'linkedin',
                'summary',
                'experiences',
                'skills',
                'template',
                'photo_url',
                'education',
                'languages',
                'additional_info',
            ]),
        ]);
    }

    public function update(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $data = $request->all();
        $data['skills'] = $this->normalizeSkills($data['skills'] ?? []);

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'template' => ['required', 'in:modern,minimal,elegant,corporate,sidebar,classic,creative,mono'],
            // Stores only the filename for a fixed storage path.
            'photo_url' => ['nullable', 'string', 'max:255'],
            'experiences' => ['nullable', 'array', 'max:50'],
            'experiences.*.company' => ['nullable', 'string', 'max:255'],
            'experiences.*.role' => ['nullable', 'string', 'max:255'],
            'experiences.*.start' => ['nullable', 'string', 'max:50'],
            'experiences.*.end' => ['nullable', 'string', 'max:50'],
            'experiences.*.current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array', 'max:50'],
            'skills.*.name' => ['nullable', 'string', 'max:80'],
            'skills.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'education' => ['nullable', 'array', 'max:20'],
            'education.*.course' => ['nullable', 'string', 'max:255'],
            'education.*.institution' => ['nullable', 'string', 'max:255'],
            'education.*.start' => ['nullable', 'string', 'max:50'],
            'education.*.end' => ['nullable', 'string', 'max:50'],
            'education.*.status' => ['nullable', 'string', 'max:100'],
            'languages' => ['nullable', 'array', 'max:20'],
            'languages.*.name' => ['nullable', 'string', 'max:100'],
            'languages.*.level' => ['nullable', 'string', 'max:100'],
            'languages.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'additional_info' => ['nullable', 'array', 'max:30'],
            'additional_info.*.label' => ['nullable', 'string', 'max:100'],
            'additional_info.*.value' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $resume->update([
            ...$validated,
            'experiences' => $validated['experiences'] ?? [],
            'skills' => $validated['skills'] ?? [],
            'education' => $validated['education'] ?? [],
            'languages' => $validated['languages'] ?? [],
            'additional_info' => $validated['additional_info'] ?? [],
        ]);

        return Redirect::back();
    }

    public function updatePhoto(Request $request, Resume $resume): RedirectResponse|JsonResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $validated = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'max:2048'],
        ])->validate();

        $this->deleteStoredPhotoIfAny($resume);

        $file = $validated['photo'];

        $ext = strtolower((string) $file->extension());
        if ($ext === '') {
            $ext = 'jpg';
        }

        $fileName = 'photo_' . now()->format('YmdHis') . '.' . $ext;
        $path = $file->storeAs("resume-photos/{$resume->id}", $fileName, 'public');

        $resume->forceFill([
            'photo_url' => $fileName,
        ])->save();

        if ($request->wantsJson()) {
            return response()->json([
                'photo_url' => $fileName,
            ]);
        }

        return Redirect::back();
    }

    public function destroyPhoto(Request $request, Resume $resume): RedirectResponse|JsonResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $this->deleteStoredPhotoIfAny($resume);
        $resume->forceFill(['photo_url' => null])->save();

        if ($request->wantsJson()) {
            return response()->json([
                'photo_url' => null,
            ]);
        }

        return Redirect::back();
    }

    public function destroy(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $this->deleteStoredPhotoIfAny($resume);
        $resume->delete();

        return Redirect::route('resumes.index');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Setting;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('manage-settings');

        return view('admin.settings.edit', [
            'groups' => config('cms-settings', []),
            'values' => app(Settings::class)->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $groups = config('cms-settings', []);

        // Build the rules from the registry so a new field needs no code here.
        $rules = [];
        $keys = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                $keys[] = $field['key'];
                $rules["settings.{$field['key']}"] = match ($field['type']) {
                    'email' => ['nullable', 'email', 'max:160'],
                    'url' => ['nullable', 'url', 'max:500'],
                    'tel' => ['nullable', 'string', 'max:40'],
                    'textarea' => ['nullable', 'string', 'max:1000'],
                    default => ['nullable', 'string', 'max:255'],
                };
            }
        }

        $validated = $request->validate($rules, [], $this->attributeNames($groups));

        /** @var class-string<Setting> $settingModel */
        $settingModel = cms_model('setting');

        foreach ($keys as $key) {
            $settingModel::updateOrCreate(
                ['key' => $key],
                ['value' => $validated['settings'][$key] ?? null],
            );
        }

        // Setting::booted() flushes on each save, but do it once more in case
        // a model was swapped out through config/cms-models.php.
        Settings::flush();

        return back()->with('status', 'Οι ρυθμίσεις αποθηκεύτηκαν.');
    }

    /** @return array<string, string> */
    private function attributeNames(array $groups): array
    {
        $names = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                $names["settings.{$field['key']}"] = mb_strtolower($field['label']);
            }
        }

        return $names;
    }
}

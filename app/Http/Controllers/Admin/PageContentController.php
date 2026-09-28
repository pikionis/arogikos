<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Page;
use App\Support\Content;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Κείμενα σελίδων" — the per-page copy editor.
 *
 * The whole form is generated from config/cms-content.php, so making a new
 * string editable is a registry entry and nothing else.
 *
 * A row is only written when the submitted value DIFFERS from the registry
 * default; submitting the default deletes the row again. That keeps the table
 * small, makes "restore the original" free, and means new default copy shipped
 * in a deploy reaches pages nobody has customised.
 */
class PageContentController extends Controller
{
    /** Pages that also own a `pages` row (prose body + SEO fields). */
    private const PAGE_SLUGS = [
        'about' => 'sxetika-me-emas',
        'services' => 'ypiresies',
    ];

    public function index(): View
    {
        $this->authorize('manage-pages');

        return view('admin.pages.index', [
            'pages' => config('cms-content', []),
            'customised' => ContentBlock::query()
                ->selectRaw('page_key, count(*) as total')
                ->groupBy('page_key')
                ->pluck('total', 'page_key')
                ->all(),
        ]);
    }

    public function edit(string $pageKey): View
    {
        $this->authorize('manage-pages');

        $page = config("cms-content.{$pageKey}");

        if (! $page) {
            throw new NotFoundHttpException;
        }

        return view('admin.pages.edit', [
            'pageKey' => $pageKey,
            'page' => $page,
            'values' => $this->currentValues($pageKey, $page),
            'pageModel' => $this->pageModel($pageKey),
        ]);
    }

    public function update(Request $request, string $pageKey): RedirectResponse
    {
        $this->authorize('manage-pages');

        $page = config("cms-content.{$pageKey}");

        if (! $page) {
            throw new NotFoundHttpException;
        }

        $submitted = (array) $request->input('fields', []);

        foreach ($this->fieldsOf($page) as $field) {
            $this->saveField($pageKey, $field, $submitted, $request);
        }

        $this->savePageModel($pageKey, $request);

        Content::flush();

        return back()->with('status', 'Τα κείμενα αποθηκεύτηκαν.');
    }

    /** Drop every override for a page, restoring the shipped copy. */
    public function reset(string $pageKey): RedirectResponse
    {
        $this->authorize('manage-pages');

        ContentBlock::where('page_key', $pageKey)->each(fn (ContentBlock $block) => $block->delete());

        Content::flush();

        return back()->with('status', 'Τα κείμενα επανήλθαν στις αρχικές τιμές.');
    }

    /* -------------------------------------------------------------- saving */

    private function saveField(string $pageKey, array $field, array $submitted, Request $request): void
    {
        $key = $field['key'];
        $type = $field['type'];

        if ($type === 'image') {
            $this->saveImage($pageKey, $field, $request);

            return;
        }

        // A field absent from the payload was never on the form — leave it
        // alone. Coercing it to '' would differ from the default and so write
        // a junk override for every field a partial submit omitted.
        if (! array_key_exists($key, $submitted)) {
            return;
        }

        $raw = $submitted[$key];

        [$value, $isDefault] = match ($type) {
            'lines' => $this->normaliseLines($raw, $field),
            'repeater' => $this->normaliseRepeater($raw, $field),
            'collection' => $this->normaliseCollection($raw, $field),
            'html' => $this->normaliseHtml($raw, $field),
            default => $this->normaliseScalar($raw, $field),
        };

        $this->persist($pageKey, $key, $value, $isDefault, $request);
    }

    /** @return array{0: ?string, 1: bool} */
    private function normaliseScalar(mixed $raw, array $field): array
    {
        $value = trim((string) $raw);

        return [$value, $value === (string) ($field['default'] ?? '')];
    }

    /** @return array{0: ?string, 1: bool} */
    private function normaliseHtml(mixed $raw, array $field): array
    {
        $value = (string) HtmlSanitizer::clean((string) $raw);

        return [$value, $value === (string) ($field['default'] ?? '')];
    }

    /** @return array{0: ?string, 1: bool} */
    private function normaliseLines(mixed $raw, array $field): array
    {
        $lines = Content::splitLines((string) $raw);

        return [
            json_encode($lines, JSON_UNESCAPED_UNICODE),
            $lines === array_values((array) ($field['default'] ?? [])),
        ];
    }

    /**
     * A list somebody builds: any length, in the order they left it.
     *
     * Unlike a repeater there is no topping up and no clamping — an empty
     * list means they removed everything, which is theirs to do. The order is
     * whatever order the rows arrived in, which is the order the page uses.
     *
     * @return array{0: ?string, 1: bool}
     */
    private function normaliseCollection(mixed $raw, array $field): array
    {
        $items = [];

        foreach (array_values((array) $raw) as $row) {
            $item = $this->normaliseItem((array) $row, $field['item'] ?? []);

            // A row with no name is a leftover blank, not a service.
            if (trim((string) ($item['name'] ?? '')) === '') {
                continue;
            }

            $items[] = $item;
        }

        return [
            json_encode($items, JSON_UNESCAPED_UNICODE),
            $items === array_values((array) ($field['default'] ?? [])),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subFields
     * @return array<string, mixed>
     */
    private function normaliseItem(array $row, array $subFields): array
    {
        $item = [];

        foreach ($subFields as $sub) {
            $value = $row[$sub['key']] ?? null;

            $item[$sub['key']] = match ($sub['type']) {
                'lines' => Content::splitLines((string) $value),
                'html' => (string) HtmlSanitizer::clean((string) $value),
                'toggle' => (bool) $value,
                default => trim((string) $value),
            };
        }

        return $item;
    }

    /** @return array{0: ?string, 1: bool} */
    private function normaliseRepeater(mixed $raw, array $field): array
    {
        $subFields = $field['item'] ?? [];
        $items = [];

        foreach (array_values((array) $raw) as $row) {
            $item = [];

            foreach ($subFields as $sub) {
                $submittedValue = $row[$sub['key']] ?? null;

                $item[$sub['key']] = match ($sub['type']) {
                    'lines' => Content::splitLines((string) $submittedValue),
                    'html' => (string) HtmlSanitizer::clean((string) $submittedValue),
                    default => trim((string) $submittedValue),
                };
            }

            $items[] = $item;
        }

        // The layouts are built per item, so never let the count drift from
        // what the registry (and the CSS) expects.
        $default = array_values((array) ($field['default'] ?? []));

        $max = $field['max'] ?? null;
        if ($max !== null) {
            $items = array_slice($items, 0, $max);
        }

        // Too few (a truncated or malformed submit) would blank out a whole
        // band of the page — top up from the shipped copy instead.
        $min = $field['min'] ?? 0;
        while (count($items) < $min && isset($default[count($items)])) {
            $items[] = $default[count($items)];
        }

        return [
            json_encode($items, JSON_UNESCAPED_UNICODE),
            $items === $default,
        ];
    }

    private function persist(string $pageKey, string $key, ?string $value, bool $isDefault, Request $request): void
    {
        $existing = ContentBlock::query()
            ->where('page_key', $pageKey)
            ->where('field_key', $key)
            ->where('locale', config('app.locale', 'el'))
            ->first();

        if ($isDefault) {
            // Back to the shipped copy — drop the override entirely so a future
            // change to the default still reaches this page.
            $existing?->delete();

            return;
        }

        if ($existing) {
            $existing->update(['value' => $value, 'updated_by' => $request->user()?->getKey()]);

            return;
        }

        ContentBlock::create([
            'page_key' => $pageKey,
            'field_key' => $key,
            'locale' => config('app.locale', 'el'),
            'value' => $value,
            'updated_by' => $request->user()?->getKey(),
        ]);
    }

    private function saveImage(string $pageKey, array $field, Request $request): void
    {
        $inputName = 'image_'.Str::slug($field['key'], '_');

        $removing = $request->boolean('remove_'.$inputName);
        $uploading = $request->hasFile($inputName);

        if (! $removing && ! $uploading) {
            return;
        }

        $request->validate([
            $inputName => [
                'nullable', 'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:max_width=5000,max_height=5000',
            ],
        ]);

        $block = ContentBlock::firstOrCreate(
            [
                'page_key' => $pageKey,
                'field_key' => $field['key'],
                'locale' => config('app.locale', 'el'),
            ],
            ['updated_by' => $request->user()?->getKey()],
        );

        if ($removing) {
            $block->clearMediaCollection('image');
        }

        if ($uploading) {
            $block->addMediaFromRequest($inputName)
                ->sanitizingFileName(fn (string $name) => Str::slug(
                    pathinfo($name, PATHINFO_FILENAME), '-', 'el'
                ).'.'.pathinfo($name, PATHINFO_EXTENSION))
                ->toMediaCollection('image');
        }
    }

    /**
     * The About and Services pages keep their prose body in the `pages` table,
     * which the public templates already read.
     */
    private function savePageModel(string $pageKey, Request $request): void
    {
        $model = $this->pageModel($pageKey);

        if (! $model) {
            return;
        }

        $validated = $request->validate([
            'page.body' => ['nullable', 'string', 'max:500000'],
        ]);

        // SEO title and description live in the content registry now, one
        // field per page, so there is a single place to edit them rather than
        // two that can disagree.
        $model->body = HtmlSanitizer::clean($validated['page']['body'] ?? null);
        $model->save();
    }

    /* ------------------------------------------------------------ reading */

    private function pageModel(string $pageKey): ?Page
    {
        $slug = self::PAGE_SLUGS[$pageKey] ?? null;

        if (! $slug) {
            return null;
        }

        /** @var class-string<Page> $pageModel */
        $pageModel = cms_model('page');

        return $pageModel::firstOrCreate(
            ['slug' => $slug],
            ['title' => config("cms-content.{$pageKey}.label", $slug), 'is_published' => true],
        );
    }

    /** Current value per field key — the override if any, else the default. */
    private function currentValues(string $pageKey, array $page): array
    {
        $content = app(Content::class);
        $values = [];

        foreach ($this->fieldsOf($page) as $field) {
            $dotted = "{$pageKey}.{$field['key']}";

            $values[$field['key']] = match ($field['type']) {
                'lines' => implode("\n", $content->lines($dotted)),
                'repeater', 'collection' => $content->items($dotted),
                'image' => $content->image($dotted, 'thumb'),
                default => $content->get($dotted),
            };
        }

        return $values;
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldsOf(array $page): array
    {
        $fields = [];

        foreach ($page['groups'] ?? [] as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}

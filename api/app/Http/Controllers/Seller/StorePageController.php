<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\StorePage;
use App\Services\BusinessAccessService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StorePageController extends Controller
{
    public function __construct(
        private BusinessAccessService $access,
        private AuditService $audit
    ) {}

    private function page(Request $request, int $id): StorePage
    {
        return StorePage::query()
            ->where(
                'store_id',
                $this->access->currentStore($request)->id
            )
            ->findOrFail($id);
    }

    public function index(Request $request)
    {
        $store = $this->access->currentStore($request);

        return response()->json(
            StorePage::where('store_id', $store->id)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->paginate(30)
        );
    }

    private function validatePage(
        Request $request,
        int $storeId,
        ?int $pageId = null
    ): array {
        $slugRule = Rule::unique('store_pages', 'slug')
            ->where('store_id', $storeId);

        if ($pageId !== null) {
            $slugRule->ignore($pageId);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugRule,
            ],
            'type' => [
                'required',
                Rule::in(StorePage::TYPES),
            ],
            'content' => ['nullable', 'string', 'max:100000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => [
                'nullable', 'string', 'max:500'
            ],
            'show_in_header' => ['required', 'boolean'],
            'show_in_footer' => ['required', 'boolean'],
            'sort_order' => [
                'required', 'integer', 'min:0', 'max:1000'
            ],
        ]);
    }

    public function store(Request $request)
    {
        $store = $this->access->currentStore($request);

        $data = $this->validatePage($request, $store->id);

        $page = DB::transaction(function () use (
            $request, $store, $data
        ) {
            $page = StorePage::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'type' => $data['type'],
                'is_published' => false,
                'draft_settings' => $data,
            ]);

            $this->audit->record(
                'website.page.created',
                $store->id,
                $request->user()->id,
                'StorePage',
                $page->id
            );

            return $page;
        });

        return response()->json([
            'page' => $page,
            'draft' => $page->draft_settings,
        ], 201);
    }

    public function show(Request $request, int $page)
    {
        $record = $this->page($request, $page);

        return response()->json([
            'page' => $record,
            'draft' => $record->draft_settings,
        ]);
    }

    public function update(Request $request, int $page)
    {
        $record = $this->page($request, $page);

        $data = $this->validatePage(
            $request,
            $record->store_id,
            $record->id
        );

        $record->update([
            'draft_settings' => $data,
        ]);

        return response()->json([
            'message' => 'Page draft saved.',
            'page' => $record,
            'draft' => $record->draft_settings,
        ]);
    }

    public function publish(Request $request, int $page)
    {
        $store = $this->access->currentStore($request);

        return DB::transaction(function () use (
            $request, $page, $store
        ) {
            $record = StorePage::where(
                'store_id', $store->id
            )->lockForUpdate()->findOrFail($page);

            $draft = $record->draft_settings;

            abort_unless($draft, 422, 'No draft to publish.');

            abort_if(
                StorePage::where('store_id', $store->id)
                    ->where('slug', $draft['slug'])
                    ->where('id', '!=', $record->id)
                    ->exists(),
                422,
                'This page slug is already in use.'
            );

            $record->fill($draft);

            $record->is_published = true;
            $record->published_at = now();
            $record->draft_settings = null;
            $record->save();

            $this->audit->record(
                'website.page.published',
                $store->id,
                $request->user()->id,
                'StorePage',
                $record->id
            );

            return response()->json([
                'message' => 'Page published successfully.',
                'page' => $record,
            ]);
        });
    }

    public function unpublish(Request $request, int $page)
    {
        $record = $this->page($request, $page);

        $record->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return response()->json([
            'message' => 'Page unpublished.',
        ]);
    }

    public function destroy(Request $request, int $page)
    {
        $record = $this->page($request, $page);

        DB::transaction(function () use ($request, $record) {
            $this->audit->record(
                'website.page.deleted',
                $record->store_id,
                $request->user()->id,
                'StorePage',
                $record->id
            );

            $record->delete();
        });

        return response()->noContent();
    }
}

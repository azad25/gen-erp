<?php

namespace App\Http\Controllers\Api\V1\CMS;

use App\Domain\CMS\Models\Menu;
use App\Domain\CMS\Models\MenuItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CMS - Menus",
 *     description="CMS menu management"
 * )
 * REST API v1 controller for CMS Menu CRUD operations.
 */
class MenuController extends BaseApiController
{
    /**
     * List all menus for the current site.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        
        $menus = Menu::whereHas('site', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['items' => function ($query) {
            $query->orderBy('sort_order');
        }])
        ->get();

        return $this->success($menus);
    }

    /**
     * Create a new menu.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:cms_sites,id',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:50',
        ]);

        // Verify site belongs to company
        $site = \App\Domain\CMS\Models\Site::findOrFail($validated['site_id']);
        $this->authorize('view', $site);

        $menu = Menu::create($validated);
        $menu->load('items');

        return $this->success($menu, 'Menu created successfully', 201);
    }

    /**
     * Get a specific menu with items.
     */
    public function show(Menu $menu): JsonResponse
    {
        $this->authorize('view', $menu->site);
        
        $menu->load(['items' => function ($query) {
            $query->with('children')->whereNull('parent_id')->orderBy('sort_order');
        }]);

        return $this->success($menu);
    }

    /**
     * Update a menu.
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', $menu->site);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:50',
        ]);

        $menu->update($validated);
        $menu->load('items');

        return $this->success($menu, 'Menu updated successfully');
    }

    /**
     * Delete a menu.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $this->authorize('delete', $menu->site);

        $menu->delete();

        return $this->success(null, 'Menu deleted successfully');
    }

    /**
     * Add an item to a menu.
     */
    public function addItem(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', $menu->site);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'page_id' => 'nullable|exists:cms_pages,id',
            'parent_id' => 'nullable|exists:cms_menu_items,id',
            'target' => 'nullable|string|in:_self,_blank',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['menu_id'] = $menu->id;
        $validated['target'] = $validated['target'] ?? '_self';

        $item = MenuItem::create($validated);

        return $this->success($item, 'Menu item added successfully', 201);
    }

    /**
     * Update a menu item.
     */
    public function updateItem(Request $request, MenuItem $item): JsonResponse
    {
        $this->authorize('update', $item->menu->site);

        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'url' => 'nullable|string|max:500',
            'page_id' => 'nullable|exists:cms_pages,id',
            'parent_id' => 'nullable|exists:cms_menu_items,id',
            'target' => 'sometimes|string|in:_self,_blank',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $item->update($validated);

        return $this->success($item, 'Menu item updated successfully');
    }

    /**
     * Delete a menu item.
     */
    public function deleteItem(MenuItem $item): JsonResponse
    {
        $this->authorize('delete', $item->menu->site);

        // Delete children first
        $item->children()->delete();
        $item->delete();

        return $this->success(null, 'Menu item deleted successfully');
    }

    /**
     * Reorder menu items.
     */
    public function reorderItems(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', $menu->site);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:cms_menu_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:cms_menu_items,id',
        ]);

        foreach ($validated['items'] as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->where('menu_id', $menu->id)
                ->update([
                    'sort_order' => $itemData['sort_order'],
                    'parent_id' => $itemData['parent_id'] ?? null,
                ]);
        }

        $menu->load(['items' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return $this->success($menu, 'Menu items reordered successfully');
    }
}

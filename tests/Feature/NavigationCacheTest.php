<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NavigationCacheTest extends TestCase
{
    public function test_navigation_menu_uses_cacheable_scalar_data(): void
    {
        Cache::forget('nav2_menu_items_v2');

        $this->get(route('login', [], false))->assertOk();

        $menu = Cache::get('nav2_menu_items_v2');
        $this->assertIsArray($menu);

        foreach ($menu as $items) {
            $this->assertIsArray($items);

            foreach ($items as $item) {
                $this->assertIsArray($item);
                $this->assertArrayHasKey('id', $item);
                $this->assertArrayHasKey('menu_naslov', $item);
            }
        }

        $this->get(route('login', [], false))->assertOk();
    }
}

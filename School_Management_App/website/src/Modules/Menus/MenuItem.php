<?php

namespace Website\Modules\Menus;

use Website\Support\SiteModel;

class MenuItem extends SiteModel
{
    protected $table = 'site_menu_items';
    protected $casts = ['is_visible' => 'boolean', 'new_tab' => 'boolean'];

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->where('is_visible', true)->orderBy('sort');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }
}

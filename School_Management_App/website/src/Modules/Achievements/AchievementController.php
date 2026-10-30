<?php

namespace Website\Modules\Achievements;

use Illuminate\Routing\Controller;

class AchievementController extends Controller
{
    public function index()
    {
        $all = Achievement::published()->orderBy('sort')->orderByDesc('year')->get();

        return view('website::site.achievements.index', [
            'toppers' => $all->where('category', 'board'),
            'others' => $all->where('category', '!=', 'board'),
        ]);
    }
}

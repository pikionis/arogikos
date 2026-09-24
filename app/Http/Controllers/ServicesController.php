<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Page;
use Illuminate\Contracts\View\View;

class ServicesController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = cms_model('page');

        $page = $pageModel::published()->where('slug', 'ypiresies')->first();

        return view('pages.services', compact('page'));
    }
}

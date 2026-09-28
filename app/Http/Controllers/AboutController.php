<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Author;
use App\Models\Cms\Page;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = cms_model('page');
        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $page = $pageModel::published()->where('slug', 'sxetika-me-emas')->first();

        $authors = $authorModel::active()->ordered()->with('media')->get();

        return view('pages.about', compact('page', 'authors'));
    }
}

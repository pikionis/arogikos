<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Author;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $authors = $authorModel::active()->ordered()->with('media')->get();

        return view('pages.about', compact('authors'));
    }
}

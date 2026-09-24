<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Album;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Album> $albumModel */
        $albumModel = cms_model('album');

        $albums = $albumModel::query()
            ->with(['photos.media'])
            ->orderBy('sort_order')
            ->get();

        return view('pages.gallery', compact('albums'));
    }
}

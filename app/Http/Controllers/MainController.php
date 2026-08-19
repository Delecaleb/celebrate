<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Public marketing site.
 *
 * Every page renders the same shell (layouts.marketing) with one of the
 * partials in resources/views/marketing/partials. When the client-side router
 * (resources/js/modules/pageRouter.js) asks for a page it sends `X-Partial: 1`
 * and gets back just that partial's HTML as JSON, so navigation never reloads.
 */
class MainController extends Controller
{
    /**
     * Nav bar items, in order. `route` doubles as the active-state key.
     */
    private const NAV = [
        ['route' => 'features',     'label' => 'Features'],
        ['route' => 'how-it-works', 'label' => 'How it works'],
        ['route' => 'pricing',      'label' => 'Pricing'],
        ['route' => 'stories',      'label' => 'Stories'],
    ];

    public function home(Request $request)
    {
        return $this->respond($request, 'home', [
            'nav'         => 'home',
            'title'       => 'CelebrateMi — Celebrate out loud',
            'description' => 'Create a celebration page in 60 seconds. Collect wishes, gifts and money from everyone who loves you — and keep the memories forever.',
        ]);
    }

    public function features(Request $request)
    {
        return $this->respond($request, 'features', [
            'nav'         => 'features',
            'title'       => 'Features — CelebrateMi',
            'description' => 'Wishes, cash gifts, group gifting, photobooks, custom themes and one shareable link. Everything a celebration page needs.',
        ]);
    }

    public function howItWorks(Request $request)
    {
        return $this->respond($request, 'how-it-works', [
            'nav'         => 'how-it-works',
            'title'       => 'How it works — CelebrateMi',
            'description' => 'Create a page, share the link, collect wishes and gifts, then withdraw and keep the memories. Three steps, sixty seconds.',
        ]);
    }

    public function pricing(Request $request)
    {
        return $this->respond($request, 'pricing', [
            'nav'         => 'pricing',
            'title'       => 'Pricing — CelebrateMi',
            'description' => 'Free to create a celebration page. You only pay a small fee on the cash gifts you receive.',
        ]);
    }

    public function stories(Request $request)
    {
        return $this->respond($request, 'stories', [
            'nav'         => 'stories',
            'title'       => 'Stories — CelebrateMi',
            'description' => 'Real celebrations built on CelebrateMi — birthdays, weddings, graduations and the people behind them.',
        ]);
    }

    /**
     * Render the full shell, or just the partial when the router asks for it.
     */
    private function respond(Request $request, string $page, array $data)
    {
        $data['page']     = $page;
        $data['navItems'] = self::NAV;

        if ($request->header('X-Partial')) {
            return response()->json([
                'title'       => $data['title'],
                'description' => $data['description'],
                'nav'         => $data['nav'],
                'html'        => view("marketing.partials.{$page}", $data)->render(),
            ]);
        }

        return view('layouts.marketing', $data);
    }
}

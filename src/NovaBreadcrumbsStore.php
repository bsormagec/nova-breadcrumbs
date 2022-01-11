<?php

namespace ChrisWare\NovaBreadcrumbs;

use Illuminate\Support\Str;
use Stripe\Collection;

class NovaBreadcrumbsStore
{
    protected $resource;
    protected $model;
    protected $crumbs;

    public function __construct()
    {
        $this->crumbs = collect();
    }

    public function getCrumbs()
    {
        $last = $this->crumbs->pop();
        $last['path'] = null;
        $this->crumbs->push($last);

        return $this->crumbs;
    }

    public function appendToCrumbs($title, $url = null)
    {
        $this->crumbs->push([
            'title' => __($title),
            'path' => Str::start($url, '/'),
        ]);
    }

    public function __destruct()
    {
        $this->crumbs = collect();
    }

}
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

    public function setCrumbs($crumbs)
    {
        $this->crumbs = $crumbs;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function __destruct()
    {
        $this->crumbs = collect();
    }

}
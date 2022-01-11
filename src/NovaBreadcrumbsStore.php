<?php

namespace ChrisWare\NovaBreadcrumbs;

use Illuminate\Support\Str;
use Stripe\Collection;

class NovaBreadcrumbsStore
{
    protected $resource;
    protected $model;
    protected $crumbs;
    protected $uri = [];

    public function __construct()
    {
        $this->crumbs = collect();
    }

    public function getCrumbs()
    {
        $last = $this->crumbs->pop();
        $last['path'] = null;
        $this->crumbs->push($last);

        return $this->crumbs->unique(function ($crumb) {
            return $crumb['path'].'_'.$crumb['title'];
        });
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

    public function setCrumbsByUri($uri, $crumbs)
    {
        $this->uri[$uri] = $crumbs;
    }

    public function getCrumbsByUri($uri)
    {
        $last = $this->uri[$uri]->pop();
        $last['path'] = null;
        $this->uri[$uri]->push($last);
        return $this->uri[$uri]->unique(function ($crumb) {
            return $crumb['path'].'_'.$crumb['title'];
        });
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

}
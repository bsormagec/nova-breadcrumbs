<?php

namespace ChrisWare\NovaBreadcrumbs\Http\Controllers;

use ChrisWare\NovaBreadcrumbs\NovaBreadcrumbsStore;
use ChrisWare\NovaBreadcrumbs\Traits\Breadcrumbs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\InteractsWithLenses;
use Laravel\Nova\Http\Requests\InteractsWithResources;
use Laravel\Nova\Nova;
use function React\Promise\all;

class NovaBreadcrumbsController extends Controller
{
    protected $resource;
    protected $model;
    protected $store;
    protected $crumbs;

    use InteractsWithResources, InteractsWithLenses;

    public function __construct()
    {
        $this->crumbs = collect();
        $this->store = app()->make(NovaBreadcrumbsStore::class);
    }

    public function __invoke(Request $request)
    {
        $view = Str::of($request->get('view'))->replace('-', ' ')->after('custom-');

        $pathParts = Str::of($request->input('location.href'))
            ->after(Str::of($request->input('location.origin'))->append(Nova::path())->finish('/'))
            ->before('?')
            ->explode('/')
            ->filter();

        $this->appendToCrumbs(__('Home'), '/');

        if ($request->has('query') && ($query = collect($request->get('query'))->filter()) && $query->count() > 1) {
            $cloneParts = clone $pathParts;

            if ($query->has('viaResource')) {
                $cloneParts->put(1, $query->get('viaResource'));
            }
            if ($query->has('viaResourceId')) {
                $cloneParts->put(2, $query->get('viaResourceId'));
                $resource = $this->resourceFromKey($query->get('viaResource'));
                $this->store->setResource($resource);
            }

            if (empty($this->store->getResource()) == false) {
                $model = $this->findResourceOrFail($query->get('viaResourceId'));
                $this->store->setModel($model);
                $this->appendToCrumbs($this->store->getResource()::breadcrumbResourceLabel(),
                    $cloneParts->slice(0, 2)->implode('/'));
                $this->appendToCrumbs($this->store->getModel()->breadcrumbResourceTitle(),
                    $cloneParts->slice(0, 3)->implode('/'));
            }
        }

        if ($pathParts->has(1) == false) {
            return null;
        }

        $resource = $this->resourceFromKey($pathParts->get(1));
        $this->store->setResource($resource);

        if ($this->store->getResource()) {
            $this->appendToCrumbs($this->store->getResource()::breadcrumbResourceLabel(),
                $pathParts->slice(0, 2)->implode('/'));
        }

        if ($view == 'create') {
            $this->appendToCrumbs(Str::title($view), $pathParts->slice(0, 3)->implode('/'));
        } elseif ($view == 'dashboard.custom' && count(Nova::availableDashboards($request)) >= 1) {
            $this->appendToCrumbs(Str::title($request->get('name')), $pathParts->slice(0, 3)->implode('/'));
        } elseif ($view == 'lens') {
            $lens = Str::title(str_replace('-', ' ', $pathParts->get(3)));
            $this->appendToCrumbs($lens, $pathParts->slice(0, 4)->implode('/'));
        } elseif ($pathParts->has(2)) {
            $resource = Nova::resourceForKey($pathParts->get(1));
            $this->store->setResource($resource);
            $model = $this->findResourceOrFail($pathParts->get(2));
            $this->store->setModel($model);
            if (method_exists($this->store->getModel(), 'breadcrumbResourceTitle')) {
                $this->appendToCrumbs($this->store->getModel()->breadcrumbResourceTitle(),
                    $pathParts->slice(0, 3)->implode('/'));
            }
        }

        if ($pathParts->has(3) && $view != 'lens') {
            $this->appendToCrumbs(Str::title($view),
                $pathParts->slice(0, 4)->implode('/'));
        }
        $this->store->setCrumbs($this->crumbs);
        $this->store->setCrumbsByUri($request->input('location.pathname'), $this->crumbs);

        return $this->store->getCrumbsByUri($request->input('location.pathname'));
    }

    /**
     * Get the class name of the resource being requested.
     *
     * @return mixed
     */
    public function resource()
    {
        return tap($this->store->getResource(), function ($resource) {
            abort_if(is_null($resource), 404);
        });
    }

    public function appendToCrumbs($title, $url = null)
    {
        $this->crumbs->push([
            'title' => __($title),
            'path' => Str::start($url, '/'),
        ]);
    }

    protected function resourceFromKey($key)
    {
        $resource = Nova::resourceForKey($key);
        if ($resource && in_array(Breadcrumbs::class, class_uses_recursive($resource)) == false) {
            return null;
        }

        if ($resource && method_exists($resource, 'breadcrumbs') == false) {
            return null;
        }

        if ($resource && $resource::breadcrumbs() == false) {
            return null;
        }

        return $resource;
    }
}
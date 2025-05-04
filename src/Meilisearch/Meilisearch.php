<?php

namespace JoBins\Meilisearch\Meilisearch;

use JoBins\Meilisearch\Meilisearch\Builder as JoBinsBuilder;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\MeilisearchEngine as BaseMeilisearchEngine;
use Meilisearch\Contracts\MultiSearchFederation;
use Meilisearch\Contracts\SearchQuery;

class Meilisearch extends BaseMeilisearchEngine
{
    protected function filters(Builder $builder): string
    {
        $filter = parent::filters($builder);

        // Here we get the JoBinsBuilder instead of Laravel Scout's Builder
        if (!$builder instanceof JoBinsBuilder) {
            return $filter;
        }

        if (empty($builder->filter)) {
            return $filter;
        }

        return collect([$filter, $builder->filter->toBase()])
            ->filter()
            ->implode($builder->filter->getConnector());
    }

    public function prepareMultiSearchPaginateWithOnce(JoBinsBuilder $builder, array $searchParams = [])
    {
        return once(fn () => $this->prepareMultiSearchPaginate($builder, $searchParams));
    }

    public function prepareMultiSearchPaginate(JoBinsBuilder $builder, array $searchParams = [])
    {
        $searchParams = array_merge($builder->options, $searchParams);

        if (array_key_exists('attributesToRetrieve', $searchParams)) {
            $searchParams['attributesToRetrieve'] = array_unique(array_merge(
                [$builder->model->getScoutKeyName()], // @phpstan-ignore-line
                $searchParams['attributesToRetrieve'],
            ));
        }

        $terms   = $builder->queries->toArray();
        $perPage = $searchParams['hitsPerPage'];
        $page    = $searchParams['page'];

        // If search is empty set an empty search array
        $terms = count($terms) > 0 ? $terms : [''];

        $searches = [];
        foreach ($terms as $term) {
            $searches[] = (new SearchQuery())
                ->setQuery($term)
                ->setAttributesToRetrieve(array_values($searchParams['attributesToRetrieve']) ?? [$builder->model->getScoutKeyName()]) // @phpstan-ignore-line
                ->setIndexUid($builder->model->searchableAs()) // @phpstan-ignore-line
                ->setFilter([$this->filters($builder)]);
        }

        $federation = new MultiSearchFederation();
        $federation->setLimit($perPage);
        $federation->setOffset($perPage * ($page - 1));

        return $this->meilisearch->multiSearch($searches, $federation);
    }

    public function getMultiSearchTotalCount(JoBinsBuilder $builder)
    {
        return $this->performMultiSearchTotalCount($builder, array_filter([
            'filter'      => $this->filters($builder),
            'hitsPerPage' => 1,
            'page'        => 1,
            'sort'        => $this->buildSortFromOrderByClauses($builder),
        ]));
    }

    public function performMultiSearchTotalCount(JoBinsBuilder $builder, array $searchParams = [])
    {
        $response = $this->prepareMultiSearchPaginateWithOnce($builder, $searchParams);

        return $response['estimatedTotalHits'];
    }

    public function performMultiSearchPaginate(JoBinsBuilder $builder, array $searchParams = []): array
    {
        $response = $this->prepareMultiSearchPaginateWithOnce($builder, $searchParams);

        $results['processingTimeMs'] = $response['processingTimeMs'];
        $results['hits']             = $response['hits'];
        $results['totalHits']        = $response['estimatedTotalHits'];

        return $results;
    }

    public function multiSearchPaginate(JoBinsBuilder $builder, ?int $perPage = null, ?int $page = null): array
    {
        return $this->performMultiSearchPaginate($builder, array_filter([
            'filter'      => $this->filters($builder),
            'hitsPerPage' => $perPage,
            'page'        => $page,
            'sort'        => $this->buildSortFromOrderByClauses($builder),
        ]));
    }
}

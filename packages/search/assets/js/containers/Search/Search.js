// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Icon, Loader} from 'sulu-admin-bundle/components';
import Pagination from 'sulu-admin-bundle/components/Pagination';
import {Router} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import searchStore from './stores/searchStore';
import searchResourceStore from './stores/searchResourceStore';
import SearchField from './SearchField';
import SearchResult from './SearchResult';
import searchStyles from './search.scss';
import jexl from 'jexl';
import searchResultStyles from './searchResult.scss';
import type {SearchResource} from './types';

type Props = {|
    router: Router,
|};

@observer
class Search extends React.Component<Props> {
    @observable query: ?string = undefined;
    @observable searchResources: ?{[resourceKey: string]: SearchResource} = undefined;
    @observable resourceKey: ?string = undefined;

    @action componentDidMount() {
        this.query = searchStore.query;
        this.resourceKey = searchStore.resourceKey;
        searchResourceStore.loadSearchResources()
            .then(action((searchResources: {[resourceKey: string]: SearchResource}) => {
                this.searchResources = {...searchResources};
            }));
    }

    @action handleSearchResourceChange = (resourceKey: ?string) => {
        this.resourceKey = resourceKey;
    };

    @action handleQueryChange = (query: ?string) => {
        this.query = query;
    };

    handleLimitChange = (limit: number) => {
        searchStore.setLimit(limit);
    };

    handlePageChange = (page: number) => {
        searchStore.setPage(page);
    };

    handleSearch = () => {
        searchStore.search(this.query, this.resourceKey);
    };

    handleResultClick = (index: number) => {
        if (!this.searchResources) {
            throw new Error(
                'The indexes must be available to route to a search result! This should not happen and is likely a bug.'
            );
        }

        const result = searchStore.result[index];
        const {
            route: {
                name: routeName,
                resultToRoute,
                resultToRouteName,
            },
        } = this.searchResources[result.resourceKey];

        let route = routeName;
        Object.keys(resultToRouteName).forEach((resultPath) => {
            route = route.replace(`{${resultToRouteName[resultPath]}}`, `${jexl.evalSync(resultPath, result)}`);
        });

        const {router} = this.props;
        router.navigate(
            route,
            Object.keys(resultToRoute).reduce((parameters, resultPath) => {
                parameters[resultToRoute[resultPath]] = jexl.evalSync(resultPath, result);
                return parameters;
            }, {})
        );
    };

    render() {
        const {searchResources} = this;

        if (!searchResources) {
            return <Loader />;
        }

        const results = searchStore.result.map((result, index) => (
            <SearchResult
                description={result.description || ''}
                icon={searchResources[result.resourceKey] ? searchResources[result.resourceKey].icon : null}
                image={result.imageUrl || null}
                index={index}
                key={result.id}
                locale={result.locale || null}
                onClick={this.handleResultClick}
                resource={
                    searchResources[result.resourceKey]
                        ? translate(searchResources[result.resourceKey].name)
                        : ''
                }
                title={result.title}
            />
        ));

        return (
            <div className={searchStyles.search}>
                <SearchField
                    onQueryChange={this.handleQueryChange}
                    onSearch={this.handleSearch}
                    onSearchResourceChange={this.handleSearchResourceChange}
                    query={this.query || undefined}
                    resourceKey={this.resourceKey}
                    searchResources={searchResources}
                />
                {searchStore.loading &&
                    <Loader />
                }
                {!searchStore.loading && searchStore.query && searchStore.result.length === 0 &&
                    <div className={searchStyles.nothingHint}>
                        <div className={searchStyles.nothingIcon}>
                            <Icon name="su-battery-low" />
                        </div>
                        {translate('sulu_search.nothing_found')}
                    </div>
                }
                {!searchStore.loading && searchStore.result.length > 0 &&
                    <div className={searchResultStyles.searchResultsOuterContainer}>
                        <Pagination
                            currentLimit={searchStore.limit}
                            currentPage={searchStore.page}
                            loading={searchStore.loading}
                            onLimitChange={this.handleLimitChange}
                            onPageChange={this.handlePageChange}
                            totalPages={searchStore.pages}
                        >
                            {results}
                        </Pagination>
                    </div>
                }
            </div>
        );
    }
}

export default Search;

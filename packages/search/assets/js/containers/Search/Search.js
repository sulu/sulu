// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Icon, Loader} from 'sulu-admin-bundle/components';
import Pagination from 'sulu-admin-bundle/components/Pagination';
import {Router} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import searchStore from './stores/searchStore';
import indexStore from './stores/indexStore';
import SearchField from './SearchField';
import SearchResult from './SearchResult';
import searchStyles from './search.scss';
import searchResultStyles from './searchResult.scss';
import type {Index} from './types';

type Props = {|
    router: Router,
|};

@observer
class Search extends React.Component<Props> {
    @observable query: ?string = undefined;
    @observable indexes: ?{[indexName: string]: Index} = undefined;
    @observable indexName: ?string = undefined;

    @action componentDidMount() {
        this.query = searchStore.query;
        this.indexName = searchStore.indexName;
        indexStore.loadIndexes().then(action((indexes: {[indexName: string]: Index}) => {
            this.indexes = {...indexes};
        }));
    }

    @action handleIndexChange = (indexName: ?string) => {
        this.indexName = indexName;
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
        searchStore.search(this.query, this.indexName);
    };

    handleResultClick = (index: number) => {
        if (!this.indexes) {
            throw new Error(
                'The indexes must be available to route to a search result! This should not happen and is likely a bug.'
            );
        }

        const result = searchStore.result[index];
        const {
            route: {
                name: routeName,
                resultToRoute,
            },
        } = this.indexes[result.resourceKey];
        const resultIdArray = result.id.split('::');
        const resultId = resultIdArray[1] || null;
        const resultLocale = resultIdArray[2] || null;
        const resultParams = {
            id: resultId,
            locale: resultLocale,
        };

        const {router} = this.props;
        router.navigate(
            routeName,
            Object.keys(resultToRoute).reduce((parameters, resultPath) => {
                parameters[resultPath] = resultParams[resultPath];
                return parameters;
            }, {})
        );
    };

    render() {
        const {indexes} = this;

        if (!indexes) {
            return <Loader />;
        }

        const results = searchStore.result.map((result, index) => (
            <SearchResult
                description={result.description || ''}
                icon={indexes[result.resourceKey] ? indexes[result.resourceKey].icon : null}
                image={result.imageUrl || null}
                index={index}
                key={result.id}
                locale={result.locale || null}
                onClick={this.handleResultClick}
                resource={
                    indexes[result.resourceKey]
                        ? translate(indexes[result.resourceKey].name)
                        : ''
                }
                title={result.title}
            />
        ));

        return (
            <div className={searchStyles.search}>
                <SearchField
                    indexes={indexes}
                    indexName={this.indexName}
                    onIndexChange={this.handleIndexChange}
                    onQueryChange={this.handleQueryChange}
                    onSearch={this.handleSearch}
                    query={this.query || undefined}
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

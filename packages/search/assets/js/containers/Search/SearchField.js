// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {ArrowMenu, Icon} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import searchFieldStyles from './searchField.scss';
import type {SearchResource} from './types';

type Props = {|
    onQueryChange: (query: ?string) => void,
    onSearch: () => void,
    onSearchResourceChange: (resourceKey: ?string) => void,
    query: string,
    resourceKey: ?string,
    searchResources: ?{[searchResource: string]: SearchResource},
|};

@observer
class SearchField extends React.Component<Props> {
    static defaultProps = {
        query: '',
    };

    @observable showSearchResources: boolean = false;

    @computed get allSearchResources(): ?Array<SearchResource> {
        const {searchResources} = this.props;

        if (!searchResources) {
            return undefined;
        }

        return (Object.values(searchResources): any);
    }

    @computed get searchResource(): ?SearchResource {
        const {resourceKey, searchResources} = this.props;

        if (!resourceKey || !searchResources) {
            return undefined;
        }

        return searchResources[resourceKey];
    }

    @action handleSearchResourceClick = () => {
        this.showSearchResources = true;
    };

    @action handleSearchResourceClose = () => {
        this.showSearchResources = false;
    };

    @action handleSearchResourceChange = (value: ?string) => {
        const {onSearchResourceChange, onSearch} = this.props;
        this.showSearchResources = false;
        onSearchResourceChange(value);
        onSearch();
    };

    handleQueryChange = (event: SyntheticEvent<HTMLInputElement>) => {
        const {onQueryChange} = this.props;
        onQueryChange(event.currentTarget.value);
    };

    handleQueryKeyPress = (event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            const {onSearch} = this.props;
            onSearch();
        }
    };

    handleClearClick = () => {
        const {onQueryChange, onSearch} = this.props;
        onQueryChange(undefined);
        onSearch();
    };

    render() {
        const {onSearch, query} = this.props;
        const everythingTranslation = translate('sulu_search.everything');

        return (
            <Fragment>
                <div className={searchFieldStyles.searchField}>
                    <ArrowMenu
                        anchorElement={
                            <button
                                className={searchFieldStyles.searchResourceButton}
                                onClick={this.handleSearchResourceClick}
                                type="button"
                            >
                                <span className={searchFieldStyles.searchResource}>
                                    {this.searchResource ? translate(this.searchResource.name) : everythingTranslation}
                                </span>
                                <Icon name="su-angle-down" />
                            </button>
                        }
                        onClose={this.handleSearchResourceClose}
                        open={this.showSearchResources}
                    >
                        <ArrowMenu.SingleItemSection
                            onChange={this.handleSearchResourceChange}
                            value={this.searchResource ? this.searchResource.resourceKey : undefined}
                        >
                            <ArrowMenu.Item value={undefined}>{everythingTranslation}</ArrowMenu.Item>
                            {this.allSearchResources
                                ? this.allSearchResources.map((searchResource: SearchResource) => (
                                    <ArrowMenu.Item key={searchResource.resourceKey} value={searchResource.resourceKey}>
                                        {translate(searchResource.name)}
                                    </ArrowMenu.Item>
                                ))
                                : []
                            }
                        </ArrowMenu.SingleItemSection>
                    </ArrowMenu>
                    <div className={searchFieldStyles.inputContainer}>
                        <input
                            autoFocus={true}
                            className={searchFieldStyles.input}
                            onChange={this.handleQueryChange}
                            onKeyPress={this.handleQueryKeyPress}
                            value={query}
                        />
                        {query &&
                            <Icon
                                className={searchFieldStyles.clearIcon}
                                name="su-times"
                                onClick={this.handleClearClick}
                            />
                        }
                        <Icon className={searchFieldStyles.searchIcon} name="su-search" onClick={onSearch} />
                    </div>
                </div>
                <p className={searchFieldStyles.hint}>
                    {translate('sulu_search.search_hint')}
                </p>
            </Fragment>
        );
    }
}

export default SearchField;

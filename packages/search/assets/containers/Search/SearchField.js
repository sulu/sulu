// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {ArrowMenu, Icon} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import searchFieldStyles from './searchField.scss';
import type {Index} from './types';

type Props = {|
    indexes: ?{[indexName: string]: Index},
    indexName: ?string,
    onIndexChange: (indexName: ?string) => void,
    onQueryChange: (query: ?string) => void,
    onSearch: () => void,
    query: string,
|};

@observer
class SearchField extends React.Component<Props> {
    static defaultProps = {
        query: '',
    };

    @observable showIndexes: boolean = false;

    @computed get allIndexes(): ?Array<Index> {
        const {indexes} = this.props;

        if (!indexes) {
            return undefined;
        }

        return (Object.values(indexes): any);
    }

    @computed get index(): ?Index {
        const {indexName, indexes} = this.props;

        if (!indexName || !indexes) {
            return undefined;
        }

        return indexes[indexName];
    }

    @action handleIndexClick = () => {
        this.showIndexes = true;
    };

    @action handleIndexClose = () => {
        this.showIndexes = false;
    };

    @action handleIndexChange = (value: ?string) => {
        const {onIndexChange, onSearch} = this.props;
        this.showIndexes = false;
        onIndexChange(value);
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
                                className={searchFieldStyles.indexButton}
                                onClick={this.handleIndexClick}
                                type="button"
                            >
                                <span className={searchFieldStyles.index}>
                                    {this.index ? this.index.name : everythingTranslation}
                                </span>
                                <Icon name="su-angle-down" />
                            </button>
                        }
                        onClose={this.handleIndexClose}
                        open={this.showIndexes}
                    >
                        <ArrowMenu.SingleItemSection
                            onChange={this.handleIndexChange}
                            value={this.index ? this.index.indexName : undefined}
                        >
                            <ArrowMenu.Item value={undefined}>{everythingTranslation}</ArrowMenu.Item>
                            {this.allIndexes
                                ? this.allIndexes.map((index: Index) => (
                                    <ArrowMenu.Item key={index.indexName} value={index.indexName}>
                                        {index.name}
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

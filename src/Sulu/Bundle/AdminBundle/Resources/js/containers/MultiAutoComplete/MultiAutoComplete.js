// @flow
import React from 'react';
import {autorun} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import MultiAutoCompleteComponent from '../../components/MultiAutoComplete';
import SearchStore from '../../stores/SearchStore';
import SelectionStore from '../../stores/SelectionStore';

type Props = {|
    displayProperty: string,
    filterParameter: string,
    idProperty: string,
    locale?: ?IObservableValue<string>,
    onChange: (value: Array<string | number>) => void,
    resourceKey: string,
    searchProperties: Array<string>,
    value: ?Array<string | number>,
|};

@observer
export default class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        filterParameter: 'ids',
        idProperty: 'id',
    };

    searchStore: SearchStore;
    selectionStore: SelectionStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    constructor(props: Props) {
        super(props);

        const {
            filterParameter,
            idProperty,
            locale,
            onChange,
            resourceKey,
            searchProperties,
            value,
        } = this.props;

        this.searchStore = new SearchStore(resourceKey, searchProperties);
        this.selectionStore = new SelectionStore(resourceKey, value || [], locale, filterParameter);

        this.changeDisposer = autorun(() => {
            const itemIds = this.selectionStore.items.map((item) => item[idProperty]);

            if (this.selectionStore.loading) {
                return;
            }

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            onChange(itemIds);
        });
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    handleChange = (value: Array<Object>) => {
        this.selectionStore.set(value);
        this.searchStore.clearSearchResults();
    };

    handleSearch = (query: string) => {
        this.searchStore.search(query, this.selectionStore.items.map((item) => item.id));
    };

    render() {
        const {
            props: {
                displayProperty,
                searchProperties,
            },
        } = this;

        return (
            <MultiAutoCompleteComponent
                displayProperty={displayProperty}
                loading={this.searchStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.searchStore.searchResults}
                value={this.selectionStore.items || []}
            />
        );
    }
}

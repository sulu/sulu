// @flow
import React from 'react';
import {reaction, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import MultiAutoCompleteComponent from '../../components/MultiAutoComplete';
import SearchStore from '../../stores/SearchStore';
import MultiSelectionStore from '../../stores/MultiSelectionStore';

type Props = {|
    allowAdd: boolean,
    disabled: boolean,
    displayProperty: string,
    filterParameter: string,
    id?: string,
    idProperty: string,
    locale?: ?IObservableValue<string>,
    onChange: (value: Array<string | number>) => void,
    options: Object,
    resourceKey: string,
    searchProperties: Array<string>,
    value: ?Array<string | number>,
|};

export default @observer class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        allowAdd: false,
        disabled: false,
        filterParameter: 'ids',
        idProperty: 'id',
        options: {},
    };

    searchStore: SearchStore;
    selectionStore: MultiSelectionStore<string | number>;
    changeDisposer: () => *;

    constructor(props: Props) {
        super(props);

        const {
            filterParameter,
            idProperty,
            locale,
            options,
            resourceKey,
            searchProperties,
            value,
        } = this.props;

        this.searchStore = new SearchStore(resourceKey, searchProperties, options);
        this.selectionStore = new MultiSelectionStore(resourceKey, value || [], locale, filterParameter);
        this.changeDisposer = reaction(
            () => (this.selectionStore.items.map((item) => item[idProperty])),
            (loadedItemIds: Array<string | number>) => {
                const {onChange, value} = this.props;

                if (!equals(toJS(value), toJS(loadedItemIds))) {
                    onChange(loadedItemIds);
                }
            }
        );
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    componentDidUpdate(prevProps: Props) {
        const {value} = this.props;

        if (!equals(prevProps.value, value)) {
            this.selectionStore.loadItems(value);
        }
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
                allowAdd,
                disabled,
                displayProperty,
                id,
                idProperty,
                searchProperties,
            },
        } = this;

        return (
            <MultiAutoCompleteComponent
                allowAdd={allowAdd}
                disabled={disabled}
                displayProperty={displayProperty}
                id={id}
                idProperty={idProperty}
                loading={this.searchStore.loading || this.selectionStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.searchStore.searchResults}
                value={this.selectionStore.items || []}
            />
        );
    }
}

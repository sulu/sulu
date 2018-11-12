// @flow
import React from 'react';
import {autorun} from 'mobx';
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
    resourceKey: string,
    searchProperties: Array<string>,
    value: ?Array<string | number>,
|};

@observer
export default class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        allowAdd: false,
        disabled: false,
        filterParameter: 'ids',
        idProperty: 'id',
    };

    searchStore: SearchStore;
    selectionStore: MultiSelectionStore;
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
        this.selectionStore = new MultiSelectionStore(resourceKey, value || [], locale, filterParameter);

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

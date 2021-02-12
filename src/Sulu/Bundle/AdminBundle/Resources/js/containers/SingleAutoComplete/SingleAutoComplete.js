// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleAutoCompleteComponent from '../../components/SingleAutoComplete';
import SearchStore from '../../stores/SearchStore';
import SingleSelectionStore from '../../stores/SingleSelectionStore';

type Props<T> = {|
    disabled: boolean,
    displayProperty: string,
    id?: string,
    options: Object,
    searchProperties: Array<string>,
    selectionStore: SingleSelectionStore<T>,
|};

@observer
class SingleAutoComplete<T: string | number> extends React.Component<Props<T>> {
    static defaultProps = {
        disabled: false,
        options: {},
    };

    searchStore: SearchStore;

    constructor(props: Props<T>) {
        super(props);

        const {options, selectionStore, searchProperties} = this.props;

        this.searchStore = new SearchStore(
            selectionStore.resourceKey,
            searchProperties,
            options,
            selectionStore.locale
        );
    }

    handleChange = (value: ?Object) => {
        const {selectionStore} = this.props;
        selectionStore.set(value);
        this.searchStore.clearSearchResults();
    };

    handleSearch = (query: string) => {
        this.searchStore.search(query);
    };

    render() {
        const {
            disabled,
            displayProperty,
            id,
            searchProperties,
            selectionStore,
        } = this.props;

        return (
            <SingleAutoCompleteComponent
                disabled={disabled}
                displayProperty={displayProperty}
                id={id}
                loading={this.searchStore.loading || selectionStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.searchStore.searchResults}
                value={selectionStore.item}
            />
        );
    }
}

export default SingleAutoComplete;

// @flow
import React from 'react';
import {observer} from 'mobx-react';
import MultiAutoCompleteComponent from '../../components/MultiAutoComplete';
import SearchStore from '../../stores/SearchStore';
import MultiSelectionStore from '../../stores/MultiSelectionStore';
import type {ElementRef} from 'react';

type Props = {|
    allowAdd: boolean,
    disabled: boolean,
    displayProperty: string,
    id?: string,
    idProperty: string,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    options: Object,
    searchProperties: Array<string>,
    selectionStore: MultiSelectionStore<string | number>,
|};

@observer
class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        allowAdd: false,
        disabled: false,
        idProperty: 'id',
        options: {},
    };

    searchStore: SearchStore;

    constructor(props: Props) {
        super(props);

        const {
            options,
            searchProperties,
            selectionStore,
        } = this.props;

        this.searchStore = new SearchStore(
            selectionStore.resourceKey,
            searchProperties,
            {...options, ...selectionStore.requestParameters},
            selectionStore.locale
        );
    }

    handleChange = (value: Array<Object>) => {
        const {selectionStore} = this.props;
        selectionStore.set(value);
        this.searchStore.clearSearchResults();
    };

    handleSearch = (query: string) => {
        const {selectionStore} = this.props;
        this.searchStore.search(query, selectionStore.ids);
    };

    render() {
        const {
            allowAdd,
            disabled,
            displayProperty,
            id,
            idProperty,
            inputRef,
            searchProperties,
            selectionStore,
        } = this.props;

        return (
            <MultiAutoCompleteComponent
                allowAdd={allowAdd}
                disabled={disabled}
                displayProperty={displayProperty}
                id={id}
                idProperty={idProperty}
                inputRef={inputRef}
                loading={this.searchStore.loading || selectionStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.searchStore.searchResults}
                value={selectionStore.items || []}
            />
        );
    }
}

export default MultiAutoComplete;

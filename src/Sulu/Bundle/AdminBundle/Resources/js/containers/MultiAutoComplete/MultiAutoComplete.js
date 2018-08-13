// @flow
import React from 'react';
import {observer} from 'mobx-react';
import MultiAutoCompleteComponent from '../../components/MultiAutoComplete';
import SearchStore from '../../stores/SearchStore';

type Props = {|
    displayProperty: string,
    searchProperties: Array<string>,
    onChange: (value: Array<Object>) => void,
    resourceKey: string,
    value: ?Array<Object>,
|};

@observer
export default class MultiAutoComplete extends React.Component<Props> {
    searchStore: SearchStore;

    constructor(props: Props) {
        super(props);

        const {resourceKey, searchProperties} = this.props;

        this.searchStore = new SearchStore(resourceKey, searchProperties);
    }

    handleChange = (value: Array<Object>) => {
        this.props.onChange(value);
        this.searchStore.clearSearchResults();
    };

    handleSearch = (query: string) => {
        this.searchStore.search(query);
    };

    render() {
        const {
            props: {
                displayProperty,
                searchProperties,
                value,
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
                value={value || []}
            />
        );
    }
}

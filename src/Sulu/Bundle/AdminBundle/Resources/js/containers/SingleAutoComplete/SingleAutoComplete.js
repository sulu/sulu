// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleAutoCompleteComponent from '../../components/SingleAutoComplete';
import SearchStore from '../../stores/SearchStore';

type Props = {|
    displayProperty: string,
    id?: string,
    searchProperties: Array<string>,
    onChange: (value: ?Object) => void,
    resourceKey: string,
    value: ?Object,
|};

@observer
export default class SingleAutoComplete extends React.Component<Props> {
    searchStore: SearchStore;

    constructor(props: Props) {
        super(props);

        const {resourceKey, searchProperties} = this.props;

        this.searchStore = new SearchStore(resourceKey, searchProperties);
    }

    handleChange = (value: ?Object) => {
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
                id,
                searchProperties,
                value,
            },
        } = this;

        return (
            <SingleAutoCompleteComponent
                displayProperty={displayProperty}
                id={id}
                loading={this.searchStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.searchStore.searchResults}
                value={value}
            />
        );
    }
}

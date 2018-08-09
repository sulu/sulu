// @flow
import React from 'react';
import {observer} from 'mobx-react';
import AutoCompleteComponent from '../../components/AutoComplete';
import AutoCompleteStore from './stores/AutoCompleteStore';

type Props = {|
    displayProperty: string,
    searchProperties: Array<string>,
    onChange: (value: ?Object) => void,
    resourceKey: string,
    value: ?Object,
|};

@observer
export default class AutoComplete extends React.Component<Props> {
    autoCompleteStore: AutoCompleteStore;

    constructor(props: Props) {
        super(props);

        const {resourceKey, searchProperties} = this.props;

        this.autoCompleteStore = new AutoCompleteStore(resourceKey, searchProperties);
    }

    handleChange = (value: ?Object) => {
        this.props.onChange(value);
        this.autoCompleteStore.clearSearchResults();
    };

    handleSearch = (query: string) => {
        this.autoCompleteStore.search(query);
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
            <AutoCompleteComponent
                displayProperty={displayProperty}
                loading={this.autoCompleteStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                searchProperties={searchProperties}
                suggestions={this.autoCompleteStore.searchResults}
                value={value}
            />
        );
    }
}

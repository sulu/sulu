// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleAutoCompleteComponent from '../../components/SingleAutoComplete';
import SearchStore from '../../stores/SearchStore';

type Props = {|
    disabled: boolean,
    displayProperty: string,
    id?: string,
    onChange: (value: ?Object) => void,
    options: Object,
    resourceKey: string,
    searchProperties: Array<string>,
    value: ?Object,
|};

@observer
class SingleAutoComplete extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        options: {},
    };

    searchStore: SearchStore;

    constructor(props: Props) {
        super(props);

        const {options, resourceKey, searchProperties} = this.props;

        this.searchStore = new SearchStore(resourceKey, searchProperties, options);
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
            disabled,
            displayProperty,
            id,
            searchProperties,
            value,

        } = this.props;

        return (
            <SingleAutoCompleteComponent
                disabled={disabled}
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

export default SingleAutoComplete;

// @flow
import React from 'react';
import {observer} from 'mobx-react';
import AutoCompleteComponent from '../../components/AutoComplete';
import AutoCompleteStore from './stores/AutoCompleteStore';
import autoCompleteStyles from './autoComplete.scss';

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

    handleChange = (id: ?string | number) => {
        this.props.onChange(this.autoCompleteStore.searchResults.find((searchResult) => searchResult.id === id));
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
                loading={this.autoCompleteStore.loading}
                onChange={this.handleChange}
                onSearch={this.handleSearch}
                value={value ? value[displayProperty] : undefined}
            >
                {this.autoCompleteStore.searchResults.map((searchResult) => (
                    <AutoCompleteComponent.Suggestion key={searchResult.id} value={searchResult.id}>
                        {(highlight) => searchProperties.map((field) => (
                            <span className={autoCompleteStyles.column} key={field}>
                                {highlight(searchResult[field])}
                            </span>
                        ))}
                    </AutoCompleteComponent.Suggestion>
                ))}
            </AutoCompleteComponent>
        );
    }
}

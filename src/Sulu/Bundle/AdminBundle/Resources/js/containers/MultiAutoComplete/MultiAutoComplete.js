// @flow
import React from 'react';
import {action} from 'mobx';
import {observer} from 'mobx-react';
import MultiAutoCompleteComponent from '../../components/MultiAutoComplete';
import SearchStore from '../../stores/SearchStore';
import MultiSelectionStore from '../../stores/MultiSelectionStore';
import ResourceRequester from '../../services/ResourceRequester';
import snackbarStore from '../../stores/snackbarStore';
import {translate} from '../../utils/Translator';
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
        const {allowAdd, displayProperty, idProperty, selectionStore} = this.props;

        if (allowAdd) {
            const newlyTypedItem = value.find((item) => item[displayProperty] === undefined);

            if (newlyTypedItem) {
                const typedValue = newlyTypedItem[idProperty];
                const itemsWithoutNew = value.filter((item) => item !== newlyTypedItem);

                const tempItem = {[idProperty]: typedValue, [displayProperty]: typedValue};
                selectionStore.set([...itemsWithoutNew, tempItem]);
                this.searchStore.clearSearchResults();

                ResourceRequester.post(selectionStore.resourceKey, {[displayProperty]: typedValue})
                    .then(action((createdItem) => {
                        selectionStore.set([...itemsWithoutNew, createdItem]);
                    }))
                    .catch(action(() => {
                        selectionStore.set(itemsWithoutNew);
                        snackbarStore.add(
                            {text: translate('sulu_admin.multi_auto_complete_create_error'), type: 'error'},
                            4000
                        );
                    }));

                return;
            }
        }

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

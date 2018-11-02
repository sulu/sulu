// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import debounce from 'debounce';
import Input from '../Input';
import AutoCompletePopover from '../AutoCompletePopover';
import singleAutoCompleteStyles from './singleAutoComplete.scss';

const LENS_ICON = 'su-search';
const DEBOUNCE_TIME = 300;

type Props = {|
    displayProperty: string,
    id?: string,
    loading?: boolean,
    onChange: (value: ?Object) => void,
    onFinish?: () => void,
    /** Called with a debounce when text is entered inside the input */
    onSearch: (query: string) => void,
    placeholder?: string,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
    value: ?Object,
|};

@observer
export default class SingleAutoComplete extends React.Component<Props> {
    @observable labelRef: ElementRef<'label'>;

    @observable inputValue: ?string = this.props.value ? this.props.value[this.props.displayProperty] : undefined;

    overrideValue: boolean = false;

    componentDidUpdate() {
        if (this.overrideValue) {
            const {
                displayProperty,
                value,
            } = this.props;
            this.overrideValue = false;
            this.setInputValue(value ? value[displayProperty] : undefined);
        }
    }

    componentWillUnmount() {
        this.debouncedSearch.clear();
    }

    @action setInputValue(value: ?string) {
        this.inputValue = value;
    }

    @action setLabelRef = (labelRef: ?ElementRef<'label'>) => {
        if (labelRef) {
            this.labelRef = labelRef;
        }
    };

    @computed get popoverMinWidth() {
        return this.labelRef ? this.labelRef.scrollWidth - 10 : 0;
    }

    debouncedSearch = debounce((query: string) => {
        this.props.onSearch(query);
    }, DEBOUNCE_TIME);

    handleSelect = (value: Object) => {
        this.overrideValue = true;
        this.props.onChange(value);
    };

    handleInputChange = (value: ?string) => {
        if (!value) {
            this.props.onChange(undefined);
        }

        this.setInputValue(value);
        this.debouncedSearch(this.inputValue);
    };

    render() {
        const {
            id,
            loading,
            onFinish,
            placeholder,
            searchProperties,
            suggestions,
        } = this.props;
        const {inputValue} = this;
        const showSuggestionList = (!!inputValue && inputValue.length > 0) && suggestions.length > 0;

        // The mousetrap class is required to allow mousetrap catch key bindings for up and down keys
        return (
            <div className={singleAutoCompleteStyles.singleAutoComplete}>
                <Input
                    icon={LENS_ICON}
                    id={id}
                    inputClass="mousetrap"
                    labelRef={this.setLabelRef}
                    loading={loading}
                    onBlur={onFinish}
                    onChange={this.handleInputChange}
                    placeholder={placeholder}
                    value={inputValue}
                />
                <AutoCompletePopover
                    anchorElement={this.labelRef}
                    minWidth={this.popoverMinWidth}
                    onSelect={this.handleSelect}
                    open={showSuggestionList}
                    query={inputValue}
                    searchProperties={searchProperties}
                    suggestions={suggestions}
                />
            </div>
        );
    }
}

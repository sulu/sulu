// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import Mousetrap from 'mousetrap';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import AutoCompletePopover from '../AutoCompletePopover';
import Chip from '../Chip';
import multiAutoCompleteStyles from './multiAutoComplete.scss';
import type {ElementRef} from 'react';

type Props = {|
    allowAdd: boolean,
    disabled: boolean,
    displayProperty: string,
    id?: string,
    idProperty: string,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    loading: boolean,
    onChange: (value: Array<Object>) => void,
    onFinish?: () => void,
    onSearch: (query: string) => void,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
    value: Array<Object>,
|};

const DEBOUNCE_TIME = 300;

@observer
class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        allowAdd: false,
        disabled: false,
        idProperty: 'id',
        loading: false,
    };

    @observable labelRef: ElementRef<'label'>;
    @observable inputRef: ElementRef<'input'>;

    @observable displaySuggestions = false;
    @observable inputValue: string = '';

    componentWillUnmount() {
        this.debouncedSearch.clear();
    }

    @action setLabelRef = (labelRef: ?ElementRef<'label'>) => {
        if (labelRef) {
            this.labelRef = labelRef;
        }
    };

    @action setInputRef = (ref: ?ElementRef<'input'>) => {
        const {inputRef} = this.props;

        if (inputRef) {
            inputRef(ref);
        }

        if (ref) {
            this.inputRef = ref;
        }
    };

    @computed get popoverMinWidth() {
        return this.labelRef ? this.labelRef.scrollWidth - 10 : 0;
    }

    handleDelete = (newValue: Object) => {
        const {onChange, onFinish, value} = this.props;
        onChange(value.filter((item) => item != newValue));

        // reload suggestion list as deleted item should not be excluded from suggestions anymore
        this.debouncedSearch(this.inputValue);

        if (onFinish) {
            onFinish();
        }
    };

    @action handleInputChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.inputValue = event.currentTarget.value;
        this.debouncedSearch(this.inputValue);
    };

    handleInputFocus = () => {
        Mousetrap.bind('enter', this.handleEnterAndComma);
        Mousetrap.bind(',', this.handleEnterAndComma);
        Mousetrap.bind('backspace', this.handleBackspace);

        this.search(this.inputValue);
    };

    handleInputBlur = () => {
        Mousetrap.unbind('enter');
        Mousetrap.unbind(',');
        Mousetrap.unbind('backspace');
    };

    handleEnterAndComma = () => {
        const {
            allowAdd,
            displayProperty,
            idProperty,
            suggestions,
            value,
        } = this.props;

        if (this.inputValue.length === 0) {
            return false;
        }

        const suggestion = suggestions.find((suggestion) => suggestion[displayProperty] === this.inputValue);
        if (suggestion) {
            this.handleSelect(suggestion);
            return false;
        }

        const item = value.find((item) => item[displayProperty].toLowerCase() === this.inputValue.toLowerCase());
        if (allowAdd && !item) {
            this.handleSelect({[idProperty]: this.inputValue});
            return false;
        }

        return false;
    };

    handleBackspace = () => {
        const {value} = this.props;
        if (this.inputValue.length > 0) {
            return true;
        }

        if (value.length === 0) {
            return false;
        }

        this.handleDelete(value[value.length - 1]);
    };

    @action handleSelect = (newValue: Object) => {
        const {
            onChange,
            onFinish,
            value,
        } = this.props;

        onChange([...value, newValue]);
        this.inputValue = '';
        this.inputRef.focus();

        if (onFinish) {
            onFinish();
        }
    };

    @action handlePopoverClose = () => {
        this.displaySuggestions = false;
    };

    @action search = (query: string) => {
        this.props.onSearch(query);
        this.displaySuggestions = true;
    };

    debouncedSearch = debounce(this.search, DEBOUNCE_TIME);

    render() {
        const {
            disabled,
            displayProperty,
            id,
            idProperty,
            loading,
            searchProperties,
            suggestions,
            value,
        } = this.props;

        const multiAutoCompleteClass = classNames(
            multiAutoCompleteStyles.multiAutoComplete,
            {
                [multiAutoCompleteStyles.disabled]: disabled,
            }
        );

        const inputClass = classNames(
            multiAutoCompleteStyles.input,
            'mousetrap' // required to allow mousetrap to catch key binding within input
        );

        return (
            <Fragment>
                <label className={multiAutoCompleteClass} ref={this.setLabelRef}>
                    <div className={multiAutoCompleteStyles.icon}>
                        {loading
                            ? <Loader size={16} />
                            : <Icon name="su-search" />
                        }
                    </div>
                    <div className={multiAutoCompleteStyles.items}>
                        {value.map((item) => (
                            <span className={multiAutoCompleteStyles.chip} key={item[idProperty]}>
                                <Chip
                                    disabled={disabled}
                                    onDelete={this.handleDelete}
                                    value={item}
                                >
                                    {item[displayProperty]}
                                </Chip>
                            </span>
                        ))}
                        <input
                            className={inputClass}
                            disabled={disabled}
                            id={id}
                            onBlur={this.handleInputBlur}
                            onChange={this.handleInputChange}
                            onFocus={this.handleInputFocus}
                            ref={this.setInputRef}
                            value={this.inputValue}
                        />
                    </div>
                </label>
                <AutoCompletePopover
                    anchorElement={this.labelRef}
                    idProperty={idProperty}
                    minWidth={this.popoverMinWidth}
                    onClose={this.handlePopoverClose}
                    onSelect={this.handleSelect}
                    open={!disabled && this.displaySuggestions && suggestions.length > 0}
                    query={this.inputValue}
                    searchProperties={searchProperties}
                    suggestions={suggestions}
                />
            </Fragment>
        );
    }
}

export default MultiAutoComplete;

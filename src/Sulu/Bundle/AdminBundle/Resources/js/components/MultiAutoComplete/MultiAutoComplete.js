// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import Mousetrap from 'mousetrap';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import AutoCompletePopover from '../AutoCompletePopover';
import Item from './Item';
import multiAutoCompleteStyles from './multiAutoComplete.scss';

type Props = {
    allowAdd: boolean,
    displayProperty: string,
    idProperty: string,
    loading: boolean,
    onChange: (value: Array<Object>) => void,
    onFinish?: () => void,
    onSearch: (query: string) => void,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
    value: Array<Object>,
};

const DEBOUNCE_TIME = 300;

@observer
export default class MultiAutoComplete extends React.Component<Props> {
    static defaultProps = {
        allowAdd: false,
        idProperty: 'id',
        loading: false,
    };

    @observable labelRef: ElementRef<'label'>;
    @observable inputValue = '';

    @action setLabelRef = (labelRef: ?ElementRef<'label'>) => {
        if (labelRef) {
            this.labelRef = labelRef;
        }
    };

    @computed get popoverMinWidth() {
        return this.labelRef ? this.labelRef.scrollWidth - 10 : 0;
    }

    @action handleDelete = (value: Object) => {
        this.props.onChange(this.props.value.filter((item) => item != value));
    };

    @action handleInputChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.inputValue = event.currentTarget.value;
        this.debouncedSearch(this.inputValue);
    };

    @action handleInputFocus = () => {
        Mousetrap.bind('enter', this.handleEnterAndComma);
        Mousetrap.bind(',', this.handleEnterAndComma);
    };

    @action handleInputBlur = () => {
        Mousetrap.unbind('enter');
        Mousetrap.unbind(',');
    };

    handleEnterAndComma = () => {
        const {
            allowAdd,
            displayProperty,
            idProperty,
            suggestions,
        } = this.props;

        if (this.inputValue.length === 0) {
            return false;
        }

        const suggestion = suggestions.find((suggestion) => suggestion[displayProperty] === this.inputValue);
        if (suggestion) {
            this.handleSelect(suggestion);
            return false;
        }

        if (allowAdd) {
            this.handleSelect({[idProperty]: this.inputValue});
            return false;
        }

        return false;
    };

    @action handleSelect = (newValue: Object) => {
        const {
            onChange,
            onFinish,
            value,
        } = this.props;

        onChange([...value, newValue]);
        this.inputValue = '';

        if (onFinish) {
            onFinish();
        }
    };

    debouncedSearch = debounce((query: string) => {
        this.props.onSearch(query);
    }, DEBOUNCE_TIME);

    render() {
        const {
            displayProperty,
            idProperty,
            loading,
            searchProperties,
            suggestions,
            value,
        } = this.props;

        const showSuggestionList = (!!this.inputValue && this.inputValue.length > 0) && suggestions.length > 0;

        const inputClass = classNames(
            multiAutoCompleteStyles.input,
            'mousetrap' // required to allow mousetrap to catch key binding within input
        );

        return (
            <Fragment>
                <label className={multiAutoCompleteStyles.multiAutoComplete} ref={this.setLabelRef}>
                    <div className={multiAutoCompleteStyles.icon}>
                        {loading
                            ? <Loader size={20} />
                            : <Icon name="su-search" />
                        }
                    </div>
                    <div className={multiAutoCompleteStyles.items}>
                        {value.map((item) => (
                            <Item
                                onDelete={this.handleDelete}
                                key={item[idProperty]}
                                value={item}
                            >
                                {item[displayProperty]}
                            </Item>
                        ))}
                        <input
                            className={inputClass}
                            onBlur={this.handleInputBlur}
                            onChange={this.handleInputChange}
                            onFocus={this.handleInputFocus}
                            value={this.inputValue}
                        />
                    </div>
                </label>
                <AutoCompletePopover
                    anchorElement={this.labelRef}
                    idProperty={idProperty}
                    minWidth={this.popoverMinWidth}
                    onSelect={this.handleSelect}
                    open={showSuggestionList}
                    query={this.inputValue}
                    searchProperties={searchProperties}
                    suggestions={suggestions}
                />
            </Fragment>
        );
    }
}

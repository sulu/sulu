// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import CharacterCounter from '../CharacterCounter';
import Icon from '../Icon';
import Loader from '../Loader';
import SegmentCounter from '../SegmentCounter';
import inputStyles from './input.scss';
import type {InputProps} from './types';

const LOADER_SIZE = 20;

@observer
class Input<T: ?string | ?number> extends React.Component<InputProps<T>> {
    static defaultProps = {
        alignment: 'left',
        collapsed: false,
        disabled: false,
        skin: 'default',
        type: 'text',
        valid: true,
    };

    @computed get patternFulfilled() {
        const {pattern, value} = this.props;

        if (pattern && value && typeof value === 'string') {
            const regexp = new RegExp(pattern);

            return regexp.test(value);
        }

        return true;
    }

    @computed get valid() {
        const {valid} = this.props;

        return valid && this.patternFulfilled;
    }

    setInputRef = (ref: ?ElementRef<'input'>) => {
        const {inputRef} = this.props;

        if (!inputRef) {
            return;
        }

        inputRef(ref);
    };

    setLabelRef = (ref: ?ElementRef<'label'>) => {
        const {labelRef} = this.props;

        if (!labelRef) {
            return;
        }

        labelRef(ref);
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined, event);
    };

    handleKeyPress = (event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        const {onKeyPress} = this.props;

        if (onKeyPress) {
            onKeyPress(event.key || undefined, event);
        }
    };

    render() {
        const {
            alignment,
            autocomplete,
            headline,
            id,
            inputClass,
            disabled,
            icon,
            loading,
            collapsed,
            maxCharacters,
            maxSegments,
            name,
            pattern,
            patternDescription,
            placeholder,
            onBlur,
            onIconClick,
            onClearClick,
            onKeyPress,
            segmentDelimiter,
            type,
            value,
            iconStyle,
            iconClassName,
            inputMode,
            inputRef,
            labelRef,
            skin,
            min,
            max,
            step,
        } = this.props;

        const labelClass = classNames(
            inputStyles.input,
            inputStyles[skin],
            inputStyles[alignment],
            {
                [inputStyles.error]: !this.valid,
                [inputStyles.disabled]: disabled,
                [inputStyles.collapsed]: collapsed,
                [inputStyles.hasAppendIcon]: onClearClick,
                [inputStyles.headline]: headline,
            }
        );

        const iconClass = classNames(
            inputStyles.icon,
            inputStyles[skin],
            iconClassName,
            {
                [inputStyles.iconClickable]: (!!icon && !!onIconClick),
                [inputStyles.collapsed]: collapsed,
            }
        );

        const prependContainerClass = classNames(
            inputStyles.prependedContainer,
            inputStyles[skin],
            {
                [inputStyles.collapsed]: collapsed,
            }
        );

        return (
            <Fragment>
                <label
                    className={labelClass}
                    ref={labelRef ? this.setLabelRef : undefined}
                >
                    {!loading && icon &&
                        <div className={prependContainerClass}>
                            <Icon
                                className={iconClass}
                                name={icon}
                                onClick={onIconClick ? onIconClick : undefined}
                                style={iconStyle}
                            />
                        </div>
                    }

                    {loading &&
                        <div className={prependContainerClass}>
                            <Loader size={LOADER_SIZE} />
                        </div>
                    }

                    <input
                        autoComplete={autocomplete}
                        className={inputClass}
                        disabled={disabled}
                        id={id}
                        inputMode={inputMode}
                        max={max}
                        min={min}
                        name={name}
                        onBlur={onBlur}
                        onChange={this.handleChange}
                        onKeyPress={onKeyPress ? this.handleKeyPress : undefined}
                        pattern={pattern}
                        placeholder={placeholder}
                        ref={inputRef ? this.setInputRef : undefined}
                        step={step}
                        type={type}
                        value={value == null ? '' : value}
                    />

                    {!collapsed && !!value && onClearClick &&
                        <div className={inputStyles.appendContainer}>
                            <Icon
                                className={iconClass}
                                name="su-times"
                                onClick={onClearClick ? onClearClick : undefined}
                                style={iconStyle}
                            />
                        </div>
                    }
                </label>
                {maxCharacters &&
                    <CharacterCounter max={maxCharacters} value={value} />
                }
                {segmentDelimiter && maxSegments &&
                    <SegmentCounter
                        delimiter={segmentDelimiter}
                        max={maxSegments}
                        value={value ? value.toString() : undefined}
                    />
                }
                {!this.patternFulfilled &&
                    <label className={inputStyles.patternDescription}>
                        {patternDescription || translate(
                            'sulu_admin.default_input_pattern_description',
                            {pattern: String(pattern)}
                        )}
                    </label>
                }
            </Fragment>
        );
    }
}

export default Input;

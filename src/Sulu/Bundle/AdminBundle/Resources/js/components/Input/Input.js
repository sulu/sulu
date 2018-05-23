// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import inputStyles from './input.scss';

const LOADER_SIZE = 20;

type Props = {|
    expanded: boolean,
    name?: string,
    icon?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
    labelRef?: (ref: ?ElementRef<'label'>) => void,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    valid: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string, event: SyntheticEvent<HTMLInputElement>) => void,
    onKeyPress?: (event: SyntheticKeyboardEvent<HTMLInputElement>) => void,
    onIconClick?: () => void,
    onClearClick?: () => void,
    iconStyle?: Object,
    iconClassName?: string,
    skin: 'default' | 'dark',
|};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        expanded: true,
        type: 'text',
        skin: 'default',
        valid: true,
    };

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

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {
            valid,
            icon,
            loading,
            expanded,
            name,
            placeholder,
            onIconClick,
            onClearClick,
            onKeyPress,
            type,
            value,
            iconStyle,
            iconClassName,
            inputRef,
            labelRef,
            skin,
        } = this.props;

        const labelClass = classNames(
            inputStyles.input,
            inputStyles[skin],
            {
                [inputStyles.error]: !valid,
                [inputStyles.expanded]: expanded,
                [inputStyles.hasAppendIcon]: onClearClick,
            }
        );

        const iconClass = classNames(
            inputStyles.icon,
            inputStyles[skin],
            iconClassName,
            {
                [inputStyles.iconClickable]: (!!icon && !!onIconClick),
                [inputStyles.expanded]: expanded,
            }
        );

        const prependContainerClass = classNames(
            inputStyles.prependedContainer,
            inputStyles[skin]
        );

        return (
            <label
                className={labelClass}
                ref={labelRef ? this.setLabelRef : undefined}
            >
                {!loading && icon &&
                    <div className={prependContainerClass}>
                        <Icon
                            onClick={onIconClick ? onIconClick : undefined}
                            className={iconClass}
                            name={icon}
                            style={iconStyle}
                        />
                    </div>
                }

                {loading &&
                    <div className={prependContainerClass}>
                        <Loader size={LOADER_SIZE} />
                    </div>
                }

                {expanded &&
                    <input
                        ref={inputRef ? this.setInputRef : undefined}
                        name={name}
                        type={type}
                        value={value || ''}
                        placeholder={placeholder}
                        onBlur={this.handleBlur}
                        onChange={this.handleChange}
                        onKeyPress={onKeyPress}
                    />
                }

                {expanded && value && onClearClick &&
                    <div className={inputStyles.appendContainer}>
                        <Icon
                            onClick={onClearClick ? onClearClick : undefined}
                            className={iconClass}
                            name="su-times"
                            style={iconStyle}
                        />
                    </div>
                }
            </label>
        );
    }
}

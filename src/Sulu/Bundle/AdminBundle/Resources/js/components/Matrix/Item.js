// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

type Props = {|
    disabled: boolean,
    icon: string,
    name: string,
    onChange?: (name: string, value: boolean) => void,
    title?: string,
    value: boolean,
|};

export default class Item extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        value: false,
    };

    handleClick = () => {
        const {
            name,
            onChange,
            value,
        } = this.props;

        if (!onChange) {
            return;
        }

        onChange(name, !value);
    };

    render() {
        const {
            disabled,
            icon,
            name,
            title,
            value,
        } = this.props;
        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.selected]: value,
                [itemStyles.disabled]: disabled,
            }
        );

        const itemTitle = title ? title : name.charAt(0).toUpperCase() + name.slice(1);

        return (
            <button
                className={itemClass}
                onClick={!disabled ? this.handleClick : undefined}
                title={itemTitle}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}

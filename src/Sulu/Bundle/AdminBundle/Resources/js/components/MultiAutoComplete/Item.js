// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';

type Props = {|
    children: string,
    disabled: boolean,
    onDelete: (value: Object) => void,
    value: Object,
|};

export default class Item extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleDelete = () => {
        const {onDelete, value} = this.props;
        onDelete(value);
    };

    render() {
        const {children, disabled} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.disabled]: disabled,
            }
        );

        return (
            <div className={itemClass}>
                {children}
                {!disabled && <Icon className={itemStyles.icon} name="su-times" onClick={this.handleDelete} />}
            </div>
        );
    }
}

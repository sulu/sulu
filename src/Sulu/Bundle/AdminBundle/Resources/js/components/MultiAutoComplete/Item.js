// @flow
import React from 'react';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';

type Props = {
    children: string,
    onDelete: (value: Object) => void,
    value: Object,
};

export default class Item extends React.Component<Props> {
    handleDelete = () => {
        const {onDelete, value} = this.props;
        onDelete(value);
    };

    render() {
        const {children} = this.props;

        return (
            <div className={itemStyles.item}>
                {children}
                <Icon className={itemStyles.icon} name="su-times" onClick={this.handleDelete} />
            </div>
        );
    }
}

// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import matrixStyles from './matrix.scss';

type Props = {
    icon: string,
    name: string,
    onChange: (name: string, value: boolean) => void,
    value: boolean,
};

export default class Item extends React.PureComponent<Props> {
    handleClick = () => {
        const {
            name,
            onChange,
            value,
        } = this.props;

        onChange(name, !value);
    };

    render() {
        const {
            icon,
            value,
        } = this.props;
        const itemClass = classNames(
            matrixStyles.item,
            {
                [matrixStyles.selected]: value,
            }
        );

        return (
            <div className={itemClass} onClick={this.handleClick}>
                <Icon name={icon} />
            </div>
        );
    }
}

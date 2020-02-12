// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import chipStyles from './chip.scss';

type Props = {|
    children: string,
    disabled: boolean,
    onDelete: (value: Object) => void,
    value: Object,
|};

export default class Chip extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleDelete = () => {
        const {onDelete, value} = this.props;
        onDelete(value);
    };

    render() {
        const {children, disabled} = this.props;

        const chipClass = classNames(
            chipStyles.chip,
            {
                [chipStyles.disabled]: disabled,
            }
        );

        return (
            <div className={chipClass}>
                {children}
                {!disabled && <Icon className={chipStyles.icon} name="su-times" onClick={this.handleDelete} />}
            </div>
        );
    }
}

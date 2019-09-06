// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Cell from './Cell';
import tableStyles from './table.scss';

type Props = {|
    icon: string,
    onClick: ?(rowId: string | number, rowIndex: number) => void,
    rowId: string | number,
    rowIndex: number,
    visible: boolean,
|};

export default class ButtonCell extends React.PureComponent<Props> {
    static defaultProps = {
        visible: true,
    };

    handleClick = () => {
        const {rowIndex, onClick, rowId} = this.props;

        if (onClick) {
            onClick(rowId, rowIndex);
        }
    };

    render() {
        const {
            icon,
            visible,
        } = this.props;

        const cellClass = classNames(
            tableStyles.buttonCell,
            {
                [tableStyles.visible]: visible,
            }
        );

        return (
            <Cell className={cellClass}>
                <button onClick={this.handleClick}>
                    <Icon name={icon} />
                </button>
            </Cell>
        );
    }
}

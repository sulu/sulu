// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {ButtonConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    children: string,
    selected: boolean,
    hasChildren: boolean,
    buttons?: Array<ButtonConfig>,
};

export default class Item extends React.PureComponent<Props> {
    createButtons = () => {
        const {buttons} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ButtonConfig, index: number) => {
            const key = `button-${index}`;
            const handleClick = button.onClick;

            return (
                <Icon className={columnListStyles.button} key={key} name={button.icon} onClick={handleClick} />
            );
        });
    };

    render() {
        const {children, selected, hasChildren} = this.props;

        const itemClass = classNames(
            columnListStyles.item,
            {
                [columnListStyles.isSelected]: selected,
                [columnListStyles.hasChildren]: hasChildren,
            }
        );

        return (
            <div className={itemClass}>
                <span className={columnListStyles.buttons}>
                    {this.createButtons()}
                </span>
                <span className={columnListStyles.text}>{children}</span>
                {hasChildren &&
                    <Icon className={columnListStyles.icon} name="chevron-right" />
                }
            </div>
        );
    }
}

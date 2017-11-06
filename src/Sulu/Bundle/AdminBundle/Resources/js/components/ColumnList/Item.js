// @flow
import React from 'react';
import classNames from 'classnames';
import ItemButton from './ItemButton';
import Icon from '../Icon';
import type {ItemButtonConfig} from './types';
import itemStyles from './item.scss';

type Props = {
    id: string | number,
    children: string,
    selected: boolean,
    hasChildren: boolean,
    buttons?: Array<ItemButtonConfig>,
    onClick: (id: string | number) => void,
};

export default class Item extends React.Component<Props> {
    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    createButtons = () => {
        const {buttons, id} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ItemButtonConfig, index: number) => {
            const key = `button-${index}`;

            return (
                <ItemButton id={id} key={key} config={button} />
            );
        });
    };

    render() {
        const {children, selected, hasChildren} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.selected]: selected,
            }
        );

        return (
            <div onClick={this.handleClick} className={itemClass}>
                <span className={itemStyles.buttons}>
                    {this.createButtons()}
                </span>
                <span className={itemStyles.text}>{children}</span>
                {hasChildren &&
                    <Icon className={itemStyles.children} name="chevron-right" />
                }
            </div>
        );
    }
}

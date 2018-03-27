// @flow
import React from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import ItemButton from './ItemButton';
import type {ItemButtonConfig} from './types';
import itemStyles from './item.scss';

type Props = {
    active: boolean,
    buttons?: Array<ItemButtonConfig>,
    children: string,
    hasChildren: boolean,
    id: string | number,
    onClick?: (id: string | number) => void,
    selected: boolean,
};

export default class Item extends React.Component<Props> {
    static defaultProps = {
        active: false,
        hasChildren: false,
        selected: false,
    };

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
        const {children, active, hasChildren, selected} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.active]: active,
                [itemStyles.selected]: selected,
            }
        );

        return (
            <div onClick={this.handleClick} className={itemClass}>
                <span className={itemStyles.buttons}>
                    {this.createButtons()}
                </span>
                <span className={itemStyles.text}>
                    <CroppedText>{children}</CroppedText>
                </span>
                {hasChildren &&
                    <Icon className={itemStyles.children} name="su-arrow-right" />
                }
            </div>
        );
    }
}

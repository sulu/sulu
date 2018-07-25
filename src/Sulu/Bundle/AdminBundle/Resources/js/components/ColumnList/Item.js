// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import CroppedText from '../CroppedText';
import Icon from '../Icon';
import ItemButton from './ItemButton';
import type {ItemButtonConfig} from './types';
import itemStyles from './item.scss';

type Props = {|
    active: boolean,
    buttons?: Array<ItemButtonConfig>,
    children: string,
    disabled: boolean,
    hasChildren: boolean,
    id: string | number,
    indicators?: Array<Node>,
    onClick?: (id: string | number) => void,
    selected: boolean,
|};

export default class Item extends React.Component<Props> {
    static defaultProps = {
        active: false,
        disabled: false,
        hasChildren: false,
        selected: false,
    };

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    renderButtons = () => {
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
        const {active, children, disabled, hasChildren, indicators, selected} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.active]: active,
                [itemStyles.disabled]: disabled,
                [itemStyles.selected]: selected,
            }
        );

        return (
            <div onClick={this.handleClick} className={itemClass} role="button">
                <span className={itemStyles.buttons}>
                    {this.renderButtons()}
                </span>
                <span className={itemStyles.text}>
                    <CroppedText>{children}</CroppedText>
                </span>
                {indicators && indicators.map((indicator, index) => (
                    <span key={index} className={itemStyles.indicator}>
                        {indicator}
                    </span>
                ))}
                <span className={itemStyles.children}>
                    {hasChildren &&
                        <Icon name="su-angle-right" />
                    }
                </span>
            </div>
        );
    }
}

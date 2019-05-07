// @flow
import React from 'react';
import type {Element, Node} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Input from '../Input';
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
    onOrderChange?: (id: string | number, order: number) => Promise<boolean>,
    order?: number,
    showOrderField: boolean,
    selected: boolean,
|};

export default @observer class Item extends React.Component<Props> {
    static defaultProps = {
        active: false,
        disabled: false,
        hasChildren: false,
        selected: false,
        showOrderField: false,
    };

    @observable order: ?number;

    constructor(props: Props) {
        super(props);
        this.order = this.props.order;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {order} = this.props;
        if (prevProps.order !== order) {
            this.order = order;
        }
    }

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    @action handleOrderChange = (order: ?string) => {
        if (!order) {
            this.order = undefined;
        }

        const numericOrder = parseInt(order);
        if (isNaN(numericOrder)) {
            return;
        }

        this.order = numericOrder;
    };

    handleOrderBlur = () => {
        const {id, onOrderChange, order} = this.props;

        if (onOrderChange && this.order && order !== this.order) {
            onOrderChange(id, this.order).then(action((ordered) => {
                if (!ordered) {
                    this.order = this.props.order;
                }
            }));
        }
    };

    handleOrderKeyPress = (key: ?string, event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        if (key === 'Enter') {
            event.currentTarget.blur();
        }
    };

    renderButtons = (): ?Array<Element<typeof ItemButton>> => {
        const {buttons, id} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ItemButtonConfig, index: number) => {
            const key = `button-${index}`;

            return (
                <ItemButton config={button} id={id} key={key} />
            );
        });
    };

    render() {
        const {active, children, disabled, hasChildren, indicators, showOrderField, selected} = this.props;

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.active]: active,
                [itemStyles.disabled]: disabled,
                [itemStyles.selected]: selected,
            }
        );

        return (
            <div className={itemClass} onClick={this.handleClick} role="button">
                {!showOrderField &&
                    <span className={itemStyles.buttons}>
                        {this.renderButtons()}
                    </span>
                }
                {showOrderField &&
                    <div className={itemStyles.orderInput}>
                        <Input
                            alignment="center"
                            onBlur={this.handleOrderBlur}
                            onChange={this.handleOrderChange}
                            onKeyPress={this.handleOrderKeyPress}
                            value={this.order}
                        />
                    </div>
                }
                <span className={itemStyles.text}>
                    <CroppedText>{children}</CroppedText>
                </span>
                {indicators && indicators.map((indicator, index) => (
                    <span className={itemStyles.indicator} key={index}>
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

// @flow
import React from 'react';
import Icon from '../Icon';
import type {ItemButtonConfig} from './types';
import itemStyles from './item.scss';

type Props = {|
    id: string | number,
    config: ItemButtonConfig,
|};

export default class ItemButton extends React.Component<Props> {
    handleClick = () => {
        const {id, config} = this.props;

        if (!config.onClick) {
            return;
        }

        config.onClick(id);
    };

    render() {
        return (
            <Icon className={itemStyles.button} name={this.props.config.icon} onClick={this.handleClick} />
        );
    }
}

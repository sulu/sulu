// @flow
import React from 'react';
import classNames from 'classnames';
import Dropdown from './Dropdown';
import Simple from './Simple';
import type {ToolbarItemConfig} from './types';
import {toolbarItemTypes} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {
    index: number,
    active: boolean,
    toolbarItemConfigs: Array<ToolbarItemConfig>,
};

export default class Toolbar extends React.Component<Props> {
    renderToolbarItems = (toolbarItemConfigs: Array<ToolbarItemConfig>) => {
        let items = [];

        toolbarItemConfigs.map((toolbarItemConfig: ToolbarItemConfig, index: number) => {
            let item;
            switch (toolbarItemConfig.type) {
                case toolbarItemTypes.Dropdown:
                    item = <Dropdown key={index} index={this.props.index} {...toolbarItemConfig} />
                    break;
                case toolbarItemTypes.Simple:
                    item = <Simple key={index} index={this.props.index} {...toolbarItemConfig} />
                    break;
                default:
                    break;
            }

            items.push(item);
        });

        return items;
    };

    render() {
        const {active, toolbarItemConfigs} = this.props;

        const containerClass = classNames(
            toolbarStyles.container,
            {
                [toolbarStyles.isActive]: active,
            }
        );

        return (
            <div className={containerClass}>
                {this.renderToolbarItems(toolbarItemConfigs)}
            </div>
        );
    }
}


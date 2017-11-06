// @flow
import React from 'react';
import classNames from 'classnames';
import ToolbarDropdown from './ToolbarDropdown';
import ToolbarButton from './ToolbarButton';
import type {ToolbarItemConfig} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {
    index: number,
    active: boolean,
    toolbarItems: Array<ToolbarItemConfig>,
};

export default class Toolbar extends React.Component<Props> {
    renderToolbarItems = (toolbarItems: Array<ToolbarItemConfig>) => {
        return toolbarItems.map((toolbarItemConfig: ToolbarItemConfig, index: number) => {
            switch (toolbarItemConfig.type) {
                case 'dropdown':
                    return <ToolbarDropdown key={index} index={this.props.index} {...toolbarItemConfig} />;
                case 'simple':
                    return <ToolbarButton key={index} index={this.props.index} {...toolbarItemConfig} />;
                default:
                    throw new Error('Unknown toolbar item type given: "' + toolbarItemConfig.type + '"');
            }
        });
    };

    render() {
        const {active, toolbarItems} = this.props;

        const containerClass = classNames(
            toolbarStyles.toolbar,
            {
                [toolbarStyles.active]: active,
            }
        );

        return (
            <div className={containerClass}>
                {this.renderToolbarItems(toolbarItems)}
            </div>
        );
    }
}


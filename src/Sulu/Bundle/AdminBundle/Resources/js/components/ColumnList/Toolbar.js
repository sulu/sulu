// @flow
import React from 'react';
import ToolbarDropdown from './ToolbarDropdown';
import ToolbarButton from './ToolbarButton';
import type {ToolbarItemConfig} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {|
    columnIndex?: number,
    toolbarItems: Array<ToolbarItemConfig>,
|};

export default class Toolbar extends React.Component<Props> {
    renderToolbarItems = (toolbarItems: Array<ToolbarItemConfig>) => {
        return toolbarItems.map((toolbarItemConfig: ToolbarItemConfig, index: number) => {
            switch (toolbarItemConfig.type) {
                case 'dropdown':
                    return <ToolbarDropdown key={index} columnIndex={this.props.columnIndex} {...toolbarItemConfig} />;
                case 'button':
                    return <ToolbarButton key={index} columnIndex={this.props.columnIndex} {...toolbarItemConfig} />;
                default:
                    throw new Error('Unknown toolbar item type given: "' + toolbarItemConfig.type + '"');
            }
        });
    };

    render() {
        const {toolbarItems} = this.props;

        return (
            <div className={toolbarStyles.toolbar}>
                {this.renderToolbarItems(toolbarItems)}
            </div>
        );
    }
}


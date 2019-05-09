// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import ToolbarDropdown from './ToolbarDropdown';
import ToolbarButton from './ToolbarButton';
import type {ToolbarItemConfig} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {|
    toolbarItems: Array<ToolbarItemConfig>,
    toolbarRef?: (?ElementRef<'div'>) => void,
|};

@observer
class Toolbar extends React.Component<Props> {
    static defaultProps = {
        toolbarItems: [],
    };

    @observable toolbar: ElementRef<'div'>;

    @action setToolbarRef = (ref: ?ElementRef<'div'>) => {
        const {toolbarRef} = this.props;

        if (toolbarRef) {
            toolbarRef(ref);
        }
    };

    renderToolbarItems = (toolbarItems: Array<ToolbarItemConfig>): Array<*> => {
        return toolbarItems.map((toolbarItemConfig: ToolbarItemConfig, index: number) => {
            switch (toolbarItemConfig.type) {
                case 'dropdown':
                    return <ToolbarDropdown key={index} {...toolbarItemConfig} />;
                case 'button':
                    return <ToolbarButton key={index} {...toolbarItemConfig} />;
                default:
                    throw new Error('Unknown toolbar item type given: "' + toolbarItemConfig.type + '"');
            }
        });
    };

    render() {
        const {toolbarItems} = this.props;

        return (
            <div
                className={toolbarStyles.toolbar}
                ref={this.setToolbarRef}
            >
                {this.renderToolbarItems(toolbarItems)}
            </div>
        );
    }
}

export default Toolbar;

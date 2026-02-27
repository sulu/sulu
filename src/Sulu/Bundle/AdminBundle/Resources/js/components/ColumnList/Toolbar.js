// @flow
import React from 'react';
import {action, observable, makeObservable} from 'mobx';
import {observer} from 'mobx-react';
import ToolbarDropdown from './ToolbarDropdown';
import ToolbarButton from './ToolbarButton';
import toolbarStyles from './toolbar.scss';
import type {ToolbarItemConfig} from './types';
import type {ElementRef} from 'react';

type Props = {|
    toolbarItems: Array<ToolbarItemConfig>,
    toolbarRef?: (?ElementRef<'div'>) => void,
|};

@observer
class Toolbar extends React.Component<Props> {
    constructor(...args: Array<any>) {
        super(...args);
        if (typeof makeObservable === 'function') {
            makeObservable(this);
        }
    }

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
                    return <ToolbarDropdown {...toolbarItemConfig} key={index} />;
                case 'button':
                    return <ToolbarButton {...toolbarItemConfig} key={index} />;
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

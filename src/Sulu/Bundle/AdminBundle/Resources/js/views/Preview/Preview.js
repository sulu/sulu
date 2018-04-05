// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {sidebarStore} from '../../containers/Sidebar';
import type {SidebarViewOptions} from '../../containers/Sidebar/types';
import Icon from '../../components/Icon/Icon';

@observer
export default class Preview extends React.Component<SidebarViewOptions> {
    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            sidebarStore.setSize('large');

            return;
        }

        sidebarStore.setSize('medium');
    };

    render() {
        return (
            <div>
                <h1>HELLO world</h1>

                <button onClick={this.handleToggleSidebarClick}>
                    <Icon name={sidebarStore.size !== 'large' ? 'arrow-left' : 'arrow-right'} />

                    Toggle sidebar
                </button>
            </div>
        );
    }
}

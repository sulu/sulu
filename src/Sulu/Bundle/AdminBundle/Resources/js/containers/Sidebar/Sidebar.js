// @flow
import React from 'react';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import sidebarStore from './stores/SidebarStore';
import sidebarRegistry from './registries/SidebarRegistry';
import sidebarStyles from './sidebar.scss';

type Props = {
    className?: string,
};

export default @observer class Sidebar extends React.Component<Props> {
    render() {
        if (!sidebarStore.view || sidebarRegistry.isDisabled(sidebarStore.view)) {
            return null;
        }

        const Component = sidebarRegistry.get(sidebarStore.view);
        const {
            className,
        } = this.props;

        const sidebarClass = classNames(
            sidebarStyles.sidebar,
            className
        );

        return (
            <aside className={sidebarClass}>
                <Component {...sidebarStore.props} />
            </aside>
        );
    }
}

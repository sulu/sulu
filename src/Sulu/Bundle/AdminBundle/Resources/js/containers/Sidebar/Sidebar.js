// @flow
import React from 'react';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import sidebarStore from './stores/SidebarStore';
import sidebarViewRegistry from './registries/SidebarViewRegistry';
import sidebarStyles from './sidebar.scss';

type Props = {
    className?: string,
};

@observer
export default class Sidebar extends React.Component<Props> {
    render() {
        if (!sidebarStore.view || sidebarViewRegistry.isDisabled(sidebarStore.view)) {
            return null;
        }

        const Component = sidebarViewRegistry.get(sidebarStore.view);
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

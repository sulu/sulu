// @flow
import Sidebar from './Sidebar';
import sidebarStore from './stores/sidebarStore';
import sidebarRegistry from './registries/sidebarRegistry';
import withSidebar from './withSidebar';
import type {SidebarConfig} from './types';

export default Sidebar;

export {
    sidebarStore,
    sidebarRegistry,
    withSidebar,
};

export type {SidebarConfig};

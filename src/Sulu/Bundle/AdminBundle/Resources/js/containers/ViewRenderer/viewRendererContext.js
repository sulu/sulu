// @flow
import {createContext} from 'react';

export type ViewRendererContextValue = {|
    sidebarAvailable: boolean,
    toolbarStoreKey: ?string,
|};

export default createContext<ViewRendererContextValue>({sidebarAvailable: true, toolbarStoreKey: undefined});

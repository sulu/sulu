// @flow
import Datagrid, {
    DatagridStore,
    datagridAdapterRegistry,
    AbstractAdapter,
    FlatStructureStrategy,
    InfiniteLoadingStrategy,
    PaginatedLoadingStrategy,
} from './Datagrid';
import type {DatagridAdapterProps, LoadingStrategyInterface, StructureStrategyInterface} from './Datagrid';
import {textEditorRegistry} from './TextEditor';
import {viewRegistry} from './ViewRenderer';
import Sidebar, {sidebarStore, sidebarRegistry} from './Sidebar';
import type {ViewProps} from './ViewRenderer';
import {withToolbar} from './Toolbar';
import Form, {CardCollection, fieldRegistry, FormInspector, ResourceFormStore} from './Form';
import ResourceMultiSelect from './ResourceMultiSelect';
import MultiAutoComplete from './MultiAutoComplete';
import MultiSelection from './MultiSelection';
import SingleAutoComplete from './SingleAutoComplete';
import SingleDatagridOverlay from './SingleDatagridOverlay';

export type {
    DatagridAdapterProps,
    LoadingStrategyInterface,
    StructureStrategyInterface,
    ViewProps,
};

export {
    AbstractAdapter,
    CardCollection,
    Datagrid,
    DatagridStore,
    datagridAdapterRegistry,
    fieldRegistry,
    FlatStructureStrategy,
    Form,
    FormInspector,
    MultiAutoComplete,
    MultiSelection,
    InfiniteLoadingStrategy,
    PaginatedLoadingStrategy,
    ResourceFormStore,
    ResourceMultiSelect,
    SingleAutoComplete,
    SingleDatagridOverlay,
    Sidebar,
    sidebarStore,
    sidebarRegistry,
    textEditorRegistry,
    viewRegistry,
    withToolbar,
};

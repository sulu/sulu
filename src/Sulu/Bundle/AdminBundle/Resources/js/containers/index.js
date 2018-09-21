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
import Sidebar, {sidebarStore, sidebarViewRegistry} from './Sidebar';
import type {ViewProps} from './ViewRenderer';
import {withToolbar} from './Toolbar';
import Form, {fieldRegistry, FormInspector, FormStore} from './Form';
import MultiSelect from './MultiSelect';
import MultiAutoComplete from './MultiAutoComplete';
import MultiSelection from './MultiSelection';
import SingleAutoComplete from './SingleAutoComplete';

export type {
    DatagridAdapterProps,
    LoadingStrategyInterface,
    StructureStrategyInterface,
    ViewProps,
};

export {
    AbstractAdapter,
    Datagrid,
    DatagridStore,
    datagridAdapterRegistry,
    fieldRegistry,
    FlatStructureStrategy,
    Form,
    FormInspector,
    FormStore,
    MultiSelect,
    MultiAutoComplete,
    MultiSelection,
    InfiniteLoadingStrategy,
    PaginatedLoadingStrategy,
    SingleAutoComplete,
    Sidebar,
    sidebarStore,
    sidebarViewRegistry,
    textEditorRegistry,
    viewRegistry,
    withToolbar,
};

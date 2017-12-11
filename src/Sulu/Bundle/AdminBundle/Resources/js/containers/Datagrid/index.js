// @flow
import Datagrid from './Datagrid';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import TableAdapter from './adapters/TableAdapter';
import FolderAdapter from './adapters/FolderAdapter';
import AbstractAdapter from './adapters/AbstractAdapter';
import FlatStrategy from './structureStrategies/FlatStrategy';
import PaginationStrategy from './loadingStrategies/PaginationStrategy';
import InfiniteScrollingStrategy from './loadingStrategies/InfiniteScrollingStrategy';
import type {DatagridAdapterProps, LoadingStrategyInterface, StructureStrategyInterface} from './types';

export default Datagrid;
export {
    AbstractAdapter,
    DatagridStore,
    datagridAdapterRegistry,
    TableAdapter,
    FolderAdapter,
    FlatStrategy,
    PaginationStrategy,
    InfiniteScrollingStrategy,
};
export type {
    DatagridAdapterProps,
    LoadingStrategyInterface,
    StructureStrategyInterface,
};

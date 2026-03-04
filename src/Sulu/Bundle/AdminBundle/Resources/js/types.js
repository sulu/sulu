// @flow
import type {BlockError, ErrorCollection, FieldTypeProps} from './containers/Form/types';
import type {FormFieldTypes} from './components/Form/types';
import type {ButtonOption} from './components/MultiItemSelection/types';
import type {SelectionData} from './components/RectangleSelection';
import type {BlockPreviewTransformer} from './containers/FieldBlocks/types';
import type {FieldTransformer} from './containers/List/types';
import type {ToolbarItemConfig} from './containers/Toolbar/types';
import type {LinkTypeOverlayProps} from './containers/Link/types';

export type Resource = {
    id: string | number,
    resourceKey: string,
    title?: string | null,
};

export type DependantResourceBatches = Resource[][];

export type DependantResourcesData = {
    dependantResourceBatches: DependantResourceBatches,
    dependantResourcesCount: number,
    detail: string,
    title: string,
};

export type ReferencingResourcesData = {
    referencingResources: Resource[],
    referencingResourcesCount: number,
    resource: Resource,
};

export type {
    BlockError,
    BlockPreviewTransformer,
    ButtonOption,
    ErrorCollection,
    FieldTransformer,
    FieldTypeProps,
    FormFieldTypes,
    LinkTypeOverlayProps,
    SelectionData,
    ToolbarItemConfig,
};

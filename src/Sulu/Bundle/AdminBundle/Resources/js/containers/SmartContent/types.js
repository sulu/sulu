// @flow

export type FilterCriteria = {|
    audienceTargeting: ?boolean,
    // TODO rename to categoryIds
    categories: ?Array<number>,
    categoryOperator: ?Conjunction,
    // TODO rename to datasourceId
    dataSource: ?string | number,
    // TODO rename to includeSubElements
    includeSubFolders: ?boolean,
    // TODO rename to limit
    limitResult: ?number,
    // TODO rename to presentation
    presentAs: ?string,
    sortBy: ?string,
    // TODO rename to sortOrder
    sortMethod: ?SortOrder,
    tagOperator: ?Conjunction,
    tags: ?Array<string | number>,
|};

export type SortOrder = 'asc' | 'desc';

export type Conjunction = 'or' | 'and';

export type Sorting = {
    name: string,
    value: string,
};

export type Presentation = {
    name: string,
    value: string,
};

export type SmartContentConfig = {
    datasourceAdapter?: string,
    datasourceDatagridKey?: string,
    datasourceResourceKey?: string,
    audienceTargeting: boolean,
    categories: boolean,
    limit: boolean,
    presentAs: boolean,
    sorting: Array<Sorting>,
    tags: boolean,
};

export type SmartContentConfigs = {[key: string]: SmartContentConfig};

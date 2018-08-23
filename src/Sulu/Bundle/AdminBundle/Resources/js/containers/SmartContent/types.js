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
    sortBy: ?string,
    // TODO rename to sortOrder
    sortMethod: ?SortOrder,
    tagOperator: ?Conjunction,
    tags: ?Array<string | number>,
|};

export type SortOrder = 'asc' | 'desc';

export type Conjunction = 'or' | 'and';

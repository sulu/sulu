// @flow

export type Index = {|
    icon: string,
    indexName: string,
    name: string,
    route: {|
        name: string,
        resultToRoute: {[key: string]: string},
    |},
|};

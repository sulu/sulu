// @flow

export type Index = {|
    indexName: string,
    name: string,
    route: {
        name: string,
        resultToRoute: {[key: string]: string},
    },
|};

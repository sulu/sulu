// @flow

export type SearchResource = {|
    icon: string,
    name: string,
    resourceKey: string,
    route: {|
        name: string,
        resultToRoute: {[key: string]: string},
        resultToRouteName?: {[key: string]: string},
    |},
|};

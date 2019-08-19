// @flow

type Permission = {|
    context: string,
    permissions: {[key: string]: boolean},
|};

export type Role = {|
    id: number,
    identifier: string,
    name: string,
    permissions: Array<Permission>,
    system: string,
|};

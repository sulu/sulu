// @flow
export type ContextPermission = {
    context: string,
    id: ?number,
    permissions: {[string]: boolean},
};

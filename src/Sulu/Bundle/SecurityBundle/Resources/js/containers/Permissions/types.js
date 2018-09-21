// @flow
export type ContextPermission = {
    context: string,
    id: ?string,
    permissions: {[string]: boolean},
};


// @flow
export type Permission = {
    context: string,
    id: ?string,
    permissions: {[string]: boolean},
};


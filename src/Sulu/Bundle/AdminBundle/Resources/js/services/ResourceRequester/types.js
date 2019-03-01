// @flow
export type EndpointConfiguration = {
    [string]: {
        routes: {
            detail?: string,
            list?: string,
        },
    },
};

export type ListOptions = {
    page?: ?number,
    limit?: ?number,
    locale?: ?string,
};

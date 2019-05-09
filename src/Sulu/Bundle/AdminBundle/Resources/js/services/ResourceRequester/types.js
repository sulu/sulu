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
    limit?: ?number,
    locale?: ?string,
    page?: ?number,
};

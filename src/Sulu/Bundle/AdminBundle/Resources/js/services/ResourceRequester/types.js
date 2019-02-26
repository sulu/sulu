// @flow
export type EndpointConfiguration = {
    [string]: {
        endpoint: {
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

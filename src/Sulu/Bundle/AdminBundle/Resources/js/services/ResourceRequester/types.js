// @flow
export type EndpointConfiguration = {
    [string]: {
        routes: {
            detail?: string,
            list?: string,
            prefill?: string,
        },
    },
};

export type ListOptions = {
    limit?: ?number,
    locale?: ?string,
    page?: ?number,
};

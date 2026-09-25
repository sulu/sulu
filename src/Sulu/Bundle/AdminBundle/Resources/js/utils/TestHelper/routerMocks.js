// @flow

function createRoute(
    options?: Object = {},
    attributes?: Object = {},
    children?: Array<Object> = [],
    overrides?: Object = {}
) {
    return {
        attributes,
        children,
        options,
        ...overrides,
    };
}

function createRouterMock(options?: Object = {}) {
    const route = options.route || createRoute(options.routeOptions || {});

    return {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        attributes: options.attributes || {},
        bind: jest.fn(),
        navigate: jest.fn(),
        redirect: jest.fn(),
        route,
        ...options.overrides,
    };
}

export {
    createRoute,
    createRouterMock,
};

// @flow

function createComponentMock(render?: (props: any) => any = () => null): any {
    return jest.fn((props) => render(props));
}

function getMockProps(component: any, callIndex?: number = -1): any {
    const calls = component.mock.calls;
    const normalizedCallIndex = callIndex < 0 ? calls.length + callIndex : callIndex;
    const call = calls[normalizedCallIndex];

    if (!call) {
        throw new Error('Mock component has not been rendered');
    }

    return call[0];
}

function getMockPropsCalls(component: any): Array<any> {
    return component.mock.calls.map((call) => call[0]);
}

export {
    createComponentMock,
    getMockProps,
    getMockPropsCalls,
};

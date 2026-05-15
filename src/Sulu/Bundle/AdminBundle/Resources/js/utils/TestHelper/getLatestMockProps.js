// @flow

function getLatestMockProps(mockFunction: any): any {
    const {calls} = mockFunction.mock;

    return calls[calls.length - 1][0];
}

export default getLatestMockProps;

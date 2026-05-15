// @flow

function getMockCallArg(mockFunction: any, callIndex: number, argIndex: number = 0): any {
    return mockFunction.mock.calls[callIndex][argIndex];
}

export default getMockCallArg;
